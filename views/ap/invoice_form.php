<?php
/**
 * PEGASUS ERP - AP Invoice Create Form
 * Variables: $invoice (null for new), $lines, $order, $suppliers, $paymentTerms
 */
$isEdit = !empty($invoice);
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? __('edit') . ' ' . htmlspecialchars($invoice['ap_invoice_no']) : __('create_ap_invoice') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= __('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/ap/invoices"><?= __('ap_invoices') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= $isEdit ? __('edit') : __('create') ?></span>
        </div>
    </div>
    <a href="/ap/invoices" class="btn btn-cancel"><?= __('back') ?></a>
</div>

<form method="POST" action="/ap/invoices" id="apInvoiceForm">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
    <?php if (!empty($order['po_id'])): ?>
        <input type="hidden" name="po_id" value="<?= htmlspecialchars($order['po_id']) ?>">
    <?php endif; ?>

    <!-- Header Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= __('invoice_details') ?></h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label"><?= __('supplier') ?> <span class="required">*</span></label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($suppliers ?? [] as $s): ?>
                            <option value="<?= htmlspecialchars($s['supplier_id']) ?>" <?= ($order['supplier_id'] ?? '') == $s['supplier_id'] ? 'selected' : '' ?>>
                                <?= e($s['supplier_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('supplier_invoice_no') ?></label>
                    <input type="text" name="supplier_invoice_no" class="form-input" value="<?= htmlspecialchars($invoice['supplier_invoice_no'] ?? '') ?>" placeholder="<?= __('supplier_invoice_no') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('invoice_date') ?> <span class="required">*</span></label>
                    <input type="date" name="invoice_date" class="form-input" value="<?= htmlspecialchars($invoice['invoice_date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('due_date') ?> <span class="required">*</span></label>
                    <input type="date" name="due_date" class="form-input" value="<?= htmlspecialchars($invoice['due_date'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('payment_terms') ?></label>
                    <select name="payment_term_id" class="form-select" id="paymentTermSelect">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($paymentTerms ?? [] as $pt): ?>
                            <option value="<?= htmlspecialchars($pt['term_id']) ?>" data-days="<?= htmlspecialchars($pt['due_days'] ?? '') ?>" <?= ($invoice['payment_term_id'] ?? '') == $pt['term_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pt['term_name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('currency') ?></label>
                    <select name="currency_code" class="form-select">
                        <?php foreach ([['code' => 'THB'],['code' => 'USD'],['code' => 'JPY']] as $cur): ?>
                            <option value="<?= htmlspecialchars($cur['code']) ?>" <?= ($invoice['currency_code'] ?? 'THB') === $cur['code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cur['code']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= __('line_items') ?></h3>
            <button type="button" class="btn btn-primary btn-sm" onclick="addLine()">+ <?= __('add_line') ?></button>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper" style="box-shadow:none;">
                <table class="data-table" id="lineItemsTable">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th><?= __('item_name') ?></th>
                            <th><?= __('description') ?></th>
                            <th style="width:80px;" class="text-right"><?= __('qty') ?></th>
                            <th style="width:70px;"><?= __('unit') ?></th>
                            <th style="width:120px;" class="text-right"><?= __('unit_price') ?></th>
                            <th style="width:130px;" class="text-right"><?= __('amount') ?></th>
                            <th style="width:50px;" class="text-center"><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody id="lineItemsBody">
                        <?php if (!empty($lines)): ?>
                            <?php foreach ($lines as $i => $line): ?>
                                <tr class="line-row" data-line="<?= $i ?>">
                                    <td class="text-center line-no"><?= $i + 1 ?></td>
                                    <td>
                                        <input type="hidden" name="item_id[]" value="<?= htmlspecialchars($line['item_id'] ?? '') ?>">
                                        <input type="text" class="form-input" value="<?= htmlspecialchars($line['item_name'] ?? $line['item_code'] ?? '') ?>" readonly style="background:#f5f5f5;">
                                    </td>
                                    <td><input type="text" name="item_description[]" class="form-input" value="<?= htmlspecialchars($line['item_description'] ?? $line['item_name'] ?? '') ?>"></td>
                                    <td><input type="number" name="quantity[]" class="form-input text-right line-qty" value="<?= htmlspecialchars($line['quantity'] ?? 1) ?>" step="0.01" min="0" onchange="calcLine(this)"></td>
                                    <td><input type="text" name="unit[]" class="form-input" value="<?= htmlspecialchars($line['unit'] ?? 'EA') ?>" style="width:60px;"></td>
                                    <td><input type="number" name="unit_price[]" class="form-input text-right line-price" value="<?= htmlspecialchars($line['unit_price'] ?? 0) ?>" step="0.01" min="0" onchange="calcLine(this)"></td>
                                    <td class="text-right line-amount"><?= number_format(($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0), 2) ?></td>
                                    <td class="text-center actions"><button type="button" class="btn btn-danger btn-sm" onclick="removeLine(this)" title="<?= __('delete') ?>">&times;</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Totals & Notes -->
    <div class="form-grid-2">
        <!-- Left: Notes -->
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= __('notes') ?></h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><?= __('notes') ?></label>
                    <textarea name="notes" class="form-textarea" rows="3"><?= htmlspecialchars($invoice['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right: Totals -->
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= __('summary') ?></h3></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span><?= __('subtotal') ?></span>
                        <span id="displaySubtotal" style="font-weight:600;">0.00</span>
                        <input type="hidden" name="subtotal_thb" id="subtotal" value="0">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <span><?= __('vat_rate') ?> (%)</span>
                        <input type="number" name="vat_rate" id="vatRate" class="form-input text-right" style="width:100px;" value="<?= htmlspecialchars($invoice['vat_rate'] ?? 7) ?>" step="0.01" min="0" onchange="calcTotals()">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span><?= __('vat_amount') ?></span>
                        <span id="displayVat" style="font-weight:600;">0.00</span>
                        <input type="hidden" name="vat_amount" id="vatAmount" value="0">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span><?= __('wht') ?></span>
                        <span id="displayWht" style="font-weight:600;">0.00</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;border-top:2px solid var(--color-border);padding-top:10px;">
                        <span style="font-size:16px;font-weight:700;"><?= __('grand_total') ?></span>
                        <span id="displayGrandTotal" style="font-size:16px;font-weight:700;color:var(--color-primary);">0.00</span>
                        <input type="hidden" name="grand_total_thb" id="grandTotal" value="0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="card" style="display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;">
        <a href="/ap/invoices" class="btn btn-cancel"><?= __('cancel') ?></a>
        <button type="submit" class="btn btn-primary"><?= __('save_invoice') ?></button>
    </div>
</form>

<script>
let lineIndex = <?= !empty($lines) ? count($lines) : 0 ?>;

function addLine() {
    const tbody = document.getElementById('lineItemsBody');
    const i = lineIndex++;
    const tr = document.createElement('tr');
    tr.className = 'line-row';
    tr.dataset.line = i;
    tr.innerHTML = `
        <td class="text-center line-no">${tbody.querySelectorAll('.line-row').length + 1}</td>
        <td>
            <input type="hidden" name="item_id[]" value="">
            <input type="text" class="form-input" placeholder="<?= __('item_name') ?>">
        </td>
        <td><input type="text" name="item_description[]" class="form-input" placeholder="<?= __('description') ?>"></td>
        <td><input type="number" name="quantity[]" class="form-input text-right line-qty" value="1" step="0.01" min="0" onchange="calcLine(this)"></td>
        <td><input type="text" name="unit[]" class="form-input" value="EA" style="width:60px;"></td>
        <td><input type="number" name="unit_price[]" class="form-input text-right line-price" value="0" step="0.01" min="0" onchange="calcLine(this)"></td>
        <td class="text-right line-amount">0.00</td>
        <td class="text-center actions"><button type="button" class="btn btn-danger btn-sm" onclick="removeLine(this)" title="<?= __('delete') ?>">&times;</button></td>
    `;
    tbody.appendChild(tr);
}

function removeLine(btn) {
    btn.closest('tr').remove();
    renumberLines();
    calcTotals();
}

function renumberLines() {
    document.querySelectorAll('#lineItemsBody .line-row').forEach((row, idx) => {
        row.querySelector('.line-no').textContent = idx + 1;
    });
}

function calcLine(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.line-qty')?.value) || 0;
    const price = parseFloat(row.querySelector('.line-price')?.value) || 0;
    const amount = qty * price;
    row.querySelector('.line-amount').textContent = amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    calcTotals();
}

function calcTotals() {
    let subtotal = 0;
    document.querySelectorAll('#lineItemsBody .line-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.line-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.line-price')?.value) || 0;
        subtotal += qty * price;
    });
    const vatRate = parseFloat(document.getElementById('vatRate').value) || 0;
    const vat = subtotal * vatRate / 100;
    const grand = subtotal + vat;

    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('displaySubtotal').textContent = subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('vatAmount').value = vat.toFixed(2);
    document.getElementById('displayVat').textContent = vat.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('grandTotal').value = grand.toFixed(2);
    document.getElementById('displayGrandTotal').textContent = grand.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Auto-calculate due date from payment terms
document.getElementById('paymentTermSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const days = parseInt(opt.dataset.days);
    if (days && days > 0) {
        const invoiceDate = document.querySelector('[name="invoice_date"]').value;
        if (invoiceDate) {
            const d = new Date(invoiceDate);
            d.setDate(d.getDate() + days);
            document.querySelector('[name="due_date"]').value = d.toISOString().split('T')[0];
        }
    }
});

// Recalculate on page load if lines exist
document.addEventListener('DOMContentLoaded', function() { calcTotals(); });
</script>
