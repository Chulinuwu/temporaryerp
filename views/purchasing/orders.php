<?php
/**
 * PEGASUS ERP - Purchase Order List
 * Variables: $orders, $filters, $pagination
 */
$pageTitle = __('purchase_orders') . ' - PEGASUS ERP';

$statusClasses = [
    'DRAFT'             => 'badge-draft',
    'PENDING_APPROVAL'  => 'badge-open',
    'APPROVED'          => 'badge-approved',
    'SENT'              => 'badge-approved',
    'PARTIAL_RECEIVED'  => 'badge-pending',
    'FULLY_RECEIVED'    => 'badge-paid',
    'CLOSED'            => 'badge-draft',
    'CANCELLED'         => 'badge-rejected',
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('purchase_orders') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/purchasing/orders"><?= _e('purchasing') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('purchase_orders') ?></span>
        </div>
    </div>
    <a href="/purchasing/orders/create" class="btn btn-primary"><?= _e('new_purchase_order') ?></a>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/purchasing/orders" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="status" class="form-select" style="width:160px;">
                <option value=""><?= _e('all_statuses') ?></option>
                <?php foreach (['DRAFT','PENDING_APPROVAL','APPROVED','SENT','PARTIAL_RECEIVED','FULLY_RECEIVED','CLOSED','CANCELLED'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_from" class="form-input" style="width:150px;" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="date" name="date_to" class="form-input" style="width:150px;" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <select name="supplier_id" class="form-select" style="width:220px;">
                <option value=""><?= _e('all_suppliers') ?></option>
                <?php foreach (($suppliers ?? []) as $sup): ?>
                    <option value="<?= htmlspecialchars($sup['supplier_id']) ?>" <?= ($filters['supplier_id'] ?? '') == $sup['supplier_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sup['supplier_name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="q" class="form-input" style="width:200px;" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="<?= _e('search') ?> (PO No / <?= _e('supplier') ?>)">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/purchasing/orders" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- PO Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th class="sortable"><?= _e('po_no') ?></th>
                <th class="sortable"><?= _e('date') ?></th>
                <th><?= _e('supplier') ?></th>
                <th class="text-right"><?= _e('subtotal') ?></th>
                <th class="text-right"><?= _e('vat') ?></th>
                <th class="text-right"><?= _e('wht') ?></th>
                <th class="text-right"><?= _e('net_total') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><a href="/purchasing/orders/<?= htmlspecialchars($o['po_id']) ?>"><?= htmlspecialchars($o['po_no']) ?></a></td>
                        <td><?= htmlspecialchars($o['order_date']) ?></td>
                        <td><?= e($o['supplier_name'] ?? '') ?></td>
                        <td class="text-right"><?= formatMoney($o['subtotal_thb'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney($o['vat_amount'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney($o['wht_amount'] ?? 0) ?></td>
                        <td class="text-right"><strong><?= formatMoney($o['payment_amount'] ?? 0) ?></strong></td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$o['status']] ?? 'badge-draft' ?>">
                                <?= htmlspecialchars($o['status']) ?>
                            </span>
                        </td>
                        <td class="text-center actions">
                            <a href="/purchasing/orders/<?= htmlspecialchars($o['po_id']) ?>" title="<?= _e('view') ?>">&#128065;</a>
                            <?php if (in_array($o['status'], ['DRAFT'])): ?>
                                <a href="/purchasing/orders/<?= htmlspecialchars($o['po_id']) ?>/edit" title="<?= _e('edit') ?>">&#9998;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('msg_no_orders') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($pagination)): ?>
        <div class="table-footer">
            <span><?= __('showing_of', $pagination['from'], $pagination['to'], $pagination['total']) ?></span>
            <div style="display:flex;gap:4px;">
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="btn btn-cancel btn-sm"><?= __('prev') ?></a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="btn btn-cancel btn-sm"><?= __('next') ?></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
