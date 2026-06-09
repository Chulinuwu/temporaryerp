<?php
    $dashboardId   = $dashboard['bi_dashboard_id'] ?? 0;
    $dashboardName = $dashboard['dashboard_name'] ?? '';
    $widgetsJson   = json_encode($widgets ?? [], JSON_UNESCAPED_UNICODE);
?>

<link rel="stylesheet" href="<?= asset('css/bi-builder.css') ?>">

<?php if ($isNew && !$dashboardId): ?>
<!-- New Dashboard: Name Entry -->
<div class="card" style="max-width:500px;margin:60px auto;padding:32px;">
    <h2><?= _e('bi_new_dashboard') ?></h2>
    <form method="POST" action="/bi/dashboards">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group" style="margin:16px 0;">
            <label><?= _e('bi_dashboard_name') ?></label>
            <input type="text" name="dashboard_name" class="form-control" required autofocus
                   placeholder="<?= _e('bi_dashboard_name') ?>">
        </div>
        <div class="form-group" style="margin:16px 0;">
            <label><?= __('description') ?></label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="/bi/dashboards" class="btn"><?= _e('cancel') ?></a>
            <button type="submit" class="btn btn-primary"><?= _e('bi_create_first') ?></button>
        </div>
    </form>
</div>
<?php return; endif; ?>

