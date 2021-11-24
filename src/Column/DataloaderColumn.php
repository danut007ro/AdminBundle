<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use DG\AdminBundle\DependencyInjection\Instantiator;
use DG\AdminBundle\Exception\MissingDependencyException;
use Overblog\DataLoader\DataLoader;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataloaderColumn extends AbstractProxyColumn
{
    public function __construct(protected Instantiator $instantiator)
    {
        parent::__construct($instantiator);

        if (!class_exists(DataLoader::class)) {
            throw new MissingDependencyException('Install "overblog/dataloader-bundle" to use DataloaderColumn.');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->remove('sortable')
            ->setRequired('dataloader')
            ->setAllowedTypes('dataloader', ['callable', DataLoader::class])
            ->setInfo('dataloader', 'Dataloader to be used to calculate value.')
        ;
    }

    public function getValue(mixed $row): mixed
    {
        $value = parent::getValue($row);

        if (\is_callable($this->options['dataloader'])) {
            $dataloader = ($this->options['dataloader'])($value, $row);
        } else {
            $dataloader = $this->options['dataloader'];
        }

        if (!$dataloader instanceof DataLoader) {
            throw new InvalidOptionsException(sprintf('Invalid type for "dataloader" option, expected "%s", but got "%s".', DataLoader::class, get_debug_type($dataloader)));
        }

        if (null === $value) {
            return null;
        }

        return $dataloader->load($value);
    }

    public function isSortable(): bool
    {
        return false;
    }

    protected function doRender(mixed $value, mixed $row, mixed $originalRow): mixed
    {
        if (null !== $value) {
            $value = $row[$this->name] = DataLoader::await($value);
        }

        return parent::doRender($value, $row, $originalRow);
    }
}
