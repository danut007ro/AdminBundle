<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BatchColumn extends TwigColumn
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->remove([
                'value_extractor', // There is no need for `value_extractor` from TwigColumn.
                'sortable',
            ])
            ->setDefaults([
                'template' => '@DGAdmin/column/batch.html.twig',
            ])
        ;
    }

    public function getValue(mixed $row): string
    {
        return '';
    }

    public function isSortable(): bool
    {
        return false;
    }
}
