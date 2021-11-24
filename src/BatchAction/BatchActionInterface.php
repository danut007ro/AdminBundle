<?php

declare(strict_types=1);

namespace DG\AdminBundle\BatchAction;

use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;
use DG\AdminBundle\UIAction\UIActionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatableMessage;

interface BatchActionInterface
{
    /**
     * Retrieves the label to be shown for the batch action.
     */
    public function getLabel(): TranslatableMessage|string;

    /**
     * Retrieves the icon to be shown.
     */
    public function getIcon(): string;

    /**
     * The ui action for batch request.
     */
    public function getUIAction(string $name, FormatterInterface $formatter): ?UIActionInterface;

    public function handleRequest(Request $request, TableRequest $tableRequest, Table $table, DataResultInterface $result, FormatterInterface $formatter): ?Response;
}
