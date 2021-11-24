# Table objects

The `Table` is the main object that is to be rendered by an [formatter](../formatters.md). Before processing the Table, an [adapter](../adapters/README.md) must be set on it. This adapter will be used to retrieve the data to be rendered by formatter. The Table allows to add [columns](../table/columns.md), [filters](../table/filters.md) and [batch actions](../table/batch_actions.md). You can also [expand a row](expand_row.md) and show another table inside or some custom content.

A `Table` is built by [TableFactory](../../src/Table/TableFactory.php) service like this (note that `TableFactory` can be injected directly or retrieved using [TableHelper](../../src/TableHelper.php) service):

```php
$tableFactory->createTable()
    ->addColumn('name', TextColumn::class)
    ->addColumn('position', TextColumn::class)
    ->setAdapter($adapter)
;
```

It's recommended to have the table configuration in a different service in order to keep the controller light and reuse the code. This is achieved by creating a service that implements [ConfiguratorInterface](../../src/Table/ConfiguratorInterface.php) or extends [AbstractConfigurator](../../src/Table/AbstractConfigurator.php) which allows you to create a Table like this:

```php
$tableFactory->createTableConfigurator(MyConfigurator::class);
```

```php
# MyConfigurator.php

use DG\AdminBundle\Formatter\FormatterInterface;
use DG\AdminBundle\Table\AbstractConfigurator;
use DG\AdminBundle\Table\Table;
use DG\AdminBundle\Table\TableRequest;

class MyConfigurator extends AbstractConfigurator
{
    public function configureTable(Table $table, TableRequest $request, array $options, FormatterInterface $formatter) : void
    {
        $table
            ->addColumn('name', TextColumn::class)
            ->addColumn('position', TextColumn::class)
            ->setAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
            ])
        ;
    }
}
```

As noted before, the [adapter](../adapters/README.md) is mandatory to be set on the Table using the `Table::setAdapter()` method. This method accepts `AdapterInterface | string` as adapter. If an implementation is given then it is considered configured and `$options` parameter is ignored. If a `string` is given then the [instantiator](../instantiator.md) is used to retrieve and configure the adapter.

## Options

The default global options can be modified using the `table.options` configuration from `dg_admin.yaml`. 

### transform_rows

#### type: `TransformRowsInterface | null` default: `null`

The rows transformer handles the pre-processing of rows before the response is built. This is called before the id extractor and allows to modify the rows. We provide an implementation for the interface in `CallbackTransformRows`.

The default global rows transformer can be set using `table.options.transform_rows` configuration value and pass a service. It can also be set by using the `Table::setTransformRows()` method, to be used inside a table configurator.

### id_extractor

#### type: `IdExtractorInterface | null` default: `null`

The id extractor handles retrieving the unique id for a row. The default id extractor is `null` which means that the ids will be automatically 0-indexed. We provide multiple implementations for the interface: `CallbackIdExtractor`, `PropertyPathIdExtractor` and `ExpressionIdExtractor`.

The default global id extractor can be set using `table.options.id_extractor` configuration value and pass a service. It can also be set by using the `Table::setIdExtractor()` method, to be used inside a table configurator.

### transform_batch

#### type: `TransformBatchInterface | null` default: `null`

The batch transformer handles the postprocessing of rows before the response is built. This is called after the id extractor and allows to modify the rows. We provide an implementation for the interface in `CallbackTransformBatch`.

The default global batch transformer can be set using `table.options.transform_batch` configuration value and pass a service. It can also be set by using the `Table::setTransformBatch()` method, to be used inside a table configurator.

### batch_size

#### type: `int` default `100`

The batch size specifies the maximum number of rows to process for column values in one batch. This is used to optimize the generation of column values so any used resources can be freed during the building of table response.

The default global batch size can be set using `table.options.batch_size` configuration value. It can also be set by using the `Table::setBatchSize()` method, to be used inside a table configurator.
