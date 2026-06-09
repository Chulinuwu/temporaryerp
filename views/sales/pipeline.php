<?php
/**
 * PEGASUS ERP - Sales Pipeline Dashboard
 * Variables: $pipelineSummary, $pipelineByStatus, $pipelineDeals
 */
$pageTitle = __('pipeline') . ' - PEGASUS ERP';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('pipeline') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/quotations"><?= _e('sales') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('pipeline') ?></span>
        </div>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue">&#128200;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($pipelineSummary['total_pipeline'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('total_pipeline') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green">&#9878;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($pipelineSummary['weighted_value'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('weighted_value') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon orange">&#127942;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= number_format(($pipelineSummary['win_rate'] ?? 0), 1) ?>%</div>
            <div class="kpi-label"><?= _e('win_rate') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red">&#128178;</div>
        <div class="kpi-content">
            <div class="kpi-value"><?= formatMoney($pipelineSummary['avg_deal_size'] ?? 0) ?></div>
            <div class="kpi-label"><?= _e('avg_deal_size') ?></div>
        </div>
    </div>
</div>

<!-- Pipeline by Status (Funnel) -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= _e('pipeline_by_status') ?></h3>
    </div>
    <div class="card-body">
        <?php
        $statusColors = [
            'DRAFT'       => '#9E9E9E',
            'SUBMITTED'   => '#1976D2',
            'NEGOTIATING' => '#FB8C00',
            'WON'         => '#43A047',
            'LOST'        => '#E53935',
        ];
        $maxValue = 1;
        if (!empty($pipelineByStatus)) {
            $maxValue = max(array_column($pipelineByStatus, 'total_amount')) ?: 1;
        }
        ?>
        <?php if (!empty($pipelineByStatus)): ?>
            <?php foreach ($pipelineByStatus as $ps): ?>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <span style="width:100px;font-size:13px;font-weight:500;text-align:right;"><?= htmlspecialchars($ps['status']) ?></span>
                    <div style="flex:1;background:#F5F5F5;border-radius:4px;height:28px;position:relative;">
                        <div style="width:<?= round(($ps['total_amount'] / $maxValue) * 100) ?>%;background:<?= $statusColors[$ps['status']] ?? '#1976D2' ?>;height:100%;border-radius:4px;min-width:2px;"></div>
                    </div>
                    <span style="width:130px;font-size:13px;font-weight:600;text-align:right;"><?= formatMoney($ps['total_amount']) ?></span>
                    <span style="width:40px;font-size:12px;color:var(--color-text-muted);">(<?= (int)$ps['count'] ?>)</span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:var(--color-text-muted);text-align:center;padding:20px;"><?= _e('no_pipeline_available') ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Pipeline Deals Table -->
<div class="table-wrapper">
    <div class="table-toolbar">
        <h3 class="card-title"><?= _e('pipeline_deals') ?></h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th><?= _e('customer') ?></th>
                <th><?= _e('quotation_no') ?></th>
                <th><?= _e('project') ?></th>
                <th class="text-right"><?= _e('amount') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th><?= _e('expected_close') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pipelineDeals)): ?>
                <?php
                $badgeMap = [
                    'DRAFT'       => 'badge-draft',
                    'SUBMITTED'   => 'badge-open',
                    'NEGOTIATING' => 'badge-pending',
                    'WON'         => 'badge-approved',
                    'LOST'        => 'badge-rejected',
                ];
                ?>
                <?php foreach ($pipelineDeals as $deal): ?>
                    <tr>
                        <td><?= e($deal['customer_name'] ?? '') ?></td>
                        <td><a href="/sales/quotations/<?= htmlspecialchars($deal['quotation_id']) ?>"><?= htmlspecialchars($deal['quotation_no']) ?></a></td>
                        <td><?= htmlspecialchars($deal['project_name'] ?? '-') ?></td>
                        <td class="text-right"><strong><?= formatMoney($deal['grand_total_thb'] ?? 0) ?></strong></td>
                        <td class="text-center">
                            <span class="badge <?= $badgeMap[$deal['status']] ?? 'badge-draft' ?>"><?= htmlspecialchars($deal['status']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($deal['expiry_date'] ?? '-') ?></td>
                        <td class="text-center actions">
                            <a href="/sales/quotations/<?= htmlspecialchars($deal['quotation_id']) ?>" title="View">&#128065;</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_deals') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
