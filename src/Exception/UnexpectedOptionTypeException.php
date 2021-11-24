<?php

declare(strict_types=1);

namespace DG\AdminBundle\Exception;

class UnexpectedOptionTypeException extends InvalidArgumentException
{
    public function __construct(string $option, mixed $value, string $expectedType)
    {
        parent::__construct(sprintf('Option "%s" expected of type %s, but instead "%s" was given.', $option, $expectedType, get_debug_type($value)));
    }
}
