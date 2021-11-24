<?php

declare(strict_types=1);

namespace DG\AdminBundle;

use DG\AdminBundle\BatchAction\BatchActionInterface;
use DG\AdminBundle\DependencyInjection\Instantiator;
use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\Formatter\AbstractFormatter;
use DG\AdminBundle\Formatter\AjaxFormatterInterface;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Result\TableHelperResult;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableBatchRequest;
use DG\AdminBundle\Table\TableFactory;
use DG\AdminBundle\Table\TableReference;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Twig\Environment;

class TableHelper
{
    public const BATCH_KEY = '_dgAdminBatch';
    public const HEADER_SUBMIT = 'X-DGAdmin-Submit';

    private ?TableHelperResult $result = null;

    /**
     * @param class-string<AbstractFormatter> $defaultFormatter
     */
    public function __construct(
        private string $defaultFormatter,
        private Instantiator $instantiator,
        private TableFactory $tableFactory,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TokenStorageInterface $tokenStorage,
        private Environment $twig,
    ) {
    }

    /**
     * @param FormatterInterface|FormatterInterface[] $formatters
     */
    public function handleRequest(Request $request, FormatterInterface|array $formatters): TableHelperResult
    {
        // Ensure we have an array of formatters.
        if ($isSubtable = !\is_array($formatters)) {
            $formatters = [$formatters];
        }

        // Check for unique and valid table name and table name url among all formatters.
        $tableNames = $tableNameUrls = [];
        foreach ($formatters as $formatter) {
            // Get table name and check for uniqueness.
            $tableName = $formatter->getTableName();
            if (isset($tableNames[$tableName]) || !FormConfigBuilder::isValidName($tableName)) {
                throw new InvalidArgumentException(sprintf('Table name "%s" should be used only once or it\'s invalid. It should start with a letter, digit or underscore and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").', $tableName));
            }

            $tableNames[$tableName] = $formatter;

            if ($formatter instanceof AjaxFormatterInterface) {
                // Get table name url and check for uniqueness.
                $tableNameUrl = $formatter->getTableNameUrl();
                if (null !== $tableNameUrl) {
                    if (isset($tableNameUrls[$tableNameUrl]) || !FormConfigBuilder::isValidName($tableNameUrl)) {
                        throw new InvalidArgumentException(sprintf('Table name url "%s" should be used only once or it\'s invalid. It should start with a letter, digit or underscore and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").', $tableNameUrl));
                    }

                    $tableNameUrls[$tableNameUrl] = $formatter;
                }
            }
        }

        // Let each formatter try to handle this request. Only one can handle it.
        /** @var null|Response $response */
        $response = null;
        foreach ($formatters as $formatter) {
            // Get default table request from formatter.
            $tableRequest = $formatter->getTableRequest();
            $requestIsForTable = false;

            if (null !== $response || !$this->parseBatchRequest($request, $formatter, $tableRequest)) {
                if (!$isSubtable) {
                    // Parse table request only if not a subtable.
                    $this->parseTableRequest($request, $formatter, $tableRequest);
                }

                $requestIsForTable = $formatter instanceof AjaxFormatterInterface
                    ? $formatter->parseTableRequest($request, $tableRequest, $isSubtable) // Let formatter update the request.
                    : $isSubtable
                ;
            }

            // Create table for request.
            $table = $formatter->buildTable($tableRequest);

            // If got a filter and some filter data then submit form.
            if (null !== ($filter = $table->getFilter()) && $tableRequest->hasFilters()) {
                $filters = $tableRequest->getFilters();
                if (!$tableRequest->isBatch()) {
                    $options = $filter->getConfig()->getOptions();

                    // Manually add CSRF token if not already set. Batch requests have own CSRF field.
                    // We do this in order to make valid filter form when loading page first time.
                    if ($options['csrf_protection'] && !\array_key_exists($options['csrf_field_name'], $filters)) {
                        $manager = $options['csrf_token_manager'];
                        $tokenId = $options['csrf_token_id'] ?: ($filter->getName() ?: \get_class($filter->getConfig()->getType()->getInnerType()));
                        $filters[$options['csrf_field_name']] = (string) $manager->getToken($tokenId);
                    }
                }

                $filter->submit($filters);
            }

            if (null !== $response || (!$requestIsForTable && !$tableRequest->isBatch())) {
                // Request already handled or not for this table.
                continue;
            }

            // A specific request was made for this table or a batch request, need to process it and set response (if any).
            $result = $table->process($tableRequest);

            if ($tableRequest->isBatch()) {
                // Handle batch request.
                $batchActions = $table->getBatchActions();
                /** @var BatchActionInterface $batchAction */
                if (!($batchAction = ($batchActions[$tableRequest->getBatch()->getName()] ?? null)) instanceof BatchActionInterface) {
                    throw new InvalidArgumentException(sprintf('Unknown batch action "%s". It should be one of %s.', $tableRequest->getBatch()->getName(), implode(', ', array_keys($batchActions))));
                }

                $response = $batchAction->handleRequest($request, $tableRequest, $table, $result, $formatter);
            } elseif ($formatter instanceof AjaxFormatterInterface) {
                // Handle table request.
                $response = $formatter->formatDataResult($tableRequest, $table, $result);
            } else {
                // Render response directly.
                $response = new Response($this->twig->render(
                    $formatter->getTemplate(),
                    array_merge($formatter->getTemplateParameters(), ['formatter' => $formatter]),
                ));
            }
        }

        return $this->result = new TableHelperResult($formatters, $response);
    }

