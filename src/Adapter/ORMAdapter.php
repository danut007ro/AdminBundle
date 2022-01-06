<?php

declare(strict_types=1);

namespace DG\AdminBundle\Adapter;

use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\Exception\LogicException;
use DG\AdminBundle\Exception\MissingDependencyException;
use DG\AdminBundle\Exception\RuntimeException;
use DG\AdminBundle\Exception\UnexpectedOptionTypeException;
use DG\AdminBundle\Result\Data\ArrayDataResult;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Result\Data\IteratorDataResult;
use DG\AdminBundle\Table\TableRequest;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Uid\Uuid;

/**
 * @template T of object
 * @implements CRUDAdapterInterface<T>
 */
class ORMAdapter extends AbstractAdapter implements CRUDAdapterInterface
{
    public function __construct(
        protected ManagerRegistry $registry,
        protected FilterBuilderUpdaterInterface $filterBuilderUpdater,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function configure(array $options): static
    {
        parent::configure($options);

        if (null === $this->options['query_builder'] && null === $this->options['entity']) {
            throw new LogicException('"entity" option must be set if "query_builder" is not specified.');
        }

        if ($this->options['batch_column_id_as_uuid'] && !class_exists(Uuid::class)) {
            throw new MissingDependencyException('Install "symfony/uid" to use ids as Uuid.');
        }

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'entity' => null,
                'entity_alias' => 'entity',
                'query_builder' => null,
                'query' => null,
                'order_by' => [],
                'search_columns' => [],
                'batch_column_id' => 'entity.id',
                'batch_column_id_as_uuid' => false,
                'iterate' => false,
                'hydration_mode' => AbstractQuery::HYDRATE_OBJECT,
                'count_total' => true,
                'count_filtered' => true,
                'count_distinct' => false,
                'count_output_walker' => true,
                'count_fetch_join_collection' => false,
                'force_apply_filters' => false,
            ])
            ->setAllowedTypes('entity', ['null', 'string'])
            ->setAllowedTypes('entity_alias', 'string')
            ->setAllowedTypes('query_builder', ['null', QueryBuilder::class, 'callable'])
            ->setAllowedTypes('query', ['null', 'callable'])
            ->setAllowedTypes('order_by', 'string[]')
            ->setAllowedTypes('search_columns', ['string[]', 'callable'])
            ->setAllowedTypes('batch_column_id', ['null', 'string'])
            ->setAllowedTypes('batch_column_id_as_uuid', 'bool')
            ->setAllowedTypes('iterate', 'bool')
            ->setAllowedTypes('hydration_mode', 'int')
            ->setAllowedTypes('count_total', ['int', 'bool', 'callable'])
            ->setAllowedTypes('count_filtered', ['int', 'bool', 'callable'])
            ->setAllowedTypes('count_distinct', 'bool')
            ->setAllowedTypes('count_output_walker', 'bool')
            ->setAllowedTypes('count_fetch_join_collection', 'bool')
            ->setAllowedTypes('force_apply_filters', 'bool')
            ->setInfo('entity', 'Entity class to use for repository. Mandatory if "query_builder" is not specified or using `read()` method.')
            ->setInfo('entity_alias', 'Entity alias to use when creating QueryBuilder.')
            ->setInfo('query_builder', 'Query builder to use when retrieving results. If `entity` options is specified then the callable will receive initial QueryBuilder built from the repository.')
            ->setInfo('query', 'Callable to be called after getting Query from QueryBuilder. Can be used to configure the cache.')
            ->setInfo('order_by', 'Mapping between column names and column to sort.')
            ->setInfo('search_columns', 'Columns to apply global search. Searching will be `LIKE search%`')
            ->setInfo('batch_column_id', 'Column name to use for batch by id. Use `null` to disable functionality.')
            ->setInfo('batch_column_id_as_uuid', 'Specify if ids should be used as Uuid for batch processing.')
            ->setInfo('iterate', 'Specify it the result should be iterated. Works only if "hydration_mode" is `HYDRATE_OBJECT`.')
            ->setInfo('hydration_mode', 'Hydration mode for results.')
            ->setInfo('count_total', 'How to count total values.')
            ->setInfo('count_filtered', 'How to count filtered values (count after applying filters).')
            ->setInfo('count_distinct', 'Specify if should apply DISTINCT clause when counting.')
            ->setInfo('count_output_walker', 'Specify if should use OutputWalker when counting.')
            ->setInfo('count_fetch_join_collection', 'Specify if should use fetch join collection when counting.')
            ->setInfo('force_apply_filters', 'Apply filters even if filters request isn\'t submitted.')
        ;
    }

    public function list(TableRequest $request, ?FormInterface $filter = null): DataResultInterface
    {
        $qb = $this->getQueryBuilder($request);

        $totalCount = null;
        if (TableRequest::TOTAL_ALL === $request->getTotal()) {
            // Calculate total count applying default filters.
            $qbTotal = clone $qb;
            if (null !== $filter && $this->options['force_apply_filters']) {
                $this->filterBuilderUpdater->addFilterConditions($filter, $qbTotal);
            }
            $totalCount = $this->calculateTotalCount($qbTotal, $request);
        }

        // Apply filters.
        if (null !== $filter) {
            $applyFilters = false;
            if ($filter->isSubmitted()) {
                if (!$filter->isValid()) {
                    return new ArrayDataResult([], $totalCount, 0);
                }

                $applyFilters = true;
            } elseif ($this->options['force_apply_filters']) {
                $applyFilters = true;
            }

            if ($applyFilters) {
                $this->filterBuilderUpdater->addFilterConditions($filter, $qb);
            }
        }

        $offset = $request->getOffset();
        $limit = $request->getLimit();
        $filteredCount = null;

        /*
         * Calculate filtered count even if simple totals is needed (hasNext) and should iterate results with pagination.
         * This is needed in order to ensure that we can calculate hasNext if iterating.
         */
        if (
            TableRequest::TOTAL_ALL === $request->getTotal() // Should calculate all totals.
            || (
                TableRequest::TOTAL_SIMPLE === $request->getTotal() // Should calculate simple totals (hasNext).
                && $this->options['iterate'] // Is iterating results.
                && null !== $limit // Is using pagination.
            )
        ) {
            $filteredCount = $this->calculateFilteredCount($qb, $request, $filter, $totalCount);
        }

        // Apply pagination.
        $hasNext = false;
        if (null !== $limit) {
            $limitModifier = false;
            if (null !== $filteredCount) {
                // Filtered count already calculated, just set `hasNext` flag.
                $hasNext = $filteredCount > ($offset + $limit);
            } elseif (!$this->options['iterate'] && TableRequest::TOTAL_SIMPLE === $request->getTotal()) {
                // Simple total and not iterating, the limit should be +1.
                $limitModifier = true;
            } elseif (TableRequest::TOTAL_NONE !== $request->getTotal()) {
                // No `filteredCount` is calculated but a total is needed.
                throw new LogicException('Need to calculate total but cannot calculate `count_filtered`.');
            }

            $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit + ($limitModifier ? 1 : 0))
            ;
        }

        // Apply `order_by`.
        $this->orderBy($qb, $request);

        // Build Query from QueryBuilder.
        $query = $qb->getQuery();
        if (null !== $this->options['query']) {
            $this->options['query']($query, $request, $qb);
        }

        // Get filtered result.
        if ($this->options['iterate']) {
            $response = new IteratorDataResult($query->toIterable([], $this->options['hydration_mode']), $totalCount, $filteredCount, $hasNext);
        } else {
            $result = $query->getResult($this->options['hydration_mode']);
            if (null !== $limit && \count($result) > $limit) {
                // Remove extra row that was added in order to calculate hasNext flag.
                $hasNext = true;
                $result = \array_slice($result, 0, $limit);
            }

            $response = new ArrayDataResult($result, $totalCount, $filteredCount, $hasNext);
        }

        return $response;
    }

    public function create(mixed $data): void
    {
        $manager = $this->getManagerForClass($data);
        $manager->persist($data);
        $manager->flush();
    }

    public function read(mixed $id): ?object
    {
        /** @var ?class-string<T> $entity */
        $entity = $this->options['entity'];
        if (null === $entity) {
            throw new LogicException('Cannot read from adapter if no "entity" is set.');
        }

        return $this->registry->getRepository($entity)->find($id);
    }

    public function update(mixed $data): void
    {
        $manager = $this->getManagerForClass($data);
        $manager->flush();
    }

    public function delete(mixed $data): void
    {
        $manager = $this->getManagerForClass($data);
        $manager->remove($data);
        $manager->flush();
    }

    protected function getQueryBuilder(TableRequest $request): QueryBuilder
    {
        if ($this->options['query_builder'] instanceof QueryBuilder) {
            return $this->options['query_builder'];
        }

        $queryBuilder = null;
        if (null !== $this->options['entity']) {
            /** @var class-string<T> $entity */
            $entity = $this->options['entity'];
            /** @var EntityRepository<T> $repository */
            $repository = $this->registry->getRepository($entity);
            $queryBuilder = $repository->createQueryBuilder($this->options['entity_alias']);
        }

        if (\is_callable($this->options['query_builder'])) {
            if (null === $queryBuilder) {
                // No QueryBuilder until now, need one.
                $queryBuilder = ($this->options['query_builder'])($request);
            } else {
                // QueryBuilder is built until now, allow to update.
                ($this->options['query_builder'])($request, $queryBuilder);
            }
        }

        if (!$queryBuilder instanceof QueryBuilder) {
            throw new UnexpectedOptionTypeException('query_builder', $queryBuilder, QueryBuilder::class);
        }

        if (null !== $this->options['batch_column_id'] && $request->isBatch()) {
            $batch = $request->getBatch();
            $ids = $batch->getIds();
            if (!$batch->isAll() || \count($ids) > 0) {
                if ($this->options['batch_column_id_as_uuid']) {
                    // Convert to Uuid.
                    array_walk(
                        $ids,
                        /** @phpstan-ignore-next-line */
                        static fn (string & $id) => $id = Uuid::fromString($id)->toBinary(),
                    );
                }

                $operator = $batch->isAll() ? 'NOT IN' : 'IN';
                $queryBuilder
                    ->andWhere("{$this->options['batch_column_id']} {$operator} (:_dgAdminBatchIds)")
                    ->setParameter('_dgAdminBatchIds', $ids)
                ;
            }
        }

        if ('' !== $request->getSearch()) {
            if (\is_callable($this->options['search_columns'])) {
                $this->options['search_columns']($queryBuilder, $request->getSearch());
            } elseif (\count($this->options['search_columns']) > 0) {
                $expr = [];
                foreach ($this->options['search_columns'] as $column) {
                    $expr[] = $queryBuilder->expr()->like($column, ':_dgAdminSearch');
                }

                $queryBuilder
                    ->andWhere($queryBuilder->expr()->orX(...$expr))
                    ->setParameter('_dgAdminSearch', $request->getSearch().'%')
                ;
            }
        }

        return $queryBuilder;
    }

    protected function calculateTotalCount(QueryBuilder $qb, TableRequest $request): ?int
    {
        if (false === $this->options['count_total']) {
            return null;
        }

        if (\is_int($this->options['count_total'])) {
            return $this->options['count_total'];
        }

        if (\is_callable($this->options['count_total'])) {
            $count = $this->options['count_total']($request, $qb);
        } else {
            $query = $qb->getQuery();
            $paginator = new Paginator($query, $this->options['count_fetch_join_collection']);
            $paginator->setUseOutputWalkers($this->options['count_output_walker']);
            $query->setHint(CountWalker::HINT_DISTINCT, $this->options['count_distinct']);
            $count = $paginator->count();
        }

        if (null !== $count && !\is_int($count)) {
            throw new InvalidArgumentException(sprintf('The "count_total" option should return a valid "int" or "null", but instead it returned "%s".', get_debug_type($count)));
        }

        return $count;
    }

    protected function calculateFilteredCount(QueryBuilder $qb, TableRequest $request, ?FormInterface $filter, ?int $totalCount): ?int
    {
        if (false === $this->options['count_filtered']) {
            return null;
        }

        if (\is_int($this->options['count_filtered'])) {
            return $this->options['count_filtered'];
        }

        if (null === $filter || !$filter->isSubmitted() || !$filter->isValid()) {
            // No filters to apply.
            return $totalCount;
        }

        if (\is_callable($this->options['count_filtered'])) {
            $count = $this->options['count_filtered']($request, $qb, $filter, $totalCount);
        } else {
            $query = $qb->getQuery();
            $paginator = new Paginator($query, $this->options['count_fetch_join_collection']);
            $paginator->setUseOutputWalkers($this->options['count_output_walker']);
            $query->setHint(CountWalker::HINT_DISTINCT, $this->options['count_distinct']);
            $count = $paginator->count();
        }

        if (null !== $count && !\is_int($count)) {
            throw new InvalidArgumentException(sprintf('The "count_filtered" option should return a valid "int" or "null", but instead it returned "%s".', get_debug_type($count)));
        }

        return $count;
    }

    protected function orderBy(QueryBuilder $qb, TableRequest $request): void
    {
        foreach ($request->getOrderBy() as $column => $order) {
            if (null === $column = ($this->options['order_by'][$column] ?? null)) {
                continue;
            }

            $qb->addOrderBy($column, $order);
        }
    }

    /**
     * @param T $data
     */
    protected function getManagerForClass(mixed $data): ObjectManager
    {
        if (null === $manager = $this->registry->getManagerForClass(\get_class($data))) {
            throw new RuntimeException(sprintf('Cannot get manager for class "%s".', \get_class($data)));
        }

        return $manager;
    }
}
