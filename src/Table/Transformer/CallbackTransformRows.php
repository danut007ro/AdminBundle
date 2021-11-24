<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table\Transformer;

use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;

class CallbackTransformRows implements TransformRowsInterface
{
    /**
     * @var callable(&$array, Table, TableRequest):void
     */
    protected mixed $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function transformRows(array &$rows, Table $table, TableRequest $request): void
    {
        ($this->callback)($rows, $table, $request);
    }
}
