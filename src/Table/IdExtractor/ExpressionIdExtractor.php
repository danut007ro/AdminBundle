<?php

declare(strict_types=1);

namespace DG\AdminBundle\Table\IdExtractor;

use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionIdExtractor implements IdExtractorInterface
{
    protected ExpressionLanguage $expressionLanguage;

    public function __construct(protected Expression $expression)
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->expression = $this->expressionLanguage->parse($this->expression, ['row', 'table', 'request']);
    }

    public function extractId(mixed $row, Table $table, TableRequest $request): string
    {
        return (string) $this->expressionLanguage->evaluate($this->expression, ['row' => $row, 'table' => $table, 'request' => $request]);
    }
}
