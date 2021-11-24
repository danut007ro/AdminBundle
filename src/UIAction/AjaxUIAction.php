<?php

declare(strict_types=1);

namespace DG\AdminBundle\UIAction;

use Symfony\Component\OptionsResolver\OptionsResolver;

class AjaxUIAction extends ExpandTableRowUIAction
{
    public const NAME = '_dg_admin.uiaction.ajax';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'refresh_table' => '',
            ])
            ->setAllowedTypes('refresh_table', ['bool', 'string'])
            ->setInfo('refresh_table', 'Name of table to refresh. If `TRUE` then refresh all tables on page. If empty string, then refresh closest table.')
        ;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getParameters(): array
    {
        return array_merge(
            parent::getParameters(),
            [
                'refresh_table' => $this->options['refresh_table'],
            ],
        );
    }
}
