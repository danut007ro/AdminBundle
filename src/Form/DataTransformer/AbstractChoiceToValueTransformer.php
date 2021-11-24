<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\DataTransformer;

use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\ChoicesToValuesTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\ChoiceToValueTransformer;

/**
 * This is basically ChoiceToValueTransformer but also allows adding choices.
 */
abstract class AbstractChoiceToValueTransformer implements DataTransformerInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $choices = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(protected array $options)
    {
    }

    public function hasChoice(string $choice): bool
    {
        return \array_key_exists($choice, $this->choices);
    }

    public function addChoice(string $id, mixed $choice): self
    {
        $this->choices[$id] = $choice;

        return $this;
    }

    public function transform(mixed $value): mixed
    {
        return $this->buildTransformer()->transform($value);
    }

    public function reverseTransform(mixed $value): mixed
    {
        return $this->buildTransformer()->reverseTransform($value);
    }

    protected function buildTransformer(): DataTransformerInterface
    {
        $choiceList = $this->buildChoiceList();

        return ($this->options['multiple'] ?? false)
            ? new ChoicesToValuesTransformer($choiceList)
            : new ChoiceToValueTransformer($choiceList);
    }

    abstract protected function buildChoiceList(): ChoiceListInterface;
}
