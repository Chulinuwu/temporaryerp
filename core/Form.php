<?php
/**
 * PEGASUS ERP - Form field components
 * Reusable server-rendered field builders. Each returns an HTML string to echo
 * inside a form. Markup matches the app's .form-group / .form-input / .form-select.
 *
 * Common opts: value, required, disabled, placeholder, help, id, col (grid-column
 * span e.g. '1/-1'), group_class (extra class on wrapper), error (bool|string;
 * string also renders a .form-error message), attrs (assoc extra attributes).
 *
 * See reference/components.md.
 */

class Form
{
    private const ICON_CARET = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    private const ICON_CAL = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="2.5" y="3.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.3"/><path d="M2.5 6.5h11M5.5 2v2M10.5 2v2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>';
    private const ICON_CLOCK = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.3"/><path d="M8 5v3l2 1.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    public static function text(string $name, string $label, array $opts = []): string
    {
        $opts['type'] = $opts['type'] ?? 'text';
        return self::input($name, $label, $opts);
    }

    /**
     * Custom date / time / datetime picker. opts['mode'] = date | time | datetime.
     * The hidden input submits the same string the native control would
     * (YYYY-MM-DD, HH:MM, or YYYY-MM-DDTHH:MM).
     */
    public static function datetime(string $name, string $label, array $opts = []): string
    {
        $mode = in_array($opts['mode'] ?? 'date', ['date', 'time', 'datetime'], true) ? $opts['mode'] : 'date';
        $value = (string)($opts['value'] ?? '');
        $ph = $opts['placeholder'] ?? ($mode === 'time' ? 'Select time' : 'Select date');
        $icon = $mode === 'time' ? self::ICON_CLOCK : self::ICON_CAL;
        $display = self::dateDisplay($value, $mode);

        $field = '<div class="cmp' . (!empty($opts['error']) ? ' is-error' : '') . '" data-cmp-date data-mode="' . $mode . '">'
            . self::hidden($name, $opts, $value)
            . self::trigger($display, $ph, '<span class="cmp-trigger-icon">' . $icon . '</span>')
            . '<div class="cmp-panel" hidden></div></div>';
        return self::group($label, $field, $opts);
    }

    public static function textarea(string $name, string $label, array $opts = []): string
    {
        $attrs = self::commonAttrs($name, $opts);
        $attrs['class'] = self::fieldClass('form-textarea', $opts);
        $attrs['rows'] = $opts['rows'] ?? 3;
        if (!empty($opts['placeholder'])) {
            $attrs['placeholder'] = $opts['placeholder'];
        }
        $field = '<textarea' . self::attrStr($attrs) . '>' . self::esc($opts['value'] ?? '') . '</textarea>';
        return self::group($label, $field, $opts);
    }

    /**
     * Custom select (searchable combobox). $options: list of ['value'=>,'label'=>]
     * or an assoc map value=>label. Submits via a hidden input named $name.
     * opts['addable'] = true adds an in-panel "Create new" action. Use it only for
     * free-text columns - the created option submits its typed text, which would
     * break an integer foreign-key field.
     * opts['none_label'] = null suppresses the empty option (e.g. currency).
     */
    public static function select(string $name, string $label, array $options, array $opts = []): string
    {
        $value = (string)($opts['value'] ?? '');
        $suppressNone = array_key_exists('none_label', $opts) && $opts['none_label'] === null;
        $placeholder = $suppressNone
            ? ($opts['placeholder'] ?? 'Select')
            : ($opts['none_label'] ?? ('- ' . ($opts['none_text'] ?? 'None') . ' -'));

        $items = '';
        if (!$suppressNone) {
            $items .= self::option('', $placeholder, false);
        }
        $selectedLabel = '';
        $found = false;
        foreach (self::normalizeOptions($options) as $o) {
            $v = (string)$o['value'];
            $isSel = ($v === $value && $value !== '');
            if ($isSel) { $selectedLabel = $o['label']; $found = true; }
            $items .= self::option($v, $o['label'], $isSel);
        }
        if ($value !== '' && !$found) { // free-text value not in the list (e.g. a previously added one)
            $selectedLabel = $value;
            $items .= self::option($value, $value, true);
        }

        $footer = '';
        $addableAttr = '';
        if (!empty($opts['addable'])) {
            $addLabel = $opts['add_label'] ?? 'Create "%s"';
            $addableAttr = ' data-addable data-add-label="' . self::esc($addLabel) . '"';
            $idle = trim(str_replace(['"%s"', '%s'], '', $addLabel));
            $footer = '<div class="cmp-footer"><button type="button" class="cmp-add-action" data-cmp-add>'
                . '<span class="cmp-plus">+</span><span class="cmp-add-text">' . self::esc($idle) . '</span></button></div>';
        }

        $field = '<div class="cmp' . (!empty($opts['error']) ? ' is-error' : '') . '" data-cmp-select' . $addableAttr . '>'
            . self::hidden($name, $opts, $value)
            . self::trigger($selectedLabel, $placeholder, '<span class="cmp-trigger-icon cmp-caret">' . self::ICON_CARET . '</span>')
            . '<div class="cmp-panel" hidden>'
            . '<div class="cmp-search"><input type="text" placeholder="' . self::esc($opts['search_placeholder'] ?? 'Search...') . '"></div>'
            . '<ul class="cmp-options" role="listbox">' . $items . '</ul>'
            . $footer
            . '</div></div>';
        return self::group($label, $field, $opts);
    }

