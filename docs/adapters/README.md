# Adapters

Adapters are the core elements bridging Table functionality to their underlying data source. Popular implementations for common data sources are provided, and more are welcomed.

An adapter is called by the bundle when a request for data is received, including search and sorting criteria, and returns a result set with metadata on record counts.

To further provide simplified integration for `CRUD` operations through [ControllerHelper](../../src/ControllerHelper.php), the bundle has an [CRUDAdapterInterface](../../src/Adapter/CRUDAdapterInterface.php) and its implementation [AbstractDoctrineORMCRUDAdapter](#abstractdoctrineormcrudadapter) which integrates with the `ORMAdapter`.

Ready-made adapters are supplied for easy integration with various data sources:

- [ORMAdapter](#ormadapter)
    - [AbstractDoctrineORMCRUDAdapter](#abstractdoctrineormcrudadapter)
- [DataResultAdapter](#dataresultadapter)
- [CallbackAdapter](#callbackadapter)

## ORMAdapter

This adapter is the most commonly used because it provides integration with Doctrine entities. Check out the [documentation](orm_adapter.md) for all the options this adapter provides.

### AbstractDoctrineORMCRUDAdapter

This adapter is a default implementation of [CRUDAdapterInterface](../../src/Adapter/CRUDAdapterInterface.php) which can be used together with an ORMAdapter to provide easy `CRUD` operations through [ControllerHelper](../../src/ControllerHelper.php). You can define a `CRUD` adapter for a given `Employee` entity like so:

```php
use DG\AdminBundle\Adapter\AbstractDoctrineORMCRUDAdapter;
use DG\AdminBundle\Adapter\ORMAdapter;
use DG\AdminBundle\DependencyInjection\Instantiator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends AbstractDoctrineORMCRUDAdapter<Employee>
 */
class EmployeeAdapter extends AbstractDoctrineORMCRUDAdapter
{
    public function __construct(ManagerRegistry $managerRegistry, Instantiator $instantiator)
    {
        parent::__construct(
            Employee::class,
            $managerRegistry,
            $instantiator->getAdapter(ORMAdapter::class, ['entity' => Employee::class]),
        );
    }
}

```

## DataResultAdapter

This is a simpler adapter that can be used to provide some already calculated data:

```php
$data = [
    ['name' => 'John Doe', 'position' => 'Accountant'],
    ['name' => 'Jane Doe', 'position' => 'Manager'],
];

$adapter = new DataResultAdapter(new ArrayDataResult($data, count($data), count($data)));
```

## CallbackAdapter

This is a simple adapter that can be used to provide data from a callback function. The callback has the following signature: `(TableRequest, ?FormInterface): DataResultInterface`. 
