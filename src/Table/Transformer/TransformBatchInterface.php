<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table\Transformer;

use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;

interface TransformBatchInterface
{
    /**
     * @param mixed[][] $batch
     */
    public function transformBatch(array &$batch, Table $table, TableRequest $request): void;
}
