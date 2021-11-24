<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use DG\AdminBundle\DateFormat\DateFormats;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntlDateTimeColumn extends TextColumn
{
    private DateTimeToLocalizedStringTransformer $transformer;

    public function __construct(private DateFormats $formats)
    {
    }

    public function configure(array $options): static
    {
        parent::configure($options);

        $this->transformer = new DateTimeToLocalizedStringTransformer(
            $this->options['input_timezone'],
            $this->options['output_timezone'],
            null,
            null,
            \IntlDateFormatter::GREGORIAN,
            $this->formats->getDateFormat($this->options['format'])->getFormat(),
        );

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $this->addValueExtractorOption($resolver);

        $resolver
            ->setRequired('format')
            ->setDefaults([
                'input_timezone' => null,
                'output_timezone' => null,
            ])
            ->setAllowedTypes('format', 'string')
            ->setInfo('format', 'Format name to use for converting.')
        ;
    }

    public function getValue(mixed $row): mixed
    {
        return $this->options['value_extractor']->extractValue($row);
    }

    protected function doRender(mixed $value, mixed $row, mixed $originalRow): string
    {
        if (!$value instanceof \DateTimeInterface) {
            return '';
        }

        return $this->transformer->transform($value);
    }
}
