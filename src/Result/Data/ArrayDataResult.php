<?php

declare(strict_types=1);

namespace DG\AdminBundle\Result\Data;

use Generator;

class ArrayDataResult extends AbstractDataResult
{
    /**
     * @param mixed[] $data
     */
    public function __construct(
        protected array $data,
        ?int $totalCount = null,
        ?int $filteredCount = null,
        ?bool $hasNext = null,
    ) {
        parent::__construct($totalCount, $filteredCount, $hasNext);
    }

    public function getData(): Generator
    {
        yield from $this->data;
    }
}
