<?php

declare(strict_types=1);

namespace DG\AdminBundle\Result\Data;

abstract class AbstractDataResult implements DataResultInterface
{
    public function __construct(
        protected ?int $totalCount = null,
        protected ?int $filteredCount = null,
        protected ?bool $hasNext = null,
    ) {
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    public function getFilteredCount(): ?int
    {
        return $this->filteredCount;
    }

    public function hasNext(): ?bool
    {
        return $this->hasNext;
    }
}
