<?php
/**
 * PEGASUS ERP - Deal List (案件一覧)
 * Variables: $deals, $filters, $statuses, $salesPersons, $customers
 */
$statusColors = [];
foreach (($statuses ?? []) as $s) {
    $statusColors[$s['status_name']] = $s['color'] ?? '#757575';
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('deals') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/quotations"><?= _e('sales') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('deals') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/sales/deals/kanban" class="btn btn-cancel"><?= _e('kanban_view') ?></a>
        <a href="/sales/deals/create" class="btn btn-primary"><?= _e('new_deal') ?></a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:12px 20px;margin-bottom:16px;">
    <form method="GET" action="/sales/deals" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <!-- Customer dropdown -->
        <div class="form-group" style="margin-bottom:0;">
            <select name="customer_id" class="form-select" style="width:200px;">
                <option value=""><?= _e('all_customers') ?></option>
                <?php foreach (($customers ?? []) as $c): ?>
                    <option value="<?= e($c['customer_id']) ?>" <?= ($filters['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
                        <?= e($c['customer_name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Status -->
        <div class="form-group" style="margin-bottom:0;">
            <select name="status" class="form-select" style="width:170px;">
                <option value=""><?= _e('all_statuses') ?></option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s['status_name']) ?>" <?= ($filters['status'] ?? '') === $s['status_name'] ? 'selected' : '' ?>>
                        <?= e($s['status_name']) ?> (<?= $s['win_pct'] ?>%)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Sales person -->
        <div class="form-group" style="margin-bottom:0;">
            <select name="sales" class="form-select" style="width:150px;">
                <option value=""><?= _e('all_sales_persons') ?></option>
                <?php foreach ($salesPersons as $sp): ?>
                    <option value="<?= e($sp['employee_id']) ?>" <?= ($filters['sales'] ?? '') == $sp['employee_id'] ? 'selected' : '' ?>>
                        <?= e($sp['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Deal name -->
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="deal_name" class="form-input" style="width:180px;"
                   value="<?= e($filters['deal_name'] ?? '') ?>" placeholder="<?= __('deal_name') ?>">
        </div>
        <!-- Amount range -->
        <div class="form-group" style="margin-bottom:0;display:flex;align-items:center;gap:4px;">
            <input type="number" step="0.01" min="0" name="amount_min" class="form-input" style="width:110px;"
                   value="<?= e($filters['amount_min'] ?? '') ?>" placeholder="<?= __('amount') ?> min">
            <span style="color:var(--color-text-muted);">~</span>
            <input type="number" step="0.01" min="0" name="amount_max" class="form-input" style="width:110px;"
                   value="<?= e($filters['amount_max'] ?? '') ?>" placeholder="<?= __('amount') ?> max">
        </div>
        <!-- Win probability range -->
        <div class="form-group" style="margin-bottom:0;display:flex;align-items:center;gap:4px;">
            <input type="number" min="0" max="100" name="win_min" class="form-input" style="width:70px;"
                   value="<?= e($filters['win_min'] ?? '') ?>" placeholder="<?= __('possibility') ?> min">
            <span style="color:var(--color-text-muted);">~</span>
            <input type="number" min="0" max="100" name="win_max" class="form-input" style="width:70px;"
                   value="<?= e($filters['win_max'] ?? '') ?>" placeholder="<?= __('possibility') ?> max">
            <span style="color:var(--color-text-muted);font-size:12px;">%</span>
        </div>
        <!-- Free-text fallback -->
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="q" class="form-input" style="width:150px;"
                   value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= __('search') ?> (No/PJ)">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/sales/deals" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Summary Cards -->
<?php
$totalAmount = 0;
$weightedAmount = 0;
foreach ($deals as $d) {
    $totalAmount += floatval($d['expected_amount'] ?? 0);
    $weightedAmount += floatval($d['expected_amount'] ?? 0) * floatval($d['win_pct'] ?? 0) / 100;
}
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:16px;">
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= _e('total_deals') ?></div>
        <div style="font-size:24px;font-weight:700;color:var(--color-primary);"><?= count($deals) ?></div>
    </div>
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= _e('total_pipeline') ?></div>
        <div style="font-size:24px;font-weight:700;color:var(--color-primary);"><?= formatMoney($totalAmount) ?></div>
    </div>
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= _e('weighted_pipeline') ?></div>
        <div style="font-size:24px;font-weight:700;color:#4CAF50;"><?= formatMoney($weightedAmount) ?></div>
    </div>
</div>

<!-- Deals Table -->
<div class="table-wrapper" style="overflow-x:auto;">
    <table class="data-table" style="min-width:1100px;">
        <thead>
            <tr>
                <th style="width:110px;"><?= _e('deal_no') ?></th>
                <th style="min-width:180px;"><?= _e('deal_name') ?></th>
                <th style="min-width:120px;"><?= _e('customer') ?></th>
                <th><?= _e('solution') ?></th>
                <th class="text-right" style="width:120px;"><?= _e('amount') ?></th>
                <th class="text-center" style="width:100px;"><?= _e('possibility') ?></th>
                <th style="width:90px;"><?= _e('sales_person') ?></th>
                <th style="width:60px;" class="text-center"><?= _e('activities') ?></th>
                <th class="text-center" style="width:90px;"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($deals)): ?>
                <?php foreach ($deals as $d): ?>
                    <tr>
                        <td><a href="/sales/deals/<?= e($d['deal_id']) ?>" style="font-weight:500;"><?= e($d['deal_no']) ?></a></td>
                        <td>
                            <a href="/sales/deals/<?= e($d['deal_id']) ?>"><?= e($d['deal_name']) ?></a>
                            <?php if (!empty($d['pj_no'])): ?>
                                <div style="font-size:11px;color:var(--color-text-muted);">PJ: <?= e($d['pj_no']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($d['customer_name'] ?? '') ?></td>
                        <td style="font-size:12px;"><?= e($d['solution_name'] ?? '') ?></td>
                        <td class="text-right"><strong><?= formatMoney($d['expected_amount'] ?? 0) ?></strong></td>
                        <td class="text-center">
                            <span class="badge" style="background:<?= e($d['color'] ?? '#757575') ?>;color:#fff;font-size:11px;">
                                <?= e($d['status_name'] ?? '') ?>
                            </span>
                        </td>
                        <td style="font-size:12px;"><?= e($d['sales_person_name'] ?? '') ?></td>
                        <td class="text-center"><?= intval($d['activity_count'] ?? 0) ?></td>
                        <td class="text-center actions" style="white-space:nowrap;">
                            <a href="/sales/deals/<?= e($d['deal_id']) ?>" title="<?= __('view') ?>">&#128065;</a>
                            <a href="/sales/deals/<?= e($d['deal_id']) ?>/edit" title="<?= __('edit') ?>">&#9998;</a>
                            <form method="POST" action="/sales/deals/<?= e($d['deal_id']) ?>/delete" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')" title="<?= __('delete') ?>" style="background:none;border:none;cursor:pointer;">&#128465;</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding:40px;color:var(--color-text-muted);">
                        <?= _e('no_deals_found') ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (!empty($deals)): ?>
        <div class="table-footer">
            <span><?= count($deals) ?> <?= __('deals') ?></span>
        </div>
    <?php endif; ?>
</div>
