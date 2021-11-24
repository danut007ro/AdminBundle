<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table;

use DG\AdminBundle\Adapter\AbstractAdapter;
use DG\AdminBundle\Adapter\AdapterInterface;
use DG\AdminBundle\BatchAction\AbstractBatchAction;
use DG\AdminBundle\BatchAction\BatchActionInterface;
use DG\AdminBundle\Column\AbstractColumn;
use DG\AdminBundle\Column\BatchColumn;
use DG\AdminBundle\Column\ColumnInterface;
use DG\AdminBundle\DependencyInjection\Instantiator;
use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\Exception\LogicException;
use DG\AdminBundle\Result\Data\CallbackDataResult;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\IdExtractor\IdExtractorInterface;
use DG\AdminBundle\Table\Transformer\TransformBatchInterface;
use DG\AdminBundle\Table\Transformer\TransformRowsInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Table
{
    private ?AdapterInterface $adapter = null;
    private ?FormInterface $filter = null;

    /**
     * @var array<string, ColumnInterface>
     */
    private array $columns = [];

    /**
     * @var null|string[]
     */
    private ?array $columnOrders = null;

    /**
     * @var array<string, BatchActionInterface>
     */
    private array $batchActions = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private Instantiator $instantiator,
        private FormFactoryInterface $formFactory,
        private array $options = [],
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'batch_size' => 100,
                'transform_rows' => null,
                'id_extractor' => null,
                'transform_batch' => null,
            ])
            ->setAllowedTypes('batch_size', 'int')
            ->setAllowedTypes('transform_rows', ['null', TransformRowsInterface::class])
            ->setAllowedTypes('id_extractor', ['null', IdExtractorInterface::class])
            ->setAllowedTypes('transform_batch', ['null', TransformBatchInterface::class])
            ->setInfo('batch_size', 'Batch size to use for processing rows.')
            ->setInfo('transform_rows', 'Service to call after retrieving rows.')
            ->setInfo('id_extractor', 'Service to call for extracting id from row.')
            ->setInfo('transform_batch', 'Service to call after processing batch.')
        ;
    }

    public function getFilter(): ?FormInterface
    {
        return $this->filter;
    }

    public function setFilter(FormInterface|string $filter): self
    {
        if (\is_string($filter)) {
            $filter = $this->formFactory->create($filter);
        }

        $this->filter = $filter;

        return $this;
    }

    public function getAdapter(): AdapterInterface
    {
        if (null === $this->adapter) {
            throw new LogicException('Adapter must be set on Table.');
        }

        return $this->adapter;
    }

    /**
     * @param AdapterInterface|class-string<AbstractAdapter> $adapter
     * @param array<string, mixed>                           $options
     */
    public function setAdapter(string|AdapterInterface $adapter, array $options = []): self
    {
        $this->adapter = $adapter instanceof AdapterInterface
            ? $adapter
            : $this->instantiator->getAdapter($adapter, $options)
        ;

        return $this;
    }

    public function setTransformRows(TransformRowsInterface $transformRows): self
    {
        $this->options['transform_rows'] = $transformRows;

        return $this;
    }

    public function setIdExtractor(IdExtractorInterface $idExtractor): self
    {
        $this->options['id_extractor'] = $idExtractor;

        return $this;
    }

    public function setTransformBatch(TransformBatchInterface $transformBatch): self
    {
        $this->options['transform_batch'] = $transformBatch;

        return $this;
    }

    public function setBatchSize(int $batchSize): self
    {
        $this->options['batch_size'] = $batchSize;

        return $this;
    }

    /**
     * @return array<string, ColumnInterface>
     */
    public function getColumns(): array
    {
        if (null === $this->columnOrders) {
            return $this->columns;
        }

        $columns = [];
        foreach ($this->columnOrders as $columnOrder) {
            if (!\array_key_exists($columnOrder, $this->columns)) {
                throw new InvalidArgumentException(sprintf('Unknown column order "%s". It should be one of %s.', $columnOrder, implode(', ', array_keys($this->columns))));
            }

            $columns[$columnOrder] = $this->columns[$columnOrder];
        }

        return $columns;
    }

    public function getBatchColumn(): ?BatchColumn
    {
        foreach ($this->columns as $column) {
            if ($column instanceof BatchColumn) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param class-string<AbstractColumn>|ColumnInterface $column
     * @param array<string, mixed>                         $options
     */
    public function addColumn(string $name, string|ColumnInterface $column, array $options = [], bool $reallyAdd = true): self
    {
        if (!$reallyAdd) {
            return $this;
        }

        if (isset($this->columns[$name])) {
            throw new InvalidArgumentException(sprintf('Column with name "%s" is already defined.', $name));
        }

        if (!$column instanceof ColumnInterface) {
            $column = $this->instantiator->getColumn($column, array_merge($options, ['name' => $name]));
        }

        if ($column instanceof BatchColumn && null !== $this->getBatchColumn()) {
            throw new InvalidArgumentException(sprintf('BatchColumn is already defined for "%s".', $name));
        }

        $this->columns[$name] = $column;

        return $this;
    }

    public function removeColumn(string $name): self
    {
        if (!isset($this->columns[$name])) {
            throw new InvalidArgumentException(sprintf('Column with name "%s" is not defined.', $name));
        }

        unset($this->columns[$name]);

        return $this;
    }

    /**
     * @param string[] $columnOrders
     */
    public function setColumnOrders(array $columnOrders): self
    {
        $this->columnOrders = $columnOrders;

        return $this;
    }

    /**
     * @return array<string, BatchActionInterface>
     */
    public function getBatchActions(): array
    {
        return $this->batchActions;
    }

    /**
     * @param BatchActionInterface|class-string<AbstractBatchAction> $batchAction
     * @param array<string, mixed>                                   $options
     */
    public function addBatchAction(string $name, string|BatchActionInterface $batchAction, array $options = []): self
    {
        if (isset($this->batchActions[$name])) {
            throw new InvalidArgumentException(sprintf('Batch action with name "%s" is already defined.', $name));
        }

        if (!$batchAction instanceof BatchActionInterface) {
            $batchAction = $this->instantiator->getBatchAction($batchAction, $options);
        }

        $this->batchActions[$name] = $batchAction;

        return $this;
    }

    public function process(TableRequest $request): DataResultInterface
    {
        $result = $this->getAdapter()->list($request, $this->filter);

        return new CallbackDataResult(
            function () use ($request, $result) {
                $columnPriorities = $this->buildColumnPriorities();
                $currentId = 0;
                $rows = [];
                foreach ($result->getData() as $row) {
                    // Build array of rows of maximum `batch_size`.
                    $rows[] = $row;
                    if (\count($rows) >= $this->options['batch_size']) {
                        yield from $this->handleRows($rows, $request, $columnPriorities, $currentId);
                        $rows = [];
                    }
                }

                yield from $this->handleRows($rows, $request, $columnPriorities, $currentId);
            },
            $result->getTotalCount(),
            $result->getFilteredCount(),
            $result->hasNext(),
        );
    }

    public static function randomName(int $length = 10): string
    {
        $name = 'dg-admin-';
        $chars = '1234567890qwertyuiopasdfghjklzxcvbnm_-';
        $charsLen = \strlen($chars);
        for ($i = 0; $i < $length; ++$i) {
            $name .= $chars[rand(0, $charsLen - 1)];
        }

        return $name;
    }

    /**
     * @param mixed[]  $rows
     * @param string[] $columnPriorities
     *
     * @return mixed[]
     */
    private function handleRows(array $rows, TableRequest $request, array $columnPriorities, int &$currentId): array
    {
        if ($this->options['transform_rows'] instanceof TransformRowsInterface) {
            // Transform rows.
            $this->options['transform_rows']->transformRows($rows, $this, $request);
        }

        // Build batch from rows.
        $batch = $originalRowIdx = [];
        foreach ($rows as $idx => $row) {
            $id = $currentId++;
            if ($this->options['id_extractor'] instanceof IdExtractorInterface) {
                // Extract id for row.
                $id = $this->options['id_extractor']->extractId($row, $this, $request);
            }

            // Build empty row ordered by $this->columns.
            $batch[$id] = [];
            foreach ($this->columns as $name => $column) {
                $batch[$id][$name] = null;
            }

            // Get values ordered by priority.
            foreach ($columnPriorities as $name) {
                $batch[$id][$name] = $this->columns[$name]->getValue($row);
            }

            $originalRowIdx[$id] = $idx;
        }

        if ($this->options['transform_batch'] instanceof TransformBatchInterface) {
            // Transform batch.
            $this->options['transform_batch']->transformBatch($batch, $this, $request);
        }

        // Build final batch.
        foreach ($batch as $idx => &$row) {
            foreach ($columnPriorities as $name) {
                $row[$name] = $this->columns[$name]->render($row[$name], $row, $rows[$originalRowIdx[$idx]]);
            }

            if (null !== $this->columnOrders) {
                $order = [];
                foreach ($this->columnOrders as $columnOrder) {
                    if (!isset($row[$columnOrder])) {
                        throw new InvalidArgumentException(sprintf('Unknown column order "%s". It should be one of %s.', $columnOrder, implode(', ', array_keys($this->columns))));
                    }

                    $order[$columnOrder] = $row[$columnOrder];
                }

                $row = $order;
            }
        }

        return $batch;
    }

    /**
     * @return string[]
     */
    private function buildColumnPriorities(): array
    {
        $columns = [];
        foreach ($this->columns as $name => $column) {
            $columns[$column->getPriority()][] = $name;
        }

        krsort($columns);

        return array_merge(...$columns);
    }
}
