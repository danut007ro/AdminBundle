<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column\ValueExtractor;

class CallbackValueExtractor implements ValueExtractorInterface
{
    /**
     * @var callable(mixed):mixed
     */
    protected mixed $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function extractValue(mixed $row): mixed
    {
        return ($this->callback)($row);
    }
}
