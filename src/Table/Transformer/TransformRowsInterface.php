<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table\Transformer;

use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;

interface TransformRowsInterface
{
    /**
     * @param mixed[][] $rows
     */
    public function transformRows(array &$rows, Table $table, TableRequest $request): void;
}
