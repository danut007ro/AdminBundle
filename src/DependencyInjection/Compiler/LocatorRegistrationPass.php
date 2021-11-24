<?php

declare(strict_types=1);

namespace DG\AdminBundle\DependencyInjection\Compiler;

use DG\AdminBundle\Adapter\AbstractAdapter;
use DG\AdminBundle\BatchAction\AbstractBatchAction;
use DG\AdminBundle\Column\AbstractColumn;
use DG\AdminBundle\DependencyInjection\Instantiator;
use DG\AdminBundle\Formatter\AbstractFormatter;
use DG\AdminBundle\Table\AbstractConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class LocatorRegistrationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition(Instantiator::class)
            ->setArguments([[
                AbstractAdapter::class => $this->registerLocator($container, 'adapter'),
                AbstractFormatter::class => $this->registerLocator($container, 'formatter'),
                AbstractConfigurator::class => $this->registerLocator($container, 'table_configurator'),
                AbstractColumn::class => $this->registerLocator($container, 'column'),
                AbstractBatchAction::class => $this->registerLocator($container, 'batch_action'),
            ]])
        ;
    }

    private function registerLocator(ContainerBuilder $container, string $baseTag): Definition
    {
        $types = [];
        foreach ($container->findTaggedServiceIds("dg_admin.{$baseTag}") as $serviceId => $tag) {
            $types[$serviceId] = new Reference($serviceId);
        }

        return $container
            ->register("dg_admin.{$baseTag}_locator", ServiceLocator::class)
            ->addTag('container.service_locator')
            ->setPublic(false)
            ->setArguments([$types])
        ;
    }
}
