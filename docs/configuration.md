# Configuration

Configuration in `config/packages/dg_admin.yaml` is defined by default as follows:

```yaml
dg_admin:
    # Default formatter for tables. Change this to DatatableFormatter if needed
    default_formatter: DG\AdminBundle\Formatter\InlineFormatter
    
    # Configuration for Table
    table:
        # Default global options for Table
        options: []
                
    # Date ranges to show when filtering (see below)
    date_ranges: []
    
    # Date formats that are recognized and can be rendered in IntlDateTimeColumn (see below)
    date_formats: []
    
    # Configuration for InlineFormatter
    inline_formatter:
        template: '@DGAdmin/formatter/inline_formatter.html.twig'
        template_parameters: []
    
    # Configuration for DatatableFormatter
    datatable_formatter:
        # Template for container that includes filter/batch actions, which will include the table HTML
        template: '@DGAdmin/formatter/formatter_ajax.html.twig'
        template_parameters: []
        table_template: '@DGAdmin/formatter/datatable_table.html.twig'
        table_template_parameters: []
        # HTTP method to make requests (GET or POST)
        method: POST
        # Default global options for DataTables (https://datatables.net/reference/option/)
        options:
            serverSide: true
            processing: true
            paging: true
            pagingType: full_numbers
            lengthChange: true
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, '']] # Use '' as name to specify translated entry for 'All'
            pageLength: 10
            searching: false
            info: true
```

### default_formatter

This is the default formatter to be used by `TableFactory::createDefaultFormatter()` method.

### date_ranges

Date ranges represent the intervals that can be selected by default when using [DateRangePickerType](../src/Form/Type/DateRangePickerType.php). The `start` and `end` intervals are expressed as operations applied to a [momentjs](https://momentjs.com/docs/#/manipulating/) object. For instance, for `yesterday` entry defined below, the momentjs is used as `moment().substract(1, 'days')`.

Every entry can be translated using `date_ranges.[key]` from `dg_admin` domain for every key that is defined. The ranges are automatically added to translation by a [translation extractor](https://symfony.com/doc/current/translation.html#extracting-translation-contents-and-updating-catalogs-automatically). The entries are exposed by [DateFormats::getDateFormat()](../src/DateFormat/DateFormats.php).

```yaml
    date_ranges:
        today:
            start: startOf('day')
            end: endOf('day')
        yesterday:
            start: subtract(1, 'days').startOf('day')
            end: subtract(1, 'days').endOf('day')
        last_7_days:
            start: subtract(6, 'days').startOf('day')
            end: endOf('day')
        last_30_days:
            start: subtract(29, 'days').startOf('day')
            end: endOf('day')
        this_month:
            start: startOf('month').startOf('day')
            end: endOf('month').endOf('day')
        last_month:
            start: subtract(1, 'month').startOf('month').startOf('day')
            end: subtract(1, 'month').endOf('month').endOf('day')
```

### date_formats

Date formats define the entries that are accepted by [IntlDateTimeColumn](../src/Column/IntlDateTimeColumn.php) and [DateRangePickerType](../src/Form/Type/DateRangePickerType.php). These formats allow for easy display of localized `DateTime` values. The data is exposed in [DateFormats::getDateFormat()](../src/DateFormat/DateFormats.php).

This entry only defines the allowed format names. The formats are automatically added to translation by a [translation extractor](https://symfony.com/doc/current/translation.html#extracting-translation-contents-and-updating-catalogs-automatically). To define actual formats you should define the following translation keys on `dg_admin` domain:

```
date_format.[name] - format for PHP's DateTime object (https://www.php.net/manual/en/datetime.format.php)
date_format.[name].moment - format for momentjs.format (https://momentjs.com/docs/#/displaying/format/)
date_format.[name].time - 12/24/<empty> - specify the time format. If left empty, then no time will be shown for this format
```

For example, for the `date_formats` given below, you can use the following translations:

```yaml
    date_formats: [ date, date_short, date_long, date_time, date_time_short, date_time_long ]
```

```
date_format.date: MM/dd/YYYY
date_format.date.moment: MM/DD/YYYY
date_format.date.time:
date_format.date_short: MMM d, YYYY
date_format.date_short.moment: MMM D, YYYY
date_format.date_short.time:
date_format.date_long: MMMM d, YYYY
date_format.date_long.moment: MMMM D, YYYY
date_format.date_long.time:
date_format.date_time: MM/dd/YYYY h:mm a
date_format.date_time.moment: MM/DD/YYYY h:mm A
date_format.date_time.time: 12
date_format.date_time_short: MMMM d, YYYY h:mm a
date_format.date_time_short.moment: MMMM D, YYYY h:mm A
date_format.date_time_short.time: 12
date_format.date_time_long: EEEE, MMMM d, YYYY h:mm a
date_format.date_time_long.moment: dddd, MMMM D, YYYY h:mm A
date_format.date_time_long.time: 12
```
