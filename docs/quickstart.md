# Quickstart

The following code in your controller prepares a fully functional DataTables instance to use. The [TableHelper](../src/TableHelper.php) service is injected to expose convenience methods in your controller. In the following example, the controller method will receive both `GET` and `POST` requests since by default DatatableFormatter is set to make `POST` requests.

An [adapter](adapters/README.md) is created for an `Employee` entity. Then a [Table](table/README.md) is built using the `TableFactory`. On the `Table` instance we add 2 columns of type [TextColumn](table/columns.md#textcolumn). Then we bind the `Adapter` to `Table`.

The `handleRequest()` method will take care of handling any callbacks, similar to how Symfony's Form component works. If it turns out the request originated from a callback we let the table provide the controller response, otherwise we render a template that will automatically include the `Table`.

```php
use DG\AdminBundle\Adapter\ORMAdapter;
use DG\AdminBundle\Column\TextColumn;
use DG\AdminBundle\Formatter\DatatableFormatter;
use DG\AdminBundle\TableHelper;

class MyController extends AbstractController
{
    public function listEmployeesAction(Request $request, TableHelper $tableHelper): Response
    {
        // Create table.
        $table = $tableHelper->getTableFactory()->createTable()
            ->addColumn('firstName', TextColumn::class)
            ->addColumn('lastName', TextColumn::class)
            ->setAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
            ])
        ;

        // Try to handle Symfony request.
        $result = $tableHelper->handleRequest($request, [
            $tableHelper->createFormatter(DatatableFormatter::class, $table),
        ]);

        // If we got a response (for a table request) then return it.
        if ($result->hasResponse()) {
            return $result->getResponse();
        }

        // Continue Symfony request processing and render template.
        return $this->render('table_template.html.twig');
    }
}
```

> Note that the above code can be further reduced by having the components in separate classes (table configurator, adapter).

## Frontend

In your Twig template, `table_template.html.twig` in the example above, you need to ensure that in the `<body>` tag there is a call to `dg_admin_init_body()` function and javascript file [dg_admin.js](../src/Resources/public/dg_admin.js) is included.

To display the table at a certain position in your Twig template, you need to call `dg_admin_table()` function. This function initializes the global options for admin, like the available ranges for date range picker.

```twig
<!-- Insert this in your <body> tag -->
<body {{ dg_admin_init_body() }}>

<!-- Insert this where you want the table to appear -->
{{ dg_admin_table() }}
```

> Remember that if you use the [DatatableFormatter](formatters.md#datatableformatter) then you must load [datatables](https://github.com/DataTables/DataTables) library before this code. Also, note that [jQuery Form plugin](https://github.com/jquery-form/form) and [conditionize2](https://github.com/rguliev/conditionize2.js) libraries are required.

### Setup with AdminLTE

To setup an application with [AdminLTE](https://github.com/ColorlibHQ/AdminLTE) you just need to require both [dg_admin.js](../src/Resources/public/dg_admin.js) and [dg_admin.scss](../src/Resources/public/dg_admin.scss) files.
