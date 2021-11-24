<?php

declare(strict_types=1);

namespace DG\AdminBundle\UIAction;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpandTableRowUIAction implements UIActionInterface
{
    public const NAME = '_dg_admin.uiaction.expandTableRow';

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(protected array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'url' => '',
                'url_parameters' => [],
                'disable_auto' => false,
                'add_table_request_table' => false,
                'add_table_request_var' => '',
            ])
            ->setAllowedTypes('url', 'string')
            ->setAllowedTypes('url_parameters', 'array')
            ->setAllowedTypes('disable_auto', 'bool')
            ->setAllowedTypes('add_table_request_table', ['bool', 'string'])
            ->setAllowedTypes('add_table_request_var', 'string')
            ->setInfo('url', 'Url to make ajax request. If left empty, will use `href` from html element, or current `window.location`.')
            ->setInfo('url_parameters', 'Parameters to be passed to `fetch()` javascript function.')
            ->setInfo('disable_auto', 'Disable automatic handle of ui action.')
            ->setInfo('add_table_request_table', 'Name of table to add request for. If `TRUE` then add closest table. If string, then find table by name.')
            ->setInfo('add_table_request_var', 'Name of variable on which to add table request.')
        ;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getParameters(): array
    {
        return [
            'url' => $this->options['url'],
            'url_parameters' => $this->options['url_parameters'],
            'disable_auto' => $this->options['disable_auto'],
            'add_table_request_table' => $this->options['add_table_request_table'],
            'add_table_request_var' => $this->options['add_table_request_var'],
        ];
    }
}
