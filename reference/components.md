# Form components reference

Reusable server-rendered field builders in `core/Form.php`, styled by
`public/css/components.css` and driven by `public/js/components.js`. Each builder
returns an HTML string to echo inside a form. The dropdown and date picker are
custom widgets (no native `<select>` / date input); each keeps a hidden `<input>`
whose name/value match what the native control would submit, so controllers are
unchanged.

## Usage

```php
<?= Form::text('contact', 'Contact', ['value' => $row['contact'] ?? '', 'placeholder' => '...']) ?>
<?= Form::textarea('notes', 'Notes', ['value' => $row['notes'] ?? '', 'rows' => 3, 'col' => '1/-1']) ?>
<?= Form::datetime('due', 'Due', ['mode' => 'datetime', 'value' => $row['due'] ?? '']) ?>
<?= Form::select('supplier_id', 'Supplier', $options, ['value' => $id, 'error' => $errors['supplier_id'] ?? false]) ?>
<?= Form::select('department', 'Department', $deptOptions, ['addable' => true, 'add_label' => 'Create department "%s"']) ?>
```

`$options` is a list of `['value'=>, 'label'=>]` or an assoc map `value=>label`.

## Common opts

| Key | Applies to | Effect |
|---|---|---|
| `value` | all | Current value (selected option / date / text) |
| `required` | all | Adds the `*` marker (server still validates) |
| `placeholder` | text, textarea, datetime | Placeholder text |
| `help` | all | Hint line under the field |
| `error` | all | `true` adds error styling; a string also renders a `.form-error` message |
| `col` | all | `grid-column` span, e.g. `'1/-1'` for full width in a grid |
| `group_class` | all | Extra class on the `.form-group` wrapper (e.g. `form-full`) |
| `id`, `attrs` | all | Field id / extra HTML attributes |

## Field-specific opts

- `Form::datetime` - `mode`: `date` | `time` | `datetime`. Hidden value format matches
  the native control (`YYYY-MM-DD`, `HH:MM`, `YYYY-MM-DDTHH:MM`). Popup is a custom
  calendar with month nav, plus hour/minute selects when the mode includes time.
- `Form::select` - searchable custom combobox:
  - `none_text` / `none_label` - the empty-choice label. `none_label => null` removes
    the empty choice entirely (e.g. a currency that always has a value).
  - `addable => true` - shows an in-panel "Create new" action. The created option is
    submitted as its typed text, so use addable ONLY for free-text columns. Never put
    it on an integer foreign-key field (e.g. `supplier_id`) - the text would break the
    insert. `add_label` customizes the action text; `%s` is replaced with the typed value.
  - `search_placeholder` - search box placeholder.

## How the JS finds widgets

`components.js` initializes every `[data-cmp-select]` and `[data-cmp-date]` on
`DOMContentLoaded`. For markup added later (AJAX), call
`window.PegasusComponents.init(scope)`.

## In use

`views/purchasing/request_form.php` (PR) and `views/purchasing/order_form.php` (PO)
render their header fields through these builders. The PR Department field is the
addable example; supplier/project/payment-term stay non-addable because they are FKs.
