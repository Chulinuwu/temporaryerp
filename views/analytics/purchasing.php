<?php
/**
 * Analytics — Purchasing Dashboard
 * Variables: $filters, $bySupplier, $byMonth, $byStatus, $kpi
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('analytics_purchasing') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('analytics_purchasing') ?></span>
        </div>
    </div>
</div>

<div class="card" style="padding:12px 20px;margin-bottom:16px;">
    <form method="GET" action="/analytics/purchasing" style="display:flex;gap:10px;align-items:center;">
        <label><?= __('date_from') ?>:</label>
        <input type="date" name="date_from" class="form-input" value="<?= e($filters['date_from']) ?>" style="width:160px;">
        <label><?= __('date_to') ?>:</label>
        <input type="date" name="date_to" class="form-input" value="<?= e($filters['date_to']) ?>" style="width:160px;">
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px;">
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('total_pos') ?></div>
        <div style="font-size:24px;font-weight:700;color:var(--color-primary);"><?= intval($kpi['total_po']) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('total_amount') ?></div>
        <div style="font-size:20px;font-weight:700;"><?= formatMoney($kpi['total_amount']) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('supplier_count') ?></div>
        <div style="font-size:24px;font-weight:700;"><?= intval($kpi['supplier_cnt']) ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    <div class="card" style="padding:12px 14px;">
        <h3 style="margin-bottom:6px;font-size:14px;"><?= __('by_status') ?></h3>
        <div style="position:relative;height:240px;"><canvas id="chartStatus"></canvas></div>
    </div>
    <div class="card" style="padding:12px 14px;">
        <h3 style="margin-bottom:6px;font-size:14px;"><?= __('by_period') ?></h3>
        <div style="position:relative;height:240px;"><canvas id="chartMonth"></canvas></div>
    </div>
    <div class="card" style="padding:12px 14px;grid-column:1/-1;">
        <h3 style="margin-bottom:6px;font-size:14px;"><?= __('by_supplier') ?> (Top 15)</h3>
        <div style="max-height:300px;overflow-y:auto;">
            <table class="data-table" style="font-size:12px;">
                <thead><tr><th>#</th><th><?= _e('supplier') ?></th><th class="text-right"><?= __('count') ?></th><th class="text-right"><?= __('amount') ?></th></tr></thead>
                <tbody>
                    <?php foreach ($bySupplier as $i => $s): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($s['supplier_name'] ?? '') ?></td>
                        <td class="text-right"><?= intval($s['cnt']) ?></td>
                        <td class="text-right"><?= formatMoney($s['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const statData = <?= json_encode($byStatus, JSON_UNESCAPED_UNICODE) ?>;
const monData  = <?= json_encode($byMonth, JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: statData.map(s => s.status),
        datasets: [{
            data: statData.map(s => parseFloat(s.amount)),
            backgroundColor: ['#9E9E9E','#FB8C00','#4CAF50','#1976D2','#26A69A','#66BB6A','#2E7D32','#D32F2F']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('chartMonth'), {
    type: 'bar',
    data: {
        labels: monData.map(m => m.month),
        datasets: [{
            label: 'Amount',
            data: monData.map(m => parseFloat(m.amount)),
            backgroundColor: '#26A69A'
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>
