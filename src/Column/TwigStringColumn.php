<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Twig\TemplateWrapper;

class TwigStringColumn extends AbstractColumn
{
    protected TemplateWrapper $templateWrapper;

    public function __construct(protected Environment $twig)
    {
    }

    public function configure(array $options): static
    {
        parent::configure($options);

        $this->templateWrapper = $this->twig->createTemplate('{{- include(template_from_string(column_template)) -}}');

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $this->addValueExtractorOption($resolver);

        $resolver
            ->setRequired('template')
            ->setDefault('template_parameters', [])
            ->setAllowedTypes('template', 'string')
            ->setAllowedTypes('template_parameters', 'array')
            ->setInfo('template', 'String to be used as Twig template for rendering.')
            ->setInfo('template_parameters', 'Parameters to pass when rendering template.')
        ;
    }

    public function getValue(mixed $row): mixed
    {
        return $this->options['value_extractor']->extractValue($row);
    }

    protected function doRender(mixed $value, mixed $row, mixed $originalRow): string
    {
        return $this->twig->render(
            $this->templateWrapper,
            array_merge(
                $this->options['template_parameters'],
                [
                    'column_template' => $this->options['template'],
                    'value' => $value,
                    'row' => $row,
                    'originalRow' => $originalRow,
                ],
            ),
        );
    }
}
