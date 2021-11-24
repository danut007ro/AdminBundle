# Cookbook for `ActionColumn`

This bundle provides an easy way to implement `CRUD` actions for a Table row. In order to do so, your adapter needs to implement [CRUDAdapterInterface](../../src/Adapter/CRUDAdapterInterface.php). If you use the [ORMAdapter](../adapters/orm_adapter.md) we provide an implementation in [AbstractDoctrineORMCRUDAdapter](../../src/Adapter/AbstractDoctrineORMCRUDAdapter.php). See the [following example](../adapters/README.md#abstractdoctrineormcrudadapter) on how to declare an adapter for your Doctrine entity. In the following examples we'll assume that you already have an `EmployeeAdapter`.

Actions are based on `UI Actions` so you can also check the [UI Actions documentation](../ui_actions.md) for more information.

- [Displaying a `create` popup with a Form](#displaying-a-create-popup-with-a-form)
- [Redirecting to a page for `read` action](#redirecting-to-a-page-for-read-action)
- [Displaying an `update` popup with a Form](#displaying-an-update-popup-with-a-form)
- [Displaying a `delete` confirmation popup](#displaying-a-delete-confirmation-popup)
- [Displaying a confirmation popup with custom action](#displaying-a-confirmation-popup-with-custom-action)
- [Calling an action directly, without confirmation](#calling-an-action-directly-without-confirmation)

## Displaying a `create` popup with a Form

Since the `create` action is not linked to a Table row, it can be triggered from anywhere on page. There are two ways to trigger the popup from your Twig template:

1. using the `dg_admin_uiaction()` Twig function:

    ```twig
    <a href="/employee/create" {{ dg_admin_uiaction(constant('DG\\AdminBundle\\UIAction\\AjaxDialogUIAction::NAME'), {'refresh_table': true, 'restore_url': ''}) }}>Create employee</a>
    ```

2. using `DGAdmin.ajaxDialog()` javascript method:
    ```javascript
    <a href="/employee/create" onclick="DGAdmin.ajaxDialog(this, {'restore_url':''}).then(() => DGAdmin.refreshAllTables()); return false">Create employee</a>
    ```

> Note that `restore_url` option is needed if you want to change the url of page to `/employee/create` when popup is shown. This will also automatically display the popup when directly accessing that url, and restore the address when the popup is closed.
>
> If a validation fails when submitting, the popup will be changed with new content. If validation is ok, then after closing the popup all tables will refresh in order to reflect newly added entries. You can also refresh a specific table, or none. More documentation can be read in the [javascript documentation](../javascript.md).

Next, we'll assume that you have an `EmployeeForm` and an `EmployeeAdapter`. Your controller needs to look like this:

```php
# Controller/EmployeeController.php

use DG\AdminBundle\ControllerHelper;
use DG\AdminBundle\Response\ResponseUpdater;
use DG\AdminBundle\Response\SwalNotificationResponse;
use DG\AdminBundle\UIAction\AjaxDialogUIAction;

class EmployeeController extends AbstractController
{
    // List employees at url /employee/list
    public function listEmployeesAction(...): Response
    {
    }
    
    // This method will receive both GET and POST methods at url /employee/create
    public function createEmployeeAction(Request $request, ControllerHelper $controllerHelper, EmployeeAdapter $employeeAdapter): Response
    {
        return
            // Attempt to handle default url. Directly accessing the /employee/create url will call listEmployeesAction() and the popup will appear automatically.
            $controllerHelper->default($request, __CLASS__.'::listEmployeesAction', new AjaxDialogUIAction(['restore_url' => '/employee/list']))
            // Attempt to create employee using adapter.
            ?? $controllerHelper->crudCreate($request, $this->createForm(EmployeeForm::class), $employeeAdapter)
            // Employee is created, close popup.
            ?? ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => 'Employee created']));
    }
}
```

## Redirecting to a page for `read` action

Redirecting to an url is the simplest action that can be done inside an `ActionColumn`:

```php
$table->addColumn('actions', ActionColumn::class, [
    'actions' => fn (Employee $employee): array => [
        [
            'url' => "/employee/read/{$employee->getId()}", # Url to read/show details action (better to use Router)
            'icon' => 'fas fa-eye',
        ],
    ],
]);
```

## Displaying an `update` popup with a Form

The `update` action should be displayed on Table rows, so we use an `ActionColumn` to do that:

```php
$table->addColumn('actions', ActionColumn::class, [
    'actions' => fn (Employee $employee): array => [
        [
            'url' => "/employee/update/{$employee->getId()}", # Url to update action (better to use Router)
            'icon' => 'fas fa-edit',
            'ui_action' => new AjaxDialogUIAction(['restore_url' => '']), # Same option as create, to handle url
        ],
    ],
]);
```

The controller is almost the same as `create` action, so we list only the `updateEmployeeAction()` method:

```php
# Controller/EmployeeController.php

// This method will receive both GET and POST methods at url /employee/update/{id}
public function updateEmployeeAction(Request $request, Employee $employee, ControllerHelper $controllerHelper, EmployeeAdapter $employeeAdapter): Response
{
    return
        // Attempt to handle default url. Directly accessing the /employee/update/{id} url will call listEmployeesAction() and the popup will appear automatically.
        $controllerHelper->default($request, __CLASS__.'::listEmployeesAction', new AjaxDialogUIAction(['restore_url' => '/employee/list']))
        // Attempt to create employee using adapter.
        ?? $controllerHelper->crudUpdate($request, $this->createForm(EmployeeForm::class, $employee), $employeeAdapter)
        // Employee is updated, close popup.
        ?? ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => 'Employee updated']));
}
```

## Displaying a `delete` confirmation popup

The `delete` action can be simplified a little, because we don't need to show url for it (note that the `restore_url` is not present anymore):

```php
$table->addColumn('actions', ActionColumn::class, [
    'actions' => fn (Employee $employee): array => [
        [
            'url' => "employee/delete/{$employee->getId()}", # Url to delete action (better to use Router)
            'icon' => 'fas fa-trash',
            'ui_action' => new AjaxDialogUIAction(), # Note that restore_url option is not present anymore
        ],
    ],
]);
```

The controller is also simpler, with default handling removed:

```php
# Controller/EmployeeController.php

// This method will receive both GET and DELETE methods at url /employee/delete/{id}
public function deleteEmployeeAction(Request $request, Employee $employee, ControllerHelper $controllerHelper, EmployeeAdapter $employeeAdapter): Response
{
    return
        // Attempt to create employee using adapter.
        $controllerHelper->crudDelete($request, $employee, $employeeAdapter)
        // Employee is deleted, close popup.
        ?? ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => 'Employee deleted']));
}
```

## Displaying a confirmation popup with custom action

Let's create a custom action for an employee to anonymize it (change name to "John Doe"). This action will require confirmation from user, so we need to display a popup (code is almost the same as `delete` action with different url of course):

```php
$table->addColumn('actions', ActionColumn::class, [
    'actions' => fn (Employee $employee): array => [
        [
            'url' => "employee/anonymize/{$employee->getId()}", # Url to anonymize action (better to use Router)
            'icon' => 'fas fa-eye',
            'ui_action' => new AjaxDialogUIAction(),
        ],
    ],
]);
```

The controller will need to handle custom action:

```php
# Controller/EmployeeController.php

// This method will receive both GET and POST methods at url /employee/anonymize/{id}
public function anonymizeEmployeeAction(Request $request, Employee $employee, ControllerHelper $controllerHelper, EmployeeAdapter $employeeAdapter): Response
{
    $response = $controllerHelper->action($request, '@DGAdmin/dialog/action.html.twig', [
        'title' => 'Confirmation',
        'message' => 'Are you sure you want to anonymize?',
    ]);

    if (null !== $response) {
        return $response;
    }

    $employee->setName('John Doe');
    $employeeAdapter->update($employee);

    return ResponseUpdater::closeDialog(new SwalNotificationResponse(['title' => 'Employee anonymized']));
}
```

## Calling an action directly, without confirmation

Let's create a custom action for an employee to disable it. We won't use a confirmation popup, but we need CSRF protection, so we'll implement it also:

```php
$table->addColumn('actions', ActionColumn::class, [
    'actions' => fn (Employee $employee): array => [
        [
            'url' => "employee/disable/{$employee->getId()}", # Url to disable action (better to use Router)
            'icon' => 'fas fa-check',
            'ui_action' => new AjaxUIAction([
                'url_parameters' => [
                    'method' => Request::METHOD_POST,
                    'body' => [
                        // We use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface to generate CSRF token.
                        '_token' => $this->tokenManager->getToken('disableEmployee')->getValue(),
                    ],
                ],
            ]),
        ],
    ],
]);
```

The controller will just check the CSRF token and disable the Employee:

```php
# Controller/EmployeeController.php

// This method will receive just POST methods at url /employee/disable/{id}
public function disableEmployeeAction(Request $request, Employee $employee, ControllerHelper $controllerHelper, EmployeeAdapter $employeeAdapter): Response
{
    if ($this->isCsrfTokenValid('disableEmployee', $request->get('_token'))) {
        $employee->setEnabled(false);
        $employeeAdapter->update($employee);
    }

    return new SwalNotificationResponse(['title' => 'Employee disabled']);
}
```
