# Batch actions

Batch actions are actions that you can add for your entire table. Current filters are automatically added to the request. Note that just like filters, currently only the [DatatableFormatter](../formatters.md#datatableformatter) together with [ORMAdapter](../adapters/orm_adapter.md) supports batch actions.

To add a batch action you need to call `addBatchAction()` method on `Table` object. This method accepts `BatchActionInterface | string`. If an implementation is given then it is considered configured and `$options` parameter is ignored. If a `string` is given then the [instantiator](../instantiator.md) is used to retrieve and configure the batch action. 

If you want the user to select some rows from a Table then you can also add a column with checkboxes using [BatchColumn](columns.md#batchcolumn). Adding this column automatically adds options to select all rows, none or current page.  You can also [export a different set of columns](columns.md#showing-a-different-set-of-columns-when-exporting) or [different content for each column](columns.md#showing-different-content-for-a-column-when-exporting).

All batch actions have the default following options:

| Option | Type                     | Description                              |
|--------|--------------------------|------------------------------------------|
| label  | `string` (required)      | Label to be shown for this batch action. |
| icon   | `string`                 | Icon to be shown for this batch action.  |

- [Exporting as CSV using ExportCsvBatchAction](#exporting-as-csv)
- [Exporting as XLSX using ExportXlsxBatchAction](#exporting-as-xlsx)
- [Cookbook](#cookbook)
    - [Creating a custom batch action](#creating-a-custom-batch-action)
    - [Creating a custom batch action with confirmation](#creating-a-custom-batch-action-with-confirmation)
    - [Creating a custom batch action that displays a form](#creating-a-custom-batch-action-that-displays-a-form)

## Exporting as CSV

This batch action depends on [portphp/csv](https://github.com/portphp/csv) library.

| Option   | Type                             | Description                       |
|----------|----------------------------------|-----------------------------------|
| filename | `string` (default: `export.csv`) | Filename for the downloaded file. |

## Exporting as XLSX

This batch action depends on [portphp/spreadsheet](https://github.com/portphp/spreadsheet) library.

| Option   | Type                              | Description                       |
|----------|-----------------------------------|-----------------------------------|
| filename | `string` (default: `export.xlsx`) | Filename for the downloaded file. |
| sheet    | `string` \| `null` (default)      | Sheet name inside xlsx file.      |

# Cookbook

This cookbook will explain how to create a custom batch action to enable/disable an Employee entity. There are 3 kids of batch actions and we will cover all of them:

- making an ajax request without any confirmation
- showing a confirmation dialog and making the ajax request after user confirming the action
- showing a Form inside a dialog and making the ajax request after user completing it with valid data

> We'll assume that you already have a [CRUDAdapter](../adapters/README.md#abstractdoctrineormcrudadapter) for your Employee entity. Processing the entity will need the `id` field, so make sure that it is added to columns (maybe as `visible => false`).
> 
> To add batch action on Table you need to call `$table->->addBatchAction('enable', EnableAllBatchAction::class, ['label' => 'Enable all selected'])`. You can omit the `label` option and add it directly using the `configureOptions()` method of batch action, but we won't cover that here.

## Creating a custom batch action

```php
use DG\AdminBundle\BatchAction\AbstractBatchAction;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Response\SwalNotificationResponse;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;

class EnableAllBatchAction extends AbstractBatchAction
{
    public function handleRequest(Request $request, TableRequest $tableRequest, Table $table, DataResultInterface $result, FormatterInterface $formatter): Response
    {
        if (null !== $response = $this->validateSelectionNotEmpty($result)) {
            return $response;
        }

        // This is the CRUD adapter.
        $adapter = $table->getAdapter();
        foreach ($result->getData() as $row) {
            // Read Employee entity using the adapter and enable it.
            if (null !== $employee = $adapter->read($row['id'])) {
                $employee->setEnabled(true);
                $adapter->update($employee);
            }
        }

        // Show a notification when done.
        return new SwalNotificationResponse(['title' => 'All selected employees enabled']);
    }
}
```

## Creating a custom batch action with confirmation

```php
use DG\AdminBundle\BatchAction\AbstractBatchAction;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Response\ResponseUpdater;
use DG\AdminBundle\Response\SwalNotificationResponse;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;
use DG\AdminBundle\TableHelper;
use DG\AdminBundle\UIAction\AjaxDialogUIAction;

class DisableAllBatchAction extends AbstractBatchAction
{
    public function __construct(
        TableHelper $tableHelper,
        TranslatorInterface $translator,
        private Environment $twig,
    ) {
        parent::__construct($tableHelper, $translator);
    }


    public function handleRequest(Request $request, TableRequest $tableRequest, Table $table, DataResultInterface $result, FormatterInterface $formatter): Response
    {
        if (null !== $response = $this->validateSelectionNotEmpty($result)) {
            return $response;
        }

        // Display dialog if batch request isn't submitted.
        if (!$tableRequest->getBatch()->isSubmitted()) {
            return new Response(
                $this->twig->render(
                    '@DGAdmin/dialog/action.html.twig',
                    [
                        'title' => 'Disable all employees',
                        'message' => 'Are you sure you want to disable all selected employees?',
                    ],
                ),
            );
        }

        // This is the CRUD adapter.
        $adapter = $table->getAdapter();
        foreach ($result->getData() as $row) {
            // Read Employee entity using the adapter and disable it.
            if (null !== $employee = $adapter->read($row['id'])) {
                $employee->setEnabled(false);
                $adapter->update($employee);
            }
        }

        // Close dialog and show a notification when done.
        return ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => 'All selected employees disabled']));
    }
    
    public function getUIAction(string $name, FormatterInterface $formatter): AjaxDialogUIAction
    {
        // The button will show a dialog.
        return new AjaxDialogUIAction(array_merge(
            $this->buildAjaxUIActionParameters($name, $formatter),
            ['append_body_data' => true],
        ));
    }
}
```

## Creating a custom batch action that displays a form

```php
use DG\AdminBundle\BatchAction\AbstractBatchAction;
use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Response\ResponseUpdater;
use DG\AdminBundle\Response\SwalNotificationResponse;
use DG\AdminBundle\Result\Data\DataResultInterface;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;
use DG\AdminBundle\TableHelper;
use DG\AdminBundle\UIAction\AjaxDialogUIAction;

class ChangeAllBatchAction extends AbstractBatchAction
{
    public function __construct(
        TableHelper $tableHelper,
        TranslatorInterface $translator,
        private Environment $twig,
        private FormFactoryInterface $formFactory,
    ) {
        parent::__construct($tableHelper, $translator);
    }


    public function handleRequest(Request $request, TableRequest $tableRequest, Table $table, DataResultInterface $result, FormatterInterface $formatter): Response
    {
        if (null !== $response = $this->validateSelectionNotEmpty($result)) {
            return $response;
        }

        // Form to be shown on dialog.
        $form = $this->formFactory->createBuilder()
            ->add('enabled', CheckboxType::class, ['required' => false])
            ->getForm()
        ;

        // Let form handle the request if it's submitted.
        if ($tableRequest->getBatch()->isSubmitted()) {
            $form->handleRequest($request);
        }

        // Display dialog if batch request isn't submitted or the form has errors.
        if (!$form->isSubmitted() || !$form->isValid()) {
            return new Response(
                $this->twig->render(
                    '@DGAdmin/dialog/create_update.html.twig',
                    [
                        'title' => 'Update all employees',
                        'dialog_class' => 'modal-sm',
                        'form' => $form->createView(),
                    ],
                ),
                $form->isSubmitted() ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK,
            );
        }

        // This is the CRUD adapter.
        $adapter = $table->getAdapter();
        foreach ($result->getData() as $row) {
            // Read Employee entity using the adapter and set enabled status from form.
            if (null !== $employee = $adapter->read($row['id'])) {
                $employee->setEnabled($form->get('enabled')->getData());
                $adapter->update($employee);
            }
        }

        // Close dialog and show a notification when done.
        return ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => 'All selected employees updated']));
    }
    
    public function getUIAction(string $name, FormatterInterface $formatter): AjaxDialogUIAction
    {
        // The button will show a dialog.
        return new AjaxDialogUIAction(array_merge(
            $this->buildAjaxUIActionParameters($name, $formatter),
            ['append_body_data' => true],
        ));
    }
}
```
