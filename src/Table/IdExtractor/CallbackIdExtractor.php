<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table\IdExtractor;

use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;

class CallbackIdExtractor implements IdExtractorInterface
{
    /**
     * @var callable(mixed, Table, TableRequest):string
     */
    protected mixed $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function extractId(mixed $row, Table $table, TableRequest $request): string
    {
        return ($this->callback)($row, $table, $request);
    }
}
