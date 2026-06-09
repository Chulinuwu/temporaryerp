/**
 * PEGASUS ERP - BI Dashboard Builder
 * Vanilla JS — Chart.js 4.x integration
 */
(function () {
    'use strict';

    // ── State ──
    let schema = null;           // whitelist from /api/bi/schema
    let charts = new Map();      // widgetId → Chart instance
    let widgetMeta = new Map();  // widgetId → { dataConfig, chartOptions, ... }
    let nextTempId = -1;         // temp IDs for unsaved widgets
    const COLORS = [
        '#003366','#0066cc','#3399ff','#66ccff',
        '#ff6600','#ff9933','#ffcc00','#33cc66',
        '#cc3333','#9933cc','#ff6699','#339999',
    ];

    const isViewMode = window.BI_VIEW_MODE || false;
    const dashboardId = window.BI_DASHBOARD_ID || 0;
    const csrf = window.BI_CSRF || '';
    const labels = window.BI_LABELS || {};

    // ════════════════════════════════════════════════════════════
    //  Init
    // ════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', async function () {
        await loadSchema();
        loadExistingWidgets();
        if (!isViewMode) {
            initPaletteDrag();
            initCanvasDrop();
            initToolbar();
            initConfigPanel();
        }
        updateEmptyState();
    });

    async function loadSchema() {
        try {
            const res = await fetch('/api/bi/schema');
            schema = await res.json();
        } catch (e) {
            console.error('Failed to load BI schema', e);
            schema = { tables: {}, aggregates: [], operators: [], date_transforms: [] };
        }
    }

    function loadExistingWidgets() {
        const widgetsData = window.BI_WIDGETS || [];
        widgetsData.forEach(function (w) {
            var dc = w.data_config;
            if (typeof dc === 'string') dc = JSON.parse(dc);
            var co = w.chart_options;
            if (typeof co === 'string') co = JSON.parse(co);

            widgetMeta.set(w.bi_widget_id, {
                bi_widget_id: w.bi_widget_id,
                widget_name: w.widget_name,
                chart_type: w.chart_type,
                data_config: dc || {},
                chart_options: co || {},
                grid_x: w.grid_x,
                grid_y: w.grid_y,
                grid_w: w.grid_w,
                grid_h: w.grid_h,
            });

            createWidgetElement(w.bi_widget_id, w.widget_name, w.chart_type,
                w.grid_x, w.grid_y, w.grid_w, w.grid_h);

            // Auto-load chart data
            if (dc && dc.source_table) {
                fetchAndRender(w.bi_widget_id);
            }
        });
    }

    // ════════════════════════════════════════════════════════════
    //  Palette Drag
    // ════════════════════════════════════════════════════════════
    function initPaletteDrag() {
        document.querySelectorAll('.bi-palette-item').forEach(function (item) {
            item.addEventListener('dragstart', function (e) {
                e.dataTransfer.setData('text/plain', item.dataset.chartType);
                e.dataTransfer.effectAllowed = 'copy';
                var grid = document.getElementById('biGrid');
                if (grid) grid.classList.add('bi-drag-active');
            });
            item.addEventListener('dragend', function () {
                var grid = document.getElementById('biGrid');
                if (grid) grid.classList.remove('bi-drag-active');
            });
        });
    }

    // ════════════════════════════════════════════════════════════
    //  Canvas Drop
    // ════════════════════════════════════════════════════════════
    function initCanvasDrop() {
        var canvas = document.getElementById('biCanvas');
        if (!canvas) return;

        canvas.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });

        canvas.addEventListener('drop', function (e) {
            e.preventDefault();
            var grid = document.getElementById('biGrid');
            if (grid) grid.classList.remove('bi-drag-active');

            var chartType = e.dataTransfer.getData('text/plain');
            if (!chartType) return;

            // Check if it's a palette drop (new widget) vs widget move
            if (['bar','line','pie','doughnut','area','horizontalBar','kpi','table'].indexOf(chartType) === -1) return;

            addNewWidget(chartType);
        });
    }

    function addNewWidget(chartType) {
        var id = nextTempId--;
        var name = (labels.untitled || 'Untitled') + ' ' + chartType;
        var gridPos = findNextGridPosition();

        widgetMeta.set(id, {
            bi_widget_id: id,
            widget_name: name,
            chart_type: chartType,
            data_config: {},
            chart_options: {},
            grid_x: gridPos.x,
            grid_y: gridPos.y,
            grid_w: 6,
            grid_h: 4,
        });

        createWidgetElement(id, name, chartType, gridPos.x, gridPos.y, 6, 4);
        updateEmptyState();
        openConfig(id);
    }

    function findNextGridPosition() {
        var maxY = 0;
        widgetMeta.forEach(function (m) {
            var bottom = m.grid_y + m.grid_h;
            if (bottom > maxY) maxY = bottom;
        });
        return { x: 0, y: maxY };
    }

    // ════════════════════════════════════════════════════════════
    //  Widget DOM
    // ════════════════════════════════════════════════════════════
    function createWidgetElement(id, name, chartType, gx, gy, gw, gh) {
        var grid = document.getElementById('biGrid');
        if (!grid) return;

        var el = document.createElement('div');
        el.className = 'bi-widget';
        el.dataset.widgetId = id;
        el.style.gridColumn = (gx + 1) + ' / span ' + gw;
        el.style.gridRow = (gy + 1) + ' / span ' + gh;

        var headerHtml = '<div class="bi-widget-header"' + (!isViewMode ? ' draggable="true"' : '') + '>'
            + '<span class="bi-widget-title">' + escapeHtml(name) + '</span>';

        if (!isViewMode) {
            headerHtml += '<div class="bi-widget-actions">'
                + '<button class="bi-widget-btn bi-widget-edit" title="Configure">&#9881;</button>'
                + '<button class="bi-widget-btn bi-widget-delete" title="Delete">&times;</button>'
                + '</div>';
        }
        headerHtml += '</div>';

        var bodyHtml = '<div class="bi-widget-body">';
        if (chartType === 'kpi') {
            bodyHtml += '<div><div class="bi-kpi-value">--</div><div class="bi-kpi-label">' + escapeHtml(name) + '</div></div>';
        } else if (chartType === 'table') {
            bodyHtml += '<div class="bi-table-widget"><p style="color:#aaa;text-align:center">' + (labels.noData || 'No data') + '</p></div>';
        } else {
            bodyHtml += '<canvas id="chart_' + id + '"></canvas>';
        }
        bodyHtml += '</div>';

        var resizeHtml = !isViewMode ? '<div class="bi-widget-resize"></div>' : '';

        el.innerHTML = headerHtml + bodyHtml + resizeHtml;
        grid.appendChild(el);

        // Event listeners
        if (!isViewMode) {
            var editBtn = el.querySelector('.bi-widget-edit');
            if (editBtn) editBtn.addEventListener('click', function () { openConfig(id); });

            var deleteBtn = el.querySelector('.bi-widget-delete');
            if (deleteBtn) deleteBtn.addEventListener('click', function () { deleteWidget(id); });

            initWidgetDrag(el, id);
            initWidgetResize(el, id);
        }
    }

    // ════════════════════════════════════════════════════════════
    //  Widget Drag (move)
    // ════════════════════════════════════════════════════════════
    function initWidgetDrag(el, widgetId) {
        var header = el.querySelector('.bi-widget-header');
        if (!header) return;

        header.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', 'move:' + widgetId);
            e.dataTransfer.effectAllowed = 'move';
            el.style.opacity = '0.5';
        });
        header.addEventListener('dragend', function () {
            el.style.opacity = '1';
        });
    }

    // ════════════════════════════════════════════════════════════
    //  Widget Resize
    // ════════════════════════════════════════════════════════════
    function initWidgetResize(el, widgetId) {
        var handle = el.querySelector('.bi-widget-resize');
        if (!handle) return;

        handle.addEventListener('mousedown', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var meta = widgetMeta.get(widgetId);
            if (!meta) return;

            var startX = e.clientX;
            var startY = e.clientY;
            var startW = meta.grid_w;
            var startH = meta.grid_h;

            var grid = document.getElementById('biGrid');
            var cellW = grid.offsetWidth / 12;
            var cellH = 60 + 12; // row height + gap

            function onMove(ev) {
                var dx = ev.clientX - startX;
                var dy = ev.clientY - startY;
                var newW = Math.max(2, Math.min(12, startW + Math.round(dx / cellW)));
                var newH = Math.max(2, startH + Math.round(dy / cellH));
                meta.grid_w = newW;
                meta.grid_h = newH;
                el.style.gridColumn = (meta.grid_x + 1) + ' / span ' + newW;
                el.style.gridRow = (meta.grid_y + 1) + ' / span ' + newH;
            }

            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                // Re-render chart to fit new size
                var chart = charts.get(widgetId);
                if (chart) chart.resize();
            }

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    // ════════════════════════════════════════════════════════════
    //  Delete Widget
    // ════════════════════════════════════════════════════════════
    function deleteWidget(id) {
        if (!confirm(labels.confirmDelete || 'Delete this widget?')) return;

        var el = document.querySelector('[data-widget-id="' + id + '"]');
        if (el) el.remove();

        var chart = charts.get(id);
        if (chart) { chart.destroy(); charts.delete(id); }
        widgetMeta.delete(id);

        // Server delete if persisted
        if (id > 0) {
            fetch('/api/bi/widgets/' + id + '/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
        }
        updateEmptyState();
    }

    // ════════════════════════════════════════════════════════════
    //  Config Panel
    // ════════════════════════════════════════════════════════════
    function initConfigPanel() {
        var closeBtn = document.getElementById('biConfigClose');
        if (closeBtn) closeBtn.addEventListener('click', closeConfig);

        var previewBtn = document.getElementById('cfgPreview');
        if (previewBtn) previewBtn.addEventListener('click', previewWidget);

        var saveBtn = document.getElementById('cfgSaveWidget');
        if (saveBtn) saveBtn.addEventListener('click', saveCurrentWidget);

        var tableSelect = document.getElementById('cfgSourceTable');
        if (tableSelect) tableSelect.addEventListener('change', onTableChange);

        var xColSelect = document.getElementById('cfgXColumn');
        if (xColSelect) xColSelect.addEventListener('change', onXColumnChange);

        var addFilterBtn = document.getElementById('cfgAddFilter');
        if (addFilterBtn) addFilterBtn.addEventListener('click', addFilterRow);

        // Populate table dropdown
        populateTableDropdown();
    }

    function populateTableDropdown() {
        var sel = document.getElementById('cfgSourceTable');
        if (!sel || !schema) return;
        sel.innerHTML = '<option value="">-- Select --</option>';
        Object.keys(schema.tables).forEach(function (key) {
            var opt = document.createElement('option');
            opt.value = key;
            opt.textContent = schema.tables[key].label;
            sel.appendChild(opt);
        });
    }

    function onTableChange() {
        var table = document.getElementById('cfgSourceTable').value;
        populateColumnDropdowns(table);
    }

    function populateColumnDropdowns(table) {
        var xSel = document.getElementById('cfgXColumn');
        var ySel = document.getElementById('cfgYColumn');
        var sSel = document.getElementById('cfgSeriesColumn');
        if (!xSel || !ySel || !sSel) return;

        xSel.innerHTML = '<option value="">-- Select --</option>';
        ySel.innerHTML = '<option value="">-- Count --</option>';
        sSel.innerHTML = '<option value="">-- None --</option>';

        if (!table || !schema.tables[table]) return;
        var cols = schema.tables[table].columns;
        var joins = schema.tables[table].joins || {};

        // All columns for X and Series
        Object.keys(cols).forEach(function (k) {
            var c = cols[k];
            var optX = document.createElement('option');
            optX.value = k;
            optX.textContent = c.label + ' (' + c.type + ')';
            xSel.appendChild(optX);

            if (c.type === 'string' || c.type === 'integer') {
                var optS = document.createElement('option');
                optS.value = k;
                optS.textContent = c.label;
                sSel.appendChild(optS);
            }

            if (c.aggregatable) {
                var optY = document.createElement('option');
                optY.value = k;
                optY.textContent = c.label;
                ySel.appendChild(optY);
            }
        });

        // Join columns for series
        Object.keys(joins).forEach(function (jk) {
            var jCols = joins[jk].columns;
            Object.keys(jCols).forEach(function (jck) {
                var optS = document.createElement('option');
                optS.value = jck;
                optS.textContent = jCols[jck].label + ' (join)';
                sSel.appendChild(optS);
            });
        });
    }

    function onXColumnChange() {
        var table = document.getElementById('cfgSourceTable').value;
        var col = document.getElementById('cfgXColumn').value;
        var transformGrp = document.getElementById('cfgXTransformGroup');
        if (!table || !col || !schema.tables[table]) {
            if (transformGrp) transformGrp.style.display = 'none';
            return;
        }
        var colDef = schema.tables[table].columns[col];
        if (colDef && colDef.groupable) {
            if (transformGrp) transformGrp.style.display = '';
        } else {
            if (transformGrp) transformGrp.style.display = 'none';
        }
    }

    function openConfig(widgetId) {
        var panel = document.getElementById('biConfigPanel');
        if (!panel) return;
        panel.style.display = '';

        var meta = widgetMeta.get(widgetId);
        if (!meta) return;

        document.getElementById('cfgWidgetId').value = widgetId;
        document.getElementById('cfgWidgetName').value = meta.widget_name || '';
        document.getElementById('cfgChartType').value = meta.chart_type || 'bar';

        var dc = meta.data_config || {};
        document.getElementById('cfgSourceTable').value = dc.source_table || '';
        onTableChange(); // populate columns

        setTimeout(function () {
            document.getElementById('cfgXColumn').value = (dc.x_axis && dc.x_axis.column) || '';
            onXColumnChange();
            document.getElementById('cfgXTransform').value = (dc.x_axis && dc.x_axis.transform) || '';
            document.getElementById('cfgYColumn').value = (dc.y_axis && dc.y_axis.column) || '';
            document.getElementById('cfgAggregate').value = (dc.y_axis && dc.y_axis.aggregate) || 'SUM';
            document.getElementById('cfgSeriesColumn').value = (dc.series && dc.series.column) || '';

            // Filters
            var filtersDiv = document.getElementById('cfgFilters');
            filtersDiv.innerHTML = '';
            (dc.filters || []).forEach(function (f) {
                addFilterRow(null, f);
            });
        }, 50);
    }

    function closeConfig() {
        var panel = document.getElementById('biConfigPanel');
        if (panel) panel.style.display = 'none';
    }

    // ── Filter Rows ──
    function addFilterRow(e, existingFilter) {
        var filtersDiv = document.getElementById('cfgFilters');
        if (!filtersDiv) return;

        var table = document.getElementById('cfgSourceTable').value;
        var cols = (schema && schema.tables[table]) ? schema.tables[table].columns : {};

        var row = document.createElement('div');
        row.className = 'bi-filter-row';

        // Column select
        var colSel = document.createElement('select');
        colSel.innerHTML = '<option value="">Column</option>';
        Object.keys(cols).forEach(function (k) {
            var opt = document.createElement('option');
            opt.value = k;
            opt.textContent = cols[k].label;
            if (existingFilter && existingFilter.column === k) opt.selected = true;
            colSel.appendChild(opt);
        });

        // Operator select
        var opSel = document.createElement('select');
        ['=','!=','>','>=','<','<=','LIKE','IN'].forEach(function (op) {
            var opt = document.createElement('option');
            opt.value = op;
            opt.textContent = op;
            if (existingFilter && existingFilter.operator === op) opt.selected = true;
            opSel.appendChild(opt);
        });

        // Value input
        var valInput = document.createElement('input');
        valInput.type = 'text';
        valInput.placeholder = 'Value';
        if (existingFilter) {
            valInput.value = Array.isArray(existingFilter.value)
                ? existingFilter.value.join(',')
                : (existingFilter.value || '');
        }

        // Remove button
        var removeBtn = document.createElement('button');
        removeBtn.className = 'bi-filter-remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.addEventListener('click', function () { row.remove(); });

        row.appendChild(colSel);
        row.appendChild(opSel);
        row.appendChild(valInput);
        row.appendChild(removeBtn);
        filtersDiv.appendChild(row);
    }

    function collectFilters() {
        var rows = document.querySelectorAll('#cfgFilters .bi-filter-row');
        var filters = [];
        rows.forEach(function (row) {
            var selects = row.querySelectorAll('select');
            var input = row.querySelector('input');
            var col = selects[0] ? selects[0].value : '';
            var op = selects[1] ? selects[1].value : '=';
            var val = input ? input.value : '';
            if (!col || val === '') return;

            if (op === 'IN') {
                val = val.split(',').map(function (v) { return v.trim(); });
            }
            filters.push({ column: col, operator: op, value: val });
        });
        return filters;
    }

    function buildDataConfig() {
        var table = document.getElementById('cfgSourceTable').value;
        var xCol = document.getElementById('cfgXColumn').value;
        var xTrans = document.getElementById('cfgXTransform').value;
        var yCol = document.getElementById('cfgYColumn').value;
        var agg = document.getElementById('cfgAggregate').value;
        var sCol = document.getElementById('cfgSeriesColumn').value;

        var dc = { source_table: table };
        if (xCol) {
            dc.x_axis = { column: xCol };
            if (xTrans) dc.x_axis.transform = xTrans;
        }
        if (yCol) {
            dc.y_axis = { column: yCol, aggregate: agg };
        } else {
            dc.y_axis = { column: '', aggregate: 'COUNT' };
        }
        if (sCol) {
            dc.series = { column: sCol };
        }

        // Detect needed joins
        if (schema.tables[table] && schema.tables[table].joins) {
            var joins = [];
            var joinDefs = schema.tables[table].joins;
            [sCol].forEach(function (c) {
                if (!c) return;
                Object.keys(joinDefs).forEach(function (jk) {
                    if (joinDefs[jk].columns[c] && joins.indexOf(jk) === -1) {
                        joins.push(jk);
                    }
                });
            });
            if (joins.length) dc.joins = joins;
        }

        dc.filters = collectFilters();

        // Global date range
        var ds = document.getElementById('biGlobalDateStart');
        var de = document.getElementById('biGlobalDateEnd');
        if (ds && ds.value && xCol) {
            dc.filters.push({ column: xCol, operator: '>=', value: ds.value });
        }
        if (de && de.value && xCol) {
            dc.filters.push({ column: xCol, operator: '<=', value: de.value });
        }

        return dc;
    }

    // ════════════════════════════════════════════════════════════
    //  Preview & Save
    // ════════════════════════════════════════════════════════════
    function previewWidget() {
        var widgetId = parseInt(document.getElementById('cfgWidgetId').value);
        var meta = widgetMeta.get(widgetId);
        if (!meta) return;

        meta.widget_name = document.getElementById('cfgWidgetName').value || 'Widget';
        meta.chart_type = document.getElementById('cfgChartType').value;
        meta.data_config = buildDataConfig();

        // Update title
        var el = document.querySelector('[data-widget-id="' + widgetId + '"]');
        if (el) {
            var titleEl = el.querySelector('.bi-widget-title');
            if (titleEl) titleEl.textContent = meta.widget_name;
        }

        fetchAndRender(widgetId);
    }

    async function saveCurrentWidget() {
        var widgetId = parseInt(document.getElementById('cfgWidgetId').value);
        var meta = widgetMeta.get(widgetId);
        if (!meta) return;

        meta.widget_name = document.getElementById('cfgWidgetName').value || 'Widget';
        meta.chart_type = document.getElementById('cfgChartType').value;
        meta.data_config = buildDataConfig();

        var body = {
            bi_dashboard_id: dashboardId,
            widget_name: meta.widget_name,
            chart_type: meta.chart_type,
            data_config: meta.data_config,
            chart_options: meta.chart_options,
            grid_x: meta.grid_x,
            grid_y: meta.grid_y,
            grid_w: meta.grid_w,
            grid_h: meta.grid_h,
        };
        if (widgetId > 0) body.bi_widget_id = widgetId;

        try {
            var res = await fetch('/api/bi/widgets', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            var result = await res.json();

            if (result.bi_widget_id && widgetId < 0) {
                // Update temp ID to real ID
                var newId = result.bi_widget_id;
                var el = document.querySelector('[data-widget-id="' + widgetId + '"]');
                if (el) el.dataset.widgetId = newId;

                var chart = charts.get(widgetId);
                if (chart) { charts.set(newId, chart); charts.delete(widgetId); }

                widgetMeta.set(newId, meta);
                widgetMeta.delete(widgetId);
                meta.bi_widget_id = newId;

                document.getElementById('cfgWidgetId').value = newId;

                // Re-create canvas id
                var canvas = el ? el.querySelector('canvas') : null;
                if (canvas) canvas.id = 'chart_' + newId;
            }

            // Update title
            var titleEl = document.querySelector('[data-widget-id="' + (result.bi_widget_id || widgetId) + '"] .bi-widget-title');
            if (titleEl) titleEl.textContent = meta.widget_name;

            fetchAndRender(result.bi_widget_id || widgetId);
            showFlash('success', 'Widget saved');
        } catch (e) {
            console.error('Save widget error', e);
            showFlash('error', 'Failed to save widget');
        }
    }

    // ════════════════════════════════════════════════════════════
    //  Fetch Data & Render Chart
    // ════════════════════════════════════════════════════════════
    async function fetchAndRender(widgetId) {
        var meta = widgetMeta.get(widgetId);
        if (!meta || !meta.data_config || !meta.data_config.source_table) return;

        // Apply global date filters
        var dc = JSON.parse(JSON.stringify(meta.data_config));
        var ds = document.getElementById('biGlobalDateStart');
        var de = document.getElementById('biGlobalDateEnd');
        if (!dc.filters) dc.filters = [];

        try {
            var res = await fetch('/api/bi/query', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(dc),
            });
            var data = await res.json();
            if (data.error) {
                console.error('Query error:', data.error);
                return;
            }
            renderChart(widgetId, meta.chart_type, data, meta.widget_name);
        } catch (e) {
            console.error('Fetch error', e);
        }
    }

    function renderChart(widgetId, chartType, data, widgetName) {
        // Destroy existing
        var existing = charts.get(widgetId);
        if (existing) { existing.destroy(); charts.delete(widgetId); }

        var el = document.querySelector('[data-widget-id="' + widgetId + '"]');
        if (!el) return;

        if (chartType === 'kpi') {
            renderKPI(el, data, widgetName);
            return;
        }
        if (chartType === 'table') {
            renderTable(el, data);
            return;
        }

        var canvas = el.querySelector('canvas');
        if (!canvas) {
            // Recreate canvas if missing
            var body = el.querySelector('.bi-widget-body');
            if (body) {
                body.innerHTML = '<canvas id="chart_' + widgetId + '"></canvas>';
                canvas = body.querySelector('canvas');
            }
        }
        if (!canvas) return;

        var ctx = canvas.getContext('2d');
        var type = chartType;
        var fill = false;

        if (chartType === 'area') {
            type = 'line';
            fill = true;
        }
        if (chartType === 'horizontalBar') {
            type = 'bar';
        }

        var datasets = (data.datasets || []).map(function (ds, i) {
            var color = COLORS[i % COLORS.length];
            var d = {
                label: ds.label || '',
                data: ds.data || [],
                backgroundColor: (type === 'pie' || type === 'doughnut')
                    ? ds.data.map(function (_, j) { return COLORS[j % COLORS.length]; })
                    : color,
                borderColor: (type === 'line') ? color : undefined,
                borderWidth: (type === 'line') ? 2 : 1,
                fill: fill,
            };
            return d;
        });

        var options = {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: (chartType === 'horizontalBar') ? 'y' : 'x',
            plugins: {
                legend: { display: datasets.length > 1 || type === 'pie' || type === 'doughnut' },
            },
            scales: {}
        };

        if (type !== 'pie' && type !== 'doughnut') {
            options.scales = {
                x: { grid: { display: false } },
                y: { beginAtZero: true },
            };
        }

        var chart = new Chart(ctx, {
            type: type,
            data: { labels: data.labels || [], datasets: datasets },
            options: options,
        });

        charts.set(widgetId, chart);
    }

    function renderKPI(el, data, name) {
        var body = el.querySelector('.bi-widget-body');
        if (!body) return;
        var val = 0;
        if (data.datasets && data.datasets[0] && data.datasets[0].data) {
            val = data.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);
        }
        body.innerHTML = '<div>'
            + '<div class="bi-kpi-value">' + formatNumber(val) + '</div>'
            + '<div class="bi-kpi-label">' + escapeHtml(name) + '</div>'
            + '</div>';
    }

    function renderTable(el, data) {
        var body = el.querySelector('.bi-widget-body');
        if (!body) return;
        if (!data.labels || !data.labels.length) {
            body.innerHTML = '<div class="bi-table-widget"><p style="color:#aaa;text-align:center">' + (labels.noData || 'No data') + '</p></div>';
            return;
        }

        var html = '<div class="bi-table-widget"><table><thead><tr><th>Label</th>';
        (data.datasets || []).forEach(function (ds) {
            html += '<th>' + escapeHtml(ds.label || 'Value') + '</th>';
        });
        html += '</tr></thead><tbody>';
        data.labels.forEach(function (lbl, i) {
            html += '<tr><td>' + escapeHtml(lbl) + '</td>';
            (data.datasets || []).forEach(function (ds) {
                html += '<td>' + formatNumber(ds.data[i] || 0) + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        body.innerHTML = html;
    }

    // ════════════════════════════════════════════════════════════
    //  Toolbar
    // ════════════════════════════════════════════════════════════
    function initToolbar() {
        var saveLayoutBtn = document.getElementById('biSaveLayout');
        if (saveLayoutBtn) saveLayoutBtn.addEventListener('click', saveLayout);

        var refreshBtn = document.getElementById('biRefreshAll');
        if (refreshBtn) refreshBtn.addEventListener('click', refreshAll);

        var nameInput = document.getElementById('biDashboardName');
        if (nameInput) {
            nameInput.addEventListener('change', function () {
                saveDashboardName(nameInput.value);
            });
        }
    }

    async function saveLayout() {
        if (!dashboardId) return;
        var widgets = [];
        widgetMeta.forEach(function (m, id) {
            if (id > 0) {
                widgets.push({
                    bi_widget_id: id,
                    grid_x: m.grid_x,
                    grid_y: m.grid_y,
                    grid_w: m.grid_w,
                    grid_h: m.grid_h,
                });
            }
        });

        try {
            await fetch('/api/bi/dashboards/' + dashboardId + '/layout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ widgets: widgets }),
            });
            showFlash('success', 'Layout saved');
        } catch (e) {
            showFlash('error', 'Failed to save layout');
        }
    }

    async function saveDashboardName(name) {
        if (!dashboardId) return;
        var form = new FormData();
        form.append('_csrf_token', csrf);
        form.append('dashboard_name', name);

        try {
            await fetch('/bi/dashboards/' + dashboardId, { method: 'POST', body: form });
        } catch (e) {
            console.error('Save name error', e);
        }
    }

    function refreshAll() {
        widgetMeta.forEach(function (m, id) {
            fetchAndRender(id);
        });
    }

    // ════════════════════════════════════════════════════════════
    //  Helpers
    // ════════════════════════════════════════════════════════════
    function updateEmptyState() {
        var empty = document.getElementById('biCanvasEmpty');
        if (!empty) return;
        empty.style.display = widgetMeta.size > 0 ? 'none' : '';
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function formatNumber(n) {
        if (typeof n !== 'number') n = parseFloat(n) || 0;
        return n.toLocaleString(undefined, { maximumFractionDigits: 2 });
    }

    function showFlash(type, message) {
        var existing = document.querySelector('.bi-flash');
        if (existing) existing.remove();

        var div = document.createElement('div');
        div.className = 'alert alert-' + type + ' bi-flash';
        div.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;min-width:200px;';
        div.innerHTML = '<span>' + escapeHtml(message) + '</span>'
            + '<button class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
        document.body.appendChild(div);
        setTimeout(function () { if (div.parentNode) div.remove(); }, 3000);
    }

})();
