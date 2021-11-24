<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TextColumn extends AbstractColumn
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $this->addValueExtractorOption($resolver);

        $resolver
            ->setDefault('raw', false)
            ->setAllowedTypes('raw', 'bool')
            ->setInfo('raw', 'Specify if the value is to be used as it is or encoded with "htmlspecialchars()".')
        ;
    }

    public function getValue(mixed $row): mixed
    {
        return $this->options['value_extractor']->extractValue($row);
    }

    protected function doRender(mixed $value, mixed $row, mixed $originalRow): string
    {
        return $this->options['raw'] ? (string) $value : htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE);
    }
}
