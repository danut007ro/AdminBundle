<?php

declare(strict_types=1);

namespace DG\AdminBundle\Formatter;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractAjaxFormatter extends AbstractFormatter implements AjaxFormatterInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'table_name_url' => true,
            ])
            ->setAllowedTypes('table_name_url', ['bool', 'string'])
            ->setInfo(
                'table_name_url',
                <<<'INFO'
                Name to use for extracting table request from url.
                Use `FALSE` to disable request parsing and `TRUE` to use `table_name` option as value.
                Use "" to parse request with no name.
                INFO
            )
        ;
    }

    public function getTableNameUrl(): ?string
    {
        if (false === $this->options['table_name_url']) {
            return null;
        }

        if (true === $this->options['table_name_url']) {
            return $this->options['table_name'];
        }

        return $this->options['table_name_url'];
    }
}