<div class="bi-builder">
    <!-- ═══ Top Toolbar ═══ -->
    <div class="bi-toolbar">
        <div class="bi-toolbar-left">
            <a href="/bi/dashboards" class="btn btn-sm">&larr; <?= _e('bi_back_to_list') ?></a>
            <?php if ($dashboardId): ?>
                <input type="text" id="biDashboardName" class="bi-toolbar-name"
                       value="<?= htmlspecialchars($dashboardName) ?>"
                       placeholder="<?= _e('bi_dashboard_name') ?>">
            <?php endif; ?>
        </div>
        <div class="bi-toolbar-right">
            <div class="bi-toolbar-daterange">
                <label><?= _e('bi_date_from') ?>:</label>
                <input type="date" id="biGlobalDateStart">
                <label><?= _e('bi_date_to') ?>:</label>
                <input type="date" id="biGlobalDateEnd">
                <button class="btn btn-sm" id="biRefreshAll"><?= _e('bi_refresh') ?></button>
            </div>
            <?php if ($dashboardId): ?>
                <button class="btn btn-sm btn-primary" id="biSaveLayout"><?= _e('bi_save_layout') ?></button>
                <a href="/bi/dashboards/<?= $dashboardId ?>" class="btn btn-sm"><?= _e('bi_view_mode') ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bi-builder-body">
        <!-- ═══ Left: Chart Palette ═══ -->
        <div class="bi-palette">
            <div class="bi-palette-title"><?= _e('bi_chart_types') ?></div>

            <div class="bi-palette-item" draggable="true" data-chart-type="bar">
                <div class="bi-palette-icon">&#9646;&#9646;&#9646;</div>
                <div class="bi-palette-label"><?= _e('bi_chart_bar') ?></div>
            </div>
            <div class="bi-palette-item" draggable="true" data-chart-type="line">
                <div class="bi-palette-icon">&#10138;</div>
                <div class="bi-palette-label"><?= _e('bi_chart_line') ?></div>
            </div>
            <div class="bi-palette-item" draggable="true" data-chart-type="pie">
                <div class="bi-palette-icon">&#9684;</div>
                <div class="bi-palette-label"><?= _e('bi_chart_pie') ?></div>
            </div>
            <div class="bi-palette-item" draggable="true" data-chart-type="doughnut">
                <div class="bi-palette-icon">&#9711;</div>
                <div class="bi-palette-label"><?= _e('bi_chart_doughnut') ?></div>
            </div>
            <div class="bi-palette-item" draggable="true" data-chart-type="area">
                <div class="bi-palette-icon">&#9650;</div>
                <div class="bi-palette-label"><?= _e('bi_chart_area') ?></div>
            </div>
            <div class="bi-palette-item" draggable="true" data-chart-type="horizontalBar">
                <div class="bi-palette-icon">&#9644;&#9644;&#9644;</div>
                <div class="bi-palette-label"><?= _e('bi_chart_hbar') ?></div>
            </div>
            <div class="bi-palette-item" draggable="true" data-chart-type="kpi">
                <div class="bi-palette-icon" style="font-size:20px;font-weight:700;">#</div>
                <div class="bi-palette-label"><?= _e('bi_chart_kpi') ?></div>
            </div>
            <div class="bi-palette-item" draggable="true" data-chart-type="table">
                <div class="bi-palette-icon">&#9638;</div>
                <div class="bi-palette-label"><?= _e('bi_chart_table') ?></div>
            </div>
        </div>

        <!-- ═══ Center: Grid Canvas ═══ -->
        <div class="bi-canvas" id="biCanvas">
            <div class="bi-grid" id="biGrid">
                <!-- Widgets are rendered here by JS -->
            </div>
            <div class="bi-canvas-empty" id="biCanvasEmpty">
                <p><?= _e('bi_drag_chart_here') ?></p>
            </div>
        </div>

        <!-- ═══ Right: Config Panel ═══ -->
        <div class="bi-config-panel" id="biConfigPanel" style="display:none;">
            <div class="bi-config-header">
                <h3><?= _e('bi_configure') ?></h3>
                <button class="bi-config-close" id="biConfigClose">&times;</button>
            </div>
            <div class="bi-config-body">
                <input type="hidden" id="cfgWidgetId">

                <div class="form-group">
                    <label><?= _e('bi_widget_name') ?></label>
                    <input type="text" id="cfgWidgetName" class="form-control">
                </div>

                <div class="form-group">
                    <label><?= _e('bi_chart_type') ?></label>
                    <select id="cfgChartType" class="form-control">
                        <option value="bar"><?= _e('bi_chart_bar') ?></option>
                        <option value="line"><?= _e('bi_chart_line') ?></option>
                        <option value="pie"><?= _e('bi_chart_pie') ?></option>
                        <option value="doughnut"><?= _e('bi_chart_doughnut') ?></option>
                        <option value="area"><?= _e('bi_chart_area') ?></option>
                        <option value="horizontalBar"><?= _e('bi_chart_hbar') ?></option>
                        <option value="kpi"><?= _e('bi_chart_kpi') ?></option>
                        <option value="table"><?= _e('bi_chart_table') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= _e('bi_data_source') ?></label>
                    <select id="cfgSourceTable" class="form-control">
                        <option value=""><?= _e('bi_select_table') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= _e('bi_x_axis') ?></label>
                    <select id="cfgXColumn" class="form-control"></select>
                </div>

                <div class="form-group" id="cfgXTransformGroup">
                    <label><?= _e('bi_date_group') ?></label>
                    <select id="cfgXTransform" class="form-control">
                        <option value=""><?= _e('bi_none') ?></option>
                        <option value="day"><?= _e('bi_by_day') ?></option>
                        <option value="week"><?= _e('bi_by_week') ?></option>
                        <option value="month"><?= _e('bi_by_month') ?></option>
                        <option value="quarter"><?= _e('bi_by_quarter') ?></option>
                        <option value="year"><?= _e('bi_by_year') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= _e('bi_y_axis') ?></label>
                    <select id="cfgYColumn" class="form-control"></select>
                </div>

                <div class="form-group">
                    <label><?= _e('bi_aggregate') ?></label>
                    <select id="cfgAggregate" class="form-control">
                        <option value="SUM">SUM</option>
                        <option value="COUNT">COUNT</option>
                        <option value="AVG">AVG</option>
                        <option value="MIN">MIN</option>
                        <option value="MAX">MAX</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= _e('bi_series') ?></label>
                    <select id="cfgSeriesColumn" class="form-control">
                        <option value=""><?= _e('bi_none') ?></option>
                    </select>
                </div>

                <!-- Filters -->
                <div class="form-group">
                    <label><?= _e('bi_filters') ?></label>
                    <div id="cfgFilters"></div>
                    <button class="btn btn-sm" id="cfgAddFilter" style="margin-top:8px;">+ <?= _e('bi_add_filter') ?></button>
                </div>

                <div class="bi-config-actions">
                    <button class="btn btn-primary" id="cfgPreview"><?= _e('bi_preview') ?></button>
                    <button class="btn btn-primary" id="cfgSaveWidget"><?= _e('save') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pass data to JS -->
<script>
    window.BI_DASHBOARD_ID = <?= (int)$dashboardId ?>;
    window.BI_WIDGETS = <?= $widgetsJson ?>;
    window.BI_CSRF = '<?= csrf_token() ?>';
    window.BI_LABELS = {
        value: '<?= _e('bi_value') ?>',
        noData: '<?= _e('bi_no_data') ?>',
        confirmDelete: '<?= _e('confirm_delete') ?>',
        untitled: '<?= _e('bi_untitled') ?>',
    };
</script>
<script src="<?= asset('js/bi-builder.js') ?>"></script>
