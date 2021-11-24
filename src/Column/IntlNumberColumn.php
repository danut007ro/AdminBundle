<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntlNumberColumn extends TextColumn
{
    private NumberToLocalizedStringTransformer $transformer;

    public function configure(array $options): static
    {
        parent::configure($options);

        $this->transformer = new NumberToLocalizedStringTransformer(
            $this->options['scale'],
            $this->options['grouping'],
            $this->options['rounding_mode'],
        );

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $this->addValueExtractorOption($resolver);

        $resolver
            ->setDefaults([
                'scale' => null,
                'grouping' => false,
                'rounding_mode' => \NumberFormatter::ROUND_HALFUP,
            ])
            ->setAllowedTypes('scale', ['null', 'int'])
            ->setAllowedTypes('grouping', ['null', 'bool'])
            ->setAllowedTypes('rounding_mode', 'int')
            ->setInfo('scale', 'Argument to NumberToLocalizedStringTransformer constructor.')
            ->setInfo('grouping', 'Argument to NumberToLocalizedStringTransformer constructor.')
            ->setInfo('rounding_mode', 'Argument to NumberToLocalizedStringTransformer constructor.')
        ;
    }

    public function getValue(mixed $row): mixed
    {
        return $this->options['value_extractor']->extractValue($row);
    }

    protected function doRender(mixed $value, mixed $row, mixed $originalRow): string
    {
        if (!is_numeric($value)) {
            return $value;
        }

        return $this->transformer->transform((float) $value);
    }
}
