<?php

declare(strict_types=1);

namespace DG\AdminBundle\Result\Data;

use Generator;

class IteratorDataResult extends AbstractDataResult
{
    /**
     * @param iterable<mixed> $data
     */
    public function __construct(
        protected iterable $data,
        ?int $totalCount = null,
        ?int $filteredCount = null,
        bool $hasNext = false,
    ) {
        parent::__construct($totalCount, $filteredCount, $hasNext);
    }

    public function getData(): Generator
    {
        yield from $this->data;
    }
}
