<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table;

use DG\AdminBundle\DependencyInjection\Instantiator;
use DG\AdminBundle\Table\IdExtractor\IdExtractorInterface;
use DG\AdminBundle\Table\Transformer\TransformBatchInterface;
use DG\AdminBundle\Table\Transformer\TransformRowsInterface;
use Symfony\Component\Form\FormFactoryInterface;

class TableFactory
{
    /**
     * @param array<string, mixed> $defaultOptions
     */
    public function __construct(
        private array $defaultOptions,
        private Instantiator $instantiator,
        private FormFactoryInterface $formFactory,
        private ?TransformRowsInterface $transformRowsService = null,
        private ?IdExtractorInterface $idExtractorService = null,
        private ?TransformBatchInterface $transformBatchService = null,
    ) {
        $this->defaultOptions = array_merge(
            $this->defaultOptions,
            [
                'transform_rows' => $this->transformRowsService,
                'id_extractor' => $this->idExtractorService,
                'transform_batch' => $this->transformBatchService,
            ]
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTable(array $options = []): Table
    {
        return new Table($this->instantiator, $this->formFactory, array_merge($this->defaultOptions, $options));
    }

    /**
     * @param class-string<AbstractConfigurator>|ConfiguratorInterface $configurator
     * @param array<string, mixed>                                     $configuratorOptions
     * @param array<string, mixed>                                     $tableOptions
     */
    public function createTableConfigurator(string|ConfiguratorInterface $configurator, array $configuratorOptions = [], array $tableOptions = []): TableReference
    {
        $table = $this->createTable($tableOptions);

        if (\is_string($configurator)) {
            $configurator = $this->instantiator->getTableConfigurator($configurator);
        }

        return new TableReference($table, $configurator, $configuratorOptions);
    }
}