    // --- internals ---

    private static function option(string $value, string $labelText, bool $selected): string
    {
        return '<li class="cmp-option' . ($selected ? ' is-selected' : '') . '" role="option"'
            . ' data-value="' . self::esc($value) . '" data-label="' . self::esc($labelText) . '">'
            . self::esc($labelText) . '</li>';
    }

    private static function hidden(string $name, array $opts, string $value): string
    {
        return '<input type="hidden" name="' . self::esc($name) . '"'
            . ' id="' . self::esc($opts['id'] ?? $name) . '" value="' . self::esc($value) . '">';
    }

    private static function trigger(string $display, string $placeholder, string $iconHtml): string
    {
        $isPlaceholder = $display === '';
        return '<button type="button" class="cmp-trigger' . ($isPlaceholder ? ' is-placeholder' : '') . '">'
            . '<span class="cmp-trigger-value" data-placeholder="' . self::esc($placeholder) . '">'
            . self::esc($isPlaceholder ? $placeholder : $display) . '</span>'
            . $iconHtml . '</button>';
    }

    private static function dateDisplay(string $value, string $mode): string
    {
        if ($value === '') { return ''; }
        if ($mode === 'time') { return $value; }
        $value = str_replace('T', ' ', $value);
        $parts = explode(' ', $value, 2);
        $seg = explode('-', $parts[0]);
        $date = count($seg) === 3 ? $seg[2] . '/' . $seg[1] . '/' . $seg[0] : $parts[0];
        if ($mode === 'datetime') { return trim($date . ' ' . substr($parts[1] ?? '', 0, 5)); }
        return $date;
    }

    private static function input(string $name, string $label, array $opts): string
    {
        $attrs = self::commonAttrs($name, $opts);
        $attrs['class'] = self::fieldClass('form-input', $opts);
        $attrs['type'] = $opts['type'];
        if (isset($opts['value'])) {
            $attrs['value'] = $opts['value'];
        }
        foreach (['placeholder', 'maxlength', 'min', 'max', 'step', 'pattern'] as $k) {
            if (isset($opts[$k]) && $opts[$k] !== '') {
                $attrs[$k] = $opts[$k];
            }
        }
        $field = '<input' . self::attrStr($attrs) . '>';
        return self::group($label, $field, $opts);
    }

    private static function commonAttrs(string $name, array $opts): array
    {
        $attrs = ['name' => $name, 'id' => $opts['id'] ?? $name];
        if (!empty($opts['required'])) {
            $attrs['required'] = true;
        }
        if (!empty($opts['disabled'])) {
            $attrs['disabled'] = true;
        }
        foreach (($opts['attrs'] ?? []) as $k => $v) {
            $attrs[$k] = $v;
        }
        return $attrs;
    }

    private static function fieldClass(string $base, array $opts): string
    {
        return !empty($opts['error']) ? $base . ' error' : $base;
    }

    private static function group(string $label, string $field, array $opts): string
    {
        $cls = 'form-group' . (!empty($opts['group_class']) ? ' ' . $opts['group_class'] : '');
        $style = !empty($opts['col']) ? ' style="grid-column:' . self::esc($opts['col']) . ';"' : '';
        $req = !empty($opts['required']) ? ' <span class="required">*</span>' : '';
        $for = self::esc($opts['id'] ?? '');

        $extra = '';
        if (!empty($opts['error']) && is_string($opts['error'])) {
            $extra .= '<div class="form-error">' . self::esc($opts['error']) . '</div>';
        }
        if (!empty($opts['help'])) {
            $extra .= '<div class="form-hint">' . self::esc($opts['help']) . '</div>';
        }

        return '<div class="' . $cls . '"' . $style . '>'
            . '<label class="form-label" for="' . $for . '">' . self::esc($label) . $req . '</label>'
            . $field . $extra . '</div>';
    }

    private static function normalizeOptions(array $options): array
    {
        $out = [];
        foreach ($options as $k => $v) {
            if (is_array($v)) {
                $out[] = ['value' => $v['value'] ?? '', 'label' => $v['label'] ?? ($v['value'] ?? '')];
            } else {
                $out[] = ['value' => $k, 'label' => $v];
            }
        }
        return $out;
    }

    private static function attrStr(array $attrs): string
    {
        $s = '';
        foreach ($attrs as $k => $v) {
            if ($v === true) {
                $s .= ' ' . $k;
            } elseif ($v !== false && $v !== null) {
                $s .= ' ' . $k . '="' . self::esc($v) . '"';
            }
        }
        return $s;
    }

    private static function esc($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}
