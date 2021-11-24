<?php

declare(strict_types=1);

namespace DG\AdminBundle\Formatter;

use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;

interface FormatterInterface
{
    /**
     * Build Table using specified TableRequest and retrieve it.
     */
    public function buildTable(TableRequest $tableRequest): Table;

    /**
     * Retrieve built Table.
     */
    public function getTable(): Table;

    /**
     * Retrieve name of this formatter.
     */
    public function getName(): string;

    /**
     * Retrieve internal table name. Used for rendering.
     */
    public function getTableName(): string;

    /**
     * Retrieve default table request that is set for this formatter.
     */
    public function getTableRequest(): TableRequest;

    /**
     * Retrieve http method to be used for requests.
     */
    public function getMethod(): string;

    /**
     * Retrieve the url to be used for requests.
     */
    public function getUrl(): string;

    public function getCsrfTokenId(): string;

    /**
     * Template that should be rendered.
     */
    public function getTemplate(): string;

    /**
     * Parameters for template.
     *
     * @return mixed[]
     */
    public function getTemplateParameters(): array;

    /**
     * Get variables to be `jsonencode()`'d into html template.
     *
     * @return array<string, mixed>
     */
    public function getVars(): array;
}
