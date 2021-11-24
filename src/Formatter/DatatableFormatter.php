<?php

declare(strict_types=1);

namespace DG\AdminBundle\Formatter;

use DG\AdminBundle\Exception\InvalidArgumentException;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DatatableFormatter extends AbstractAjaxFormatter
{
    private const PARAM = '_datatable';

    /**
     * @param array<string, mixed> $defaultOptions
     */
    public function __construct(
        array $defaultOptions,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($defaultOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'template' => $this->defaultOptions['template'],
                'table_template' => $this->defaultOptions['table_template'],
                'table_template_parameters' => [],
                'options' => [],
            ])
            ->setAllowedTypes('table_template', 'string')
            ->setAllowedTypes('table_template_parameters', 'array')
            ->setAllowedTypes('options', 'array')
            ->setInfo('table_template', 'Twig template to use when rendering table.')
            ->setInfo('table_template_parameters', 'Parameters to be passed to Twig template when rendering table.')
            ->setInfo('options', 'Options to be passed when initializing Datatables js library.')
        ;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function configure(array $options): static
    {
        $options['options'] = array_merge(
            $this->defaultOptions['options'] ?? [],
            $options['options'] ?? [],
        );

        // Update lengthMenu in $options with translated 'All'.
        if (\is_array($options['options']['lengthMenu'] ?? null) && 2 === \count($options['options']['lengthMenu'])) {
            $all = $this->translator->trans('All', [], 'dg_admin');
            foreach ($options['options']['lengthMenu'][1] as $k => $v) {
                if ('' === $v) {
                    $options['options']['lengthMenu'][1][$k] = $all;
                }
            }
        }

        $options['template_parameters'] = array_merge(
            $this->defaultOptions['template_parameters'] ?? [],
            $options['template_parameters'] ?? [],
        );

        $options['table_template_parameters'] = array_merge(
            $this->defaultOptions['table_template_parameters'] ?? [],
            $options['table_template_parameters'] ?? [],
        );

        parent::configure($options);

        return $this;
    }

    public function getName(): string
    {
        return 'datatable';
    }

    public function parseTableRequest(Request $request, TableRequest $tableRequest, bool $isSubtable = false): bool
    {
        // Determine what params should use based on request method.
        /** @var mixed $params */
        $params = $request->isMethod(Request::METHOD_POST) ? $request->request->all() : $request->query->all();

        if (!$request->isXmlHttpRequest() || !$request->isMethod($this->getMethod())) {
            // For now, this request is not for this table.
            // Let's check if actually a datatable with this name or just a single subtable.
            if (($params[self::PARAM] ?? null) !== $this->getTableName() && !$isSubtable) {
                return false;
            }
        }

        if ($this->options['options']['paging']) {
            $start = $params['start'] ?? null;
            if (is_numeric($start)) {
                $tableRequest->setOffset(max(0, (int) $start));
            }
        }

        if ($this->options['options']['lengthChange']) {
            $length = $params['length'] ?? null;
            if (is_numeric($length)) {
                $length = (int) $length;
                if ($length > 0) {
                    $tableRequest->setLimit($length);
                }
            }
        }

        if (!$tableRequest->hasLimit()) {
            // No default limit was set and, use the limit from datatable.
            $tableRequest->setLimit($this->options['options']['pageLength']);
        }

        // Fix limit if is invalid. Use smallest limit (excluding null).
        if (!\in_array(null === $tableRequest->getLimit() ? -1 : $tableRequest->getLimit(), $this->options['options']['lengthMenu'][0], true)) {
            // Find smallest limit (excluding null).
            $lengths = $this->options['options']['lengthMenu'][0];
            $all = array_search(-1, $lengths, true);
            if (false !== $all) {
                unset($lengths[$all]);
            }

            if (\count($lengths) > 0) {
                $tableRequest->setLimit(min(...array_values($lengths)));
            }
        }

        if ($this->options['options']['ordering']) {
            $order = $params['order'] ?? null;
            $columns = $params['columns'] ?? null;
            if (\is_array($order) && \is_array($columns)) {
                $orderBys = [];
                foreach ($order as $orderBy) {
                    if (!\is_array($orderBy)
                        || 2 !== \count($orderBy)
                        || !is_scalar($columnIndex = ($orderBy['column'] ?? null))
                        || !is_scalar($dir = ($orderBy['dir'] ?? null))) {
                        continue;
                    }

                    $dir = (string) $dir;
                    if (!\in_array($dir, [TableRequest::ORDER_ASC, TableRequest::ORDER_DESC], true)) {
                        continue;
                    }

                    $columnIndex = (string) $columnIndex;
                    $column = ($columns[$columnIndex]['name'] ?? null);
                    if (is_scalar($column)) {
                        $orderBys[(string) $column] = $dir;
                    }
                }

                $tableRequest->setOrderBy($orderBys);
            }
        }

        $filters = $params['filters'] ?? null;
        if (\is_array($filters)) {
            $tableRequest->setFilters($filters);
        }

        if ($this->options['options']['searching']) {
            $searching = $params['search']['value'] ?? null;
            if (is_scalar($searching)) {
                $tableRequest->setSearch((string) $searching);
            }
        }

        $tableRequest->getOptions()->set('_draw', is_scalar($params['draw'] ?? null) ? ((string) $params['draw']) : '');
        $tableRequest->getOptions()->set('_init', \array_key_exists('_init', $params) || $isSubtable);
        $tableRequest->getOptions()->set('_subtable', $isSubtable);

        // Setup total count based on wanted pagination type.
        if (!$this->options['options']['paging']) {
            $tableRequest->setTotal(TableRequest::TOTAL_NONE);
        } elseif (str_starts_with($this->options['options']['pagingType'], 'simple')) {
            $tableRequest->setTotal(TableRequest::TOTAL_SIMPLE);
        }

        return true;
    }

    public function formatDataResult(TableRequest $request, Table $table, DataResultInterface $result): JsonResponse
    {
        if (!$request->getOptions()->has('_draw')) {
            throw new InvalidArgumentException('Required option "draw" missing.');
        }

        $data = [];
        foreach ($result->getData() as $k => $row) {
            $data[] = array_merge(
                $row,
                ['DT_RowID' => $k],
            );
        }

        $json = [
            'draw' => $request->getOptions()->get('_draw'),
            'recordsTotal' => $result->getTotalCount(),
            'recordsFiltered' => $result->getFilteredCount(),
            'data' => $data,
        ];

        $isSubtable = $request->getOptions()->getBoolean('_subtable');
        $isInit = $request->getOptions()->getBoolean('_init');

        if ($isInit || $isSubtable) {
            if ($isInit && $isSubtable) {
                // Initializing a subtable, also set container html.
                $json['container'] = $this->twig->render(
                    $this->getTemplate(),
                    [
                        'formatter' => $this,
                        'parameters' => $this->getTemplateParameters(),
                    ],
                );
            }

            $json['template'] = $this->twig->render($this->options['table_template'], [
                'parameters' => $this->options['table_template_parameters'],
                'columns' => $table->getColumns(),
            ]);

            $json['options'] = $this->options['options'];
            $json['options']['displayStart'] = $request->getOffset();
            $json['options']['pageLength'] = null === ($limit = $request->getLimit()) ? -1 : $limit;
            if (!$json['options']['serverSide']) {
                // Disable "processing" message if no server side.
                $json['options']['processing'] = false;
            }

            $json['options']['columns'] = [];
            foreach ($table->getColumns() as $name => $column) {
                $json['options']['columns'][] = [
                    'data' => $name,
                    'name' => $name,
                    'orderable' => $column->isSortable(),
                    'visible' => $column->isVisible(),
                ];
            }

            $json['options']['order'] = [];
            $columnToIndex = array_flip(array_keys($table->getColumns()));
            foreach ($request->getOrderBy() as $column => $dir) {
                if (\array_key_exists($column, $columnToIndex)) {
                    $json['options']['order'][] = [$columnToIndex[$column], $dir];
                }
            }

            if ($request->hasSearch()) {
                $json['options']['search']['search'] = $request->getSearch();
            }
        }

        return new JsonResponse($json);
    }
}
