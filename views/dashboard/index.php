<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('dashboard') ?></h1>
        <div class="breadcrumb">
            <span class="breadcrumb-current"><?= _e('dashboard') ?></span>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue">&#9733;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($kpi['cash_balance'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('cash_balance') ?></div>
            <div class="kpi-change"><?= e($kpi['current_month'] ?? date('M-Y')) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green">&#9650;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($kpi['monthly_income'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('monthly_income') ?></div>
            <div class="kpi-change up"><?= _e('actual') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red">&#9660;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($kpi['monthly_expense'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('monthly_expense') ?></div>
            <div class="kpi-change down"><?= _e('actual') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#F3E5F5;color:#7B1FA2;">&#9830;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($kpi['pipeline_total'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('pipeline_total') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green">&#8776;</div>
        <div class="kpi-content">
            <?php $netCashFlow = ($kpi['monthly_income'] ?? 0) - ($kpi['monthly_expense'] ?? 0); ?>
            <div class="kpi-value"><?= formatMoney($netCashFlow) ?></div>
            <div class="kpi-label"><?= _e('net_cash_flow') ?></div>
            <div class="kpi-change <?= $netCashFlow >= 0 ? 'up' : 'down' ?>">
                <?= $netCashFlow >= 0 ? '+' : '' ?><?= formatMoney($netCashFlow) ?>
            </div>
        </div>
    </div>
</div>

<!-- Two Column Layout -->
<div style="display:grid;grid-template-columns:3fr 2fr;gap:16px;margin-bottom:16px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= _e('cashflow_actual') ?></div>
        </div>
        <div class="card-body">
            <canvas id="cashflowChart" height="280"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= _e('pipeline_by_status') ?></div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= _e('status') ?></th>
                        <th class="text-center"><?= _e('quantity') ?></th>
                        <th class="text-right"><?= _e('total') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pipeline)): ?>
                        <?php foreach ($pipeline as $row): ?>
                            <tr>
                                <td><span class="badge badge-<?= strtolower(e($row['status'] ?? '')) ?>"><?= e($row['status'] ?? '') ?></span></td>
                                <td class="text-center"><?= (int)($row['count'] ?? 0) ?></td>
                                <td class="text-right"><?= formatMoney($row['total'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center" style="color:#9E9E9E;padding:24px;"><?= _e('no_pipeline_data') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Customers -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><?= _e('top_customers') ?></div>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= _e('customer_name') ?></th>
                    <th class="text-right"><?= _e('pipeline_total') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($topCustomers)): ?>
                    <?php foreach ($topCustomers as $i => $cust): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($cust['customer_name'] ?? '') ?></td>
                            <td class="text-right"><?= formatMoney($cust['pipeline_total'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center" style="color:#9E9E9E;padding:24px;"><?= _e('no_data') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cashflowData = <?= json_encode($cashflow ?? [], JSON_UNESCAPED_UNICODE) ?>;
    var labels = cashflowData.map(function(d) { return d.month; });
    var incomeData = cashflowData.map(function(d) { return d.income; });
    var expenseData = cashflowData.map(function(d) { return d.expense; });
    var netData = cashflowData.map(function(d) { return d.net; });

    var ctx = document.getElementById('cashflowChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: '<?= _e('income') ?>', data: incomeData, backgroundColor: '#43A047', borderRadius: 4, barPercentage: 0.7 },
                    { label: '<?= _e('expense') ?>', data: expenseData, backgroundColor: '#E53935', borderRadius: 4, barPercentage: 0.7 },
                    { label: '<?= _e('net') ?>', data: netData, backgroundColor: '#1976D2', borderRadius: 4, barPercentage: 0.7 }
                ]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, padding: 16, font: { size: 12 } } },
                    tooltip: { callbacks: { label: function(c) { return c.dataset.label + ': ' + (c.parsed.x || 0).toLocaleString('en-US', {minimumFractionDigits: 2}); } } }
                },
                scales: {
                    x: { ticks: { callback: function(v) { return v.toLocaleString('en-US'); }, font: { size: 11 } }, grid: { color: '#F0F0F0' } },
                    y: { ticks: { font: { size: 12 } }, grid: { display: false } }
                }
            }
        });
    }
});
</script>
