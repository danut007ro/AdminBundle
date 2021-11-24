<?php

declare(strict_types=1);

namespace DG\AdminBundle;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractConfigurableClass
{
    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * @param array<string, mixed> $options
     */
    public function configure(array $options): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        return $this;
    }

    abstract public function configureOptions(OptionsResolver $resolver): void;
}
