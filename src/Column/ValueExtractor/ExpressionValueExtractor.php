<?php

declare(strict_types=1);

namespace DG\AdminBundle\Column\ValueExtractor;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionValueExtractor implements ValueExtractorInterface
{
    protected ExpressionLanguage $expressionLanguage;

    public function __construct(protected Expression $expression)
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->expression = $this->expressionLanguage->parse($this->expression, ['row']);
    }

    public function extractValue(mixed $row): mixed
    {
        return $this->expressionLanguage->evaluate($this->expression, ['row' => $row]);
    }
}
