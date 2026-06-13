<?php
/**
 * PEGASUS ERP - Purchase Order Create/Edit Form
 * Variables: $order (null for new), $suppliers, $paymentTerms, $currencies, $whtRates, $errors
 */
$isEdit = !empty($order) && !empty($order['po_id']);
$fromPrId = !empty($order['from_pr_id']) ? (int)$order['from_pr_id'] : 0;
$fromPrNo = $order['from_pr_no'] ?? '';
$pageTitle = ($isEdit ? __('edit_po') . " #{$order['po_no']}" : __('new_purchase_order')) . ' - PEGASUS ERP';
?>
<?php if ($fromPrId): ?>
<div class="card" style="background:#E3F2FD;border-left:4px solid #1976D2;padding:12px 16px;margin-bottom:12px;">
    <strong>📋 <?= _e('pr_convert_to_po') ?>:</strong> <?= htmlspecialchars($fromPrNo) ?>
    (<a href="/purchasing/requests/<?= $fromPrId ?>"><?= _e('view') ?> PR</a>)
</div>
<?php elseif (!$isEdit): ?>
<!-- PR picker — REQUIRED to create a PO -->
<div class="card" style="background:#FFF3E0;border-left:4px solid #FB8C00;padding:14px 18px;margin-bottom:12px;">
    <h3 style="margin:0 0 8px 0;color:#E65100;">⚠ <?= _e('po_requires_pr') ?></h3>
    <p style="margin:0 0 10px 0;color:#555;font-size:13px;">
        <?= _e('po_pick_pr_hint') ?>
    </p>
    <?php if (empty($approvedPrs)): ?>
        <p style="color:#C62828;"><?= _e('po_no_approved_prs') ?></p>
        <a href="/purchasing/requests" class="btn btn-secondary">→ <?= _e('menu_purchase_requests') ?></a>
    <?php else: ?>
        <form method="GET" action="/purchasing/orders/create" style="display:flex;gap:8px;align-items:end;">
            <div class="form-group" style="flex:1;margin:0;">
                <label class="form-label"><?= _e('pr_pick_approved') ?> <span style="color:red;">*</span></label>
                <select name="from_pr_id" class="form-select" required>
                    <option value=""><?= _e('select_pr') ?></option>
                    <?php foreach ($approvedPrs as $pr): ?>
                        <option value="<?= (int)$pr['pr_id'] ?>">
                            <?= htmlspecialchars($pr['pr_no']) ?>
                            — <?= htmlspecialchars($pr['requester_name'] ?: '-') ?>
                            — <?= htmlspecialchars($pr['request_date']) ?>
                            — ฿<?= number_format((float)$pr['est_total_thb'], 2) ?>
                            <?= !empty($pr['suggested_supplier_name'])
                                ? '(→ ' . htmlspecialchars($pr['suggested_supplier_name']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= _e('load_from_pr') ?? 'Load from PR' ?></button>
        </form>
    <?php endif; ?>
</div>
<!-- Block the form below until a PR is chosen -->
<div class="card" style="padding:30px;text-align:center;color:#888;">
    <?= _e('po_select_pr_first') ?>
</div>
<?php endif; ?>

<?php if ($isEdit || $fromPrId): // only render the actual form when PR linked or editing existing PO ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? _e('edit_po') . ' #' . htmlspecialchars($order['po_no']) : _e('new_purchase_order') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/purchasing/orders"><?= _e('purchasing') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/purchasing/orders"><?= _e('purchase_orders') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= $isEdit ? _e('edit') : _e('create') ?></span>
        </div>
    </div>
    <a href="/purchasing/orders" class="btn btn-cancel"><?= _e('back_to_list') ?></a>
</div>

<form method="POST" action="<?= $isEdit ? '/purchasing/orders/' . htmlspecialchars($order['po_id']) : '/purchasing/orders' ?>" id="poForm">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>
    <?php if ($fromPrId): ?>
        <input type="hidden" name="from_pr_id" value="<?= $fromPrId ?>">
    <?php endif; ?>

    <!-- Header Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= _e('po_details') ?></h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <?php
                $poSupplierOptions = array_map(fn($s) => ['value' => $s['supplier_id'], 'label' => $s['supplier_name'] ?? ''], $suppliers ?? []);
                $poCurrencyOptions = array_map(fn($c) => ['value' => $c['code'], 'label' => $c['code']], $currencies ?? [['code' => 'THB'], ['code' => 'USD'], ['code' => 'JPY']]);
                $poTermOptions = array_map(fn($pt) => ['value' => $pt['term_id'], 'label' => $pt['term_name_en']], $paymentTerms ?? []);
                $poProjectOptions = array_map(function ($p) {
                    $label = $p['pj_no'];
                    if (!empty($p['pj_name'])) { $label .= ' — ' . mb_substr($p['pj_name'], 0, 40); }
                    if (!empty($p['customer_name'])) { $label .= ' (' . mb_substr($p['customer_name'], 0, 25) . ')'; }
                    return ['value' => $p['project_id'], 'label' => $label];
                }, $projects ?? []);
                ?>
                <!-- Left Column -->
                <?= Form::select('supplier_id', _e('supplier'), $poSupplierOptions, [
                    'value' => $order['supplier_id'] ?? '',
                    'required' => true,
                    'none_label' => '-- ' . _e('select_supplier') . ' --',
                    'error' => $errors['supplier_id'] ?? false,
                ]) ?>

                <?= Form::datetime('order_date', _e('order_date'), [
                    'mode' => 'date',
                    'value' => $order['order_date'] ?? date('Y-m-d'),
                    'required' => true,
                ]) ?>

                <?= Form::text('contact_person_name', _e('contact_person'), [
                    'value' => $order['contact_person_name'] ?? '',
                    'placeholder' => _e('supplier_contact'),
                ]) ?>

                <?= Form::datetime('delivery_date', _e('delivery_date'), [
                    'mode' => 'date',
                    'value' => $order['delivery_date'] ?? '',
                ]) ?>

                <?= Form::select('currency_code', _e('currency'), $poCurrencyOptions, [
                    'value' => $order['currency_code'] ?? 'THB',
                    'none_label' => null,
                ]) ?>

                <?= Form::select('payment_term_id', _e('payment_terms'), $poTermOptions, [
                    'value' => $order['payment_term_id'] ?? '',
                    'none_label' => '-- ' . _e('select') . ' --',
                ]) ?>

                <!-- PJ No. (project) -->
                <?= Form::select('project_id', __('pj_no'), $poProjectOptions, [
                    'value' => $order['project_id'] ?? '',
                    'none_label' => '-- ' . _e('select') . ' --',
                ]) ?>

                <?= Form::textarea('notes', _e('notes'), [
                    'value' => $order['notes'] ?? '',
                    'rows' => 2,
                    'placeholder' => _e('notes'),
                    'group_class' => 'form-full',
                ]) ?>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= _e('line_items') ?></h3>
            <button type="button" class="btn btn-primary btn-sm" onclick="addLine()">+ <?= _e('add_line') ?></button>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper" style="box-shadow:none;">
                <table class="data-table" id="lineItemsTable">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th><?= _e('description') ?></th>
                            <th style="width:80px;" class="text-right"><?= _e('qty') ?></th>
                            <th style="width:70px;"><?= _e('unit') ?></th>
                            <th style="width:120px;" class="text-right"><?= _e('unit_price') ?></th>
                            <th style="width:80px;" class="text-right"><?= _e('disc') ?> %</th>
                            <th style="width:130px;" class="text-right"><?= _e('amount') ?></th>
                            <th style="width:50px;" class="text-center"><?= _e('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody id="lineItemsBody">
                        <?php if (!empty($order['lines'])): ?>
                            <?php foreach ($order['lines'] as $i => $line): ?>
                                <tr class="line-row" data-line="<?= $i ?>">
                                    <td class="text-center line-no"><?= $i + 1 ?></td>
                                    <td><input type="text" name="lines[<?= $i ?>][item_description]" class="form-input" value="<?= htmlspecialchars($line['item_description']) ?>" required></td>
                                    <td><input type="number" name="lines[<?= $i ?>][quantity]" class="form-input text-right line-qty" value="<?= htmlspecialchars($line['quantity']) ?>" step="0.01" min="0" onchange="calcLine(this)"></td>
                                    <td><input type="text" name="lines[<?= $i ?>][unit]" class="form-input" value="<?= htmlspecialchars($line['unit'] ?? 'EA') ?>" style="width:60px;"></td>
                                    <td><input type="number" name="lines[<?= $i ?>][unit_price]" class="form-input text-right line-price" value="<?= htmlspecialchars($line['unit_price']) ?>" step="0.01" min="0" onchange="calcLine(this)"></td>
                                    <td><input type="number" name="lines[<?= $i ?>][discount_rate]" class="form-input text-right line-disc" value="<?= htmlspecialchars($line['discount_rate'] ?? 0) ?>" step="0.01" min="0" max="100" onchange="calcLine(this)"></td>
                                    <td class="text-right line-amount"><?= formatMoney($line['ext_price'] ?? 0) ?></td>
                                    <td class="text-center actions"><button type="button" class="btn btn-danger btn-sm" onclick="removeLine(this)" title="<?= _e('remove') ?>">&times;</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Totals & Remarks -->
    <div class="form-grid-2">
        <!-- Left: Remarks -->
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= _e('notes') ?></h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><?= _e('notes') ?></label>
                    <textarea name="notes" class="form-textarea" rows="3"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= _e('reference_no') ?></label>
                    <input type="text" name="reference_no" class="form-input" value="<?= htmlspecialchars($order['reference_no'] ?? '') ?>" placeholder="<?= _e('reference_no') ?>">
                </div>
            </div>
        </div>

        <!-- Right: Totals -->
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= _e('summary') ?></h3></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span><?= _e('subtotal') ?></span>
                        <span id="displaySubtotal" style="font-weight:600;"><?= formatMoney($order['subtotal_thb'] ?? 0) ?></span>
                        <input type="hidden" name="subtotal_thb" id="subtotal" value="<?= htmlspecialchars($order['subtotal_thb'] ?? 0) ?>">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <span><?= _e('discount') ?></span>
                        <input type="number" name="discount_amount" id="discountAmount" class="form-input text-right" style="width:140px;" value="<?= htmlspecialchars($order['discount_amount'] ?? 0) ?>" step="0.01" min="0" onchange="calcTotals()">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <span><?= _e('vat_rate') ?> (%)</span>
                        <input type="number" name="vat_rate" id="vatRate" class="form-input text-right" style="width:100px;" value="<?= htmlspecialchars($order['vat_rate'] ?? 7) ?>" step="0.01" min="0" onchange="calcTotals()">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span><?= _e('vat_amount') ?></span>
                        <span id="displayVat" style="font-weight:600;"><?= formatMoney($order['vat_amount'] ?? 0) ?></span>
                        <input type="hidden" name="vat_amount" id="vatAmount" value="<?= htmlspecialchars($order['vat_amount'] ?? 0) ?>">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <span><?= _e('wht_rate') ?> (%)</span>
                        <select name="wht_rate" id="whtRate" class="form-select" style="width:120px;" onchange="calcTotals()">
                            <option value="0" <?= ($order['wht_rate'] ?? 0) == 0 ? 'selected' : '' ?>><?= _e('wht_none') ?></option>
                            <option value="1" <?= ($order['wht_rate'] ?? 0) == 1 ? 'selected' : '' ?>>1%</option>
                            <option value="2" <?= ($order['wht_rate'] ?? 0) == 2 ? 'selected' : '' ?>>2%</option>
                            <option value="3" <?= ($order['wht_rate'] ?? 0) == 3 ? 'selected' : '' ?>>3%</option>
                            <option value="5" <?= ($order['wht_rate'] ?? 0) == 5 ? 'selected' : '' ?>>5%</option>
                        </select>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span><?= _e('wht_amount') ?></span>
                        <span id="displayWht" style="font-weight:600;color:var(--color-danger);">-<?= formatMoney($order['wht_amount'] ?? 0) ?></span>
                        <input type="hidden" name="wht_amount" id="whtAmount" value="<?= htmlspecialchars($order['wht_amount'] ?? 0) ?>">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;border-top:2px solid var(--color-border);padding-top:10px;">
                        <span style="font-size:16px;font-weight:700;"><?= _e('payment_amount') ?></span>
                        <span id="displayNetTotal" style="font-size:16px;font-weight:700;color:var(--color-primary);"><?= formatMoney($order['payment_amount'] ?? 0) ?></span>
                        <input type="hidden" name="payment_amount" id="netTotal" value="<?= htmlspecialchars($order['payment_amount'] ?? 0) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="card" style="display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;">
        <a href="/purchasing/orders" class="btn btn-cancel"><?= _e('cancel') ?></a>
        <button type="submit" name="action" value="draft" class="btn btn-cancel"><?= _e('save_as_draft') ?></button>
        <button type="submit" name="action" value="submit" class="btn btn-primary"><?= _e('submit_po') ?></button>
    </div>
</form>
<?php endif; // /isEdit || $fromPrId ?>

<script>
let lineIndex = <?= isset($order['lines']) ? count($order['lines']) : 0 ?>;

function addLine() {
    const tbody = document.getElementById('lineItemsBody');
    const i = lineIndex++;
    const tr = document.createElement('tr');
    tr.className = 'line-row';
    tr.dataset.line = i;
    tr.innerHTML = `
        <td class="text-center line-no">${tbody.querySelectorAll('.line-row').length + 1}</td>
        <td><input type="text" name="lines[${i}][item_description]" class="form-input" required></td>
        <td><input type="number" name="lines[${i}][quantity]" class="form-input text-right line-qty" value="1" step="0.01" min="0" onchange="calcLine(this)"></td>
        <td><input type="text" name="lines[${i}][unit]" class="form-input" value="EA" style="width:60px;"></td>
        <td><input type="number" name="lines[${i}][unit_price]" class="form-input text-right line-price" value="0" step="0.01" min="0" onchange="calcLine(this)"></td>
        <td><input type="number" name="lines[${i}][discount_rate]" class="form-input text-right line-disc" value="0" step="0.01" min="0" max="100" onchange="calcLine(this)"></td>
        <td class="text-right line-amount">0.00</td>
        <td class="text-center actions"><button type="button" class="btn btn-danger btn-sm" onclick="removeLine(this)" title="Remove">&times;</button></td>
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
    const disc = parseFloat(row.querySelector('.line-disc')?.value) || 0;
    const amount = qty * price * (1 - disc / 100);
    row.querySelector('.line-amount').textContent = amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    calcTotals();
}

function calcTotals() {
    let subtotal = 0;
    document.querySelectorAll('#lineItemsBody .line-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.line-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.line-price')?.value) || 0;
        const disc = parseFloat(row.querySelector('.line-disc')?.value) || 0;
        subtotal += qty * price * (1 - disc / 100);
    });
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const vatRate = parseFloat(document.getElementById('vatRate').value) || 0;
    const whtRate = parseFloat(document.getElementById('whtRate').value) || 0;
    const afterDiscount = subtotal - discount;
    const vat = afterDiscount * vatRate / 100;
    const wht = afterDiscount * whtRate / 100;
    const net = afterDiscount + vat - wht;

    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('displaySubtotal').textContent = subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('vatAmount').value = vat.toFixed(2);
    document.getElementById('displayVat').textContent = vat.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('whtAmount').value = wht.toFixed(2);
    document.getElementById('displayWht').textContent = '-' + wht.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('netTotal').value = net.toFixed(2);
    document.getElementById('displayNetTotal').textContent = net.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
