<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table\IdExtractor;

use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;

interface IdExtractorInterface
{
    public function extractId(mixed $row, Table $table, TableRequest $request): string;
}
