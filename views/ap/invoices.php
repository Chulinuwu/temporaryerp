<?php
/**
 * PEGASUS ERP - AP Invoice List
 * Variables: $invoices, $filters, $pagination
 */
$pageTitle = 'AP Invoices - PEGASUS ERP';

$statusClasses = [
    'DRAFT'     => 'badge-draft',
    'RECEIVED'  => 'badge-open',
    'APPROVED'  => 'badge-approved',
    'PARTIAL'   => 'badge-pending',
    'PAID'      => 'badge-paid',
    'OVERDUE'   => 'badge-overdue',
    'CANCELLED' => 'badge-rejected',
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('ap_invoices') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/ap/invoices"><?= _e('menu_ap') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_invoices') ?></span>
        </div>
    </div>
    <a href="/ap/invoices/create" class="btn btn-primary"><?= _e('new_invoice') ?></a>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/ap/invoices" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="status" class="form-select" style="width:150px;">
                <option value=""><?= _e('all_statuses') ?></option>
                <?php foreach (['DRAFT','RECEIVED','APPROVED','PARTIAL','PAID','OVERDUE','CANCELLED'] as $s): ?>
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
            <input type="text" name="supplier" class="form-input" style="width:200px;" value="<?= htmlspecialchars($filters['supplier'] ?? '') ?>" placeholder="Search supplier...">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/ap/invoices" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Invoice Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th class="sortable"><?= _e('invoice_no') ?></th>
                <th><?= _e('reference') ?></th>
                <th class="sortable"><?= _e('date') ?></th>
                <th class="sortable"><?= _e('due_date') ?></th>
                <th><?= _e('supplier') ?></th>
                <th class="text-right"><?= _e('amount') ?></th>
                <th class="text-right"><?= _e('wht') ?></th>
                <th class="text-right"><?= _e('net_total') ?></th>
                <th class="text-right"><?= _e('paid_amount') ?></th>
                <th class="text-right"><?= _e('balance') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
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
                    if ($dueDate && !in_array($inv['status'], ['PAID','CANCELLED'])) {
                        $agingDays = (int)((time() - strtotime($dueDate)) / 86400);
                    }
                    ?>
                    <tr>
                        <td><a href="/ap/invoices/<?= htmlspecialchars($inv['ap_invoice_id']) ?>"><?= htmlspecialchars($inv['ap_invoice_no']) ?></a></td>
                        <td><?= htmlspecialchars($inv['supplier_invoice_no'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($inv['invoice_date']) ?></td>
                        <td><?= htmlspecialchars($inv['due_date'] ?? '-') ?></td>
                        <td><?= e($inv['supplier_name'] ?? '') ?></td>
                        <td class="text-right"><?= formatMoney($inv['grand_total_thb'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney($inv['wht_amount'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney(($inv['grand_total_thb'] ?? 0) - ($inv['wht_amount'] ?? 0)) ?></td>
                        <td class="text-right"><?= formatMoney($inv['paid_amount_thb'] ?? 0) ?></td>
                        <td class="text-right"><strong><?= formatMoney($balance) ?></strong></td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$inv['status']] ?? 'badge-draft' ?>">
                                <?= htmlspecialchars($inv['status']) ?>
                            </span>
                            <?php if ($agingDays > 0): ?>
                                <br><span style="font-size:11px;color:var(--color-danger);"><?= $agingDays ?>d overdue</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center actions">
                            <a href="/ap/invoices/<?= htmlspecialchars($inv['ap_invoice_id']) ?>" title="View">&#128065;</a>
                            <?php if (in_array($inv['status'], ['RECEIVED','APPROVED','PARTIAL'])): ?>
                                <a href="/ap/payments/create?invoice_id=<?= htmlspecialchars($inv['ap_invoice_id']) ?>" title="Make Payment">&#128181;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_invoices_found') ?></td>
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
