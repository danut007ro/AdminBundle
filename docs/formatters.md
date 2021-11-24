# Formatters for Table

The formatter handles parsing a Symfony Request object, determining if it's a valid request for a DataTable and building the response. You can set the default formatter using `dg_admin.default_formatter` [configuration option](configuration.md). All formatters support the following options:

| Option              | Type                                          | Description                                                                                  |
|---------------------|-----------------------------------------------|----------------------------------------------------------------------------------------------|
| table               | `Table` \| `TableReference` (required)        | Table to be formatted.                                                                       |
| table_name          | `string`                                      | Name to use as internal table name. Must be unique in group.                                 |
| table_request       | `TableRequest`                                | Default TableRequest to use.                                                                 |
| method              | `string` (`GET` \| `POST`) (default: `POST`)  | HTTP method to use when making requests.                                                     |
| url                 | `string`                                      | Url to use for requests. If empty string, then current url will be used (retrieved with js). |
| csrf_token_id       | `string` (default: `_dg_admin_csrf_token_id`) | CSRF token id for validating requests.                                                       |
| template            | `string` (required)                           | Twig template to use when rendering formatter.                                               |
| template_parameters | `array`                                       | Parameters to be passed to Twig template when rendering the formatter template.              |

- [InlineFormatter](#inlineformatter)
- [DatatableFormatter](#datatableformatter)

## InlineFormatter

The inline formatter will render an inline table. The template used by default is `@DGAdmin/formatter/inline_formatter.html.twig`. This template can also be defined globally using the `dg_admin.inline_formatter.template` [configuration option](configuration.md).

## DatatableFormatter

The datatable formatter will render a datatable. The template used by default is `@DGAdmin/formatter/formatter_ajax.html.twig`. This template can also be defined globally using the `dg_admin.datatable_formatter.template` [configuration option](configuration.md).

The `options` can also be set by using the `dg_admin.datatable_formatter.options` configuration option.

| Option                    | Type                                                               | Description                                                                                                                                                                     |
|---------------------------|--------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| table_name_url            | `bool` \| `string` (default: `true`)                               | Name to use for extracting table request from url. Use `FALSE` to disable request parsing and `TRUE` to use `table_name` option as value. Use "" to parse request with no name. |
| table_template            | `string` (default: `@DGAdmin/formatter/datatable_table.html.twig`) | Twig template to use when rendering table.                                                                                                                                      |
| table_template_parameters | `array`                                                            | Parameters to be passed to Twig template when rendering table.                                                                                                                  |
| options                   | `array`                                                            | Options to be passed when initializing Datatables js library.                                                                                                                   |
