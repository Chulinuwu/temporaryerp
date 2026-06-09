<?php
    $dashboardId = $dashboard['bi_dashboard_id'] ?? 0;
    $widgetsJson = json_encode($widgets ?? [], JSON_UNESCAPED_UNICODE);
?>

<link rel="stylesheet" href="<?= asset('css/bi-builder.css') ?>">

<div class="bi-builder">
    <div class="bi-toolbar">
        <div class="bi-toolbar-left">
            <a href="/bi/dashboards" class="btn btn-sm">&larr; <?= _e('bi_back_to_list') ?></a>
            <h2 style="margin:0 0 0 16px;"><?= htmlspecialchars($dashboard['dashboard_name']) ?></h2>
        </div>
        <div class="bi-toolbar-right">
            <div class="bi-toolbar-daterange">
                <label><?= _e('bi_date_from') ?>:</label>
                <input type="date" id="biGlobalDateStart">
                <label><?= _e('bi_date_to') ?>:</label>
                <input type="date" id="biGlobalDateEnd">
                <button class="btn btn-sm" id="biRefreshAll"><?= _e('bi_refresh') ?></button>
            </div>
            <?php
                $user = Auth::user();
                if ((int)$dashboard['owner_user_id'] === (int)$user['user_id'] || Auth::isAdmin()):
            ?>
                <a href="/bi/dashboards/<?= $dashboardId ?>/edit" class="btn btn-sm btn-primary"><?= _e('bi_edit') ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bi-builder-body">
        <div class="bi-canvas bi-canvas-view">
            <div class="bi-grid" id="biGrid">
                <!-- Widgets rendered by JS -->
            </div>
        </div>
    </div>
</div>

<script>
    window.BI_DASHBOARD_ID = <?= (int)$dashboardId ?>;
    window.BI_WIDGETS = <?= $widgetsJson ?>;
    window.BI_CSRF = '<?= csrf_token() ?>';
    window.BI_VIEW_MODE = true;
    window.BI_LABELS = {
        value: '<?= _e('bi_value') ?>',
        noData: '<?= _e('bi_no_data') ?>',
    };
</script>
<script src="<?= asset('js/bi-builder.js') ?>"></script>
