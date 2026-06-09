<?php
/**
 * PEGASUS ERP - AR Payments
 * Variables: $payments, $filters, $pagination, $customers, $bankAccounts
 */
$pageTitle = 'AR Payments - PEGASUS ERP';

$statusClasses = [
    'DRAFT'     => 'badge-draft',
    'CONFIRMED' => 'badge-approved',
    'VOIDED'    => 'badge-rejected',
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('ar_payments') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/ar/invoices"><?= _e('menu_ar') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('ar_payments') ?></span>
        </div>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('paymentModal').classList.add('active')"><?= _e('new_payment') ?></button>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/ar/payments" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="status" class="form-select" style="width:150px;">
                <option value=""><?= _e('all_statuses') ?></option>
                <?php foreach (['DRAFT','CONFIRMED','VOIDED'] as $s): ?>
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
        <a href="/ar/payments" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Payment Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th class="sortable">Payment No</th>
                <th class="sortable"><?= _e('date') ?></th>
                <th><?= _e('customer') ?></th>
                <th><?= _e('invoice_no') ?></th>
                <th><?= _e('payment_method') ?></th>
                <th class="text-right"><?= _e('amount') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($payments)): ?>
                <?php foreach ($payments as $pmt): ?>
                    <tr>
                        <td><a href="/ar/payments/<?= htmlspecialchars($pmt['payment_id']) ?>"><?= htmlspecialchars($pmt['payment_no']) ?></a></td>
                        <td><?= htmlspecialchars($pmt['payment_date']) ?></td>
                        <td><?= e($pmt['customer_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($pmt['invoice_refs'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($pmt['payment_method'] ?? '-') ?></td>
                        <td class="text-right"><strong><?= formatMoney($pmt['amount_thb'] ?? 0) ?></strong></td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$pmt['status']] ?? 'badge-draft' ?>">
                                <?= htmlspecialchars($pmt['status']) ?>
                            </span>
                        </td>
                        <td class="text-center actions">
                            <a href="/ar/payments/<?= htmlspecialchars($pmt['payment_id']) ?>" title="View">&#128065;</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_payments_found') ?></td>
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

<!-- Record Payment Modal -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Record AR Payment</h3>
            <button class="modal-close" onclick="document.getElementById('paymentModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="/ar/payments">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Customer <span class="required">*</span></label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($customers ?? [] as $c): ?>
                                <option value="<?= htmlspecialchars($c['customer_id']) ?>"><?= e($c['customer_name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" name="payment_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method <span class="required">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="BANK_TRANSFER">Bank Transfer</option>
                            <option value="CASH">Cash</option>
                            <option value="CHEQUE">Cheque</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-input" placeholder="Bank name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (THB) <span class="required">*</span></label>
                        <input type="number" name="amount_thb" class="form-input" step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reference / Cheque No</label>
                        <input type="text" name="reference_no" class="form-input" placeholder="Reference number">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Invoice No (to allocate)</label>
                        <input type="text" name="invoice_no" class="form-input" placeholder="e.g. INV-2026-0001" value="<?= htmlspecialchars($_GET['invoice_id'] ?? '') ?>">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-textarea" rows="2" placeholder="Payment notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="document.getElementById('paymentModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Record Payment</button>
            </div>
        </form>
    </div>
</div>
