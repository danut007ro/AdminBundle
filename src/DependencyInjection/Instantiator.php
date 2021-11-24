<?php

declare(strict_types=1);

namespace DG\AdminBundle\DependencyInjection;

use DG\AdminBundle\Adapter\AbstractAdapter;
use DG\AdminBundle\BatchAction\AbstractBatchAction;
use DG\AdminBundle\Column\AbstractColumn;
use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\Formatter\AbstractFormatter;
use DG\AdminBundle\Table\AbstractConfigurator;
use Symfony\Component\DependencyInjection\ServiceLocator;

class Instantiator
{
    /**
     * @param ServiceLocator[] $locators
     */
    public function __construct(private array $locators = [])
    {
    }

    /**
     * @param class-string<AbstractAdapter> $type
     * @param null|array<string, mixed>     $options
     */
    public function getAdapter(string $type, ?array $options = null): AbstractAdapter
    {
        return $this->getInstance($type, AbstractAdapter::class, $options);
    }

    /**
     * @param class-string<AbstractFormatter> $type
     * @param null|array<string, mixed>       $options
     */
    public function getFormatter(string $type, ?array $options = null): AbstractFormatter
    {
        return $this->getInstance($type, AbstractFormatter::class, $options);
    }

    /**
     * @param class-string<AbstractConfigurator> $type
     * @param null|array<string, mixed>          $options
     */
    public function getTableConfigurator(string $type, ?array $options = null): AbstractConfigurator
    {
        return $this->getInstance($type, AbstractConfigurator::class, $options);
    }

    /**
     * @param class-string<AbstractColumn> $type
     * @param null|array<string, mixed>    $options
     */
    public function getColumn(string $type, ?array $options = null): AbstractColumn
    {
        return $this->getInstance($type, AbstractColumn::class, $options);
    }

    /**
     * @param class-string<AbstractBatchAction> $type
     * @param null|array<string, mixed>         $options
     */
    public function getBatchAction(string $type, ?array $options = null): AbstractBatchAction
    {
        return $this->getInstance($type, AbstractBatchAction::class, $options);
    }

    /**
     * @template T
     *
     * @param class-string<T>           $baseType
     * @param null|array<string, mixed> $options
     *
     * @return T
     */
    private function getInstance(string $type, string $baseType, ?array $options)
    {
        if (isset($this->locators[$baseType]) && $this->locators[$baseType]->has($type)) {
            $service = $this->locators[$baseType]->get($type);
            if (null !== $options) {
                $service->configure($options);
            }

            return $service;
        }

        if (class_exists($type) && is_subclass_of($type, $baseType)) {
            /** @var T $instance */
            $instance = new $type();
            if (null !== $options) {
                $instance->configure($options);
            }

            return $instance;
        }

        throw new InvalidArgumentException(sprintf('Could not resolve type "%s" to a service or class, are you missing a use statement? Or is it implemented but does it not correctly derive from "%s"?', $type, $baseType));
    }
}
