<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use Twig\TemplateWrapper;

class TwigColumn extends AbstractColumn
{
    protected TemplateWrapper $templateWrapper;

    public function __construct(protected Environment $twig)
    {
    }

    public function configure(array $options): static
    {
        parent::configure($options);

        $this->templateWrapper = $this->twig->load($this->options['template']);

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $this->addValueExtractorOption($resolver);
        $this->addTwigTemplateOption($resolver);
    }

    public function getValue(mixed $row): mixed
    {
        return $this->options['value_extractor']->extractValue($row);
    }

    protected function doRender(mixed $value, mixed $row, mixed $originalRow): string
    {
        return $this->templateWrapper->render(
            array_merge(
                $this->options['template_parameters'],
                [
                    'value' => $value,
                    'row' => $row,
                    'originalRow' => $originalRow,
                ],
            ),
        );
    }
}
