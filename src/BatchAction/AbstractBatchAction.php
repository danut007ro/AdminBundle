<?php

declare(strict_types=1);

namespace DG\AdminBundle\BatchAction;

use DG\AdminBundle\AbstractConfigurableClass;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Response\SwalNotificationResponse;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\TableHelper;
use DG\AdminBundle\UIAction\AjaxUIAction;
use DG\AdminBundle\UIAction\UIActionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractBatchAction extends AbstractConfigurableClass implements BatchActionInterface
{
    public function __construct(protected TableHelper $tableHelper, protected TranslatorInterface $translator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('label')
            ->setDefault('icon', '')
            ->setAllowedTypes('label', ['string', TranslatableMessage::class])
            ->setAllowedTypes('icon', 'string')
            ->setInfo('label', 'Label to be shown for this batch action.')
            ->setInfo('icon', 'Icon to be shown for this batch action.')
        ;
    }

    public function getLabel(): string|TranslatableMessage
    {
        return $this->options['label'];
    }

    public function getIcon(): string
    {
        return $this->options['icon'];
    }

    public function getUIAction(string $name, FormatterInterface $formatter): ?UIActionInterface
    {
        return new AjaxUIAction($this->buildAjaxUIActionParameters($name, $formatter));
    }

    /**
     * Build parameters to be used for ui ajax requests.
     *
     * @param mixed[] $extraParams
     *
     * @return mixed[]
     */
    protected function buildAjaxUIActionParameters(string $name, FormatterInterface $formatter, array $extraParams = []): array
    {
        $params = $this->tableHelper->buildBatchParameters($formatter, $name);

        return [
            'url' => $formatter->getUrl(),
            'url_parameters' => [
                'method' => $formatter->getMethod(),
                'body' => array_merge(
                    [TableHelper::BATCH_KEY => $params],
                    $extraParams,
                ),
            ],
            'add_table_request_table' => true,
            'add_table_request_var' => TableHelper::BATCH_KEY,
        ];
    }

    protected function validateSelectionNotEmpty(DataResultInterface $result): ?SwalNotificationResponse
    {
        return 0 === $result->getTotalCount()
            ? new SwalNotificationResponse([
                'title' => $this->translator->trans('Select at least one entry', [], 'dg_admin'),
                'icon' => 'warning',
            ], Response::HTTP_BAD_REQUEST)
            : null;
    }
}
