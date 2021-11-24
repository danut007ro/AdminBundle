# Expand a row

Expanding a row allows to have a subtable inside it or some custom content.

In order to expand a row you need to use a special action inside an [ActionColumn](columns.md#actioncolumn). This will expand the column and call the `url` to load content to be shown:

```php
use DG\AdminBundle\Column\ActionColumn;
use DG\AdminBundle\UIAction\ExpandTableRowUIAction;

$table->addColumn('expand', ActionColumn::class, [
    'actions' => fn (Company $company): array => [
        [
            'url' => "/company/{$company->getId()}/employees", # Url to load content from (better to use Router)
            'icon' => 'fas fa-plus',
            'ui_action' => new ExpandTableRowUIAction(),
        ],
    ],
]);
```

If you want to show some custom content then just return a Symfony Response.

The better use-case is when you want to show another table. The key is that in your controller the TableHelper needs to **handle a single Formatter**, not an array of formatters:

```php
use DG\AdminBundle\Adapter\ORMAdapter;
use DG\AdminBundle\Column\TextColumn;
use DG\AdminBundle\TableHelper;

public function companyEmployees(Request $request, TableHelper $tableHelper, Company $company)
{
    $table = $tableHelper->getTableFactory()->createTable()
        ->addColumn('firstName', TextColumn::class)
        ->addColumn('lastName', TextColumn::class)
        ->setAdapter(ORMAdapter::class, [
            'entity' => Employee::class,
            'query_builder' => static fn (TableRequest $request, QueryBuilder $qb) => $qb
                ->andWhere('entity.company=:company')
                ->setParameter('company', $company)
            ,
        ])
    ;

    $result = $tableHelper->handleRequest($request, $tableHelper->createDefaultFormatter($table));

    // We are handling a single formatter, so we are sure we have a response.
    return $result->getResponse();
}
```
