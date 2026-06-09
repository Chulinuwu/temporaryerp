<?php
/**
 * PEGASUS ERP - AP Payments
 * Variables: $payments, $filters, $pagination, $suppliers, $bankAccounts
 */
$pageTitle = 'AP Payments - PEGASUS ERP';

$statusClasses = [
    'DRAFT'     => 'badge-draft',
    'CONFIRMED' => 'badge-approved',
    'VOIDED'    => 'badge-rejected',
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('ap_payments') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/ap/invoices"><?= _e('menu_ap') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('ap_payments') ?></span>
        </div>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('paymentModal').classList.add('active')"><?= _e('new_payment') ?></button>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/ap/payments" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
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
            <input type="text" name="supplier" class="form-input" style="width:200px;" value="<?= htmlspecialchars($filters['supplier'] ?? '') ?>" placeholder="Search supplier...">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/ap/payments" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Payment Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th class="sortable">Payment No</th>
                <th class="sortable"><?= _e('date') ?></th>
                <th><?= _e('supplier') ?></th>
                <th><?= _e('invoice_no') ?></th>
                <th><?= _e('payment_method') ?></th>
                <th class="text-right"><?= _e('amount') ?></th>
                <th class="text-right"><?= _e('wht') ?></th>
                <th class="text-right"><?= _e('net_total') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($payments)): ?>
                <?php foreach ($payments as $pmt): ?>
                    <tr>
                        <td><a href="/ap/payments/<?= htmlspecialchars($pmt['payment_id']) ?>"><?= htmlspecialchars($pmt['payment_no']) ?></a></td>
                        <td><?= htmlspecialchars($pmt['payment_date']) ?></td>
                        <td><?= e($pmt['supplier_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($pmt['invoice_refs'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($pmt['payment_method'] ?? '-') ?></td>
                        <td class="text-right"><?= formatMoney($pmt['amount_thb'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney($pmt['wht_amount'] ?? 0) ?></td>
                        <td class="text-right"><strong><?= formatMoney(($pmt['amount_thb'] ?? 0) - ($pmt['wht_amount'] ?? 0)) ?></strong></td>
                        <td class="text-center">
                            <span class="badge <?= $statusClasses[$pmt['status']] ?? 'badge-draft' ?>">
                                <?= htmlspecialchars($pmt['status']) ?>
                            </span>
                        </td>
                        <td class="text-center actions">
                            <a href="/ap/payments/<?= htmlspecialchars($pmt['payment_id']) ?>" title="View">&#128065;</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_payments_found') ?></td>
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
            <h3 class="modal-title">Record AP Payment</h3>
            <button class="modal-close" onclick="document.getElementById('paymentModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="/ap/payments">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Supplier <span class="required">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers ?? [] as $s): ?>
                                <option value="<?= htmlspecialchars($s['supplier_id']) ?>"><?= e($s['supplier_name'] ?? '') ?></option>
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
                        <input type="number" name="amount_thb" id="apGrossAmount" class="form-input" step="0.01" min="0.01" required placeholder="0.00" onchange="calcApNet()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WHT Rate</label>
                        <select name="wht_rate" id="apWhtRate" class="form-select" onchange="calcApNet()">
                            <option value="0">None</option>
                            <option value="1">1%</option>
                            <option value="2">2%</option>
                            <option value="3">3%</option>
                            <option value="5">5%</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">WHT Amount</label>
                        <input type="number" name="wht_amount" id="apWhtAmount" class="form-input" step="0.01" value="0" readonly style="background:#F5F5F5;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Net Paid</label>
                        <input type="number" name="net_paid" id="apNetPaid" class="form-input" step="0.01" value="0" readonly style="background:#F5F5F5;font-weight:600;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reference / Cheque No</label>
                        <input type="text" name="reference_no" class="form-input" placeholder="Reference number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Invoice No (to allocate)</label>
                        <input type="text" name="ap_invoice_no" class="form-input" placeholder="e.g. AP-INV-0001" value="<?= htmlspecialchars($_GET['invoice_id'] ?? '') ?>">
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

<script>
function calcApNet() {
    const gross = parseFloat(document.getElementById('apGrossAmount').value) || 0;
    const whtRate = parseFloat(document.getElementById('apWhtRate').value) || 0;
    const wht = gross * whtRate / 100;
    const net = gross - wht;
    document.getElementById('apWhtAmount').value = wht.toFixed(2);
    document.getElementById('apNetPaid').value = net.toFixed(2);
}
</script>
