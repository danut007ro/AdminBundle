# UI actions

UI actions are actions that happen on javascript frontend. An action on an element can be set using `dg_admin_uiaction()` Twig function:

```twig
<a href="/employee/create" {{ dg_admin_uiaction(constant('DG\\AdminBundle\\UIAction\\AjaxUIAction::NAME')) }}>Create employee</a>
```

- [ExpandTableRowUIAction](#expandtablerowuiaction)
- [AjaxUIAction](#ajaxuiaction)
- [AjaxDialogUIAction](#ajaxdialoguiaction)

## ExpandTableRowUIAction

This UI action is used for [expanding a row](table/expand_row.md).

| Option                  | Type                      | Description                                                                                               |
|-------------------------|---------------------------|-----------------------------------------------------------------------------------------------------------|
| url                     | `string`                  | Url to make ajax request. If left empty, will use `href` from html element, or current `window.location`. |
| url_parameters          | `array`                   | Parameters to be passed to `fetch()` javascript function.                                                 |
| disable_auto            | `bool` (default: `false`) | Disable automatic handling of action.                                                                     |
| add_table_request_table | `bool` (default: `false`) | Name of table to add request for. If `TRUE` then add closest table. If string, then find table by name.   |
| add_table_request_var   | `string`                  | Name of variable on which to add table request.                                                           |

## AjaxUIAction

This UI action extends `ExpandTableRowUIAction` so it inherits all its options. Additionally, it exposes the following option:

| Option        | Type               | Description                                                                                                       |
|---------------|--------------------|-------------------------------------------------------------------------------------------------------------------|
| refresh_table | `bool` \| `string` | Name of table to refresh. If `TRUE` then refresh all tables on page. If empty string, then refresh closest table. |

## AjaxDialogUIAction

This UI action extends `AjaxUIAction` so it inherits all its options. Additionally, it exposes the following options:

| Option           | Type                         | Description                                                                                                                           |
|------------------|------------------------------|---------------------------------------------------------------------------------------------------------------------------------------|
| restore_url      | `string` \| `null` (default) | Specify if url should be set to the one specified in `href`. Use `NULL` to not change url. Empty string will change back to last url. |
| form_selector    | `string` (default: `form`)   | Specify form selector to process from dialog.                                                                                         |
| append_body_data | `bool` (default: `false`)    | Specify if should append `url_parameters > body` data to form.                                                                        |
