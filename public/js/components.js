/**
 * PEGASUS ERP - Custom form components
 * Vanilla custom <select> (searchable + create-new) and date/time picker.
 * Each widget keeps a hidden <input> whose name/value match what the native
 * control submitted, so the server side is unchanged.
 */
(function () {
    'use strict';

    var openInstance = null;

    function closeOpen() {
        if (openInstance) { openInstance.close(); openInstance = null; }
    }

    document.addEventListener('click', function (e) {
        if (openInstance && !openInstance.root.contains(e.target)) { closeOpen(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeOpen(); }
    });

    /* ================= Custom select ================= */
    function Select(root) {
        this.root = root;
        this.hidden = root.querySelector('input[type=hidden]');
        this.trigger = root.querySelector('.cmp-trigger');
        this.valueEl = root.querySelector('.cmp-trigger-value');
        this.panel = root.querySelector('.cmp-panel');
        this.search = root.querySelector('.cmp-search input');
        this.list = root.querySelector('.cmp-options');
        this.addable = root.hasAttribute('data-addable');
        this.addBtn = root.querySelector('[data-cmp-add]');
        this.addLabelTpl = root.getAttribute('data-add-label') || 'Create "%s"';
        this.placeholder = this.valueEl.getAttribute('data-placeholder') || '';
        this.bind();
        this.syncFromHidden();
    }

    Select.prototype.bind = function () {
        var self = this;
        this.trigger.addEventListener('click', function () { self.toggle(); });
        this.list.addEventListener('click', function (e) {
            var li = e.target.closest('.cmp-option');
            if (li) { self.choose(li.dataset.value, li.dataset.label); }
        });
        if (this.search) {
            this.search.addEventListener('input', function () { self.filter(self.search.value); });
            this.search.addEventListener('keydown', function (e) { self.onSearchKey(e); });
        }
        if (this.addBtn) {
            this.addBtn.addEventListener('click', function () { self.createNew(); });
        }
    };

    Select.prototype.options = function () {
        return Array.prototype.slice.call(this.list.querySelectorAll('.cmp-option'));
    };

    Select.prototype.syncFromHidden = function () {
        var val = this.hidden.value;
        var match = this.options().filter(function (o) { return o.dataset.value === val; })[0];
        if (val !== '' && !match) {
            match = this.addOption(val, val);
        }
        this.options().forEach(function (o) { o.classList.remove('is-selected'); });
        if (match && val !== '') {
            match.classList.add('is-selected');
            this.setLabel(match.dataset.label, false);
        } else {
            this.setLabel(this.placeholder, true);
        }
    };

    Select.prototype.setLabel = function (text, isPlaceholder) {
        this.valueEl.textContent = text;
        this.trigger.classList.toggle('is-placeholder', !!isPlaceholder);
    };

    Select.prototype.toggle = function () { this.root.classList.contains('is-open') ? this.close() : this.open(); };

    Select.prototype.open = function () {
        closeOpen();
        openInstance = this;
        this.root.classList.add('is-open');
        this.panel.hidden = false;
        if (this.search) { this.search.value = ''; this.filter(''); this.search.focus(); }
    };

    Select.prototype.close = function () {
        this.root.classList.remove('is-open');
        this.panel.hidden = true;
        this.clearActive();
    };

    Select.prototype.choose = function (value, label) {
        this.hidden.value = value;
        this.options().forEach(function (o) { o.classList.toggle('is-selected', o.dataset.value === value); });
        this.setLabel(label, false);
        this.hidden.dispatchEvent(new Event('change', { bubbles: true }));
        this.close();
    };

    Select.prototype.addOption = function (value, label) {
        var li = document.createElement('li');
        li.className = 'cmp-option';
        li.setAttribute('role', 'option');
        li.dataset.value = value;
        li.dataset.label = label;
        li.textContent = label;
        this.list.appendChild(li);
        return li;
    };

    Select.prototype.filter = function (q) {
        q = (q || '').trim().toLowerCase();
        var shown = 0;
        this.options().forEach(function (o) {
            var hit = o.dataset.label.toLowerCase().indexOf(q) !== -1;
            o.hidden = !hit;
            if (hit) { shown++; }
        });
        this.clearActive();
        this.renderEmpty(shown === 0 && !this.addable);
        if (this.addBtn) {
            var labelEl = this.addBtn.querySelector('.cmp-add-text');
            if (q) {
                labelEl.textContent = this.addLabelTpl.replace('%s', this.search.value.trim());
            } else {
                labelEl.textContent = this.addLabelTpl.replace(' "%s"', '').replace('%s', '');
            }
        }
    };

    Select.prototype.renderEmpty = function (show) {
        var empty = this.list.querySelector('.cmp-empty');
        if (show && !empty) {
            empty = document.createElement('li');
            empty.className = 'cmp-empty';
            empty.textContent = 'No matches';
            this.list.appendChild(empty);
        } else if (!show && empty) {
            empty.remove();
        }
    };

    Select.prototype.visibleOptions = function () {
        return this.options().filter(function (o) { return !o.hidden; });
    };

    Select.prototype.clearActive = function () {
        this.options().forEach(function (o) { o.classList.remove('is-active'); });
    };

    Select.prototype.onSearchKey = function (e) {
        var vis = this.visibleOptions();
        var idx = vis.findIndex(function (o) { return o.classList.contains('is-active'); });
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            idx = Math.min(idx + 1, vis.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            idx = Math.max(idx - 1, 0);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (idx >= 0 && vis[idx]) { this.choose(vis[idx].dataset.value, vis[idx].dataset.label); }
            else if (this.addable && this.search.value.trim()) { this.createNew(); }
            return;
        } else {
            return;
        }
        this.clearActive();
        if (vis[idx]) { vis[idx].classList.add('is-active'); vis[idx].scrollIntoView({ block: 'nearest' }); }
    };

    Select.prototype.createNew = function () {
        var val = (this.search.value || '').trim();
        if (!val) { return; }
        var existing = this.options().filter(function (o) {
            return o.dataset.label.toLowerCase() === val.toLowerCase();
        })[0];
        if (existing) { this.choose(existing.dataset.value, existing.dataset.label); return; }
        var li = this.addOption(val, val); // free-text: value === label
        this.choose(li.dataset.value, li.dataset.label);
    };

    /* ================= Date / time picker ================= */
    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    var DOW = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function DatePicker(root) {
        this.root = root;
        this.mode = root.getAttribute('data-mode') || 'date';
        this.hasDate = this.mode === 'date' || this.mode === 'datetime';
        this.hasTime = this.mode === 'time' || this.mode === 'datetime';
        this.hidden = root.querySelector('input[type=hidden]');
        this.trigger = root.querySelector('.cmp-trigger');
        this.valueEl = root.querySelector('.cmp-trigger-value');
        this.panel = root.querySelector('.cmp-panel');
        this.placeholder = this.valueEl.getAttribute('data-placeholder') || '';
        this.sel = this.parse(this.hidden.value);
        var base = this.sel || new Date();
        this.viewY = base.getFullYear();
        this.viewM = base.getMonth();
        this.bind();
        this.renderLabel();
    }

    DatePicker.prototype.parse = function (v) {
        if (!v) { return null; }
        if (this.mode === 'time') {
            var t = v.split(':'); if (t.length < 2) { return null; }
            var d = new Date(); d.setHours(+t[0], +t[1], 0, 0); return d;
        }
        var iso = this.mode === 'datetime' ? v.replace(' ', 'T') : v + 'T00:00';
        var dt = new Date(iso);
        return isNaN(dt.getTime()) ? null : dt;
    };

    DatePicker.prototype.bind = function () {
        var self = this;
        this.trigger.addEventListener('click', function () { self.toggle(); });
    };

    DatePicker.prototype.toggle = function () { this.root.classList.contains('is-open') ? this.close() : this.open(); };

    DatePicker.prototype.open = function () {
        closeOpen();
        openInstance = this;
        this.root.classList.add('is-open');
        this.renderPanel();
        this.panel.hidden = false;
    };

    DatePicker.prototype.close = function () {
        this.root.classList.remove('is-open');
        this.panel.hidden = true;
    };

    DatePicker.prototype.commit = function (close) {
        this.hidden.value = this.toValue();
        this.hidden.dispatchEvent(new Event('change', { bubbles: true }));
        this.renderLabel();
        if (close) { this.close(); } else { this.renderPanel(); }
    };

    DatePicker.prototype.toValue = function () {
        if (!this.sel) { return ''; }
        var d = this.sel;
        if (this.mode === 'time') { return pad(d.getHours()) + ':' + pad(d.getMinutes()); }
        var date = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        if (this.mode === 'datetime') { return date + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); }
        return date;
    };

    DatePicker.prototype.renderLabel = function () {
        if (!this.sel) { this.valueEl.textContent = this.placeholder; this.trigger.classList.add('is-placeholder'); return; }
        this.trigger.classList.remove('is-placeholder');
        var d = this.sel;
        var datePart = pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
        var timePart = pad(d.getHours()) + ':' + pad(d.getMinutes());
        this.valueEl.textContent = this.mode === 'time' ? timePart
            : (this.mode === 'datetime' ? datePart + ' ' + timePart : datePart);
    };

    DatePicker.prototype.renderPanel = function () {
        this.panel.innerHTML = '';
        var wrap = document.createElement('div');
        wrap.className = 'cmp-cal';
        if (this.hasDate) { wrap.appendChild(this.buildCalendar()); }
        if (this.hasTime) { wrap.appendChild(this.buildTime()); }
        wrap.appendChild(this.buildActions());
        this.panel.appendChild(wrap);
    };

    DatePicker.prototype.buildCalendar = function () {
        var self = this;
        var frag = document.createDocumentFragment();

        var head = document.createElement('div');
        head.className = 'cmp-cal-head';
        var prev = el('button', 'cmp-cal-nav', '‹');
        var title = el('span', 'cmp-cal-title', MONTHS[this.viewM] + ' ' + this.viewY);
        var next = el('button', 'cmp-cal-nav', '›');
        prev.type = next.type = 'button';
        prev.onclick = function () { self.shiftMonth(-1); };
        next.onclick = function () { self.shiftMonth(1); };
        head.append(prev, title, next);
        frag.appendChild(head);

        var grid = document.createElement('div');
        grid.className = 'cmp-cal-grid';
        DOW.forEach(function (d) { grid.appendChild(el('div', 'cmp-cal-dow', d)); });

        var first = new Date(this.viewY, this.viewM, 1).getDay();
        var days = new Date(this.viewY, this.viewM + 1, 0).getDate();
        var today = new Date();
        for (var i = 0; i < first; i++) { grid.appendChild(el('div', 'cmp-cal-day', '')); }
        for (var day = 1; day <= days; day++) {
            var cell = el('button', 'cmp-cal-day', String(day));
            cell.type = 'button';
            var dd = day;
            if (sameDay(today, this.viewY, this.viewM, dd)) { cell.classList.add('is-today'); }
            if (this.sel && sameDay(this.sel, this.viewY, this.viewM, dd)) { cell.classList.add('is-selected'); }
            cell.onclick = (function (d) { return function () { self.pickDay(d); }; })(dd);
            grid.appendChild(cell);
        }
        frag.appendChild(grid);
        return frag;
    };

    DatePicker.prototype.buildTime = function () {
        var self = this;
        var row = document.createElement('div');
        row.className = 'cmp-time';
        row.appendChild(el('label', '', 'Time'));
        var h = document.createElement('select');
        var m = document.createElement('select');
        var cur = this.sel || new Date();
        for (var i = 0; i < 24; i++) { h.appendChild(opt(pad(i), this.sel && cur.getHours() === i)); }
        for (var j = 0; j < 60; j += 5) { m.appendChild(opt(pad(j), this.sel && cur.getMinutes() === j)); }
        h.onchange = m.onchange = function () {
            if (!self.sel) { self.sel = new Date(); self.sel.setSeconds(0, 0); }
            self.sel.setHours(+h.value, +m.value, 0, 0);
            self.commit(false);
        };
        row.append(h, el('span', '', ':'), m);
        return row;
    };

    DatePicker.prototype.buildActions = function () {
        var self = this;
        var row = document.createElement('div');
        row.className = 'cmp-cal-actions';
        var clear = el('button', 'cmp-cal-clear', 'Clear');
        var now = el('button', 'cmp-cal-today', this.mode === 'time' ? 'Now' : 'Today');
        clear.type = now.type = 'button';
        clear.onclick = function () { self.sel = null; self.commit(true); };
        now.onclick = function () {
            self.sel = new Date(); self.sel.setSeconds(0, 0);
            self.viewY = self.sel.getFullYear(); self.viewM = self.sel.getMonth();
            self.commit(self.mode === 'date');
        };
        row.append(clear, now);
        return row;
    };

    DatePicker.prototype.shiftMonth = function (delta) {
        this.viewM += delta;
        if (this.viewM < 0) { this.viewM = 11; this.viewY--; }
        else if (this.viewM > 11) { this.viewM = 0; this.viewY++; }
        this.renderPanel();
    };

    DatePicker.prototype.pickDay = function (day) {
        var t = this.sel || new Date(0);
        this.sel = new Date(this.viewY, this.viewM, day, this.hasTime ? t.getHours() : 0, this.hasTime ? t.getMinutes() : 0, 0, 0);
        this.commit(this.mode === 'date');
    };

    function sameDay(d, y, m, day) { return d.getFullYear() === y && d.getMonth() === m && d.getDate() === day; }
    function el(tag, cls, text) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (text != null) { e.textContent = text; } return e; }
    function opt(v, selected) { var o = document.createElement('option'); o.value = v; o.textContent = v; o.selected = !!selected; return o; }

    /* ================= init ================= */
    function init(scope) {
        (scope || document).querySelectorAll('[data-cmp-select]').forEach(function (n) {
            if (!n._cmp) { n._cmp = new Select(n); }
        });
        (scope || document).querySelectorAll('[data-cmp-date]').forEach(function (n) {
            if (!n._cmp) { n._cmp = new DatePicker(n); }
        });
    }

    document.addEventListener('DOMContentLoaded', function () { init(document); });
    window.PegasusComponents = { init: init };
})();