    /**
     * @param class-string<AbstractFormatter> $formatter
     * @param array<string, mixed>            $options
     */
    public function createFormatter(string $formatter, Table|TableReference $table, array $options = []): FormatterInterface
    {
        return $this->instantiator->getFormatter($formatter, array_merge($options, ['table' => $table]));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createDefaultFormatter(Table|TableReference $table, array $options = []): FormatterInterface
    {
        return $this->createFormatter($this->defaultFormatter, $table, $options);
    }

    public function getTableFactory(): TableFactory
    {
        return $this->tableFactory;
    }

    public function getResult(): ?TableHelperResult
    {
        return $this->result;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildBatchParameters(FormatterInterface $formatter, string $batch): array
    {
        return [
            'table' => $formatter->getTableName(),
            'name' => $batch,
            'token' => $this->csrfTokenManager->getToken($formatter->getCsrfTokenId())->getValue(),
        ];
    }

    private function parseBatchRequest(Request $request, FormatterInterface $formatter, TableRequest $tableRequest): bool
    {
        /** @var mixed $data */
        $data = $request->isMethodSafe() ? $request->query->all(self::BATCH_KEY) : $request->request->all(self::BATCH_KEY);
        if (!$request->isMethod($formatter->getMethod()) || $formatter->getTableName() !== ($data['table'] ?? null)) {
            // Invalid HTTP method or table name.
            return false;
        }

        if ($this->tokenStorage->hasToken($formatter->getCsrfTokenId()) && !$this->csrfTokenManager->isTokenValid(new CsrfToken($formatter->getCsrfTokenId(), (string) ($data['token'] ?? '')))) {
            // Invalid CSRF token.
            return false;
        }

        if ($request->isMethodSafe()) {
            $request->query->remove(self::BATCH_KEY);
        } else {
            $request->request->remove(self::BATCH_KEY);
        }

        $tableRequest->setBatch(new TableBatchRequest(
            (string) ($data['name'] ?? ''),
            '1' === ($data['all'] ?? null),
            \is_array($data['ids'] ?? null) ? $data['ids'] : [],
            $request->headers->has(self::HEADER_SUBMIT),
        ));

        $this->updateTableRequest(
            $tableRequest,
            is_scalar($data['search'] ?? null) ? (string) $data['search'] : null,
            \is_array($data['filters'] ?? null) ? $data['filters'] : null,
            is_scalar($data['list'] ?? null) ? (string) $data['list'] : null,
        );

        return true;
    }

    private function parseTableRequest(Request $request, FormatterInterface $formatter, TableRequest $tableRequest): void
    {
        if ($tableRequest->isBatch() || !$formatter instanceof AjaxFormatterInterface || null === $formatter->getTableNameUrl()) {
            // This is a batch request, not a valid formatter or the table shouldn't be in url.
            return;
        }

        if ('' === ($tableName = $formatter->getTableNameUrl())) {
            // Use root for processing params.
            $search = $request->query->get('search');
            $filters = $request->query->has('filters') ? $request->query->all('filters') : null;
            $list = $request->query->get('list');
            $request->query->remove('search');
            $request->query->remove('filters');
            $request->query->remove('list');
        } else {
            // Use specified param.
            $values = $request->query->all($tableName);
            $search = $values['search'] ?? null;
            $filters = $values['filters'] ?? null;
            $list = $values['list'] ?? null;
            // @TODO check
            unset($values['search'], $values['filters'], $values['list']);

            if (0 === \count($values)) {
                $request->query->remove($tableName);
            } else {
                $request->query->set($tableName, $values);
            }
        }

        $this->updateTableRequest(
            $tableRequest,
            is_scalar($search) ? (string) $search : null,
            \is_array($filters) ? $filters : null,
            is_scalar($list) ? (string) $list : null,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function updateTableRequest(TableRequest $request, ?string $search, ?array $filters, ?string $list): void
    {
        if (null !== $search) {
            $request->setSearch($search);
        }

        if (null !== $filters) {
            $request->setFilters($filters);
        }

        if (null !== $list) {
            $list = explode(',', $list);
            $resetOrderBy = false;

            foreach ($list as $k => $part) {
                $parts = explode('_', $part);
                if (\count($parts) < 2 || !\in_array($parts[\count($parts) - 1], [TableRequest::ORDER_ASC, TableRequest::ORDER_DESC], true)) {
                    break;
                }

                // Need to add new order by clause, reset it only once.
                if (!$resetOrderBy) {
                    $request->setOrderBy([]);
                    $resetOrderBy = true;
                }

                $request->addOrderBy(implode('_', \array_slice($parts, 0, \count($parts) - 1)), $parts[\count($parts) - 1]);
                unset($list[$k]);
            }

            $list = array_values($list);

            // Only process offset/limit if not a batch request.
            if (!$request->isBatch()) {
                if (is_numeric($list[0] ?? null)) {
                    $request->setOffset(max(0, (int) $list[0]));
                }

                if (is_numeric($list[1] ?? null)) {
                    $limit = (int) $list[1];
                    if ($limit <= 0) {
                        $limit = null;
                    }

                    $request->setLimit($limit);
                }
            }
        }
    }
}
