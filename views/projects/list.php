<?php
/**
 * PEGASUS ERP - Project List (PJ一覧)
 * Variables: $projects, $filters
 */
$statusClasses = [
    'ACTIVE'       => 'badge-open',
    'IN_PROGRESS'  => 'badge-pending',
    'COMPLETED'    => 'badge-approved',
    'ON_HOLD'      => 'badge-draft',
    'CANCELLED'    => 'badge-rejected',
];
$statusLabels = [
    'ACTIVE'       => __('pj_active'),
    'IN_PROGRESS'  => __('pj_in_progress'),
    'COMPLETED'    => __('pj_completed'),
    'ON_HOLD'      => __('pj_on_hold'),
    'CANCELLED'    => __('pj_cancelled'),
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= __('project_list') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= __('project_management') ?></span>
        </div>
    </div>
    <div>
        <a href="/projects/create" class="btn btn-primary"><?= __('new_project') ?></a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:12px 20px;margin-bottom:16px;">
    <form method="GET" action="/projects" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="status" class="form-select" style="width:150px;">
                <option value=""><?= __('all_statuses') ?></option>
                <?php foreach (['ACTIVE','IN_PROGRESS','COMPLETED','ON_HOLD','CANCELLED'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $statusLabels[$s] ?? $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <select name="year" class="form-select" style="width:120px;">
                <option value=""><?= __('all_years') ?></option>
                <?php for ($y = intval(date('Y')); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="customer" class="form-input" style="width:180px;" value="<?= e($filters['customer'] ?? '') ?>" placeholder="<?= __('search_customer') ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="q" class="form-input" style="width:200px;" value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= __('search_pj') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= __('filter') ?></button>
        <a href="/projects" class="btn btn-cancel btn-sm"><?= __('clear') ?></a>
    </form>
</div>

<!-- Project Table -->
<div class="table-wrapper" style="overflow-x:auto;">
    <table class="data-table" style="min-width:1400px;">
        <thead>
            <tr>
                <th style="width:110px;">PJ No.</th>
                <th style="min-width:200px;"><?= __('pj_name') ?></th>
                <th style="min-width:160px;"><?= __('customer') ?></th>
                <th class="text-right" style="width:130px;"><?= __('total_revenue') ?></th>
                <th class="text-right" style="width:120px;"><?= __('total_cost_short') ?></th>
                <th class="text-right" style="width:120px;"><?= __('gross_profit') ?></th>
                <th class="text-right" style="width:110px;"><?= __('invoiced') ?></th>
                <th class="text-right" style="width:110px;"><?= __('purchased') ?></th>
                <th style="width:90px;"><?= __('po_date_short') ?></th>
                <th style="width:90px;"><?= __('delivery') ?></th>
                <th class="text-center" style="width:90px;"><?= __('status') ?></th>
                <th class="text-center" style="width:70px;"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $p):
                    $rev = floatval($p['total_revenue'] ?? 0);
                    $cost = floatval($p['total_cost'] ?? 0);
                    $gp = floatval($p['gross_profit'] ?? ($rev - $cost));
                    $inv = floatval($p['invoiced_total'] ?? 0);
                    $pur = floatval($p['purchase_total'] ?? 0);
                ?>
                <tr>
                    <td>
                        <a href="/projects/<?= e($p['project_id']) ?>" style="font-weight:600;color:#003366;"><?= e($p['pj_no']) ?></a>
                        <?php if (!empty($p['so_no'])): ?>
                            <div style="font-size:10px;color:var(--color-text-muted);"><?= e($p['so_no']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/projects/<?= e($p['project_id']) ?>" style="font-size:12px;"><?= e($p['pj_name']) ?></a>
                        <?php if (!empty($p['pj_segment'])): ?>
                            <div style="font-size:10px;color:var(--color-text-muted);"><?= e($p['pj_segment']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;"><?= e($p['customer_name'] ?? '') ?></td>
                    <td class="text-right" style="font-weight:600;"><?= formatMoney($rev) ?></td>
                    <td class="text-right"><?= formatMoney($cost) ?></td>
                    <td class="text-right">
                        <span style="color:<?= $gp >= 0 ? '#4CAF50' : '#F44336' ?>;"><?= formatMoney($gp) ?></span>
                        <?php if ($rev > 0): ?>
                            <div style="font-size:10px;color:var(--color-text-muted);"><?= number_format($gp / $rev * 100, 1) ?>%</div>
                        <?php endif; ?>
                    </td>
                    <td class="text-right" style="font-size:12px;">
                        <?= $inv > 0 ? formatMoney($inv) : '-' ?>
                        <?php if ($rev > 0 && $inv > 0): ?>
                            <div style="font-size:10px;color:<?= $inv >= $rev ? '#4CAF50' : '#FF9800' ?>;"><?= number_format($inv / $rev * 100, 0) ?>%</div>
                        <?php endif; ?>
                    </td>
                    <td class="text-right" style="font-size:12px;"><?= $pur > 0 ? formatMoney($pur) : '-' ?></td>
                    <td style="font-size:11px;"><?= e($p['po_date'] ?? '-') ?></td>
                    <td style="font-size:11px;"><?= e($p['delivery_date'] ?? $p['plan_delivery_date'] ?? '-') ?></td>
                    <td class="text-center">
                        <span class="badge <?= $statusClasses[$p['status']] ?? 'badge-draft' ?>" style="font-size:10px;">
                            <?= $statusLabels[$p['status']] ?? $p['status'] ?>
                        </span>
                    </td>
                    <td class="text-center actions">
                        <a href="/projects/<?= e($p['project_id']) ?>" title="<?= __('view') ?>">&#128065;</a>
                        <a href="/projects/<?= e($p['project_id']) ?>/edit" title="<?= __('edit') ?>">&#9998;</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= __('msg_no_projects') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
