<?php
/**
 * PEGASUS ERP - AR Invoice Create Form
 * Variables: $invoice (null for new), $lines, $order, $customers, $paymentTerms
 */
$isEdit = !empty($invoice);
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? __('edit') . ' ' . htmlspecialchars($invoice['invoice_no']) : __('create_ar_invoice') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= __('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/ar/invoices"><?= __('ar_invoices') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= $isEdit ? __('edit') : __('create') ?></span>
        </div>
    </div>
    <a href="/ar/invoices" class="btn btn-cancel"><?= __('back') ?></a>
</div>

<form method="POST" action="/ar/invoices" id="arInvoiceForm">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
    <?php if (!empty($order['so_id'])): ?>
        <input type="hidden" name="so_id" value="<?= htmlspecialchars($order['so_id']) ?>">
    <?php endif; ?>

    <!-- #9: Sales-order picker — populates customer + lines from selected SO -->
    <?php if (!isset($invoice) || !$invoice): ?>
    <div class="card" style="background:#E3F2FD;border-left:4px solid #1976D2;">
        <div class="card-body" style="padding:14px 20px;">
            <label class="form-label" style="font-weight:600;"><?= __('select_from_sales_order') ?></label>
            <div style="display:flex;gap:10px;align-items:center;">
                <select id="soSelector" class="form-select" style="flex:1;">
                    <option value=""><?= __('select_so_to_load') ?></option>
                    <?php foreach (($availableOrders ?? []) as $so): ?>
                        <option value="<?= e($so['so_id']) ?>" <?= ($order['so_id'] ?? '') == $so['so_id'] ? 'selected' : '' ?>>
                            <?= e($so['so_no']) ?> - <?= e($so['customer_name'] ?? '') ?>
                            (<?= formatMoney($so['grand_total_thb']) ?>) [<?= e($so['status']) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-primary" onclick="loadFromSO()"><?= __('load') ?></button>
            </div>
            <small style="color:var(--color-text-muted);font-size:11px;"><?= __('so_picker_hint') ?></small>
            <input type="hidden" name="so_id" value="<?= e($order['so_id'] ?? '') ?>">
        </div>
    </div>
    <script>
    function loadFromSO() {
        var v = document.getElementById('soSelector').value;
        if (v) window.location.href = '/ar/invoices/create?so_id=' + v;
    }
    </script>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= __('invoice_details') ?></h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label"><?= __('customer') ?> <span class="required">*</span></label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($customers ?? [] as $c): ?>
                            <option value="<?= htmlspecialchars($c['customer_id']) ?>" <?= ($order['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
                                <?= e($c['customer_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= __('invoice_date') ?> <span class="required">*</span></label>
                    <input type="date" name="invoice_date" id="invoiceDate" class="form-input" value="<?= htmlspecialchars($invoice['invoice_date'] ?? date('Y-m-d')) ?>" required onchange="recalcDueDate()">
                </div>

                <?php
                    // Compute default due date = invoice_date + credit_days
                    $creditDays = intval($order['credit_days'] ?? 0);
                    $defaultDue = $invoice['due_date'] ?? '';
                    if (!$defaultDue && $creditDays > 0) {
                        $defaultDue = date('Y-m-d', strtotime('+' . $creditDays . ' days'));
                    } elseif (!$defaultDue) {
                        $defaultDue = date('Y-m-d', strtotime('+30 days'));
                    }
                ?>
                <div class="form-group">
                    <label class="form-label"><?= __('payment_due_date') ?> <span class="required">*</span></label>
                    <input type="date" name="due_date" id="dueDate" class="form-input"
                           value="<?= htmlspecialchars($defaultDue) ?>"
                           data-credit-days="<?= e($creditDays) ?>" required>
                </div>
                <script>
                function recalcDueDate() {
                    var inv = document.getElementById('invoiceDate');
                    var due = document.getElementById('dueDate');
                    if (!inv || !due || !inv.value) return;
                    var days = parseInt(due.dataset.creditDays || '30', 10) || 30;
                    var d = new Date(inv.value);
                    d.setDate(d.getDate() + days);
                    var y = d.getFullYear(), m = String(d.getMonth()+1).padStart(2,'0'), dd = String(d.getDate()).padStart(2,'0');
                    due.value = y + '-' + m + '-' + dd;
                }
                </script>

                <div class="form-group">
                    <label class="form-label">
                        <?= __('payment_terms') ?>
                        <span style="font-size:11px;color:var(--color-text-muted);">(<?= __('locked_from_quotation') ?>)</span>
                    </label>
                    <?php $isAdmin = (function_exists('Auth') && method_exists('Auth','isAdmin')) ? Auth::isAdmin() : (Auth::isAdmin() ?? false); ?>
                    <?php $lockedTermId = $invoice['payment_term_id'] ?? ($order['payment_term_id'] ?? ''); ?>
                    <select name="payment_term_id" class="form-select" id="paymentTermSelect" <?= !$isAdmin ? 'disabled' : '' ?> style="<?= !$isAdmin ? 'background:#f5f5f5;' : '' ?>">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($paymentTerms ?? [] as $pt): ?>
                            <option value="<?= htmlspecialchars($pt['term_id']) ?>" data-days="<?= htmlspecialchars($pt['credit_days'] ?? '') ?>" <?= $lockedTermId == $pt['term_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pt['term_name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$isAdmin): ?>
                        <input type="hidden" name="payment_term_id" value="<?= htmlspecialchars($lockedTermId) ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- #9: Split Invoicing — installment picker (shown only when payment term has multiple installments) -->
    <?php if (!empty($installments) && count($installments) > 1 && !empty($order) && !isset($invoice)): ?>
    <?php
        $grandTotal = floatval($order['grand_total_thb'] ?? 0);
        $soSubtotal = floatval($order['subtotal_thb'] ?? 0);
        // Collect covered installment seqs (single + CSV bundle)
        $invoicedSeqs = [];
        foreach (($existingInvoices ?? []) as $ei) {
            if (!empty($ei['installment_seq'])) $invoicedSeqs[] = (int)$ei['installment_seq'];
            if (!empty($ei['installment_seqs'])) {
                foreach (explode(',', $ei['installment_seqs']) as $s) {
                    $s = (int)trim($s);
                    if ($s > 0) $invoicedSeqs[] = $s;
                }
            }
        }
        $invoicedSeqs = array_unique($invoicedSeqs);
        // Compute remaining %
        $invoicedPct = 0;
        foreach ($installments as $ins) {
            if (in_array((int)$ins['seq_no'], $invoicedSeqs, true)) {
                $invoicedPct += floatval($ins['percentage']);
            }
        }
        $remainingPct = max(0, 100 - $invoicedPct);
        $remainingAmount = round($soSubtotal * $remainingPct / 100, 2);
    ?>
    <div class="card" style="background:#FFF8E1;border-left:4px solid #FB8C00;margin-top:10px;">
        <div class="card-header" style="background:transparent;">
            <h3 class="card-title"><?= __('split_invoicing') ?></h3>
        </div>
        <div class="card-body">
            <p style="font-size:12px;color:var(--color-text-muted);margin-bottom:10px;">
                <?= __('split_invoicing_hint') ?>
            </p>
            <table class="data-table" style="font-size:12px;">
                <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th style="width:50px;">#</th>
                    <th style="width:70px;" class="text-right">%</th>
                    <th><?= __('description') ?></th>
                    <th><?= __('trigger') ?></th>
                    <th style="width:80px;" class="text-right"><?= __('credit_days') ?></th>
                    <th class="text-right"><?= __('amount') ?> (THB)</th>
                    <th style="width:90px;" class="text-center"><?= __('status') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($installments as $i => $ins):
                    $pct = floatval($ins['percentage']);
                    $amount = round($soSubtotal * $pct / 100, 2);
                    $alreadyInvoiced = in_array((int)$ins['seq_no'], $invoicedSeqs, true);
                    // Default: first un-invoiced installment is pre-checked
                    $firstUninvoicedSeq = null;
                    foreach ($installments as $x) {
                        if (!in_array((int)$x['seq_no'], $invoicedSeqs, true)) {
                            $firstUninvoicedSeq = (int)$x['seq_no'];
                            break;
                        }
                    }
                    $preCheck = !$alreadyInvoiced && (int)$ins['seq_no'] === $firstUninvoicedSeq;
                ?>
                    <tr style="<?= $alreadyInvoiced ? 'opacity:0.5;background:#F5F5F5;' : '' ?>">
                        <td class="text-center">
                            <input type="checkbox" name="installment_seqs[]" value="<?= e($ins['seq_no']) ?>"
                                   class="installment-check"
                                   <?= $preCheck ? 'checked' : '' ?>
                                   <?= $alreadyInvoiced ? 'disabled' : '' ?>
                                   data-pct="<?= e($pct) ?>"
                                   data-amount="<?= e($amount) ?>"
                                   data-days="<?= e($ins['credit_days']) ?>">
                        </td>
                        <td><strong><?= e($ins['seq_no']) ?></strong></td>
                        <td class="text-right"><strong><?= number_format($pct, 2) ?>%</strong></td>
                        <td><?= e($ins['description_en'] ?? $ins['description_jp'] ?? '') ?></td>
                        <td><span class="badge" style="background:#E3F2FD;color:#1976D2;"><?= e($ins['trigger_type']) ?></span></td>
                        <td class="text-right"><?= e($ins['credit_days'] ?? '—') ?></td>
                        <td class="text-right"><strong><?= number_format($amount, 2) ?></strong></td>
                        <td class="text-center">
                            <?php if ($alreadyInvoiced): ?>
                                <span class="badge badge-paid"><?= __('invoiced') ?></span>
                            <?php else: ?>
                                <span class="badge badge-draft"><?= __('pending') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!-- Quick-action row: select ALL remaining (un-invoiced) installments at once -->
                <?php if ($remainingPct > 0): ?>
                <tr style="background:#FFF8E1;">
                    <td class="text-center">
                        <button type="button" class="btn btn-cancel btn-sm" style="padding:2px 8px;"
                                onclick="selectAllRemaining()">&#9745; <?= __('select_all_remaining') ?></button>
                    </td>
                    <td colspan="2" style="font-weight:600;"><?= __('remaining_balance') ?></td>
                    <td colspan="3" style="color:#E65100;">
                        <?= number_format($remainingPct, 2) ?>% <?= __('remaining_of_total') ?>
                    </td>
                    <td class="text-right"><strong style="color:#E65100;"><?= number_format($remainingAmount, 2) ?></strong></td>
                    <td></td>
                </tr>
                <?php endif; ?>
                <!-- Running total of selection -->
                <tr style="background:#E3F2FD;">
                    <td class="text-center">Σ</td>
                    <td colspan="2" style="font-weight:700;"><?= __('selected_total') ?></td>
                    <td colspan="3"><span id="selectedPctLabel">0.00%</span></td>
                    <td class="text-right"><strong id="selectedAmountLabel">0.00</strong></td>
                    <td></td>
                </tr>
                </tbody>
            </table>
            <script>
            function selectAllRemaining() {
                document.querySelectorAll('.installment-check:not(:disabled)').forEach(cb => cb.checked = true);
                document.dispatchEvent(new Event('installment-change'));
            }
            document.addEventListener('DOMContentLoaded', function(){
                const checks = document.querySelectorAll('input.installment-check');
                const lineCard = document.getElementById('lineItemsCard');
                const pctLabel = document.getElementById('selectedPctLabel');
                const amtLabel = document.getElementById('selectedAmountLabel');
                function refresh() {
                    let pct = 0, amt = 0, days = 0;
                    const selected = document.querySelectorAll('input.installment-check:checked');
                    selected.forEach(cb => {
                        pct += parseFloat(cb.dataset.pct || 0);
                        amt += parseFloat(cb.dataset.amount || 0);
                        // use the longest credit_days for the due-date calc
                        const d = parseInt(cb.dataset.days || '0', 10);
                        if (d > days) days = d;
                    });
                    if (pctLabel) pctLabel.textContent = pct.toFixed(2) + '%';
                    if (amtLabel) amtLabel.textContent = amt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    // Hide line-items card whenever at least one installment is selected
                    if (lineCard) lineCard.style.display = selected.length > 0 ? 'none' : '';
                    if (days > 0) {
                        const dueInp = document.getElementById('dueDate');
                        if (dueInp) dueInp.dataset.creditDays = days;
                        if (typeof recalcDueDate === 'function') recalcDueDate();
                    }
                }
                checks.forEach(cb => cb.addEventListener('change', refresh));
                document.addEventListener('installment-change', refresh);
                refresh();
            });
            </script>
        </div>
    </div>
    <?php endif; ?>

    <!-- Currency / Exchange rate -->
    <div class="card">
        <div class="card-body">
            <div class="form-grid-2">
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

                <div class="form-group">
                    <label class="form-label"><?= __('exchange_rate') ?></label>
                    <input type="number" name="exchange_rate" class="form-input" value="<?= htmlspecialchars($invoice['exchange_rate'] ?? 1) ?>" step="0.0001" min="0">
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items (hidden when split invoicing is active) -->
    <div class="card" id="lineItemsCard">
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
        <a href="/ar/invoices" class="btn btn-cancel"><?= __('cancel') ?></a>
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
