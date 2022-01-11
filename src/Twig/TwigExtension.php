<?php

declare(strict_types=1);

namespace DG\AdminBundle\Twig;

use DG\AdminBundle\DateFormat\DateFormats;
use DG\AdminBundle\Exception\LogicException;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\TableHelper;
use DG\AdminBundle\UIAction\AjaxDialogUIAction;
use DG\AdminBundle\UIAction\UIActionInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    public function __construct(
        private Environment $twig,
        private TableHelper $tableHelper,
        private DateFormats $dateFormats,
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('dg_admin_init_body', [$this, 'initBody'], ['is_safe' => ['html']]),
            new TwigFunction('dg_admin_table', [$this, 'table'], ['is_safe' => ['html']]),
            new TwigFunction('dg_admin_uiaction', [$this, 'uiAction'], ['is_safe' => ['html']]),
            new TwigFunction('dg_admin_date', [$this, 'date']),
        ];
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('dg_admin_date', [$this, 'date']),
        ];
    }

    public function initBody(): string
    {
        // Build array with date ranges.
        $dateRanges = [];
        foreach ($this->dateFormats->getDateRanges() as $interval => $dateRange) {
            $dateRanges[$this->translator->trans("date_range.{$interval}", [], 'dg_admin')] = [$dateRange['start'], $dateRange['end']];
        }

        $ajaxDialog = null;
        if (null !== ($request = $this->requestStack->getCurrentRequest()) && ($action = $request->attributes->get(UIActionInterface::KEY)) instanceof AjaxDialogUIAction) {
            $parameters = $action->getParameters();
            $refreshTable = $parameters['refresh_table'] ?? false;
            // If action should refresh current table, then refresh all the tables, since we don't know which one is the current one.
            if ('' === $refreshTable) {
                $refreshTable = true;
            }

            $ajaxDialog = array_merge($parameters, ['refresh_table' => $refreshTable]);
        }

        return $this->twig->render('@DGAdmin/init.html.twig', [
            'init' => [
                'daterangepicker' => [
                    'autoUpdateInput' => false,
                    'ranges' => $dateRanges,
                    'locale' => [
                        'applyLabel' => $this->translator->trans('Apply', [], 'dg_admin'),
                        'cancelLabel' => $this->translator->trans('Clear', [], 'dg_admin'),
                        'fromLabel' => $this->translator->trans('From', [], 'dg_admin'),
                        'toLabel' => $this->translator->trans('To', [], 'dg_admin'),
                        'customRangeLabel' => $this->translator->trans('Custom', [], 'dg_admin'),
                        'weekLabel' => $this->translator->trans('Week', [], 'dg_admin'),
                        'separator' => ' - ',
                    ],
                ],
                'ajaxDialog' => $ajaxDialog,
                'errorMessage' => $this->translator->trans('Error. Please try again.', [], 'dg_admin'),
            ],
        ]);
    }

    public function table(string|FormatterInterface $formatter = ''): string
    {
        if (!$formatter instanceof FormatterInterface) {
            if (null === $result = $this->tableHelper->getResult()) {
                throw new LogicException('No TableHelperResult processed by TableHelper.');
            }

            $formatter = $result->getFormatter($formatter);
        }

        return $this->twig->render(
            $formatter->getTemplate(),
            [
                'formatter' => $formatter,
                'parameters' => $formatter->getTemplateParameters(),
            ],
        );
    }

    /**
     * @param mixed[] $parameters
     */
    public function uiAction(null|string|UIActionInterface $uiAction, array $parameters = [], bool $disableAuto = false): string
    {
        if (null === $uiAction || '' === $uiAction) {
            return '';
        }

        if ($uiAction instanceof UIActionInterface) {
            $parameters = $uiAction->getParameters();
            $uiAction = $uiAction->getName();
        }

        return $this->twig->render('@DGAdmin/uiaction.html.twig', [
            'name' => $uiAction,
            'parameters' => $parameters,
            'disableAuto' => $disableAuto,
        ]);
    }

    /**
     * @param mixed[] $options
     */
    public function date(?\DateTimeInterface $dateTime, string $format, array $options = []): string
    {
        if (null === $dateTime) {
            return '';
        }

        /** @var CoreExtension $coreExtension */
        $coreExtension = $this->twig->getExtension(CoreExtension::class);

        $transformer = new DateTimeToLocalizedStringTransformer(
            $options['input_timezone'] ?? null,
            $options['output_timezone'] ?? $coreExtension->getTimezone()->getName(),
            null,
            null,
            \IntlDateFormatter::GREGORIAN,
            $this->dateFormats->getDateFormat($format)->getFormat(),
        );

        return $transformer->transform($dateTime);
    }
}
