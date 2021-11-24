<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table;

use DG\AdminBundle\Formatter\FormatterInterface;

interface ConfiguratorInterface
{
    /**
     * Configure a table.
     *
     * @param array<string, mixed> $options
     */
    public function configureTable(Table $table, TableRequest $request, array $options, FormatterInterface $formatter): void;
}
