# Table filters

Table filters are Symfony forms that can be used to filter table data. Note that currently only the [DatatableFormatter](../formatters.md#datatableformatter) together with [ORMAdapter](../adapters/orm_adapter.md) supports filters. The filters are applied to a QueryBuilder using [lexik/form-filter-bundle](https://github.com/lexik/LexikFormFilterBundle).

If you define custom adapters, it's your job to apply the form filters on your data. The filter is passed to `Adapter::list()` method, so it can be checked using the following code inside your adapter:

```php
if (null !== $filter && $filter->isSubmitted() && $filter->isValid()) {
    // Add filter conditions.
}
```
