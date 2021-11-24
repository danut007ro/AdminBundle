<?php

declare(strict_types=1);

namespace DG\AdminBundle\Formatter;

use DG\AdminBundle\AbstractConfigurableClass;
use DG\AdminBundle\Exception\LogicException;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableReference;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFormatter extends AbstractConfigurableClass implements FormatterInterface
{
    protected ?Table $table = null;
    protected ?TableRequest $tableRequest = null;

    /**
     * @param array<string, mixed> $defaultOptions
     */
    public function __construct(protected array $defaultOptions)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'table',
                'template',
            ])
            ->setDefaults([
                'table_name' => '',
                'table_request' => new TableRequest(),
                'method' => Request::METHOD_POST,
                'url' => '',
                'csrf_token_id' => '_dg_admin_csrf_token_id',
                'template_parameters' => [],
            ])
            ->setAllowedTypes('table', [Table::class, TableReference::class])
            ->setAllowedTypes('table_name', 'string')
            ->setAllowedTypes('table_request', TableRequest::class)
            ->setAllowedValues('method', [Request::METHOD_GET, Request::METHOD_POST])
            ->setAllowedTypes('url', 'string')
            ->setAllowedTypes('csrf_token_id', 'string')
            ->setAllowedTypes('template', 'string')
            ->setAllowedTypes('template_parameters', 'array')
            ->setInfo('table', 'Table to be formatted.')
            ->setInfo('table_name', 'Name to use as internal table name. Must be unique in group.')
            ->setInfo('table_request', 'Default TableRequest to use.')
            ->setInfo('method', 'HTTP method to use when making requests.')
            ->setInfo('url', 'Url to use for requests. If empty string, then current url will be used (retrieved with js).')
            ->setInfo('csrf_token_id', 'CSRF token id for validating requests.')
            ->setInfo('template', 'Twig template to use when rendering formatter.')
            ->setInfo('template_parameters', 'Parameters to be passed to Twig template when rendering the formatter template.')
        ;
    }

    public function buildTable(TableRequest $tableRequest): Table
    {
        if (null !== $this->table) {
            throw new LogicException('Table can be built only once.');
        }

        if ($this->options['table'] instanceof Table) {
            // Table is already built, return it.
            $table = $this->options['table'];
        } else {
            // Create table and configure it.
            /** @var TableReference $tableReference */
            $tableReference = $this->options['table'];
            $table = $this->table = $tableReference->getTable();
            $tableReference->getConfigurator()->configureTable($this->table, $tableRequest, $tableReference->getOptions(), $this);
        }

        $this->tableRequest = $tableRequest;

        return $table;
    }

    public function getTable(): Table
    {
        if (null === $this->table) {
            throw new LogicException('Table should be built before attempting to retrieve it. Did you forget to call "buildTable()"?');
        }

        return $this->table;
    }

    public function getTableName(): string
    {
        return $this->options['table_name'];
    }

    public function getTableRequest(): TableRequest
    {
        return $this->options['table_request'];
    }

    public function getMethod(): string
    {
        return $this->options['method'];
    }

    public function getUrl(): string
    {
        return $this->options['url'];
    }

    public function getCsrfTokenId(): string
    {
        return $this->options['csrf_token_id'];
    }

    public function getTemplate(): string
    {
        return $this->options['template'];
    }

    public function getTemplateParameters(): array
    {
        return $this->options['template_parameters'];
    }

    public function getVars(): array
    {
        return [];
    }
}
