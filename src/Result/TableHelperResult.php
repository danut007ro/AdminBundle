<?php

declare(strict_types=1);

namespace DG\AdminBundle\Result;

use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\Exception\LogicException;
use DG\AdminBundle\Formatter\FormatterInterface;
use Symfony\Component\HttpFoundation\Response;

class TableHelperResult
{
    /**
     * @param FormatterInterface[] $formatters
     */
    public function __construct(private array $formatters, private ?Response $response = null)
    {
    }

    public function hasResponse(): bool
    {
        return null !== $this->response;
    }

    public function getResponse(): Response
    {
        if (null === $this->response) {
            throw new LogicException('The response does not contain a Response.');
        }

        return $this->response;
    }

    /**
     * @return FormatterInterface[]
     */
    public function getFormatters(): array
    {
        return $this->formatters;
    }

    public function getFormatter(string $tableName = ''): FormatterInterface
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->getTableName() === $tableName) {
                return $formatter;
            }
        }

        throw new InvalidArgumentException(sprintf('Formatter with table name "%s" is not found.', $tableName));
    }
}
