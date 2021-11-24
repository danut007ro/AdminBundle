<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TwigBlockColumn extends TwigColumn
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $this->addValueExtractorOption($resolver);

        $resolver
            ->setRequired('block')
            ->setAllowedTypes('block', 'string')
            ->setInfo('block', 'Block name from Twig template to be used for rendering.')
        ;
    }

    public function getValue(mixed $row): mixed
    {
        return $this->options['value_extractor']->extractValue($row);
    }

    protected function doRender(mixed $value, mixed $row, mixed $originalRow): string
    {
        return $this->templateWrapper->renderBlock($this->options['block'], [
            'value' => $value,
            'row' => $row,
            'originalRow' => $originalRow,
        ]);
    }
}
