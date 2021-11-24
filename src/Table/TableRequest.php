<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table;

use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\Exception\LogicException;
use Symfony\Component\HttpFoundation\ParameterBag;

class TableRequest
{
    public const ORDER_ASC = 'asc';
    public const ORDER_DESC = 'desc';

    public const TOTAL_NONE = 0;
    public const TOTAL_SIMPLE = 1;
    public const TOTAL_ALL = 2;

    protected bool $hasOffset = false;
    protected int $offset = 0;
    protected bool $hasLimit = false;
    protected ?int $limit = null;
    /**
     * @var null|array<string, string>
     */
    protected ?array $orderBy = null;
    /**
     * @var null|array<string, mixed>
     */
    protected ?array $filters = null;
    protected ?string $search = null;
    /**
     * @var ParameterBag<mixed>
     */
    protected ParameterBag $parameters;
    /**
     * @var ParameterBag<mixed>
     */
    protected ParameterBag $options;

    protected int $total = self::TOTAL_ALL;
    protected ?TableBatchRequest $batch = null;

    public function __construct()
    {
        $this->parameters = new ParameterBag();
        $this->options = new ParameterBag();
    }

    public function hasOffset(): bool
    {
        return $this->hasOffset;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;
        $this->hasOffset = true;

        return $this;
    }

    public function hasLimit(): bool
    {
        return $this->hasLimit;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;
        $this->hasLimit = true;

        return $this;
    }

    public function hasOrderBy(): bool
    {
        return null !== $this->orderBy;
    }

    /**
     * @return array<string, string>
     */
    public function getOrderBy(): array
    {
        return $this->orderBy ?? [];
    }

    public function addOrderBy(string $column, string $sort): self
    {
        if (null === $this->orderBy) {
            $this->orderBy = [];
        }

        if (str_contains($column, ',')) {
            throw new InvalidArgumentException('Column name cannot contain "," character.');
        }

        $this->orderBy[$column] = $sort;

        return $this;
    }

    /**
     * @param array<string, string> $orderBy
     */
    public function setOrderBy(array $orderBy): self
    {
        foreach (array_keys($orderBy) as $name) {
            if (str_contains((string) $name, ',')) {
                throw new InvalidArgumentException('Column name cannot contain "," character.');
            }
        }

        $this->orderBy = $orderBy;

        return $this;
    }

    public function hasFilters(): bool
    {
        return null !== $this->filters;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters ?? [];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function hasSearch(): bool
    {
        return null !== $this->search;
    }

    public function getSearch(): string
    {
        return (string) $this->search;
    }

    public function setSearch(string $search): self
    {
        $this->search = $search;

        return $this;
    }

    /**
     * @return ParameterBag<mixed>
     */
    public function getParameters(): ParameterBag
    {
        return $this->parameters;
    }

    /**
     * @return ParameterBag<mixed>
     */
    public function getOptions(): ParameterBag
    {
        return $this->options;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function isBatch(): bool
    {
        return null !== $this->batch;
    }

    public function getBatch(): TableBatchRequest
    {
        if (null === $this->batch) {
            throw new LogicException('The request does not contain a batch');
        }

        return $this->batch;
    }

    public function setBatch(TableBatchRequest $batch): self
    {
        $this->batch = $batch;

        return $this;
    }
}
