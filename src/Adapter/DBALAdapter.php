<?php

declare(strict_types=1);

namespace DG\AdminBundle\Adapter;

use DG\AdminBundle\Exception\UnexpectedOptionTypeException;
use DG\AdminBundle\Result\Data\CallbackDataResult;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\TableRequest;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Query\QueryBuilder;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DBALAdapter extends AbstractAdapter
{
    public function __construct(protected FilterBuilderUpdaterInterface $filterBuilderUpdater)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('query_builder')
            ->setDefaults([
                'order_by' => [],
                'count_total' => true,
                'count_total_clause' => '*',
                'count_filtered' => true,
                'count_filtered_clause' => '*',
                'transform_row' => null,
            ])
            ->setAllowedTypes('query_builder', [QueryBuilder::class, 'callable'])
            ->setAllowedTypes('order_by', 'string[]')
            ->setAllowedTypes('count_total', ['int', 'bool', 'callable'])
            ->setAllowedTypes('count_total_clause', 'string')
            ->setAllowedTypes('count_filtered', ['int', 'bool', 'callable'])
            ->setAllowedTypes('count_filtered_clause', 'string')
            ->setAllowedTypes('transform_row', ['null', 'callable'])
            ->setInfo('query_builder', 'Query builder to use when retrieving results.')
            ->setInfo('order_by', 'Mapping between column names and column to sort.')
            ->setInfo('count_total', 'How to count total values.')
            ->setInfo('count_total_clause', 'Content of total COUNT clause.')
            ->setInfo('count_filtered', 'How to count filtered values (count after applying filters).')
            ->setInfo('count_filtered_clause', 'Content of filtered COUNT clause.')
            ->setInfo('transform_row', 'Callback to transform a row from the result.')
        ;
    }

    public function list(TableRequest $request, ?FormInterface $filter = null): DataResultInterface
    {
        $qb = $this->getQueryBuilder($request);

        $totalCount = null;
        if (TableRequest::TOTAL_ALL === $request->getTotal()) {
            // Calculate total count without applying filters.
            $totalCount = $this->calculateTotalCount($qb, $request);
        }

        // Apply filters.
        if (null !== $filter) {
            $this->filterBuilderUpdater->addFilterConditions($filter, $qb);
        }

        $offset = $request->getOffset();
        $limit = $request->getLimit();

        // Calculate "filteredCount" if any kind of total is needed.
        $filteredCount = null;
        if (TableRequest::TOTAL_NONE !== $request->getTotal()) {
            $filteredCount = $this->calculateFilteredCount($qb, $request, $filter, $totalCount);
        }

        $hasNext = false;
        if (null !== $limit) {
            if (null !== $filteredCount) {
                // Set `hasNext` flag.
                $hasNext = $filteredCount > ($offset + $limit);
            }

            $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
            ;
        }

        // Apply `order_by`.
        $this->orderBy($qb, $request);

        /** @var Result $result */
        $result = $qb->execute();

        return new CallbackDataResult(
            function () use ($result) {
                while (false !== ($row = $result->fetchAssociative())) {
                    if (null !== $this->options['transform_row']) {
                        $this->options['transform_row']($row);
                    }

                    yield $row;
                }
            },
            $totalCount,
            $filteredCount,
            $hasNext,
        );
    }

    protected function getQueryBuilder(TableRequest $request): QueryBuilder
    {
        $queryBuilder = $this->options['query_builder'];
        if (\is_callable($queryBuilder)) {
            $queryBuilder = $queryBuilder($request);
        }

        if (!$queryBuilder instanceof QueryBuilder) {
            throw new UnexpectedOptionTypeException('query_builder', $queryBuilder, QueryBuilder::class);
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

        $count = null;
        if (\is_callable($this->options['count_total'])) {
            $count = $this->options['count_total']($request, $qb);
        } else {
            $count = $this->count($qb, $this->options['count_total_clause']);
        }

        if (null !== $count && !\is_int($count)) {
            throw new UnexpectedOptionTypeException('count_total', $count, 'int|null');
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

        if (null === $filter || !$filter->isSubmitted()) {
            // No filters to apply.
            return $totalCount;
        }

        $count = null;
        if (\is_callable($this->options['count_filtered'])) {
            $count = $this->options['count_filtered']($request, $qb, $filter, $totalCount);
        } else {
            $count = $this->count($qb, $this->options['count_filtered_clause']);
        }

        if (null !== $count && !\is_int($count)) {
            throw new UnexpectedOptionTypeException('count_filtered', $count, 'int|null');
        }

        return $count;
    }

    protected function orderBy(QueryBuilder $qb, TableRequest $request): void
    {
        foreach ($request->getOrderBy() as $column => $order) {
            $column = $this->options['order_by'][$column] ?? null;
            if (null === $column) {
                continue;
            }

            if (\is_callable($column)) {
                $column = $column($qb, $request);
            }

            $qb->addOrderBy($column, $order);
        }
    }

    protected function count(QueryBuilder $qb, string $clause = '*'): int
    {
        $clone = clone $qb;
        $clone->resetQueryPart('orderBy');
        $sql = $clone->getSQL();

        /** @var Result $result */
        $result = $clone
            ->resetQueryParts()
            ->select("COUNT({$clause}) AS cnt")
            ->from('('.$sql.')', 'dbal_count_tbl')
            ->execute()
        ;

        return (int) $result->fetchFirstColumn()[0];
    }
}
