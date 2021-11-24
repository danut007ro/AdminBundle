<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table;

class TableBatchRequest
{
    /**
     * @param mixed[] $ids
     */
    public function __construct(
        protected string $name,
        protected bool $all = false,
        protected array $ids = [],
        protected bool $submitted = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isAll(): bool
    {
        return $this->all;
    }

    /**
     * @return mixed[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted;
    }
}
