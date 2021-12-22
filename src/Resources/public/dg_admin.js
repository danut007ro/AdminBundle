const $ = jQuery = require('jquery')
const moment = require('moment-timezone')
const Swal = require('admin-lte/plugins/sweetalert2/sweetalert2')
require('jquery-form')
require('conditionize2')
require('select2/src/js/jquery.select2')
require('admin-lte/plugins/bootstrap/js/bootstrap.bundle')
require('admin-lte/plugins/daterangepicker/daterangepicker')
require('admin-lte/plugins/datatables/jquery.dataTables')
require('admin-lte/plugins/datatables-bs4/js/dataTables.bootstrap4')
require('admin-lte/plugins/datatables-fixedheader/js/dataTables.fixedHeader')
require('admin-lte/plugins/datatables-fixedheader/js/fixedHeader.bootstrap4')
require('admin-lte/build/js/AdminLTE')

{
  // Custom conditionize.actions.show to show parent if has .form-group class.
  conditionizeActionShow = $.fn.conditionize.actions.show;
  $.fn.conditionize.actions.show = $section => {
    let $parent = $section.parent();
    if ($parent && !$parent.hasClass('form-group')) {
      $parent = $parent.parent();
    }
    conditionizeActionShow($parent && $parent.hasClass('form-group') ? $parent : $section);
  }

  // Custom conditionize.actions.hide to hide parent if has .form-group class.
  conditionizeActionHide = $.fn.conditionize.actions.hide;
  $.fn.conditionize.actions.hide = $section => {
    let $parent = $section.parent();
    if ($parent && !$parent.hasClass('form-group')) {
      $parent = $parent.parent();
    }
    conditionizeActionHide($parent && $parent.hasClass('form-group') ? $parent : $section);
  }

  let _loading = false;

  class Admin {
    ready(callback) {
      if (document.readyState === 'complete') {
        callback();
      } else {
        document.addEventListener('DOMContentLoaded', callback);
      }

      return this;
    }

    // Initialize element with admin.
    // Will search for child elements that need to have admin initialized on them (select2, datepicker, tooltips, table, ui actions).
    init(el) {
      el.querySelectorAll('.dg-admin-select2').forEach(select => initializeSelect2(select));
      el.querySelectorAll('[data-dg-admin-datepicker]').forEach(datePicker => initializeDatePicker(datePicker));
      el.querySelectorAll('[data-toggle="tooltip"]').forEach(el => $(el).tooltip({trigger: 'hover', boundary: 'window'}));
      el.querySelectorAll('[data-dg-admin-table]').forEach(table => initializeTable(table));
      el.querySelectorAll('[data-dg-admin-uiaction]').forEach(el => !el.dataset['dgAdminUiactionDisableAuto'] && initializeUIAction(el));
      el.querySelectorAll('[data-condition]').forEach(el => el.dataset['dgAdminConditionize'] && $(el).conditionize(JSON.parse(el.dataset['dgAdminConditionize'])));
      dispatchEvent('init', el);

      return this;
    }

    // Refresh all tables on current page.
    // This applies mainly to ajax tables.
    refreshAllTables() {
      document.querySelectorAll('[data-dg-admin-table]').forEach(table => {
        if (!table._dgAdminTable) {
          return;
        }

        table._dgAdminTable.refresh();
      });

      return this;
    }

    // Get Table object for element.
    findTableByElement(el) {
      if (!el) {
        return null;
      }

      const table = el.closest('[data-dg-admin-table]');
      if (table === null || !table['_dgAdminTable']) {
        return null;
      }

      return table['_dgAdminTable'];
    }

    // Get Table object by name.
    findTableByName(name) {
      let table = null;
      document.querySelectorAll('[data-dg-admin-table]').forEach(tableEl => {
        if (table !== null || !tableEl._dgAdminTable) {
          return;
        }

        if (tableEl._dgAdminTable.config.formatter.name === name) {
          table = tableEl._dgAdminTable;
        }
      });

      return table;
    }

    // Add callback for event.
    onEvent(name, callback) {
      if (!onEvents.hasOwnProperty(name)) {
        onEvents[name] = [];
      }

      onEvents[name].push(callback);

      return this;
    }

    // Load a remote ajax content and show it as dialog.
    ajaxDialog(el = null, parameters = {}, contentCallback = null) {
      return new Promise(fulfill => {
        // Allow only one _loading at a time.
        if (_loading) {
          return;
        }
        _loading = true;

        const ajaxDialogForm = dialog => {
          let formSelector = parameters.hasOwnProperty('form_selector') ? parameters['form_selector'] : 'form';
          const form = dialog.querySelector(formSelector);
          if (!form) {
            // No form to process.
            refreshTable(el, parameters['refresh_table']);
            fulfill(dialog);

            return;
          }

          $(form).ajaxForm({
            headers: {'X-DGAdmin-Submit': 1}, // Custom header to know that we submitted the form.
            beforeSubmit: data => {
              // Append data from url_parameters>body.
              if (parameters['append_body_data']) {
                for (let [key, value] of convertFormData(parameters['url_parameters']['body'] || {}).entries()) {
                  data.push({name: key, value: value});
                }
              }

              // Add table request.
              if (parameters['add_table_request_table'] !== false) {
                const table = parameters['add_table_request_table'] === true ? window.DGAdmin.findTableByElement(el) : window.DGAdmin.findTableByName(parameters['add_table_filters_table']);
                if (table) {
                  const field = parameters['add_table_request_var'];
                  for (let [key, value] of convertFormData(getTableParameters(table, false)).entries()) {
                    data.push({name: nameUrl(field, key), value: value});
                  }

                  // Add batch data.
                  if (table.filter) {
                    if (table.filter.querySelector('[data-dg-admin-table-batch-mode]')) {
                      data.push({name: nameUrl(field, 'all'), value: table.batchMode === 'all' ? 1 : 0});
                      table.batchIds.forEach((value, index) => data.push({
                        name: nameUrl(field, 'ids', index),
                        value: value
                      }));
                    } else {
                      data.push({name: nameUrl(field, 'all'), value: 1});
                    }
                  }
                }
              }

              for (let [key, value] of data.entries()) {
                let el = form.querySelector('[name="' + CSS.escape(value['name']) + '"]');
                if (!el || !el.dataset['dgAdminDatepicker']) {
                  continue;
                }

                value.value = getDatePickerValue(el);
              }

              // Disable submit button. It will be "enabled" (overwritten) with ajax response.
              $(form).find('button[type="submit"]').prop('disabled', true);
            },
            success: (e, status, response) => {
              if (response.responseText === '' || response.getResponseHeader('X-Dialog-Close')) {
                if (dialog.dataset['dgAdminRestoreUrl']) {
                  // Restore url here, because it might be used by table refresh.
                  window.history.replaceState(null, '', dialog.dataset['dgAdminRestoreUrl']);
                }

                refreshTable(el, parameters['refresh_table']);
                fulfill(dialog);

                // Delete restore url, because it's already restored.
                delete dialog.dataset['dgAdminRestoreUrl'];

                $(dialog).modal('hide');
              }
            },
            error: e => {
              // In case of error, submit button won't be "enabled" anymore, so we enable it now.
              $(form).find('button[type="submit"]').prop('disabled', false);

              const newContent = new DOMParser().parseFromString(e.responseText, 'text/html').body.querySelector('.modal-dialog');
              const currentContent = dialog.querySelector('.modal-dialog');
              if (newContent && currentContent) {
                // Hide all tooltips on dialog.
                dialog.querySelectorAll('[data-toggle="tooltip"]').forEach(tooltip => $(tooltip).tooltip('hide'));

                // Replace content of dialog.
                currentContent.replaceWith(newContent);

                this.init(dialog);
                if (contentCallback) {
                  contentCallback(dialog);
                }

                dispatchEvent('ajaxDialog', {
                  el: el,
                  parameters: parameters,
                  dialog: dialog,
                });

                ajaxDialogForm(dialog);
              }
            },
          });
        };

        window.DGAdmin.fetchUIAction(el, parameters)
          .then(response => {
            // Clear loading state.
            _loading = false;
            if (response.ok) {
              return response.text();
            }
          })
          .then(text => {
            if (!text) {
              if (typeof text !== 'undefined') {
                fulfill(text);
              }

              return;
            }

            const dialog = new DOMParser().parseFromString(text.toString(), 'text/html').body.firstChild;
            if (!dialog instanceof HTMLElement) {
              return;
            }

            document.body.appendChild(dialog);
            this.init(dialog);

            $(dialog)
              .on('hidden.bs.modal', () => {
                if (dialog.dataset['dgAdminRestoreUrl']) {
                  window.history.replaceState(null, '', dialog.dataset['dgAdminRestoreUrl']);
                }

                document.body.removeChild(dialog);
              })
              .modal('show');

            if (parameters['restore_url'] !== undefined && parameters['restore_url'] !== null) {
              dialog.dataset['dgAdminRestoreUrl'] = parameters['restore_url'] || (window.location.pathname + window.location.search);
              window.history.replaceState(null, '', parameters['url']);
            }

            if (contentCallback) {
              contentCallback(dialog);
            }

            dispatchEvent('ajaxDialog', {
              el: el,
              parameters: parameters,
              dialog: dialog,
            });

            ajaxDialogForm(dialog);
          });
      });
    }

    // Custom fetch that handles response as SweetAlert and blob file download.
    fetch(url, options) {
      return new Promise(resolve => {
        fetch(url, options)
          .then(response => {
            if (response.headers.get('X-REDIRECT')) {
              window.location = response.headers.get('X-REDIRECT');
            } else if (response.headers.get('content-type').startsWith('application/json')) {
              // Handle json.
              response.clone().json().then(json => {
                if (json['_swal']) {
                  let icon = 'success';
                  if (response.status >= 400 && response.status < 500) {
                    icon = 'warning';
                  } else if (response.status >= 500 && response.status < 600) {
                    icon = 'error';
                  }

                  Swal.fire($.extend(
                    {
                      toast: true,
                      icon: icon,
                      position: 'top-end',
                      showConfirmButton: false,
                      timer: 5000,
                    },
                    json['_swal'],
                  ));
                }
              });
            } else if (response.status >= 500 && response.status < 600) {
              Swal.fire({
                title: document.body._dgAdminInit['errorMessage'],
                toast: true,
                icon: 'error',
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
              });
            } else {
              // Handle blob.
              const disposition = response.headers.get('content-disposition');
              if (response.ok && disposition && disposition.indexOf('attachment') !== -1) {
                let filename = 'download.file';
                const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = filenameRegex.exec(disposition);

                if (matches && matches[1]) {
                  filename = matches[1].replace(/['"]/g, '');
                }

                response.clone().blob().then(blob => {
                  const a = document.createElement('a');
                  const url = window.URL.createObjectURL(blob);
                  a.href = url;
                  a.download = filename;
                  document.body.append(a);
                  a.click();
                  a.remove();
                  window.URL.revokeObjectURL(url);
                });
              }
            }

            resolve(response);
          });
      });
    }

    fetchUIAction(el, params = null) {
      if (params === null) {
        params = JSON.parse(el.dataset['dgAdminUiactionParameters'] || '{}');
      }

      if (!params['url']) {
        if (el instanceof HTMLElement && el.href) {
          params['url'] = el.href;
        } else {
          params['url'] = window.location.pathname + window.location.search
        }
      }
      params['url_parameters'] = params['url_parameters'] || {};

      let formParams = {...params['url_parameters']};
      let headers = formParams['headers'] || {};
      headers['X-Requested-With'] = 'XMLHttpRequest';
      formParams['headers'] = headers;

      if (typeof formParams['body'] === 'object') {
        formParams['body'] = convertFormData(formParams['body']);
      }

      if (params['add_table_request_table'] !== false) {
        const table = params['add_table_request_table'] === true ? window.DGAdmin.findTableByElement(el) : window.DGAdmin.findTableByName(params['add_table_filters_table']);
        if (table && formParams['body'] instanceof FormData) {
          const field = params['add_table_request_var'];
          for (let [key, value] of convertFormData(getTableParameters(table, false)).entries()) {
            formParams['body'].set(nameUrl(field, key), value);
          }

          // Add batch data.
          if (table.filter) {
            if (table.filter.querySelector('[data-dg-admin-table-batch-mode]')) {
              formParams['body'].set(nameUrl(field, 'all'), table.batchMode === 'all' ? 1 : 0);
              table.batchIds.forEach((value, index) => formParams['body'].set(nameUrl(field, 'ids', index), value));
            } else {
              formParams['body'].set(nameUrl(field, 'all'), 1);
            }
          }
        }
      }

      let url = params['url'];

      // For GET, HEAD, OPTIONS, TRACE methods, use url instead of body.
      if (formParams['body'] instanceof FormData && (!formParams['method'] || ['get', 'head', 'options', 'trace'].includes(formParams['method'].toString().toLowerCase()))) {
        const urlParams = new URLSearchParams();
        for (const [key, value] of formParams['body'].entries()) {
          urlParams.set(key, value);
        }

        url += '?' + urlParams.toString();
        delete formParams['body'];
      }

      return window.DGAdmin.fetch(url, formParams);
    }

    // Function to retrieve all parameters this field depends on.
    fieldParams(el) {
      const fieldParams = JSON.parse(el.dataset['dgAdminSelect2FieldParams'] || '{}');
      const dependsParams = {};
      for (let [selector, param] of Object.entries(fieldParams)) {
        if (selector.substring(0, 1) !== '#') {
          selector = '[name="' + CSS.escape(selector) + '"]';
        }

        dependsParams[param] = $.fn.conditionize.getValue(selector);
      }

      return dependsParams;
    }

    // Function to build url to select2 url with parameters.
    select2Url(select2, url) {
      const params = JSON.parse(select2.dataset['dgAdminSelect2FieldParams'] || '{}');
      if (!params) {
        return url;
      }

      const append = {};
      for (let [selector, param] of Object.entries(params)) {
        if (selector.substring(0, 1) !== '#') {
          selector = '[name="' + CSS.escape(selector) + '"]';
        }

        const value = $.fn.conditionize.getValue(selector);
        if (url.indexOf('{' + param + '}') !== -1) {
          if (!value.length) {
            // Required parameter (from url) is missing, return null.
            return null;
          }

          // Replace directly in url.
          url = url.replace('{' + param + '}',value);
        } else if (value.length) {
          // Append to url.
          append[param] = value;
        }
      }

      if (!Object.keys(append).length) {
        return url;
      }

      if (url.indexOf('?') === -1) {
        url += '?';
      }

      const urlSearchParams = new URLSearchParams(url);
      for (let [field, value] of Object.entries(append)) {
        if (Array.isArray(value)) {
          for (let entryValue of Object.values(value)) {
            urlSearchParams.append(field, entryValue.toString());
          }
        } else {
          urlSearchParams.set(field, value.toString());
        }
      }

      return unescape(urlSearchParams.toString());
    }
  }

  class Table {
    // Table HTML element with formatter.
    table;
    // Configuration from data element.
    config;
    // Batch mode. Can be "none" or "all".
    batchMode = 'none';
    // Batch ids.
    batchIds = [];
    constructor(table, config) {
      this.table = table;
      this.config = config;
    }

    // Get parameters to be added to url for table.
    getParameters() {
      return {
        search: '',
        orderBy: [],
        offset: 0,
        limit: 0,
      };
    }

    // Refresh the table.
    refresh() {
      // Clear current selection.
      this.setBatchMode('none');

      return this;
    }

    // Expand a row. If no html is given, then collapse the row.
    // Returns a Promise that resolves to content of new row.
    expand(el, html) {
      return new Promise(fulfill => fulfill(null));
    }

    // Set batch selection. Can be one of "none", "all" or "page".
    // "page" mode should be handled in children.
    setBatchMode(mode) {
      if (['none', 'all'].includes(mode)) {
        this.batchMode = mode;
        this.batchIds = [];
      }

      return this;
    }

    // Mark id as checked/unchecked.
    // If "checked" is undefined then only return current checked status for id (considering "isAll").
    setBatchId(id, checked) {
      const pos = this.batchIds.indexOf(id);
      const all = this.batchMode === 'all';

      if (checked === undefined) {
        if (all) {
          return pos === -1;
        }

        return pos !== -1;
      }

      if (
        (all && !checked && pos === -1) // All checked, trying to uncheck and not already checked.
        || (!all && checked && pos === -1) // Not all checked, trying to check and not already checked.
      ) {
        this.batchIds.push(id);
      } else if (
        (all && checked && pos !== -1) // All checked, trying to check and already checked.
        || (!all && !checked && pos !== -1) // Not all checked, trying to uncheck and already checked.
      ) {
        this.batchIds.splice(pos, 1);
      }

      return checked;
    }
  }

  class InlineTable extends Table {
  }

  class AjaxTable extends Table {
    filter;
    container;
    constructor(table, config) {
      super(table, config);

      // Retrieve filter and container for this table.
      this.filter = table.querySelector('form[data-dg-admin-table-filter]');
      this.container = table.querySelector('[data-dg-admin-table-container]');
      if (!this.container) {
        return;
      }

      if (this.filter) {
        this.filter.addEventListener('submit', e => {
          e.preventDefault();
          e.stopPropagation();

          this.refresh();
        });

        this.filter.addEventListener('reset', e => {
          e.preventDefault();
          e.stopPropagation();

          // Reset filters.
          this.filter.querySelectorAll('select option').forEach(select => select.removeAttribute('selected'));
          this.filter.querySelectorAll('select').forEach(select => {
            select.value = null;
            select.dispatchEvent(new Event('change'));
          });
          this.filter.querySelectorAll('input:not([type=hidden])').forEach(input => {
            input.value = '';
            input.setAttribute('value', '');
          });

          this.filter.dispatchEvent(new Event('submit', { cancelable: true }));
        });

        // Add "data-hidden-filter" to all hidden filters.
        const hiddenFilters = this.filter.querySelectorAll('.form-group.d-none');
        hiddenFilters.forEach(filter => filter.setAttribute('data-dg-admin-hidden-filter', true));

        // Handler for hiding filters.
        const filtersHide = this.filter.querySelectorAll('.filter-hide');
        filtersHide.forEach(button => button.addEventListener('click', e => {
          e.preventDefault();

          // Hide all filters.
          this.filter.querySelectorAll('[data-dg-admin-hidden-filter="true"]').forEach(filter => filter.classList.add('d-none'));

          // Update buttons.
          filtersShow.forEach(filter => filter.classList.remove('d-none'));
          filtersHide.forEach(filter => filter.classList.add('d-none'));
        }));

        // Handler for showing filters.
        const filtersShow = this.filter.querySelectorAll('.filter-show');
        filtersShow.forEach(button => button.addEventListener('click', e => {
          e.preventDefault();

          // Show all filters.
          this.filter.querySelectorAll('[data-dg-admin-hidden-filter="true"]').forEach(filter => filter.classList.remove('d-none'));

          // Update buttons.
          filtersShow.forEach(filter => filter.classList.add('d-none'));
          filtersHide.forEach(filter => filter.classList.remove('d-none'));
        }));

        if (hiddenFilters.length) {
          // Got some hidden filters, show "show" button.
          filtersShow.forEach(filter => filter.classList.remove('d-none'));
        }
      }

      this.table.querySelectorAll('[data-dg-admin-table-batch-mode]').forEach(select => select.addEventListener('click', () => {
        this.setBatchMode(select.dataset['dgAdminTableBatchMode']);
      }));
    }

    refresh(resetPaging = true) {
      super.refresh(resetPaging);
      this.container.querySelectorAll('[data-toggle="tooltip"]').forEach(tooltip => $(tooltip).tooltip('hide').tooltip('disable'));

      return this;
    }
  }

  class DatatableTable extends AjaxTable {
    datatable;
    constructor(table, config) {
      super(table, config);

      window.DGAdmin.onEvent('datatableUpdate', (table) => {
        if (table !== this) {
          return;
        }

        window.DGAdmin.init(table.container);

        // Restore batch checkboxes.
        table.container.querySelectorAll('[data-dg-admin-table-batch-checkbox]').forEach(checkbox => {
          // Set checked state.
          const id = this.datatable.row(checkbox.closest('tr')).data()['DT_RowID'];
          checkbox.checked = this.setBatchId(id);

          // Update checked state on change.
          checkbox.addEventListener('change', () => {
            this.setBatchId(id, checkbox.checked);
          });
        });
      });

      // Make "_init" request.
      new Promise(fulfill => {
        // Find data already set on table.
        const container = table.querySelector('[data-dg-admin-table-container]');
        if (container && container.hasOwnProperty('_dgAdminDatatable')) {
          // Data already set on table.
          fulfill(container._dgAdminDatatable);

          return;
        }

        // Make ajax request including window.location.search.
        let url = config.formatter.url;
        if (window.location.search) {
          let append = window.location.search;
          if (url.indexOf('?') !== -1 && append.startsWith('?')) {
            append = '&' + append.substr(1);
          }

          url += append;
        }

        // We don't use fetch here so the call will be visible on symfony profiler.
        $.ajax(url, {
          method: config.formatter.method,
          data: {
            _init: true,
            _datatable: config.formatter.name,
          },
          success: json => fulfill(json),
        });
      }).then(json => {
        // Replace container element with template.
        this.container.innerHTML = new DOMParser().parseFromString(json.template, 'text/html').body.innerHTML;

        const htmlTable = this.container.querySelector('table');
        if (!htmlTable) {
          return;
        }

        this.datatable = $(htmlTable).DataTable($.extend(json.options, {
          ajax: (request, drawCallback) => {
            if (json.data) {
              // Use data from initial request.
              json.draw = request.draw;
              drawCallback(json);
              json.data = null;
              setTimeout(() => dispatchEvent('datatableUpdate', this), 1);
            } else {
              // Make new request adding filters.
              request._datatable = config.formatter.name;
              if (this.filter) {
                request.filters = convertFormData(getFormData(this.filter));
                const formName = this.filter.getAttribute('name');
                if (formName) {
                  request.filters = request.filters[formName] || {};
                }
              }

              dispatchEvent('datatableRequest', {table: this, request: request});

              $.ajax(config.formatter.url, {
                method: config.formatter.method,
                data: request,
              }).done(data => {
                updateUrl(this);
                this.container.querySelectorAll('[data-toggle="tooltip"]').forEach(tooltip => $(tooltip).tooltip('hide').tooltip('disable'));
                dispatchEvent('datatableData', {table: this, data: data});
                drawCallback(data);
                dispatchEvent('datatableUpdate', this);
              });
            }
          },
        }));
      });
    }

    getParameters() {
      const info = this.datatable.page.info();

      return {
        search: this.datatable.search(),
        orderBy: this.datatable.order().map(order => {
          return {
            column: this.datatable.column(order[0]).dataSrc(),
            dir: order[1],
          };
        }),
        offset: info.start,
        limit: info.length,
      };
    }

    refresh(resetPaging = true) {
      super.refresh(resetPaging);
      this.datatable.ajax.reload(null, resetPaging);

      return this;
    }

    expand(el, html) {
      return new Promise(fulfill => {
        let tr = $(el).closest('tr');
        const row = this.datatable.row(tr);
        if (tr.length) {
          tr = tr[0];
        } else {
          return;
        }

        if (html === undefined) {
          // Collapse.
          if (tr.classList.contains('shown')) {
            row.child.hide();
            row.child.remove();
            tr.classList.remove('shown');
          }

          return;
        }

        const div = document.createElement('div');

        if (!(html instanceof Response)) {
          // html is not a response, use it as it is.
          div.innerHTML = html;

          row.child(div).show();
          tr.classList.add('shown');

          fulfill(div);

          return;
        }

        if (!html.ok) {
          return null;
        }

        const contentType = html.headers.get('content-type');
        if (!contentType || contentType.indexOf('application/json') === -1) {
          // Handle response as text.
          html.text().then(body => {
            div.innerHTML = body;

            row.child(div).show();
            tr.classList.add('shown');

            fulfill(div);
          });

          return;
        }

        html.json().then(json => {
          // Handle response as json.
          if (!json.hasOwnProperty('container')) {
            return;
          }

          div.innerHTML = json.container;
          delete json.container;
          // Find container and set json data to reuse.
          const table = div.querySelector('[data-dg-admin-table-container]');
          table._dgAdminDatatable = json;

          row.child(div).show();
          tr.classList.add('shown');

          fulfill(div);
        });
      });
    }

    setBatchMode(mode) {
      super.setBatchMode(mode);
      if (mode === 'page') {
        this.batchMode = mode;
        this.batchIds = [];
      }

      // Process only checkboxes from this table.
      // We need with :scope because there might be more tables expanded inside this one.
      this.container.querySelectorAll(':scope > div.dataTables_wrapper > div.row > div > div > div > table > tbody > tr > td > [data-dg-admin-table-batch-checkbox]').forEach(checkbox => {
        checkbox.checked = mode !== 'none';

        if (mode === 'page') {
          super.setBatchId(this.datatable.row(checkbox.closest('tr')).data()['DT_RowID'], true);
        }
      });

      return this;
    }
  }

  const onEvents = {};
  function dispatchEvent(name, param) {
    if (!onEvents.hasOwnProperty(name)) {
      return this;
    }

    onEvents[name].forEach(callback => callback(param));

    return this;
  }

  // Check if element is already initialized.
  function isInitialized(el) {
    if (typeof el.dataset !== 'object') {
      return false;
    }

    const initialized = !!el.dataset['dgAdminInitialized'];
    el.dataset.dgAdminInitialized = true;

    return initialized;
  }

  function initializeSelect2(select) {
    if (isInitialized(select)) {
      return this;
    }

    // Function to reload entries for a select2.
    const select2Reload = (select2, reloadUrl) => {
      // Reload entries.
      const url = window.DGAdmin.select2Url(select, reloadUrl);
      if (null === url) {
        // No selection on required parameter, clean options.
        let hadValue = select.value.length;
        select.value = null;
        Array.from(select.options).filter(option => option.value !== '').forEach(option => option.remove());
        $(select2).select2('close');
        if (hadValue) {
          $(select2).trigger('change');
        }

        return;
      }

      // Keep current selection to restore it on loaded data.
      const selection = Array.from(select.selectedOptions).map(option => option.value).filter(value => value.length);

      window.DGAdmin.fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(response => {
          if (response.ok) {
            return response.json();
          }
        })
        .then(json => {
          if (!json || !json.hasOwnProperty('results')) {
            return;
          }

          let appliedSelectionCount = 0;
          json = json['results'];
          // Re-apply selected elements.
          json.forEach((value, index, array) => {
            if (selection.indexOf(value.id.toString()) >= 0) {
              array[index].selected = 'selected';
              appliedSelectionCount++;
            }
          });

          // Reset value and remove all except placeholder.
          select.value = null;
          Array.from(select.options).filter(option => option.value !== '').forEach(option => option.remove());

          // Set options.
          $(select)
            .select2({
              dropdownParent: select.closest('.modal'),
              data: json,
            });

          if (appliedSelectionCount !== selection.length || (appliedSelectionCount === 0 && $.fn.conditionize.getValue(select).length)) {
            // Trigger change if a different number of options was applied from previous options.
            $(select).trigger('change');
          }
        });
    };

    let opts = {dropdownParent: select.closest('.modal')};
    if (select.dataset['ajax-Url']) {
      opts['ajax'] = {
        beforeSend: (jqXHR, settings) => {
          settings.url = window.DGAdmin.select2Url(select, settings.url);
        },
      };
    }

    $(select)
      .select2(opts)
      .on('select2:opening', () => {
        if (!select.dataset['ajax-Url']) {
          return;
        }

        // If select2 is using ajax, disable opening if some required fields are not set.
        for (let [param, value] of Object.entries(window.DGAdmin.fieldParams(select))) {
          if (select.dataset['ajax-Url'].indexOf('{' + param + '}') !== -1 && !value.length) {
            return false;
          }
        }
      });

    const reloadUrl = unescape(select.dataset['ajaxReloadUrl'] || '');
    if (reloadUrl) {
      // Initial load.
      select2Reload(select, reloadUrl);
    }

    for (let selector of Object.keys(JSON.parse(select.dataset['dgAdminSelect2FieldParams'] || '{}'))) {
      if (selector.substring(0, 1) !== '#') {
        selector = '[name="' + selector + '"]';
      }

      $(selector).on('change', () => {
        if (reloadUrl) {
          // Reload entries.
          select2Reload(select, reloadUrl);
        }
      });
    }

    return this;
  }

  function initializeDatePicker(datePicker) {
    if (isInitialized(datePicker)) {
      return this;
    }

    const settings = JSON.parse(datePicker.dataset['dgAdminDatepicker']);
    const parts = datePicker.value.split(settings.separator);
    datePicker.value = '';
    if (parts[0]) {
      datePicker.value = moment(parts[0]).format(settings.locale.format);
    }
    if (parts[1]) {
      datePicker.value += document.body._dgAdminInit.daterangepicker.locale.separator + moment(parts[1]).format(settings.locale.format);
    }

    // Convert ranges with moment() only once.
    if (!document.body._dgAdminInit.daterangepicker.initDefaultRanges) {
      for ([key, value] of Object.entries(document.body._dgAdminInit.daterangepicker.defaultRanges)) {
        value[0] = eval('moment()' + (value[0] !== '' ? '.' + value[0] : ''));
        value[1] = eval('moment()' + (value[1] !== '' ? '.' + value[1] : ''));
      }

      document.body._dgAdminInit.daterangepicker.initDefaultRanges = true;
    }

    $.fn.daterangepicker.defaultOptions = document.body._dgAdminInit.daterangepicker;

    $(datePicker)
      .daterangepicker(settings)
      .on('apply.daterangepicker', (e, picker) => {
        let val = picker.startDate.format(picker.locale.format);
        if (!settings.singleDatePicker) {
          val += picker.locale.separator + picker.endDate.format(picker.locale.format)
        }
        if (!picker.autoUpdateInput) {
          datePicker.value = val;
        }
      })
      .on('cancel.daterangepicker', (e, picker) => {
        if (!picker.autoUpdateInput) {
          datePicker.value = '';
        }
      });

    return this;
  }

  function getDatePickerValue(el) {
    if (el.value) {
      const daterangepicker = $(el).data('daterangepicker');
      value = daterangepicker.startDate.format();
      if (!daterangepicker.singleDatePicker) {
        value += '>' + daterangepicker.endDate.endOf('minute').format();
      }
    } else {
      value = '';
    }

    return value;
  }

  function initializeTable(table) {
    if (isInitialized(table)) {
      return this;
    }

    // Parse config from data element and set formatter url if not already set.
    const config = JSON.parse(table.dataset['dgAdminTable']);
    if (!config.formatter.url) {
      config.formatter.url = window.location.pathname + window.location.search;
    }

    let newTable = null;
    switch (config.formatter.type) {
      case 'datatable':
        newTable = new DatatableTable(table, config);
        break;
      case 'inline':
        newTable = new InlineTable(table, config);
        break;
    }

    if (newTable) {
      table._dgAdminTable = newTable;
    }

    return this;
  }

  function initializeUIAction(el) {
    if (isInitialized(el)) {
      return this;
    }

    const table = window.DGAdmin.findTableByElement(el);
    const name = el.dataset['dgAdminUiaction'];
    const params = JSON.parse(el.dataset['dgAdminUiactionParameters'] || '{}');

    switch (name) {
      case '_dg_admin.uiaction.expandTableRow':
        el.addEventListener('click', e => {
          e.preventDefault();
          e.stopPropagation();

          if (!table) {
            return;
          }

          if (el._dgAdminTableRowExpanded) {
            // Collapse.
            delete el._dgAdminTableRowLoading;
            delete el._dgAdminTableRowExpanded;
            table.expand(el);

            return;
          }

          // Set loading state.
          el._dgAdminTableRowLoading = true;
          el._dgAdminTableRowExpanded = true;
          table.expand(el, '<div class="fa-2x" style="text-align: center"><i class="fas fa-circle-notch fa-spin"></i></div>');

          // Allow events to update content.
          dispatchEvent('uiaction', {
            el: el,
            name: name,
            params: params,
          });

          // Check if subtable is still loading. The event might have updated it.
          if (el._dgAdminTableRowLoading) {
            el._dgAdminTableRowLoading = false;
            window.DGAdmin.fetchUIAction(el, params)
              .then(response => table.expand(el, response))
              .then(container => {
                if (container) {
                  window.DGAdmin.init(container);
                }

                dispatchEvent('uiaction', {
                  el: el,
                  name: name,
                  params: params,
                });
              });
          }
        });
        break;

      case '_dg_admin.uiaction.ajax':
        el.addEventListener('click', e => {
          e.preventDefault();
          e.stopPropagation();

          if (el instanceof HTMLElement && el.classList.contains('dropdown-item')) {
            // Close dropdown.
            $(el.parentElement).dropdown('toggle');
          }

          window.DGAdmin.fetchUIAction(el, params).then(response => {
            if (response.status >= 200 && response.status < 400) {
              refreshTable(el, params['refresh_table']);
              dispatchEvent('uiaction', {
                el: el,
                name: name,
                params: params,
                response: response,
              });
            }
          });
        });
        break;

      case '_dg_admin.uiaction.ajaxDialog':
        el.addEventListener('click', e => {
          e.preventDefault();
          e.stopPropagation();

          if (el instanceof HTMLElement && el.classList.contains('dropdown-item')) {
            // Close dropdown.
            $(el.parentElement).dropdown('toggle');
          }

          // Show dialog.
          window.DGAdmin.ajaxDialog(el, params).then(response => {
            if (response.status >= 200 && response.status < 400) {
              dispatchEvent('uiaction', {
                el: el,
                name: name,
                params: params,
                response: response,
              });
            }
          });
        });
        break;
    }

    return this;
  }

  function mergeURLSearchParams(params1, params2, merge = false) {
    for (const [key, value] of params2) {
      if (merge) {
        params1.append(key, value);
      } else {
        params1.set(key, value);
      }
    }

    return params1;
  }

  function refreshTable(el, refresh) {
    if (refresh === false) {
      return;
    }
    if (refresh === true) {
      window.DGAdmin.refreshAllTables();

      return;
    }

    const table = refresh === '' ? window.DGAdmin.findTableByElement(el) : window.DGAdmin.findTableByName(refresh);
    if (table) {
      table.refresh(false);
    }
  }

  // Get all parameters for elements that depends on this element.
  function getDependsParams(el) {
    let params = new URLSearchParams();
    const form = el.closest('form');
    let depends = el.dataset['dgAdminDepends'];

    if (!form || !depends) {
      return params
    }

    JSON.parse(depends)
      .filter(depend => '' !== depend.name)
      .forEach(depend => {
        // Find element that this depends on.
        const el = form.querySelector('[name="' + depend.field + '"]');
        if (el) {
          // Add its value. For select add all selected option values.
          if (el.tagName === 'SELECT') {
            Array.from(el.selectedOptions).forEach(option => params.append(depend.name, option.value));
          } else {
            params.append(depend.name, el.value);
          }
        }
      });

    return params;
  }

  // Get cleaned FormData for a form.
  function getFormData(form, removeEmpty = false) {
    const data = new FormData();
    if (!form) {
      return data;
    }

    for (let [key, value] of new FormData(form)) {
      const el = form.elements[key];

      // Convert value of daterangepicker.
      if (el.dataset['dgAdminDatepicker']) {
        value = getDatePickerValue(el);
      }

      // If removing empty, we need to ignore params with no value or hidden.
      if (removeEmpty && (value.toString() === '' || el.getAttribute('type') === 'hidden')) {
        continue;
      }

      data.append(key, value);
    }

    return data;
  }

  // Get all parameters for table (search, filters, list).
  function getTableParameters(table, removeEmpty = true) {
    const tableParameters = table.getParameters();
    let parameters = {};

    const search = tableParameters.search.toString();
    if (search !== '') {
      parameters.search = search;
    }

    parameters.list = '';
    tableParameters.orderBy.forEach(orderBy => {
      parameters.list += (parameters.list !== '' ? ',' : '') + orderBy.column + '_' + orderBy.dir;
    });
    parameters.list += (parameters.list !== '' ? ',' : '') + tableParameters.offset + ',' + tableParameters.limit;

    if (table.filter) {
      parameters.filters = convertFormData(getFormData(table.filter, removeEmpty));
      const filterName = table.filter.getAttribute('name');
      if (filterName) {
        parameters.filters = parameters.filters[filterName] || {};
      }
    }

    return parameters;
  }

  // Convert FormData to object and vice-versa.
  function convertFormData(formDataOrObject, prefix, formDataCfg) {
    if (formDataOrObject instanceof FormData) {
      // Convert FormData to object.
      let object = {};
      formDataOrObject.forEach((value, key) => {
        let innerObject = object;
        key
          .split('[')
          .map(s => s.replace(']',''))
          .forEach((el, index, arr) => {
            if (index < arr.length - 1) {
              // This element has some children.
              if (innerObject[el] === undefined) {
                innerObject[el] = {};
              }

              innerObject = innerObject[el];
            } else {
              // If no key is specified, set autoincrement index.
              if (el === '') {
                el = Object.keys(innerObject).length;
              }

              innerObject[el] = value;
            }
          })
        ;
      });

      return object;
    }

    // Convert object to FormData using https://github.com/therealparmesh/object-to-formdata.
    const isUndefined = (value) => value === undefined;
    const isNull = (value) => value === null;
    const isBoolean = (value) => typeof value === 'boolean';
    const isObject = (value) => value === Object(value);
    const isArray = (value) => Array.isArray(value);
    const isDate = (value) => value instanceof Date;
    const isBlob = (value) =>
      value &&
      typeof value.size === 'number' &&
      typeof value.type === 'string' &&
      typeof value.slice === 'function';
    const isFile = (value) =>
      isBlob(value) &&
      typeof value.name === 'string' &&
      (typeof value.lastModifiedDate === 'object' ||
        typeof value.lastModified === 'number');

    const serialize = (obj, cfg, fd, pre) => {
      cfg = cfg || {};
      cfg.indices = isUndefined(cfg.indices) ? false : cfg.indices;
      cfg.nullsAsUndefineds = isUndefined(cfg.nullsAsUndefineds)
        ? false
        : cfg.nullsAsUndefineds;
      cfg.booleansAsIntegers = isUndefined(cfg.booleansAsIntegers)
        ? false
        : cfg.booleansAsIntegers;
      cfg.allowEmptyArrays = isUndefined(cfg.allowEmptyArrays)
        ? false
        : cfg.allowEmptyArrays;
      fd = fd || new FormData();

      if (isUndefined(obj)) {
        return fd;
      } else if (isNull(obj)) {
        if (!cfg.nullsAsUndefineds) {
          fd.append(pre, '');
        }
      } else if (isBoolean(obj)) {
        if (cfg.booleansAsIntegers) {
          fd.append(pre, obj ? 1 : 0);
        } else {
          fd.append(pre, obj);
        }
      } else if (isArray(obj)) {
        if (obj.length) {
          obj.forEach((value, index) => {
            const key = pre + '[' + (cfg.indices ? index : '') + ']';

            serialize(value, cfg, fd, key);
          });
        } else if (cfg.allowEmptyArrays) {
          fd.append(pre + '[]', '');
        }
      } else if (isDate(obj)) {
        fd.append(pre, obj.toISOString());
      } else if (isObject(obj) && !isFile(obj) && !isBlob(obj)) {
        Object.keys(obj).forEach((prop) => {
          const value = obj[prop];

          if (isArray(value)) {
            while (prop.length > 2 && prop.lastIndexOf('[]') === prop.length - 2) {
              prop = prop.substring(0, prop.length - 2);
            }
          }

          const key = pre ? pre + '[' + prop + ']' : prop;

          serialize(value, cfg, fd, key);
        });
      } else {
        fd.append(pre, obj);
      }

      return fd;
    };

    let fd = new FormData();
    serialize(formDataOrObject, formDataCfg, fd, prefix);

    return fd;
  }

  // Update url for table when changed.
  function updateUrl(table) {
    const name = table.config.formatter['name_url'];
    if (null === name) {
      return;
    }

    // Build new params starting from current parameters and removing the ones for current table.
    const parameters = new URLSearchParams(window.location.search);
    parameters.delete(nameUrl(name, 'search'));
    parameters.delete(nameUrl(name, 'list'));
    for (const key of Array.from(parameters.keys())) {
      if (key.startsWith(nameUrl(name, 'filters') + '[')) {
        parameters.delete(key);
      }
    }

    // Get table parameters and add it to correct name (if any).
    const tempTableParameters = getTableParameters(table);
    let tableParameters = tempTableParameters;
    if (name) {
      tableParameters = {};
      tableParameters[name] = tempTableParameters;
    }
    for (const [key, value] of convertFormData(tableParameters).entries()) {
      parameters.set(key, value);
    }

    dispatchEvent('updateUrl', parameters);
    let url = parameters.toString();
    if (url.length) {
      url = '?' + url;
    }

    window.history.replaceState(null, '', window.location.pathname + url);
  }

  // Build name to be used in url.
  function nameUrl(formName, name, index = '') {
    if (formName !== '') {
      const pos = name.indexOf('[');
      if (pos === -1) {
        // No need to split current key, since it's not an array.
        name = `${formName}[${name}]`;
      } else {
        // Key is an array, split part.
        name = formName + '[' + name.substr(0, pos) + ']' + name.substr(pos);
      }
    }

    if (index !== '') {
      name += `[${index}]`;
    }

    return name;
  }

  window.DGAdmin = new Admin();
}

window.DGAdmin.ready(() => {
  document.body._dgAdminInit = JSON.parse(document.body.dataset['dgAdminInit'] || '{}');
  if (document.body._dgAdminInit.ajaxDialog) {
    // Show dialog.
    DGAdmin.ajaxDialog(null, document.body._dgAdminInit.ajaxDialog);
  }
  DGAdmin.init(document);
});

// Check all jquery ajax responses if it contains SweetAlert notification.
$(document).ajaxComplete(function (event, xhr) {
  if (xhr['responseJSON'] && xhr['responseJSON']['_swal']) {
    Swal.fire($.extend(
      {
        toast: true,
        icon: 'success',
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
      },
      xhr['responseJSON']['_swal'],
    ));
  } else if (xhr.status >= 500 && xhr.status < 600) {
    Swal.fire({
      title: document.body._dgAdminInit['errorMessage'],
      toast: true,
      icon: 'error',
      position: 'top-end',
      showConfirmButton: false,
      timer: 5000,
    });
  }
});
