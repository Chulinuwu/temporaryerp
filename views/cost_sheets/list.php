<?php
/**
 * PEGASUS ERP - Cost Sheet List (原価算出一覧)
 */
$statusLabels = [
    'DRAFT' => __('status_draft'), 'CONFIRMED' => __('status_approved'), 'LINKED' => __('linked'),
];
$statusClasses = [
    'DRAFT' => 'badge-draft', 'CONFIRMED' => 'badge-approved', 'LINKED' => 'badge-open',
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= __('cost_sheet_list') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= __('cost_sheet_list') ?></span>
        </div>
    </div>
    <a href="/cost-sheets/create" class="btn btn-primary">+ <?= __('new_cost_sheet') ?></a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="/cost-sheets" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;">
            <select name="status" class="form-input" style="width:140px;">
                <option value=""><?= __('all_statuses') ?></option>
                <option value="DRAFT" <?= ($filters['status'] ?? '') === 'DRAFT' ? 'selected' : '' ?>><?= __('status_draft') ?></option>
                <option value="CONFIRMED" <?= ($filters['status'] ?? '') === 'CONFIRMED' ? 'selected' : '' ?>><?= __('status_approved') ?></option>
                <option value="LINKED" <?= ($filters['status'] ?? '') === 'LINKED' ? 'selected' : '' ?>><?= __('linked') ?></option>
            </select>
            <input type="text" name="customer" class="form-input" style="width:160px;" placeholder="<?= __('search_customer') ?>" value="<?= e($filters['customer'] ?? '') ?>">
            <input type="text" name="q" class="form-input" style="width:220px;" placeholder="<?= __('cost_sheet_no') ?> / <?= __('cost_sheet_name') ?>..." value="<?= e($filters['q'] ?? '') ?>">
            <button type="submit" class="btn btn-primary btn-sm"><?= __('filter') ?></button>
            <a href="/cost-sheets" class="btn btn-cancel btn-sm"><?= __('clear') ?></a>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:140px;"><?= __('cost_sheet_no') ?></th>
                    <th><?= __('cost_sheet_name') ?></th>
                    <th style="width:180px;"><?= __('customer') ?></th>
                    <th class="text-right" style="width:130px;"><?= __('total') ?> (Baht)</th>
                    <th class="text-center" style="width:60px;">Items</th>
                    <th style="width:110px;"><?= __('quotation_ref') ?></th>
                    <th style="width:90px;">PJ</th>
                    <th style="width:80px;"><?= __('status') ?></th>
                    <th style="width:70px;"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sheets)): ?>
                    <?php foreach ($sheets as $s): ?>
                    <tr>
                        <td>
                            <a href="/cost-sheets/<?= e($s['cost_sheet_id']) ?>" style="font-weight:600;color:var(--color-primary);">
                                <?= e($s['sheet_no']) ?>
                            </a>
                            <?php if (!empty($s['source_file'])): ?>
                                <div style="font-size:10px;color:var(--color-text-muted);">&#128196; <?= e(substr($s['source_file'], 0, 30)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($s['sheet_name']) ?></td>
                        <td style="font-size:12px;"><?= e($s['customer_name'] ?? '') ?></td>
                        <td class="text-right" style="font-weight:600;"><?= formatMoney($s['total_cost'] ?? 0) ?></td>
                        <td class="text-center"><?= intval($s['item_count'] ?? 0) ?></td>
                        <td style="font-size:12px;">
                            <?php if (!empty($s['quotation_no'])): ?>
                                <a href="/sales/quotations"><?= e($s['quotation_no']) ?></a>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td style="font-size:12px;">
                            <?php if (!empty($s['pj_no'])): ?>
                                <a href="/projects/<?= e($s['project_id']) ?>"><?= e($s['pj_no']) ?></a>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $statusClasses[$s['status']] ?? 'badge-draft' ?>">
                                <?= $statusLabels[$s['status']] ?? $s['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="/cost-sheets/<?= e($s['cost_sheet_id']) ?>" style="font-size:18px;" title="<?= __('view') ?>">&#128065;</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= __('no_cost_sheets') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
