<?php

declare(strict_types=1);

namespace DG\AdminBundle\DependencyInjection;

use DG\AdminBundle\Formatter\InlineFormatter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dg_admin');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('default_formatter')
            ->info('Default formatter for tables')
            ->defaultValue(InlineFormatter::class)
            ->cannotBeEmpty()
            ->end()
            ->end()
        ;

        $this->addTableSection($rootNode);
        $this->addDateRangesSection($rootNode);
        $this->addDateFormatsSection($rootNode);
        $this->addInlineFormatterSection($rootNode);
        $this->addDatatableFormatterSection($rootNode);

        return $treeBuilder;
    }

    private function addTableSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
            ->arrayNode('table')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('options')
            ->info('Default options to set for Table')
            ->variablePrototype()->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    private function addDateRangesSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->fixXmlConfig('date_range')
            ->children()
            ->arrayNode('date_ranges')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('start')->defaultValue('')->end()
            ->scalarNode('end')->defaultValue('')->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    private function addDateFormatsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->fixXmlConfig('date_format')
            ->children()
            ->arrayNode('date_formats')
            ->info('Date formats that need translations.')
            ->beforeNormalization()->ifString()->then(function ($v) { return [$v]; })->end()
            ->prototype('scalar')->end()
            ->defaultValue([])
            ->end()
            ->end()
        ;
    }

    private function addInlineFormatterSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
            ->arrayNode('inline_formatter')
            ->info('Default parameters for inline formatter')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('template')
            ->info('Default template to be used for inline HTML')
            ->defaultValue('@DGAdmin/formatter/inline_formatter.html.twig')
            ->cannotBeEmpty()
            ->end()
            ->arrayNode('template_parameters')
            ->info('Default parameters to be passed to the template')
            ->variablePrototype()->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    private function addDatatableFormatterSection(ArrayNodeDefinition $node): void
    {
        $defaultOptions = [
            'serverSide' => true,
            'processing' => true,
            'paging' => true,
            'pagingType' => 'full_numbers',
            'lengthChange' => true,
            'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
            'pageLength' => 10,
            'searching' => false,
            'ordering' => true,
            'info' => true,
        ];

        $node
            ->children()
            ->arrayNode('datatable_formatter')
            ->info('Default parameters for DataTable formatter')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('template')
            ->info('Default template to be used for DataTable container HTML')
            ->defaultValue('@DGAdmin/formatter/formatter_ajax.html.twig')
            ->cannotBeEmpty()
            ->end()
            ->arrayNode('template_parameters')
            ->info('Default parameters to be passed to the container template')
            ->variablePrototype()->end()
            ->end()
            ->scalarNode('table_template')
            ->info('Default template to be used for DataTable HTML')
            ->defaultValue('@DGAdmin/formatter/datatable_table.html.twig')
            ->cannotBeEmpty()
            ->end()
            ->arrayNode('table_template_parameters')
            ->info('Default parameters to be passed to the table template')
            ->variablePrototype()->end()
            ->end()
            ->enumNode('method')
            ->info('HTTP method for parsing data request')
            ->values([Request::METHOD_GET, Request::METHOD_POST])
            ->defaultValue(Request::METHOD_POST)
            ->end()
            ->arrayNode('options')
            ->info('Default options to set for DataTable')
            ->variablePrototype()->end()
            ->defaultValue($defaultOptions)
            ->beforeNormalization()
            ->ifArray()
            ->then(static fn ($array) => array_merge($defaultOptions, $array))
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }
}
