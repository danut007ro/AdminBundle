<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column;

use DG\AdminBundle\AbstractConfigurableClass;
use DG\AdminBundle\Column\ValueExtractor\PropertyPathValueExtractor;
use DG\AdminBundle\Column\ValueExtractor\ValueExtractorInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

abstract class AbstractColumn extends AbstractConfigurableClass implements ColumnInterface
{
    protected string $name = '';
    protected array $options = [];
    /**
     * @var OptionsResolver[]
     */
    private static array $resolversByClass = [];

    public function configure(array $options): static
    {
        // Cache OptionsResolver by class.
        $class = static::class;
        if (!isset(self::$resolversByClass[$class])) {
            self::$resolversByClass[$class] = new OptionsResolver();
            $this->configureOptions(self::$resolversByClass[$class]);
        }

        $this->options = self::$resolversByClass[$class]->resolve($options);
        $this->name = $this->options['name'];

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('name')
            ->setDefaults([
                'label' => null,
                'priority' => 0,
                'sortable' => false,
                'visible' => true,
                'render' => null,
            ])
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('label', ['null', 'string', TranslatableMessage::class])
            ->setNormalizer('label', static fn (Options $options, $value): string|TranslatableMessage => $value ?? $options['name'])
            ->setAllowedTypes('priority', 'int')
            ->setAllowedTypes('sortable', 'bool')
            ->setAllowedTypes('visible', 'bool')
            ->setAllowedTypes('render', ['null', 'callable'])
            ->setInfo('name', 'Name that is used for column. Unique among all columns in current table.')
            ->setInfo('label', 'Label to be displayed as column name. If `NULL` is given, then it will default to `name` option.')
            ->setInfo('priority', 'Priority for calculating column value. Higher takes priority.')
            ->setInfo('sortable', 'Specify if this column is sortable.')
            ->setInfo('visible', 'Specify if this column is visible.')
            ->setInfo('render', 'Custom callable for rendering column. Will override default column rendering.')
        ;
    }

    public function getLabel(): string|TranslatableMessage
    {
        return $this->options['label'];
    }

    public function getPriority(): int
    {
        return $this->options['priority'];
    }

    public function isSortable(): bool
    {
        return $this->options['sortable'];
    }

    public function isVisible(): bool
    {
        return $this->options['visible'];
    }

    public function render(mixed $value, mixed $row, mixed $originalRow): mixed
    {
        if (\is_callable($this->options['render'])) {
            return $this->options['render']($value, $row, $originalRow);
        }

        return $this->doRender($value, $row, $originalRow);
    }

    abstract protected function doRender(mixed $value, mixed $row, mixed $originalRow): mixed;

    protected function addValueExtractorOption(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('value_extractor', null)
            ->setAllowedTypes('value_extractor', ['null', ValueExtractorInterface::class])
            ->setNormalizer('value_extractor', static fn (Options $options, $value): ValueExtractorInterface => $value ?? new PropertyPathValueExtractor($options['name']))
            ->setInfo('value_extractor', 'How to calculate column value. If `NULL` is given, then `name` option will be used as property path to retrieve value.')
        ;
    }

    protected function addTwigTemplateOption(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('template')
            ->setDefault('template_parameters', [])
            ->setAllowedTypes('template', 'string')
            ->setAllowedTypes('template_parameters', 'array')
            ->setInfo('template', 'Twig file template to be used for rendering.')
            ->setInfo('template_parameters', 'Parameters to pass when rendering template.')
        ;
    }
}
