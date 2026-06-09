<?php
/**
 * PEGASUS ERP - Sales Order List
 * Variables: $orders, $filters, $pagination
 */
$pageTitle = __('sales_orders') . ' - PEGASUS ERP';

$statusClasses = [
    'DRAFT'            => 'badge-draft',
    'CONFIRMED'        => 'badge-open',
    'IN_PRODUCTION'    => 'badge-pending',
    'PARTIAL_SHIPPED'  => 'badge-pending',
    'SHIPPED'          => 'badge-approved',
    'INVOICED'         => 'badge-paid',
    'CLOSED'           => 'badge-draft',
    'CANCELLED'        => 'badge-rejected',
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('sales_orders') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/quotations"><?= _e('sales') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('sales_orders') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button type="button" onclick="bulkPdf('salesorder')" class="btn btn-cancel" id="btn-bulk-pdf" style="display:none;">&#128424; <?= _e('bulk_print') ?></button>
        <a href="/sales/orders/create" class="btn btn-primary"><?= _e('new_sales_order') ?></a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/sales/orders" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="status" class="form-select" style="width:160px;">
                <option value=""><?= _e('all_statuses') ?></option>
                <?php foreach (['DRAFT','CONFIRMED','IN_PRODUCTION','PARTIAL_SHIPPED','SHIPPED','INVOICED','CLOSED','CANCELLED'] as $s): ?>
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
            <input type="text" name="customer" class="form-input" style="width:200px;" value="<?= htmlspecialchars($filters['customer'] ?? '') ?>" placeholder="<?= _e('search_customer') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/sales/orders" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Orders Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px;" class="text-center">
                    <input type="checkbox" id="check-all" onchange="toggleAll(this)">
                </th>
                <th class="sortable"><?= _e('so_no') ?></th>
                <th class="sortable"><?= _e('date') ?></th>
                <th><?= _e('customer') ?></th>
                <th><?= _e('deal_ref') ?></th>
                <th><?= _e('quotation_ref') ?></th>
                <th class="text-right"><?= _e('subtotal') ?></th>
                <th class="text-right"><?= _e('grand_total') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="row-check" value="<?= htmlspecialchars($o['so_id']) ?>" onchange="updateBulkBtn()">
                        </td>
                        <td><a href="/sales/orders/<?= htmlspecialchars($o['so_id']) ?>" style="font-weight:500;"><?= htmlspecialchars($o['so_no']) ?></a></td>
                        <td><?= htmlspecialchars($o['order_date']) ?></td>
                        <td><?= e($o['customer_name'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($o['deal_no'])): ?>
                                <a href="/sales/deals/<?= htmlspecialchars($o['deal_id']) ?>" style="font-size:12px;"><?= htmlspecialchars($o['deal_no']) ?></a>
                                <?php if (!empty($o['deal_name'])): ?>
                                    <div style="font-size:11px;color:var(--color-text-muted);"><?= htmlspecialchars($o['deal_name']) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;"><?= htmlspecialchars($o['quotation_no'] ?? '-') ?></td>
                        <td class="text-right"><?= formatMoney($o['subtotal_thb'] ?? 0) ?></td>
                        <td class="text-right"><strong><?= formatMoney($o['grand_total_thb'] ?? 0) ?></strong></td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$o['status']] ?? 'badge-draft' ?>">
                                <?= htmlspecialchars($o['status']) ?>
                            </span>
                        </td>
                        <td class="text-center actions">
                            <a href="/sales/orders/<?= htmlspecialchars($o['so_id']) ?>" title="View">&#128065;</a>
                            <a href="/pdf/salesorder/<?= htmlspecialchars($o['so_id']) ?>" target="_blank" title="PDF">&#128424;</a>
                            <?php if (in_array($o['status'], ['DRAFT','CONFIRMED'])): ?>
                                <a href="/sales/orders/<?= htmlspecialchars($o['so_id']) ?>/edit" title="Edit">&#9998;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= __('msg_no_orders') ?></td>
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

<!-- Bulk PDF Form (hidden) -->
<form id="bulk-pdf-form" method="POST" action="/pdf/bulk" target="_blank" style="display:none;">
    <input type="hidden" name="type" value="salesorder">
</form>

<script>
function toggleAll(el) {
    document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = el.checked; });
    updateBulkBtn();
}
function updateBulkBtn() {
    var checked = document.querySelectorAll('.row-check:checked').length;
    document.getElementById('btn-bulk-pdf').style.display = checked > 0 ? 'inline-flex' : 'none';
}
function bulkPdf(type) {
    var form = document.getElementById('bulk-pdf-form');
    form.querySelectorAll('input[name="ids[]"]').forEach(function(el) { el.remove(); });
    document.querySelectorAll('.row-check:checked').forEach(function(cb) {
        var input = document.createElement('input');
        input.type = 'hidden'; input.name = 'ids[]'; input.value = cb.value;
        form.appendChild(input);
    });
    form.submit();
}
</script>
