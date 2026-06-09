<?php
/**
 * Analytics — Quotation Dashboard
 * Variables: $filters, $byProbability, $byCustomer, $bySolution, $byMonth, $kpi
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('analytics_quotations') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('analytics_quotations') ?></span>
        </div>
    </div>
</div>

<div class="card" style="padding:12px 20px;margin-bottom:16px;">
    <form method="GET" action="/analytics/quotations" style="display:flex;gap:10px;align-items:center;">
        <label><?= __('date_from') ?>:</label>
        <input type="date" name="date_from" class="form-input" value="<?= e($filters['date_from']) ?>" style="width:160px;">
        <label><?= __('date_to') ?>:</label>
        <input type="date" name="date_to" class="form-input" value="<?= e($filters['date_to']) ?>" style="width:160px;">
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
    </form>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px;">
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('total_quotations') ?></div>
        <div style="font-size:24px;font-weight:700;color:var(--color-primary);"><?= intval($kpi['total_qt']) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('total_amount') ?></div>
        <div style="font-size:20px;font-weight:700;"><?= formatMoney($kpi['total_amount']) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('won_count') ?></div>
        <div style="font-size:24px;font-weight:700;color:#4CAF50;"><?= intval($kpi['won_cnt']) ?></div>
    </div>
    <div class="card" style="padding:14px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= __('won_amount') ?></div>
        <div style="font-size:20px;font-weight:700;color:#4CAF50;"><?= formatMoney($kpi['won_amount']) ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    <!-- By probability -->
    <div class="card" style="padding:12px 14px;">
        <h3 style="margin-bottom:6px;font-size:14px;"><?= __('by_probability') ?></h3>
        <div style="position:relative;height:240px;"><canvas id="chartProbability"></canvas></div>
    </div>
    <!-- By solution category -->
    <div class="card" style="padding:12px 14px;">
        <h3 style="margin-bottom:6px;font-size:14px;"><?= __('by_solution') ?></h3>
        <div style="position:relative;height:240px;"><canvas id="chartSolution"></canvas></div>
    </div>
    <!-- By month -->
    <div class="card" style="padding:12px 14px;grid-column:1/-1;">
        <h3 style="margin-bottom:6px;font-size:14px;"><?= __('by_period') ?></h3>
        <div style="position:relative;height:200px;"><canvas id="chartMonth"></canvas></div>
    </div>
    <!-- By customer top 15 -->
    <div class="card" style="padding:12px 14px;grid-column:1/-1;">
        <h3 style="margin-bottom:6px;font-size:14px;"><?= __('by_customer') ?> (Top 15)</h3>
        <div style="max-height:300px;overflow-y:auto;">
            <table class="data-table" style="font-size:12px;">
                <thead><tr><th>#</th><th><?= _e('customer') ?></th><th class="text-right"><?= __('count') ?></th><th class="text-right"><?= __('amount') ?></th></tr></thead>
                <tbody>
                    <?php foreach ($byCustomer as $i => $c): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($c['customer_name'] ?? '') ?></td>
                        <td class="text-right"><?= intval($c['cnt']) ?></td>
                        <td class="text-right"><?= formatMoney($c['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const probData = <?= json_encode($byProbability, JSON_UNESCAPED_UNICODE) ?>;
const solData  = <?= json_encode($bySolution, JSON_UNESCAPED_UNICODE) ?>;
const monData  = <?= json_encode($byMonth, JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('chartProbability'), {
    type: 'bar',
    data: {
        labels: probData.map(p => (p.status_name || '—') + ' (' + (p.win_pct || 0) + '%)'),
        datasets: [{
            label: 'Amount',
            data: probData.map(p => parseFloat(p.amount)),
            backgroundColor: probData.map(p => p.color || '#1976D2')
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('chartSolution'), {
    type: 'doughnut',
    data: {
        labels: solData.map(s => s.category_name || '—'),
        datasets: [{
            data: solData.map(s => parseFloat(s.amount)),
            backgroundColor: ['#1976D2','#4CAF50','#FF9800','#E91E63','#9C27B0','#00BCD4','#FFC107','#795548','#607D8B','#F44336','#3F51B5','#009688']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('chartMonth'), {
    type: 'line',
    data: {
        labels: monData.map(m => m.month),
        datasets: [{
            label: 'Amount',
            data: monData.map(m => parseFloat(m.amount)),
            borderColor: '#1976D2',
            backgroundColor: 'rgba(25,118,210,0.15)',
            tension: 0.3,
            fill: true
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>
