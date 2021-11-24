<?php

declare(strict_types=1);

namespace DG\AdminBundle\Formatter;

use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface AjaxFormatterInterface extends FormatterInterface
{
    /**
     * Retrieve table name to parse from request.
     * If NULL then request won't be parsed.
     * If empty, then the root of request is parsed as table.
     */
    public function getTableNameUrl(): ?string;

    /**
     * Update TableRequest with parsed data from Request.
     *
     * @return bool TRUE if Request was specific for this formatter, FALSE if it wasn't
     */
    public function parseTableRequest(Request $request, TableRequest $tableRequest, bool $isSubtable = false): bool;

    public function formatDataResult(TableRequest $request, Table $table, DataResultInterface $result): ?Response;
}
