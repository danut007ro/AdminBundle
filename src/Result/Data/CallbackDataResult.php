<?php

declare(strict_types=1);

namespace DG\AdminBundle\Result\Data;

use Generator;

class CallbackDataResult extends AbstractDataResult
{
    /**
     * @var callable():Generator<mixed>
     */
    protected $callback;

    public function __construct(callable $callback, ?int $totalCount, ?int $filteredCount, ?bool $hasNext)
    {
        parent::__construct($totalCount, $filteredCount, $hasNext);

        $this->callback = $callback;
    }

    public function getData(): Generator
    {
        yield from \call_user_func($this->callback);
    }
}
