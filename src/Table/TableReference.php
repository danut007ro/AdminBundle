<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table;

/**
 * This class encapsulates a Table, its ConfiguratorInterface and options
 * in order to configure a table after TableRequest is built.
 */
class TableReference
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private Table $table,
        private ConfiguratorInterface $configurator,
        private array $options = [],
    ) {
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getConfigurator(): ConfiguratorInterface
    {
        return $this->configurator;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
