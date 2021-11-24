<?php

declare(strict_types=1);

namespace DG\AdminBundle\DependencyInjection;

use DG\AdminBundle\AbstractConfigurableClass;
use DG\AdminBundle\Adapter\AdapterInterface;
use DG\AdminBundle\AdminBundle;
use DG\AdminBundle\BatchAction\BatchActionInterface;
use DG\AdminBundle\Column\ColumnInterface;
use DG\AdminBundle\DateFormat\DateFormats;
use DG\AdminBundle\Formatter\DatatableFormatter;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Formatter\InlineFormatter;
use DG\AdminBundle\Table\ConfiguratorInterface;
use DG\AdminBundle\Table\TableFactory;
use DG\AdminBundle\TableHelper;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class AdminExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->getDefinition(TableHelper::class)
            ->replaceArgument('$defaultFormatter', $config['default_formatter'])
        ;

        $this->optionsTable($container, $config['table']);
        $container->getDefinition(TableFactory::class)
            ->replaceArgument('$defaultOptions', $config['table']['options'])
        ;

        $container->getDefinition(DateFormats::class)
            ->replaceArgument('$ranges', $config['date_ranges'])
            ->replaceArgument('$formats', $config['date_formats'])
        ;

        $this->registerFormatters($config, $container);
        $container->registerForAutoconfiguration(AbstractConfigurableClass::class)
            ->setShared(false)
        ;

        $container->registerForAutoconfiguration(AdapterInterface::class)
            ->addTag('dg_admin.adapter')
        ;

        $container->registerForAutoconfiguration(BatchActionInterface::class)
            ->addTag('dg_admin.batch_action')
        ;

        $container->registerForAutoconfiguration(FormatterInterface::class)
            ->addTag('dg_admin.formatter')
        ;

        $container->registerForAutoconfiguration(ConfiguratorInterface::class)
            ->addTag('dg_admin.table_configurator')
        ;

        $container->registerForAutoconfiguration(ColumnInterface::class)
            ->addTag('dg_admin.column')
        ;
    }

    public function getAlias(): string
    {
        return 'dg_admin';
    }

    public function prepend(ContainerBuilder $container): void
    {
        $refl = new \ReflectionClass(AdminBundle::class);
        if (false === $filename = $refl->getFileName()) {
            return;
        }

        $path = \dirname($filename).'/Resources/views';

        $container->prependExtensionConfig('twig', ['paths' => [$path]]);
    }

    /**
     * @param mixed[] $config
     */
    private function optionsTable(ContainerBuilder $container, array &$config): void
    {
        if (\is_string($config['options']['transform_rows'] ?? null)) {
            $container->setAlias('dg_admin.table.transform_rows', $config['options']['transform_rows']);
            unset($config['options']['transform_rows']);
        }

        if (\is_string($config['options']['id_extractor'] ?? null)) {
            $container->setAlias('dg_admin.table.id_extractor', $config['options']['id_extractor']);
            unset($config['options']['id_extractor']);
        }

        if (\is_string($config['options']['transform_batch'] ?? null)) {
            $container->setAlias('dg_admin.table.transform_batch', $config['options']['transform_batch']);
            unset($config['options']['transform_batch']);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerFormatters(array $config, ContainerBuilder $container): void
    {
        $container->getDefinition(DatatableFormatter::class)
            ->replaceArgument('$defaultOptions', $config['datatable_formatter'])
        ;

        $container->getDefinition(InlineFormatter::class)
            ->replaceArgument('$defaultOptions', $config['inline_formatter'])
        ;
    }
}
