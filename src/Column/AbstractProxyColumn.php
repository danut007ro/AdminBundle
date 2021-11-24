<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use DG\AdminBundle\DependencyInjection\Instantiator;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractProxyColumn extends AbstractColumn
{
    protected ColumnInterface $column;

    public function __construct(private Instantiator $instantiator)
    {
    }

    public function configure(array $options): static
    {
        parent::configure($options);

        if ($this->options['column'] instanceof ColumnInterface) {
            $this->column = $this->options['column'];
        } else {
            /** @var class-string<AbstractColumn> $column */
            $column = $this->options['column'];
            /** @var array<string, mixed> $options */
            $options = array_merge($this->options['column_options'], ['name' => $this->name]);
            $this->column = $this->instantiator->getColumn($column, $options);
        }

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $this->addValueExtractorOption($resolver);

        $resolver
            ->setRequired('column')
            ->setDefault('column_options', [])
            ->setAllowedTypes('column', ['string', ColumnInterface::class])
            ->setAllowedTypes('column_options', 'array')
            ->setInfo('column', 'Type of column to proxy `getValue()` and `render()` methods to.')
            ->setInfo('column_options', 'Options to be used for configuring proxy column.')
        ;
    }

    public function getValue(mixed $row): mixed
    {
        return $this->options['value_extractor']->extractValue($row);
    }

    protected function doRender(mixed $value, mixed $row, mixed $originalRow): mixed
    {
        $columnValue = $this->column->getValue($value);

        return $this->column->render($columnValue, $row, $originalRow);
    }
}
