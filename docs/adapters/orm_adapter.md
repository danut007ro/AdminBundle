# ORMAdapter

This adapter provides integration with Doctrine ORM. Assume a simple `Employee` table with some basic fields and a ManyToOne relationship to `Company` for these examples. The `$tableFactory` is an instance of [TableFactory](../../src/Table/TableFactory.php) injected as dependency from Symfony container.

This example will list all entries from `Employee` entity:

```php
use DG\AdminBundle\Adapter\ORMAdapter;

$table = $tableFactory->createTable()
    ->addColumn('firstName', TextColumn::class)
    ->addColumn('lastName', TextColumn::class)
    ->setAdapter(ORMAdapter::class, [
        'entity' => Employee::class,
    ])
;
```

You will notice when running the above example that ordering the columns doesn't appear. This happens because the bundle tries to get out of your way and give you full control. Because of this, no automatic joins will be made either, so a full example that also displays company name (by avoiding making a query for each employee) is as follows:

```php
use DG\AdminBundle\Adapter\ORMAdapter;
use DG\AdminBundle\Column\ValueExtractor\PropertyPathValueExtractor;
use DG\AdminBundle\Table\TableRequest;

$table = $tableFactory->createTable()
    ->addColumn('firstName', TextColumn::class, ['sortable' => true])
    ->addColumn('lastName', TextColumn::class, ['sortable' => true])
    ->addColumn('company', TextColumn::class, ['value_extractor' => new PropertyPathValueExtractor('company.name'), 'sortable' => true])
    ->setAdapter(ORMAdapter::class, [
        'entity' => Employee::class,
        'query_builder' => static fn (TableRequest $request, QueryBuilder $qb) => $qb
            ->join('entity.company', 'company')
            ->addSelect('company')
        ,
        'order_by' => [
            'firstName' => 'entity.firstName',
            'lastName' => 'entity.lastName',
            'company' => 'company.name',
        ],
    ])
;
```

Please note that in the example above the `entity` is used as root alias (Employee). This is the default and it can be configured with `entity_alias` option.

## Options

The `ORMAdapter` accepts the following options:

### entity `string | null (default)`

When given a `string` it must be the FQCN of the main entity that the table is showing. This will create the QueryBuilder with `entity_alias` as alias.

When given `null` then it is ignored and `query_builder` option must be specified instead.

### entity_alias `string`, default: 'entity'

This option is used only when `entity` option is set and it will be the aliased name for root entity. 

### query_builder `callable(TableRequest, ?QueryBuilder):void|QueryBuilder | Doctrine\ORM\QueryBuilder | null (default)`

If a `callable` is set, it will be used to either:

- if no `entity` option is set, then the callable will receive the `TableRequest` as parameter and must return the `QueryBuilder` that will be used further
- if `entity` option is set, then the QueryBuilder is already created and the callable will also receive the `TableRequest` but the second argument will be the `QueryBuilder` object which can be modified

### query `callable | null (default)`

This callback will be called after retrieving `Query` from `QueryBuilder`. It can be used to set the cache. The parameters for callback are: `Query`, `TableRequest`, `QueryBuilder`.

### order_by `string[]`, default: []

This option sets the mapping between displayed columns and queried columns. It can be used to sort by a column from joined table.

### search_columns `string[] | callable(QueryBuilder $qb, string $search)`, default: []

This option specifies what columns should be used for global searching. The query is `LIKE search%`.

### batch_column_id `string | null`, default: 'entity.id'

This option specifies the column name to be used as identifier when processing a batch request (adding SQL `IN` clause). Use `null` to disable the processing of batch request, and do custom processing in `query_builder` if wanted.

### batch_column_id_as_uuid `bool`, default: false

This option works with `batch_column_id` option and specifies if the ids should be converted to Symfony `Uuid` prior adding the SQL `IN` clause. 

### iterate `bool`, default: false

Specify if should retrieve results using `AbstractQuery::toIterable()` method.

### hydration_mode `int`, default: `AbstractQuery::HYDRATE_OBJECT`

Specify the hydration mode for query.

### count_total `int | bool | callable(TableRequest, QueryBuilder):?int`, default: true

This option specifies how the total count should be calculated. If `FALSE` then the count won't be calculated at all.

The default count mechanism uses [Doctrine Paginator](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/tutorials/pagination.html) together with `count_fetch_join_collection`, `count_output_walker` and `count_distinct` options.

### count_filtered `int | bool | callable(TableRequest, QueryBuilder, FormInterface $filter, ?int $totalCount):?int`, default: true

This option specifies how the filtered count (count after applying filters) should be calculated. If `FALSE` then the filtered count won't be calculated at all.

The default count mechanism uses [Doctrine Paginator](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/tutorials/pagination.html) together with `count_fetch_join_collection`, `count_output_walker` and `count_distinct` options.

### count_distinct `bool`, default: false

Specify if should apply `DISTINCT` clause when counting (total and filtered) using the default [Doctrine Paginator](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/tutorials/pagination.html).

### count_output_walker `bool`, default: true

Specify if should use output walkers when counting (total and filtered) using the default [Doctrine Paginator](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/tutorials/pagination.html).

### count_fetch_join_collection `bool`, default: false

Specify if should fetch join collection when counting (total and filtered) using the default [Doctrine Paginator](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/tutorials/pagination.html).

### force_apply_filters `bool`, default: false

Specify if should apply filters even if no request is submitted with filters.
