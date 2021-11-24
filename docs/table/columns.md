# Columns

Columns implement the transformations required to convert raw data into output ready to be rendered by a [Formatter](../formatters.md).

The bundle provides multiple column types, and you can add your custom column types. All columns have the default following options:

| Option   | Type                                                       | Description                                                                                      |
|----------|------------------------------------------------------------|--------------------------------------------------------------------------------------------------|
| name     | `string` (required)                                        | Name of column. Unique among columns in current table.                                           |
| label    | `string` \| `null` (default)                               | Label to be displayed as column name. If `NULL` is given, then it will default to `name` option. |
| priority | `int` (default: `0`)                                       | Priority for calculating column value. Higher takes priority.                                    |
| sortable | `bool` (default: `false`)                                  | Specify if this column is sortable.                                                              |
| visible  | `bool` (default: `true`)                                   | Specify if this column is visible.                                                               |
| render   | `callable($value, $row, $originalRow)` \| `null` (default) | Custom callable for rendering column. Will override default column rendering.                    |

- [Generic columns](#generic-columns)
    - [TextColumn](#textcolumn)
    - [IntlNumberColumn](#intlnumbercolumn)
    - [IntlDateTimeColumn](#intldatetimecolumn)
    - [TwigColumn](#twigcolumn)
    - [TwigBlockColumn](#twigblockcolumn)
    - [TwigStringColumn](#twigstringcolumn)
- [Special columns](#special-columns)
    - [BatchColumn](#batchcolumn)
    - [ActionColumn](#actioncolumn)
        - [Cookbook for ActionColumn](cookbook_for_actioncolumn.md)
    - [DataloaderColumn](#dataloadercolumn)
- [Cookbook](#cookbook)
    - [Creating your own column type](#creating-your-own-column-type)
        - [Non-reusable column type](#non-reusable-column-type)
    - [Showing a different set of columns when exporting](#showing-a-different-set-of-columns-when-exporting)
    - [Showing different content for a column when exporting](#showing-different-content-for-a-column-when-exporting)

## Generic columns

These are more generic columns that can be used in your application. They define an additional option `value_extractor` which specifies how to handle the extraction of value from a row. We provide multiple implementations for ValueExtractorInterface: `PropertyPathValueExtractor`, `ExpressionValueExtractor` and `CallbackValueExtractor`. If `value_extractor` option is `NULL` then the `name` option is used with `PropertyPathValueExtractor`.

### TextColumn

Text columns are the most frequently used column type, as they can be used to display any kind of data that is eventually rendered as plain text.

| Option          | Type                                          | Description                                                                                                            |
|-----------------|-----------------------------------------------|------------------------------------------------------------------------------------------------------------------------|
| raw             | `bool` (default: `false`)                     | Do not escape cell content to be safe for use in HTML.                                                                 |
| value_extractor | `ValueExtractorInterface` \| `null` (default) | How to calculate column value. If `NULL` is given, then `name` option will be used as property path to retrieve value. |

### IntlNumberColumn

This column type converts a number to display using the [NumberToLocalizedStringTransformer](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Form/Extension/Core/DataTransformer/NumberToLocalizedStringTransformer.php) and current locale. The following options are passed as constructor parameters.

| Option          | Type                                              | Description                                                                                                            |
|-----------------|---------------------------------------------------|------------------------------------------------------------------------------------------------------------------------|
| scale           | `int` \| `null` (default)                         | Argument for `NumberToLocalizedStringTransformer` constructor.                                                         |
| grouping        | `bool` \| `null` (default)                        | Argument for `NumberToLocalizedStringTransformer` constructor.                                                         |
| rounding_mode   | `int` (default: `\NumberFormatter::ROUND_HALFUP`) | Argument for `NumberToLocalizedStringTransformer` constructor.                                                         |
| value_extractor | `ValueExtractorInterface` \| `null` (default)     | How to calculate column value. If `NULL` is given, then `name` option will be used as property path to retrieve value. |

### IntlDateTimeColumn

Converts any `DateTimeInterface` to a string using the defined [date_formats](../configuration.md#date_formats) and [DateTimeToLocalizedStringTransformer](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Form/Extension/Core/DataTransformer/DateTimeToLocalizedStringTransformer.php).

| Option          | Type                                          | Description                                                                                                            |
|-----------------|-----------------------------------------------|------------------------------------------------------------------------------------------------------------------------|
| format          | `string` (required)                           | Format name to use for converting.                                                                                     |
| value_extractor | `ValueExtractorInterface` \| `null` (default) | How to calculate column value. If `NULL` is given, then `name` option will be used as property path to retrieve value. |

### TwigColumn

Allows to render a Twig template as column. The template will receive `value` and `row` parameters besides the ones passed by user.

| Option              | Type                                          | Description                                                                                                            |
|---------------------|-----------------------------------------------|------------------------------------------------------------------------------------------------------------------------|
| template            | `string` (required)                           | Twig file template to be used for rendering.                                                                           |
| template_parameters | `array`                                       | Parameters to pass when rendering template.                                                                            |
| value_extractor     | `ValueExtractorInterface` \| `null` (default) | How to calculate column value. If `NULL` is given, then `name` option will be used as property path to retrieve value. |

### TwigBlockColumn

This column extends `TwigColumn` and is used to render a specific block from a Twig template.

| Option          | Type                                          | Description                                                                                                            |
|-----------------|-----------------------------------------------|------------------------------------------------------------------------------------------------------------------------|
| block           | `string` (required)                           | Block name from Twig template to be used for rendering.                                                                |
| value_extractor | `ValueExtractorInterface` \| `null` (default) | How to calculate column value. If `NULL` is given, then `name` option will be used as property path to retrieve value. |

### TwigStringColumn

Allows to parse a string as a Twig template. The template will receive `value` and `row` parameters besides the ones passed by user.

| Option              | Type                                          | Description                                                                                                            |
|---------------------|-----------------------------------------------|------------------------------------------------------------------------------------------------------------------------|
| template            | `string` (required)                           | String to be used as Twig template for rendering.                                                                      |
| template_parameters | `array`                                       | Parameters to pass when rendering template.                                                                            |
| value_extractor     | `ValueExtractorInterface` \| `null` (default) | How to calculate column value. If `NULL` is given, then `name` option will be used as property path to retrieve value. |

> Please note that this column is using the [template_from_string](https://twig.symfony.com/doc/3.x/functions/template_from_string.html) Twig function which isn't available by default. You can enable it by adding `Twig\Extension\StringLoaderExtension: ~` to your `services.yaml` file.

## Special columns

These are more specialized columns that can be used in your application.

### BatchColumn

This column is used to display checkboxes in order to select rows and use them in batch actions. Please note that the `sortable` option doesn't apply on this column. It defaults to `false`.

A Table can contain only one BatchColumn. Adding this column will also render some options for selecting all, none, or all entries from page. To find out more about batch actions, for instance on how to export table data or create custom batch actions, you can check out the [batch actions documentation](batch_actions.md).

### ActionColumn

The action column allows to add some buttons for each row with [ui actions](../ui_actions.md). There are ui actions for making an ajax request, displaying a confirmation dialog and displaying a dialog with a form which can be submitted. Please note that the `sortable` option doesn't apply on this column. It defaults to `false`.

To find out more about `ActionColumn`, for instance on how to create buttons for `create`, `read`, `update` or `delete` for a table row, you can check out the [cookbook for ActionColumn](cookbook_for_actioncolumn.md).

| Option  | Type                                              | Description                             |
|---------|---------------------------------------------------|-----------------------------------------|
| actions | `array` \| `callable($row):array` (default: `[]`) | Custom callable for generating actions. |

Each action supports the following options:

| Option     | Type                                    | Description                                                                                                 |
|------------|-----------------------------------------|-------------------------------------------------------------------------------------------------------------|
| url        | `string` (default: `'#'`)               | The `href` html attribute for button.                                                                       |
| attr       | `array`                                 | The attributes for button. If no `class` is specified then it defaults to `btn btn-sm btn-outline-primary`. |
| icon       | `string`                                | Icon class for `<i>` element inside button.                                                                 |
| text       | `string`                                | Text to display on button.                                                                                  |
| ui_action  | `UIActionInterface` \| `null` (default) | The [ui action](../ui_actions.md) to set on button.                                                         |
| is_granted | `string`                                | This permission needs to be granted to show this action.                                                    |

### DataloaderColumn

This column type allows to solve the N+1 problem when dealing with related data using the [overblog/dataloader-php](https://github.com/overblog/dataloader-php) library. Please note that the `sortable` option doesn't apply on this column. It defaults to `false`.

| Option     | Type                                                           | Description                                                  |
|------------|----------------------------------------------------------------|--------------------------------------------------------------|
| dataloader | `DataLoader` \| `callable($value, $row):DataLoader` (required) | Specifies the DataLoader object to use when collecting data. |

## Cookbook

### Creating your own column type

Let's create a custom column that displays a green element for `true` or red element for any other value.

```php
use DG\AdminBundle\Column\TwigColumn;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BoolColumn extends TwigColumn
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        // We specify our default custom template for rendering column.
        $resolver->setDefault('template', 'column/bool.html.twig');
    }
}
```

The content of `column/bool.html.twig` template can be as follows:

```twig
<div class="text-center w-100">
    <span class="badge badge-pill badge-{{ value is same as(true) ? 'success' : 'danger' }}">&nbsp;</span>
</div>
```

#### Non-reusable column type

An alternative way of defining the above column type which isn't reusable is as follows (code to be added when configuring the columns):

```php
$table->addColumn('enabled', TwigColumn::class, ['template' => 'column/bool.html.twig']);
```

### Showing a different set of columns when exporting

To show a different set of columns when exporting you can use the `Table::setColumnOrders()` which allows to specify what columns to display and in which order.

```php
$table->setColumnOrders([
    // List of columns to display and in which order.
]);
```

### Showing different content for a column when exporting

You can show different content when exporting by using the `render` option (for non-reusable columns) or by using another option to specify if column should be rendered differently. Let's update the `BoolColumn` defined above by adding a new option `is_exporting`. Then when adding column you also need to set `is_exporting` option accordingly.

```php
use DG\AdminBundle\Column\TwigColumn;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BoolColumn extends TwigColumn
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'template' => 'column/bool.html.twig',
                'is_exporting' => false,
            ])
            ->setAllowedTypes('is_exporting', 'bool')
        ;
    }
    
    protected function doRender(mixed $value, mixed $row, mixed $originalRow): string
    {
        // If exporting, render a different content.
        if ($this->options['is_exporting']) {
            return true === $value ? 'TRUE' : 'FALSE';
        }

        return parent::doRender($value, $row, $originalRow);
    }
}
```
