<?php
/**
 * PEGASUS ERP - AR Invoice List
 * Variables: $invoices, $filters, $pagination
 */
$pageTitle = 'AR Invoices - PEGASUS ERP';

$statusClasses = [
    'DRAFT'     => 'badge-draft',
    'OPEN'      => 'badge-open',
    'PARTIAL'   => 'badge-pending',
    'PAID'      => 'badge-paid',
    'OVERDUE'   => 'badge-overdue',
    'CANCELLED' => 'badge-rejected',
    'VOID'      => 'badge-rejected',
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('ar_invoices') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/ar/invoices"><?= _e('menu_ar') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_invoices') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button type="button" onclick="bulkPdf('invoice')" class="btn btn-cancel" id="btn-bulk-pdf" style="display:none;">&#128424; <?= _e('bulk_print') ?></button>
        <a href="/ar/invoices/create" class="btn btn-primary"><?= _e('new_invoice') ?></a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/ar/invoices" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="status" class="form-select" style="width:150px;">
                <option value=""><?= _e('all_statuses') ?></option>
                <?php foreach (['DRAFT','OPEN','PARTIAL','PAID','OVERDUE','CANCELLED','VOID'] as $s): ?>
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
            <input type="text" name="customer" class="form-input" style="width:200px;" value="<?= htmlspecialchars($filters['customer'] ?? '') ?>" placeholder="Search customer...">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/ar/invoices" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Invoice Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px;" class="text-center">
                    <input type="checkbox" id="check-all" onchange="toggleAll(this)">
                </th>
                <th class="sortable"><?= _e('invoice_no') ?></th>
                <th class="sortable"><?= _e('date') ?></th>
                <th class="sortable"><?= _e('due_date') ?></th>
                <th><?= _e('customer') ?></th>
                <th class="text-right"><?= _e('amount') ?></th>
                <th class="text-right"><?= _e('paid_amount') ?></th>
                <th class="text-right"><?= _e('balance') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-center">Aging</th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($invoices)): ?>
                <?php foreach ($invoices as $inv): ?>
                    <?php
                    $balance = (float)($inv['balance_thb'] ?? 0);
                    $dueDate = $inv['due_date'] ?? null;
                    $agingDays = 0;
                    $agingClass = '';
                    if ($dueDate && $inv['status'] !== 'PAID' && $inv['status'] !== 'CANCELLED') {
                        $agingDays = (int)((time() - strtotime($dueDate)) / 86400);
                        if ($agingDays > 90) {
                            $agingClass = 'color:var(--color-danger);font-weight:700;';
                        } elseif ($agingDays > 60) {
                            $agingClass = 'color:var(--color-danger);font-weight:600;';
                        } elseif ($agingDays > 30) {
                            $agingClass = 'color:var(--color-warning);font-weight:600;';
                        } elseif ($agingDays > 0) {
                            $agingClass = 'color:var(--color-warning);';
                        }
                    }
                    ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="row-check" value="<?= htmlspecialchars($inv['invoice_id']) ?>" onchange="updateBulkBtn()">
                        </td>
                        <td><a href="/ar/invoices/<?= htmlspecialchars($inv['invoice_id']) ?>"><?= htmlspecialchars($inv['invoice_no']) ?></a></td>
                        <td><?= htmlspecialchars($inv['invoice_date']) ?></td>
                        <td><?= htmlspecialchars($inv['due_date'] ?? '-') ?></td>
                        <td><?= e($inv['customer_name'] ?? '') ?></td>
                        <td class="text-right"><?= formatMoney($inv['grand_total_thb'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney($inv['paid_amount_thb'] ?? 0) ?></td>
                        <td class="text-right"><strong><?= formatMoney($balance) ?></strong></td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$inv['status']] ?? 'badge-draft' ?>">
                                <?= htmlspecialchars($inv['status']) ?>
                            </span>
                        </td>
                        <td class="text-center" style="<?= $agingClass ?>">
                            <?php if ($agingDays > 0 && $inv['status'] !== 'PAID' && $inv['status'] !== 'CANCELLED'): ?>
                                <?= $agingDays ?>d
                            <?php elseif ($inv['status'] !== 'PAID' && $inv['status'] !== 'CANCELLED' && $agingDays <= 0 && $dueDate): ?>
                                <span style="color:var(--color-success);"><?= abs($agingDays) ?>d left</span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-center actions">
                            <a href="/ar/invoices/<?= htmlspecialchars($inv['invoice_id']) ?>" title="View">&#128065;</a>
                            <a href="/pdf/invoice/<?= htmlspecialchars($inv['invoice_id']) ?>" target="_blank" title="PDF">&#128424;</a>
                            <?php if ($inv['status'] === 'ISSUED' || $inv['status'] === 'PARTIAL'): ?>
                                <a href="/ar/payments/create?invoice_id=<?= htmlspecialchars($inv['invoice_id']) ?>" title="Record Payment">&#128181;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_invoices_found') ?></td>
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
    <input type="hidden" name="type" value="invoice">
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
