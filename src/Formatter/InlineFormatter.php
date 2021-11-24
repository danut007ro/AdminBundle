<?php

declare(strict_types=1);

namespace DG\AdminBundle\Formatter;

class InlineFormatter extends AbstractFormatter
{
    public function getName(): string
    {
        return 'inline';
    }

    public function configure(array $options): static
    {
        $options = array_merge($this->defaultOptions, $options);

        return parent::configure($options);
    }
}
