<?php

declare(strict_types=1);

namespace DG\AdminBundle\Result\Data;

use Generator;

interface DataResultInterface
{
    /**
     * Retrieves the total number of records.
     */
    public function getTotalCount(): ?int;

    /**
     * Retrieves the number of records available after applying filters.
     */
    public function getFilteredCount(): ?int;

    /**
     * Check if response has a next page.
     */
    public function hasNext(): ?bool;

    /**
     * Returns the raw data in the result set.
     *
     * @return Generator<mixed>
     */
    public function getData(): Generator;
}
