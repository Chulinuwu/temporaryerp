<?php
/**
 * PEGASUS ERP - Quotation Create/Edit Form
 * Features: Single category (分類 1,2,3,4), Items (1-1,1-2), Item master selection, Cost/Price separation
 * Insert category/item at any position via per-row dropdown
 * Variables: $quotation (null for new), $customers, $paymentTerms, $currencies, $errors
 */
$isEdit = !empty($quotation);
$pageTitle = ($isEdit ? __('edit') . ' ' . __('quotation') . ' #' . $quotation['quotation_no'] : __('new') . ' ' . __('quotation')) . ' - PEGASUS ERP';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? __('edit') . ' ' . __('quotation') . ' #' . htmlspecialchars($quotation['quotation_no']) : __('new') . ' ' . __('quotation') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= __('dashboard') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/quotations"><?= __('quotations') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= $isEdit ? __('edit') : __('new') ?></span>
        </div>
    </div>
    <a href="/sales/quotations" class="btn btn-cancel"><?= __('back_to_list') ?></a>
</div>

<form method="POST" action="<?= $isEdit ? '/sales/quotations/' . htmlspecialchars($quotation['quotation_id']) : '/sales/quotations' ?>" id="quotationForm">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <!-- Header Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= __('quotation_details') ?></h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label"><?= __('customer') ?> <span class="required">*</span></label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($customers ?? [] as $c): ?>
                            <option value="<?= htmlspecialchars($c['customer_id']) ?>" <?= ($quotation['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
                                <?= e($c['customer_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('issue_date') ?> <span class="required">*</span></label>
                    <input type="date" name="issue_date" id="issueDateInput" class="form-input" value="<?= htmlspecialchars($quotation['issue_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('attention_name') ?></label>
                    <input type="text" name="attention_name" class="form-input" value="<?= htmlspecialchars($quotation['attention_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('expiry_date') ?></label>
                    <input type="date" name="expiry_date" id="expiryDateInput" class="form-input" value="<?= htmlspecialchars($quotation['expiry_date'] ?? date('Y-m-d', strtotime('+1 month'))) ?>">
                </div>
                <script>
                    // Auto-fill expiry_date as issue_date + 1 month whenever issue_date changes
                    (function(){
                        var iss = document.getElementById('issueDateInput');
                        var exp = document.getElementById('expiryDateInput');
                        if (!iss || !exp) return;
                        iss.addEventListener('change', function(){
                            if (!iss.value) return;
                            var d = new Date(iss.value);
                            d.setMonth(d.getMonth() + 1);
                            var y = d.getFullYear();
                            var m = String(d.getMonth() + 1).padStart(2, '0');
                            var dd = String(d.getDate()).padStart(2, '0');
                            exp.value = y + '-' + m + '-' + dd;
                        });
                    })();
                </script>
                <div class="form-group">
                    <label class="form-label"><?= __('attention_email') ?></label>
                    <input type="email" name="attention_email" class="form-input" value="<?= htmlspecialchars($quotation['attention_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('currency') ?></label>
                    <select name="currency_code" class="form-select">
                        <?php foreach ($currencies ?? [['code'=>'THB'],['code'=>'USD'],['code'=>'JPY']] as $cur): ?>
                            <option value="<?= htmlspecialchars($cur['code']) ?>" <?= ($quotation['currency_code'] ?? 'THB') === $cur['code'] ? 'selected' : '' ?>><?= htmlspecialchars($cur['code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('payment_terms') ?></label>
                    <select name="payment_term_id" class="form-select">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($paymentTerms ?? [] as $pt): ?>
                            <option value="<?= htmlspecialchars($pt['term_id']) ?>" <?= ($quotation['payment_term_id'] ?? '') == $pt['term_id'] ? 'selected' : '' ?>><?= htmlspecialchars($pt['term_name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('project_name') ?></label>
                    <input type="text" name="project_name" class="form-input" value="<?= htmlspecialchars($quotation['project_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('project_code') ?></label>
                    <input type="text" name="project_code" class="form-input" value="<?= htmlspecialchars($quotation['project_code'] ?? '') ?>" placeholder="<?= __('project_code_hint') ?>">
                </div>
                <div class="form-group form-full">
                    <label class="form-label"><?= __('ship_to_address') ?></label>
                    <textarea name="ship_to_address" class="form-textarea" rows="2"><?= htmlspecialchars($quotation['ship_to_address'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:8px;">
            <h3 class="card-title"><?= __('line_items') ?></h3>
            <div style="display:flex;gap:6px;">
                <button type="button" class="btn btn-sm" style="background:#2e7d32;color:#fff;" onclick="openCostSheetModal()">&#128200; <?= __('import_from_cost_sheet') ?></button>
                <button type="button" class="btn btn-sm" style="background:#003366;color:#fff;" onclick="addCategoryRow()">+ <?= __('category') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="addItemRow()">+ <?= __('add_item') ?></button>
            </div>
        </div>
        <div class="card-body" style="padding:0;overflow-x:auto;">
            <table class="data-table" id="lineItemsTable" style="min-width:1100px;">
                <thead>
                    <tr>
                        <th style="width:60px;"><?= __('no') ?></th>
                        <th style="min-width:220px;"><?= __('description') ?></th>
                        <th style="width:65px;" class="text-right"><?= __('qty') ?></th>
                        <th style="width:55px;"><?= __('unit') ?></th>
                        <th style="width:110px;" class="text-right"><?= __('cost_price') ?></th>
                        <th style="width:70px;" class="text-right"><?= __('markup') ?>%</th>
                        <th style="width:110px;" class="text-right"><?= __('selling_price') ?></th>
                        <th style="width:110px;" class="text-right"><?= __('selling_amount') ?></th>
                        <th style="width:100px;" class="text-right"><?= __('profit') ?></th>
                        <th style="width:55px;" class="text-right"><?= __('profit') ?>%</th>
                        <th style="width:110px;"></th>
                    </tr>
                </thead>
                <tbody id="lineItemsBody">
                    <?php if (!empty($quotation['lines'])):
                        foreach ($quotation['lines'] as $i => $line):
                            $isCat = !empty($line['is_category_row']);
                    ?>
                        <?php if ($isCat): ?>
                            <tr class="line-row category-row" data-idx="<?= $i ?>" data-type="category">
                                <td class="text-center line-no-display" style="font-weight:700;color:#003366;"><?= htmlspecialchars($line['line_no']) ?></td>
                                <td colspan="9">
                                    <input type="hidden" name="lines[<?= $i ?>][row_type]" value="category">
                                    <input type="hidden" name="lines[<?= $i ?>][is_category_row]" value="1">
                                    <select name="lines[<?= $i ?>][item_description]" class="form-select cat-input" required>
                                        <option value="">-- <?= __('select') ?> --</option>
                                        <?php foreach (($quotationCategories ?? []) as $qc): ?>
                                            <?php $disp = $qc['name_jp']; ?>
                                            <option value="<?= htmlspecialchars($disp) ?>"
                                                <?= ($line['item_description'] ?? '') === $disp ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($qc['category_code'] . ' — ' . $disp) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php
                                        // If existing value not in master, keep it as a fallback option
                                        $existingDesc = $line['item_description'] ?? '';
                                        $found = false;
                                        foreach (($quotationCategories ?? []) as $qc) {
                                            if ($qc['name_jp'] === $existingDesc) { $found = true; break; }
                                        }
                                        if (!$found && $existingDesc !== ''): ?>
                                            <option value="<?= htmlspecialchars($existingDesc) ?>" selected>
                                                <?= htmlspecialchars($existingDesc) ?> (<?= __('legacy') ?? 'legacy' ?>)
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td class="text-center row-actions">
                                    <div class="row-action-group">
                                        <button type="button" class="btn-move" onclick="moveLineUp(this)" title="<?= __('move_up') ?>">&#9650;</button>
                                        <button type="button" class="btn-move" onclick="moveLineDown(this)" title="<?= __('move_down') ?>">&#9660;</button>
                                        <button type="button" class="btn-insert" onclick="toggleInsertMenu(this)" title="<?= __('insert') ?>">+</button>
                                        <div class="insert-menu">
                                            <div class="insert-menu-item" onclick="insertCategoryAfter(this)">+ <?= __('category') ?></div>
                                            <div class="insert-menu-item" onclick="insertItemAfter(this)">+ <?= __('add_item') ?></div>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeLine(this)">&times;</button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr class="line-row item-row" data-idx="<?= $i ?>" data-type="item">
                                <td class="text-center line-no-display"><?= htmlspecialchars($line['line_no']) ?></td>
                                <td style="position:relative;">
                                    <input type="hidden" name="lines[<?= $i ?>][row_type]" value="item">
                                    <input type="hidden" name="lines[<?= $i ?>][is_category_row]" value="0">
                                    <input type="hidden" name="lines[<?= $i ?>][item_id]" class="line-item-id" value="<?= htmlspecialchars($line['item_id'] ?? '') ?>">
                                    <input type="text" name="lines[<?= $i ?>][item_description]" class="form-input line-desc" value="<?= htmlspecialchars($line['item_description']) ?>" autocomplete="off" oninput="searchItem(this)" onfocus="searchItem(this)" required>
                                    <div class="item-suggest" style="display:none;"></div>
                                </td>
                                <td><input type="number" name="lines[<?= $i ?>][quantity]" class="form-input text-right line-qty" value="<?= htmlspecialchars($line['quantity']) ?>" step="0.01" min="0" onchange="calcLine(this)"></td>
                                <td><input type="text" name="lines[<?= $i ?>][unit]" class="form-input line-unit" value="<?= htmlspecialchars($line['unit'] ?? 'EA') ?>" style="width:50px;"></td>
                                <td><input type="number" name="lines[<?= $i ?>][cost_total]" class="form-input text-right line-cost" value="<?= htmlspecialchars($line['cost_total'] ?? 0) ?>" step="0.01" min="0" onchange="calcFromCost(this)"></td>
                                <td><input type="number" name="lines[<?= $i ?>][markup_rate]" class="form-input text-right line-markup" value="<?= htmlspecialchars($line['discount_rate'] ?? 0) ?>" step="0.1" onchange="calcFromMarkup(this)"></td>
                                <td><input type="number" name="lines[<?= $i ?>][unit_price]" class="form-input text-right line-price" value="<?= htmlspecialchars($line['unit_price'] ?? 0) ?>" step="0.01" min="0" onchange="calcFromPrice(this)"></td>
                                <td class="text-right line-sell-amount">0.00</td>
                                <td class="text-right line-profit">0.00</td>
                                <td class="text-right line-profit-pct">0.0%</td>
                                <td class="text-center row-actions">
                                    <div class="row-action-group">
                                        <button type="button" class="btn-move" onclick="moveLineUp(this)" title="<?= __('move_up') ?>">&#9650;</button>
                                        <button type="button" class="btn-move" onclick="moveLineDown(this)" title="<?= __('move_down') ?>">&#9660;</button>
                                        <button type="button" class="btn-insert" onclick="toggleInsertMenu(this)" title="<?= __('insert') ?>">+</button>
                                        <div class="insert-menu">
                                            <div class="insert-menu-item" onclick="insertCategoryAfter(this)">+ <?= __('category') ?></div>
                                            <div class="insert-menu-item" onclick="insertItemAfter(this)">+ <?= __('add_item') ?></div>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeLine(this)">&times;</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight:700;background:var(--color-gray-50);">
                        <td colspan="7" class="text-right"><?= __('total') ?></td>
                        <td class="text-right" id="totalSelling">0.00</td>
                        <td class="text-right" id="totalProfit">0.00</td>
                        <td class="text-right" id="totalProfitPct">0.0%</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Inspection Schedule (検収スケジュール) -->
    <?php $existInsp = $inspections ?? []; ?>
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h3 class="card-title">🗓 <?= __('inspection_schedule') ?></h3>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-cancel btn-sm" onclick="loadInspFromPaymentTerm()">
                    📋 <?= __('load_from_payment_term') ?>
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="addInspRow()">+ <?= __('add_row') ?></button>
            </div>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="data-table" id="inspTable" style="margin:0;font-size:12px;">
                <thead>
                <tr style="background:#FAFAFA;">
                    <th style="width:50px;">#</th>
                    <th><?= __('description') ?></th>
                    <th style="width:80px;" class="text-right">%</th>
                    <th style="width:130px;" class="text-right"><?= __('amount') ?> (THB)</th>
                    <th style="width:160px;"><?= __('inspection_date') ?></th>
                    <th style="width:130px;"><?= __('status') ?></th>
                    <th style="width:40px;"></th>
                </tr>
                </thead>
                <tbody id="inspBody">
                <?php if (empty($existInsp)): ?>
                    <tr class="insp-empty"><td colspan="7" class="text-center" style="padding:20px;color:#999;">
                        <?= __('no_inspection_hint') ?>
                    </td></tr>
                <?php else: foreach ($existInsp as $ins): ?>
                    <tr class="insp-row">
                        <td><input type="number" name="insp_seq[]"    class="form-input insp-seq"    style="width:50px;"  value="<?= e($ins['seq_no']) ?>" min="1"></td>
                        <td><input type="text"   name="insp_desc[]"   class="form-input"             value="<?= e($ins['description']) ?>"></td>
                        <td><input type="number" name="insp_pct[]"    class="form-input insp-pct"    style="width:80px;text-align:right;" step="0.01" min="0" max="100" value="<?= e($ins['percentage']) ?>" onchange="recalcInspAmt()"></td>
                        <td class="insp-amt-cell text-right"><?= number_format(floatval($ins['amount']), 2) ?></td>
                        <td><input type="date"   name="insp_date[]"   class="form-input" value="<?= e($ins['expected_inspection_date']) ?>"></td>
                        <td>
                            <select name="insp_status[]" class="form-select">
                                <?php foreach (['PENDING','IN_PROGRESS','DELIVERED','INSPECTED','CANCELLED'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $ins['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="insp_notes[]" value="<?= e($ins['notes'] ?? '') ?>">
                            <button type="button" onclick="this.closest('tr').remove();recalcInspAmt();" style="background:none;border:none;cursor:pointer;color:#D32F2F;">✕</button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                <tr style="background:#E3F2FD;font-weight:700;">
                    <td colspan="2" class="text-right"><?= __('total') ?></td>
                    <td class="text-right insp-total-pct">0.00%</td>
                    <td class="text-right insp-total-amt">0.00</td>
                    <td colspan="3"></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <script>
    // Payment-term installments provided by PHP (term_id → [{seq, pct, desc, trigger}])
    const _paymentTerms = <?= json_encode(array_column($paymentTerms ?? [], 'term_id'), JSON_UNESCAPED_UNICODE) ?>;

    function getSubtotal() {
        // Sum ext_price from line_items (fallback: header subtotal_thb)
        let sum = 0;
        document.querySelectorAll('input[name="ext_price[]"], .line-amount').forEach(el => {
            sum += parseFloat(el.value || el.textContent || 0) || 0;
        });
        if (sum === 0) {
            const hidden = document.querySelector('input[name="subtotal_thb"]');
            if (hidden) sum = parseFloat(hidden.value || 0);
        }
        return sum;
    }
    function recalcInspAmt() {
        const subtotal = getSubtotal();
        let totalPct = 0, totalAmt = 0;
        document.querySelectorAll('#inspBody tr.insp-row').forEach(tr => {
            const pct = parseFloat(tr.querySelector('.insp-pct')?.value || 0);
            const amt = Math.round(subtotal * pct / 100 * 100) / 100;
            totalPct += pct;
            totalAmt += amt;
            const cell = tr.querySelector('.insp-amt-cell');
            if (cell) cell.textContent = amt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        });
        document.querySelector('.insp-total-pct').textContent = totalPct.toFixed(2) + '%';
        document.querySelector('.insp-total-amt').textContent = totalAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        // Color warning if not 100%
        document.querySelector('.insp-total-pct').style.color = Math.abs(totalPct - 100) < 0.1 ? '#2E7D32' : '#D32F2F';
    }
    function addInspRow(data = {}) {
        document.querySelectorAll('#inspBody .insp-empty').forEach(e => e.remove());
        const body = document.getElementById('inspBody');
        const nextSeq = body.querySelectorAll('tr.insp-row').length + 1;
        const tr = document.createElement('tr');
        tr.className = 'insp-row';
        tr.innerHTML = `
            <td><input type="number" name="insp_seq[]" class="form-input insp-seq" style="width:50px;" value="${data.seq || nextSeq}" min="1"></td>
            <td><input type="text" name="insp_desc[]" class="form-input" value="${(data.desc || '').replace(/"/g,'&quot;')}"></td>
            <td><input type="number" name="insp_pct[]" class="form-input insp-pct" style="width:80px;text-align:right;" step="0.01" min="0" max="100" value="${data.pct || 0}" onchange="recalcInspAmt()"></td>
            <td class="insp-amt-cell text-right">0.00</td>
            <td><input type="date" name="insp_date[]" class="form-input" value="${data.date || ''}"></td>
            <td>
                <select name="insp_status[]" class="form-select">
                    <option value="PENDING">PENDING</option>
                    <option value="IN_PROGRESS">IN_PROGRESS</option>
                    <option value="DELIVERED">DELIVERED</option>
                    <option value="INSPECTED">INSPECTED</option>
                    <option value="CANCELLED">CANCELLED</option>
                </select>
            </td>
            <td class="text-center">
                <input type="hidden" name="insp_notes[]" value="">
                <button type="button" onclick="this.closest('tr').remove();recalcInspAmt();" style="background:none;border:none;cursor:pointer;color:#D32F2F;">✕</button>
            </td>`;
        body.appendChild(tr);
        recalcInspAmt();
    }
    function loadInspFromPaymentTerm() {
        const ptSel = document.querySelector('select[name="payment_term_id"]');
        const termId = ptSel ? ptSel.value : null;
        if (!termId) {
            alert('<?= __('select_payment_term_first') ?>');
            return;
        }
        fetch('/api/payment-terms/' + termId + '/installments')
            .then(r => r.json())
            .then(data => {
                // API returns {term, installments:[...]}; also tolerate a flat array.
                const list = Array.isArray(data) ? data : (data.installments || []);
                if (list.length === 0) {
                    alert('<?= __('no_installments_for_term') ?>');
                    return;
                }
                if (document.querySelectorAll('#inspBody tr.insp-row').length > 0 &&
                    !confirm('<?= __('confirm_replace_inspection') ?>')) return;
                document.getElementById('inspBody').innerHTML = '';
                const baseDate = document.querySelector('input[name="issue_date"]')?.value;
                list.forEach(ins => {
                    let date = '';
                    if (baseDate && ins.credit_days) {
                        const d = new Date(baseDate);
                        d.setDate(d.getDate() + parseInt(ins.credit_days));
                        date = d.toISOString().slice(0, 10);
                    }
                    addInspRow({
                        seq: ins.seq_no,
                        desc: (ins.description_en || ins.trigger_type || 'Phase ' + ins.seq_no),
                        pct: ins.percentage,
                        date: date,
                    });
                });
            })
            .catch(err => { console.error(err); alert('Failed to load installments'); });
    }
    document.addEventListener('DOMContentLoaded', recalcInspAmt);
    // Recalculate when line items change
    document.addEventListener('change', function(e) {
        if (e.target.matches('input[name="ext_price[]"], input[name="unit_price[]"], input[name="quantity[]"]')) {
            setTimeout(recalcInspAmt, 100);
        }
    });
    </script>

    <!-- Totals & Remarks -->
    <div class="form-grid-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= __('remarks') ?></h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><?= __('remarks_visible') ?></label>
                    <textarea name="remark_text" class="form-textarea" rows="3"><?= htmlspecialchars($quotation['remark_text'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('internal_notes') ?></label>
                    <textarea name="note_text" class="form-textarea" rows="3"><?= htmlspecialchars($quotation['note_text'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= __('summary') ?></h3></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span><?= __('subtotal') ?></span>
                        <span id="displaySubtotal" style="font-weight:600;"><?= formatMoney($quotation['subtotal_thb'] ?? 0) ?></span>
                        <input type="hidden" name="subtotal_thb" id="subtotal" value="<?= htmlspecialchars($quotation['subtotal_thb'] ?? 0) ?>">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <span><?= __('discount') ?></span>
                        <input type="number" name="discount_amount" id="discountAmount" class="form-input text-right" style="width:140px;" value="<?= htmlspecialchars($quotation['discount_amount'] ?? 0) ?>" step="0.01" min="0" onchange="calcGrandTotals()">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <span><?= __('vat_rate') ?> (%)</span>
                        <input type="number" name="vat_rate" id="vatRate" class="form-input text-right" style="width:100px;" value="<?= htmlspecialchars($quotation['vat_rate'] ?? 7) ?>" step="0.01" min="0" onchange="calcGrandTotals()">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span><?= __('vat_amount') ?></span>
                        <span id="displayVat" style="font-weight:600;"><?= formatMoney($quotation['vat_amount'] ?? 0) ?></span>
                        <input type="hidden" name="vat_amount" id="vatAmount" value="<?= htmlspecialchars($quotation['vat_amount'] ?? 0) ?>">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;border-top:2px solid var(--color-border);padding-top:10px;">
                        <span style="font-size:16px;font-weight:700;"><?= __('grand_total') ?></span>
                        <span id="displayGrandTotal" style="font-size:16px;font-weight:700;color:var(--color-primary);"><?= formatMoney($quotation['grand_total_thb'] ?? 0) ?></span>
                        <input type="hidden" name="grand_total_thb" id="grandTotal" value="<?= htmlspecialchars($quotation['grand_total_thb'] ?? 0) ?>">
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--color-border);padding-top:8px;">
                        <span style="color:var(--color-success);font-weight:600;"><?= __('total_profit') ?></span>
                        <span id="displayTotalProfit" style="font-weight:600;color:var(--color-success);">0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="card" style="display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;">
        <a href="/sales/quotations" class="btn btn-cancel"><?= __('cancel') ?></a>
        <button type="submit" name="action" value="draft" class="btn btn-cancel"><?= __('save_as_draft') ?></button>
        <button type="submit" name="action" value="submit" class="btn btn-primary"><?= __('submit_quotation') ?></button>
    </div>
</form>

<style>
.category-row { background:#e8f0fe !important; }
.cat-input { font-weight:700; font-size:14px; color:#003366; border:none; background:transparent; width:calc(100% - 10px); }
.cat-input:focus { border:1px solid var(--color-primary); background:#fff; border-radius:4px; padding:4px 8px; }
.item-row td { padding:4px 6px !important; }
.item-row .form-input { padding:4px 6px; font-size:12px; height:30px; }
.line-profit.positive { color:var(--color-success); }
.line-profit.negative { color:var(--color-danger); }
#lineItemsTable { font-size:12px; }
#lineItemsTable thead th { font-size:11px; padding:6px; white-space:nowrap; }

/* Item suggestion dropdown */
.item-suggest { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ccc; border-radius:4px; max-height:200px; overflow-y:auto; z-index:100; box-shadow:0 4px 12px rgba(0,0,0,.15); }
.item-suggest-item { padding:6px 10px; cursor:pointer; font-size:12px; border-bottom:1px solid #f0f0f0; }
.item-suggest-item:hover { background:#e8f0fe; }
.item-suggest-item .item-code { color:#888; font-size:10px; }
.item-suggest-item .item-name { font-weight:500; }
.item-suggest-item .item-price { color:#003366; font-size:10px; float:right; }

/* Row action group — move + insert + delete */
.row-action-group { display:flex; align-items:center; gap:2px; justify-content:center; position:relative; }
.btn-move {
    width:22px; height:22px; border:1px solid #999; color:#666; background:#fff;
    border-radius:3px; cursor:pointer; font-size:12px; font-weight:700; line-height:1;
    display:flex; align-items:center; justify-content:center; padding:0;
    transition: background .15s, color .15s;
}
.btn-move:hover { background:#e0e0e0; color:#333; }
.btn-insert {
    width:24px; height:24px; border:1px solid #1a73e8; color:#1a73e8; background:#fff;
    border-radius:4px; cursor:pointer; font-size:16px; font-weight:700; line-height:1;
    display:flex; align-items:center; justify-content:center; padding:0;
    transition: background .15s, color .15s;
}
.btn-insert:hover { background:#1a73e8; color:#fff; }

/* Insert dropdown menu */
.insert-menu {
    display:none; position:absolute; top:100%; right:0; z-index:200;
    background:#fff; border:1px solid #ccc; border-radius:6px;
    box-shadow:0 4px 16px rgba(0,0,0,.18); min-width:150px; overflow:hidden;
    margin-top:2px;
}
.insert-menu.active { display:block; }
.insert-menu-item {
    padding:8px 14px; cursor:pointer; font-size:12px; font-weight:500;
    white-space:nowrap; color:#333;
}
.insert-menu-item:hover { background:#e8f0fe; color:#003366; }
.insert-menu-item:first-child { border-bottom:1px solid #eee; }
</style>

<script>
let lineIndex = <?= isset($quotation['lines']) ? count($quotation['lines']) : 0 ?>;
let searchTimer = null;

/* ── Auto-numbering: categories = 1,2,3,4; items under cat = 1-1,1-2 ── */
function renumberLines() {
    let catNum = 0;
    let itemNum = 0;
    let lastCatNum = 0;
    const rows = document.querySelectorAll('#lineItemsBody .line-row');
    rows.forEach(row => {
        const display = row.querySelector('.line-no-display');
        if (row.dataset.type === 'category') {
            catNum++;
            itemNum = 0;
            lastCatNum = catNum;
            if (display) display.textContent = catNum;
        } else {
            itemNum++;
            if (lastCatNum > 0) {
                if (display) display.textContent = lastCatNum + '-' + itemNum;
            } else {
                if (display) display.textContent = itemNum;
            }
        }
    });
}

/* ── Build row action buttons HTML ── */
function rowActionsHtml() {
    return `<div class="row-action-group">
        <button type="button" class="btn-move" onclick="moveLineUp(this)" title="<?= __('move_up') ?>">&#9650;</button>
        <button type="button" class="btn-move" onclick="moveLineDown(this)" title="<?= __('move_down') ?>">&#9660;</button>
        <button type="button" class="btn-insert" onclick="toggleInsertMenu(this)" title="挿入">+</button>
        <div class="insert-menu">
            <div class="insert-menu-item" onclick="insertCategoryAfter(this)">+ <?= __('category') ?></div>
            <div class="insert-menu-item" onclick="insertItemAfter(this)">+ <?= __('add_item') ?></div>
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeLine(this)">&times;</button>
    </div>`;
}

/* ── Toggle insert dropdown ── */
function toggleInsertMenu(btn) {
    // Close all other open menus
    document.querySelectorAll('.insert-menu.active').forEach(m => {
        if (m !== btn.nextElementSibling) m.classList.remove('active');
    });
    const menu = btn.nextElementSibling;
    menu.classList.toggle('active');
}

// Close insert menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.btn-insert') && !e.target.closest('.insert-menu')) {
        document.querySelectorAll('.insert-menu.active').forEach(m => m.classList.remove('active'));
    }
});

/* ── Category master options + coefficient map ── */
const QUOTATION_CATEGORIES = <?= json_encode(array_map(
    fn($c) => [
        'code'  => $c['category_code'],
        'name'  => $c['name_jp'],
        'coeff' => (float)($c['cost_coefficient'] ?? 1.0),
    ],
    $quotationCategories ?? []
), JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
// Lookup table: category name (= what's stored in item_description) → coefficient
const CATEGORY_COEFF = QUOTATION_CATEGORIES.reduce((m,c)=>(m[c.name]=c.coeff,m),{});

function buildCategoryOptions(selected = '') {
    let html = '<option value="">-- <?= __('select') ?> --</option>';
    QUOTATION_CATEGORIES.forEach(c => {
        const sel = (c.name === selected) ? ' selected' : '';
        const pct = ((c.coeff - 1) * 100).toFixed(0);
        html += `<option value="${c.name}"${sel}>${c.code} — ${c.name} (×${c.coeff.toFixed(2)} / +${pct}%)</option>`;
    });
    return html;
}

/* Walk back from an item row to find the parent category row and return
   its coefficient (default 1.0 if no parent or unknown name). */
function findParentCategoryCoefficient(itemRow) {
    let r = itemRow.previousElementSibling;
    while (r) {
        if (r.dataset && r.dataset.type === 'category') {
            const sel = r.querySelector('.cat-input');
            const v = sel ? sel.value : '';
            return CATEGORY_COEFF[v] || 1.0;
        }
        r = r.previousElementSibling;
    }
    return 1.0;
}

/* Auto-apply coefficient when a cost is entered (only if markup not yet customized). */
function autoApplyCategoryCoefficient(input) {
    const tr = input.closest('tr');
    if (!tr || tr.dataset.type !== 'item') return;
    const coeff = findParentCategoryCoefficient(tr);
    if (!coeff || coeff === 1.0) return;
    const markupEl = tr.querySelector('.line-markup');
    const priceEl  = tr.querySelector('.line-price');
    if (!markupEl) return;
    const currentMarkup = parseFloat(markupEl.value) || 0;
    // Only fill if user hasn't set a markup yet
    if (currentMarkup === 0) {
        markupEl.value = ((coeff - 1) * 100).toFixed(2);
        // recompute price from cost via existing calcFromCost
        if (typeof calcFromCost === 'function') {
            const costEl = tr.querySelector('.line-cost');
            if (costEl) calcFromCost(costEl);
        }
    }
}

/* When the category in a category-row changes, re-apply coefficient
   to all item rows under it (that haven't been customized). */
function onCategoryChanged(selectEl) {
    let tr = selectEl.closest('tr');
    if (!tr) return;
    const newCoeff = CATEGORY_COEFF[selectEl.value] || 1.0;
    let row = tr.nextElementSibling;
    while (row && row.dataset && row.dataset.type !== 'category') {
        if (row.dataset.type === 'item') {
            const markupEl = row.querySelector('.line-markup');
            const costEl   = row.querySelector('.line-cost');
            if (markupEl && (parseFloat(markupEl.value) || 0) === 0 && newCoeff !== 1.0) {
                markupEl.value = ((newCoeff - 1) * 100).toFixed(2);
                if (typeof calcFromCost === 'function' && costEl) calcFromCost(costEl);
            }
        }
        row = row.nextElementSibling;
    }
}

// Wire up: every cat-input <select> + every line-cost field auto-applies coefficient.
// Use event delegation so dynamically-added rows are covered too.
document.addEventListener('change', e => {
    if (e.target.matches && e.target.matches('select.cat-input')) onCategoryChanged(e.target);
});
document.addEventListener('blur', e => {
    if (e.target.matches && e.target.matches('input.line-cost')) autoApplyCategoryCoefficient(e.target);
}, true);

/* ── Create a category <tr> element ── */
function createCategoryTr() {
    const i = lineIndex++;
    const tr = document.createElement('tr');
    tr.className = 'line-row category-row';
    tr.dataset.idx = i;
    tr.dataset.type = 'category';
    tr.innerHTML = `
        <td class="text-center line-no-display" style="font-weight:700;color:#003366;"></td>
        <td colspan="9">
            <input type="hidden" name="lines[${i}][row_type]" value="category">
            <input type="hidden" name="lines[${i}][is_category_row]" value="1">
            <select name="lines[${i}][item_description]" class="form-select cat-input" required>
                ${buildCategoryOptions()}
            </select>
        </td>
        <td class="text-center row-actions">${rowActionsHtml()}</td>
    `;
    return tr;
}

/* ── Create an item <tr> element ── */
function createItemTr() {
    const i = lineIndex++;
    const tr = document.createElement('tr');
    tr.className = 'line-row item-row';
    tr.dataset.idx = i;
    tr.dataset.type = 'item';
    tr.innerHTML = `
        <td class="text-center line-no-display"></td>
        <td style="position:relative;">
            <input type="hidden" name="lines[${i}][row_type]" value="item">
            <input type="hidden" name="lines[${i}][is_category_row]" value="0">
            <input type="hidden" name="lines[${i}][item_id]" class="line-item-id" value="">
            <input type="text" name="lines[${i}][item_description]" class="form-input line-desc" autocomplete="off" oninput="searchItem(this)" onfocus="searchItem(this)" placeholder="<?= __('search_item') ?>" required>
            <div class="item-suggest" style="display:none;"></div>
        </td>
        <td><input type="number" name="lines[${i}][quantity]" class="form-input text-right line-qty" value="1" step="0.01" min="0" onchange="calcLine(this)"></td>
        <td><input type="text" name="lines[${i}][unit]" class="form-input line-unit" value="EA" style="width:50px;"></td>
        <td><input type="number" name="lines[${i}][cost_total]" class="form-input text-right line-cost" value="0" step="0.01" min="0" onchange="calcFromCost(this)"></td>
        <td><input type="number" name="lines[${i}][markup_rate]" class="form-input text-right line-markup" value="30" step="0.1" onchange="calcFromMarkup(this)"></td>
        <td><input type="number" name="lines[${i}][unit_price]" class="form-input text-right line-price" value="0" step="0.01" min="0" onchange="calcFromPrice(this)"></td>
        <td class="text-right line-sell-amount">0.00</td>
        <td class="text-right line-profit">0.00</td>
        <td class="text-right line-profit-pct">0.0%</td>
        <td class="text-center row-actions">${rowActionsHtml()}</td>
    `;
    return tr;
}

/* ── Top header buttons ── */
function addCategoryRow() {
    const tbody = document.getElementById('lineItemsBody');
    const newRow = createCategoryTr();
    const firstRow = tbody.querySelector('.line-row');
    if (firstRow) {
        tbody.insertBefore(newRow, firstRow);
    } else {
        tbody.appendChild(newRow);
    }
    renumberLines();
    newRow.querySelector('.cat-input').focus();
}

function addItemRow() {
    const tbody = document.getElementById('lineItemsBody');
    tbody.appendChild(createItemTr());
    renumberLines();
}

/* ── Insert AFTER a specific row (per-row dropdown) ── */
function insertCategoryAfter(menuItem) {
    const row = menuItem.closest('tr');
    const newRow = createCategoryTr();
    row.parentNode.insertBefore(newRow, row.nextSibling);
    renumberLines();
    // Close menu
    menuItem.closest('.insert-menu').classList.remove('active');
    // Focus on the new category input
    newRow.querySelector('.cat-input').focus();
}

function insertItemAfter(menuItem) {
    const row = menuItem.closest('tr');
    const newRow = createItemTr();
    row.parentNode.insertBefore(newRow, row.nextSibling);
    renumberLines();
    // Close menu
    menuItem.closest('.insert-menu').classList.remove('active');
    // Focus on the new item description
    newRow.querySelector('.line-desc').focus();
}

function removeLine(btn) {
    btn.closest('tr').remove();
    renumberLines();
    calcAllTotals();
}

/* ── Move line up/down ── */
function moveLineUp(btn) {
    const row = btn.closest('tr');
    const prev = row.previousElementSibling;
    if (prev && prev.classList.contains('line-row')) {
        row.parentNode.insertBefore(row, prev);
        renumberLines();
        calcAllTotals();
        highlightRow(row);
    }
}

function moveLineDown(btn) {
    const row = btn.closest('tr');
    const next = row.nextElementSibling;
    if (next && next.classList.contains('line-row')) {
        row.parentNode.insertBefore(next, row);
        renumberLines();
        calcAllTotals();
        highlightRow(row);
    }
}

function highlightRow(row) {
    row.style.transition = 'background 0.3s';
    row.style.background = '#fff9c4';
    setTimeout(() => {
        row.style.background = '';
        if (row.dataset.type === 'category') row.style.background = '#e8f0fe';
    }, 500);
}

/* ── Item master search (autocomplete) ── */
function searchItem(input) {
    clearTimeout(searchTimer);
    const q = input.value.trim();
    const suggestDiv = input.closest('td').querySelector('.item-suggest');
    if (q.length < 1) { suggestDiv.style.display = 'none'; return; }

    searchTimer = setTimeout(() => {
        fetch('/api/items/search?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                const items = data.results || data || [];
                if (items.length === 0) { suggestDiv.style.display = 'none'; return; }
                let html = '';
                items.forEach(item => {
                    html += `<div class="item-suggest-item" onclick="selectItem(this, ${JSON.stringify(item).replace(/"/g, '&quot;')})">
                        <span class="item-code">${item.item_code || ''}</span>
                        <span class="item-name"> ${item.item_name || ''}</span>
                        <span class="item-price">${item.unit_price_std ? Number(item.unit_price_std).toLocaleString() : ''}</span>
                    </div>`;
                });
                suggestDiv.innerHTML = html;
                suggestDiv.style.display = 'block';
            })
            .catch(() => { suggestDiv.style.display = 'none'; });
    }, 250);
}

function selectItem(el, item) {
    const row = el.closest('tr');
    const suggestDiv = el.closest('.item-suggest');
    row.querySelector('.line-desc').value = item.item_name || '';
    row.querySelector('.line-item-id').value = item.item_id || '';
    if (item.unit) row.querySelector('.line-unit').value = item.unit;
    if (item.unit_cost_std && parseFloat(item.unit_cost_std) > 0) {
        row.querySelector('.line-cost').value = parseFloat(item.unit_cost_std).toFixed(2);
    }
    if (item.unit_price_std && parseFloat(item.unit_price_std) > 0) {
        row.querySelector('.line-price').value = parseFloat(item.unit_price_std).toFixed(2);
    }
    suggestDiv.style.display = 'none';
    // Recalculate markup from cost and price
    const cost = parseFloat(row.querySelector('.line-cost').value) || 0;
    const price = parseFloat(row.querySelector('.line-price').value) || 0;
    if (cost > 0) {
        row.querySelector('.line-markup').value = (((price - cost) / cost) * 100).toFixed(1);
    }
    calcLine(row.querySelector('.line-qty'));
}

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.item-suggest') && !e.target.classList.contains('line-desc')) {
        document.querySelectorAll('.item-suggest').forEach(d => d.style.display = 'none');
    }
});

/* ── Cost / Price / Markup calculations ── */
function calcFromCost(el) {
    const row = el.closest('tr');
    const cost = parseFloat(row.querySelector('.line-cost')?.value) || 0;
    const markup = parseFloat(row.querySelector('.line-markup')?.value) || 0;
    row.querySelector('.line-price').value = (cost * (1 + markup / 100)).toFixed(2);
    calcLine(el);
}

function calcFromMarkup(el) {
    const row = el.closest('tr');
    const cost = parseFloat(row.querySelector('.line-cost')?.value) || 0;
    const markup = parseFloat(row.querySelector('.line-markup')?.value) || 0;
    row.querySelector('.line-price').value = (cost * (1 + markup / 100)).toFixed(2);
    calcLine(el);
}

function calcFromPrice(el) {
    const row = el.closest('tr');
    const cost = parseFloat(row.querySelector('.line-cost')?.value) || 0;
    const price = parseFloat(row.querySelector('.line-price')?.value) || 0;
    if (cost > 0) {
        row.querySelector('.line-markup').value = (((price - cost) / cost) * 100).toFixed(1);
    }
    calcLine(el);
}

function calcLine(el) {
    const row = el.closest('tr');
    if (!row || row.dataset.type !== 'item') return;
    const qty = parseFloat(row.querySelector('.line-qty')?.value) || 0;
    const cost = parseFloat(row.querySelector('.line-cost')?.value) || 0;
    const price = parseFloat(row.querySelector('.line-price')?.value) || 0;
    const sellAmt = qty * price;
    const costAmt = qty * cost;
    const profit = sellAmt - costAmt;
    const profitPct = sellAmt > 0 ? (profit / sellAmt * 100) : 0;

    row.querySelector('.line-sell-amount').textContent = fmtNum(sellAmt);
    const profitEl = row.querySelector('.line-profit');
    profitEl.textContent = fmtNum(profit);
    profitEl.className = 'text-right line-profit ' + (profit >= 0 ? 'positive' : 'negative');
    row.querySelector('.line-profit-pct').textContent = profitPct.toFixed(1) + '%';
    calcAllTotals();
}

function calcAllTotals() {
    let totalCost = 0, totalSelling = 0;
    document.querySelectorAll('#lineItemsBody .item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.line-qty')?.value) || 0;
        const cost = parseFloat(row.querySelector('.line-cost')?.value) || 0;
        const price = parseFloat(row.querySelector('.line-price')?.value) || 0;
        totalCost += qty * cost;
        totalSelling += qty * price;
    });
    const totalProfit = totalSelling - totalCost;
    const totalProfitPct = totalSelling > 0 ? (totalProfit / totalSelling * 100) : 0;

    document.getElementById('totalSelling').textContent = fmtNum(totalSelling);
    document.getElementById('totalProfit').textContent = fmtNum(totalProfit);
    document.getElementById('totalProfitPct').textContent = totalProfitPct.toFixed(1) + '%';
    document.getElementById('subtotal').value = totalSelling.toFixed(2);
    document.getElementById('displaySubtotal').textContent = fmtNum(totalSelling);
    document.getElementById('displayTotalProfit').textContent = fmtNum(totalProfit);
    calcGrandTotals();
}

function calcGrandTotals() {
    const subtotal = parseFloat(document.getElementById('subtotal').value) || 0;
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const vatRate = parseFloat(document.getElementById('vatRate').value) || 0;
    const afterDiscount = subtotal - discount;
    const vat = afterDiscount * vatRate / 100;
    const grand = afterDiscount + vat;
    document.getElementById('vatAmount').value = vat.toFixed(2);
    document.getElementById('displayVat').textContent = fmtNum(vat);
    document.getElementById('grandTotal').value = grand.toFixed(2);
    document.getElementById('displayGrandTotal').textContent = fmtNum(grand);
}

function fmtNum(n) {
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/* ── Cost Sheet Import ── */
function openCostSheetModal() {
    document.getElementById('costSheetModal').style.display = 'flex';
    document.getElementById('cs-search-input').value = '';
    document.getElementById('cs-results').innerHTML = '';
    document.getElementById('cs-items-panel').style.display = 'none';
    searchCostSheets('');
}

function closeCostSheetModal() {
    document.getElementById('costSheetModal').style.display = 'none';
}

let csSearchTimer = null;
function searchCostSheets(q) {
    clearTimeout(csSearchTimer);
    csSearchTimer = setTimeout(() => {
        var custSel = document.querySelector('select[name="customer_id"]');
        var custId = custSel ? parseInt(custSel.value || '0', 10) : 0;
        if (!custId) {
            document.getElementById('cs-results').innerHTML = '<div style="padding:20px;text-align:center;color:#c60;"><?= __("select_customer_first") ?></div>';
            return;
        }
        fetch('/api/cost-sheets/search?customer_id=' + custId + '&q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                const sheets = data.results || [];
                const container = document.getElementById('cs-results');
                if (sheets.length === 0) {
                    container.innerHTML = '<div style="padding:20px;text-align:center;color:#999;"><?= __('no_cost_sheets') ?></div>';
                    return;
                }
                let html = '';
                sheets.forEach(s => {
                    html += `<div class="cs-result-item" onclick="loadCostSheetItems(${s.cost_sheet_id})" data-id="${s.cost_sheet_id}">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <span style="font-weight:600;color:var(--color-primary);">${s.sheet_no}</span>
                                <span style="margin-left:8px;">${s.sheet_name}</span>
                            </div>
                            <span class="badge badge-${s.status === 'CONFIRMED' ? 'success' : (s.status === 'LINKED' ? 'primary' : 'default')}" style="font-size:10px;">${s.status}</span>
                        </div>
                        <div style="font-size:11px;color:#888;margin-top:2px;">
                            ${s.customer_name ? '👤 ' + s.customer_name + ' · ' : ''}${s.item_count || 0} <?= __('items') ?> · ฿${Number(s.total_cost || 0).toLocaleString(undefined, {minimumFractionDigits:2})}
                            ${s.source_file ? ' · 📄 ' + s.source_file : ''}
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            })
            .catch(err => {
                document.getElementById('cs-results').innerHTML = '<div style="padding:20px;text-align:center;color:#c00;">Error loading cost sheets</div>';
            });
    }, 200);
}

function loadCostSheetItems(sheetId) {
    // Highlight selected
    document.querySelectorAll('.cs-result-item').forEach(el => el.classList.remove('selected'));
    document.querySelector(`.cs-result-item[data-id="${sheetId}"]`)?.classList.add('selected');

    fetch('/api/cost-sheets/' + sheetId + '/items')
        .then(r => r.json())
        .then(data => {
            const items = data.items || [];
            const panel = document.getElementById('cs-items-panel');
            const tbody = document.getElementById('cs-items-body');
            panel.style.display = 'flex';
            document.getElementById('cs-items-empty').style.display = 'none';
            panel.dataset.sheetId = sheetId;

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:16px;"><?= __('no_cost_items') ?></td></tr>';
                return;
            }

            let html = '';
            items.forEach((item, idx) => {
                if (item.is_category_row === true || item.is_category_row === 'true' || item.is_category_row === 't') {
                    html += `<tr style="background:#e8f0fe;">
                        <td style="padding:4px 8px;"><input type="checkbox" class="cs-item-check" data-idx="${idx}" checked></td>
                        <td colspan="6" style="padding:4px 8px;font-weight:700;color:#003366;">${item.description || item.category || ''}</td>
                    </tr>`;
                } else {
                    html += `<tr>
                        <td style="padding:4px 8px;"><input type="checkbox" class="cs-item-check" data-idx="${idx}" checked></td>
                        <td style="padding:4px 8px;font-size:12px;">${item.description || ''}</td>
                        <td style="padding:4px 8px;font-size:12px;">${item.supplier || ''}</td>
                        <td class="text-right" style="padding:4px 8px;font-size:12px;">${Number(item.quantity || 0).toLocaleString()}</td>
                        <td style="padding:4px 8px;font-size:12px;">${item.unit || ''}</td>
                        <td class="text-right" style="padding:4px 8px;font-size:12px;">${Number(item.unit_price || 0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                        <td class="text-right" style="padding:4px 8px;font-size:12px;">${Number(item.total_amount || 0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                    </tr>`;
                }
            });
            tbody.innerHTML = html;

            // Store items data for import
            panel.dataset.items = JSON.stringify(items);
        })
        .catch(err => {
            document.getElementById('cs-items-panel').style.display = 'none';
            alert('Failed to load items');
        });
}

function importCostSheetItems() {
    const panel = document.getElementById('cs-items-panel');
    const items = JSON.parse(panel.dataset.items || '[]');
    const checks = panel.querySelectorAll('.cs-item-check');
    const tbody = document.getElementById('lineItemsBody');
    let importedCount = 0;

    // Ask if user wants to clear existing lines
    const existingRows = tbody.querySelectorAll('.line-row');
    if (existingRows.length > 0) {
        if (confirm('<?= __('confirm_clear_lines') ?>')) {
            tbody.innerHTML = '';
        }
    }

    checks.forEach((chk, idx) => {
        if (!chk.checked) return;
        const item = items[idx];
        if (!item) return;

        const isCat = (item.is_category_row === true || item.is_category_row === 'true' || item.is_category_row === 't');

        if (isCat) {
            const tr = createCategoryTr();
            tr.querySelector('.cat-input').value = item.description || item.category || '';
            tbody.appendChild(tr);
        } else {
            const tr = createItemTr();
            tr.querySelector('.line-desc').value = item.description || '';
            tr.querySelector('.line-qty').value = item.quantity || 1;
            tr.querySelector('.line-unit').value = item.unit || 'EA';
            tr.querySelector('.line-cost').value = parseFloat(item.unit_price || 0).toFixed(2);
            tr.querySelector('.line-markup').value = '30';
            // Calculate selling price from cost + default markup
            const cost = parseFloat(item.unit_price || 0);
            const sellingPrice = cost * 1.3; // 30% markup default
            tr.querySelector('.line-price').value = sellingPrice.toFixed(2);
            tbody.appendChild(tr);
        }
        importedCount++;
    });

    renumberLines();
    // Recalculate all lines
    document.querySelectorAll('#lineItemsBody .item-row').forEach(row => {
        const el = row.querySelector('.line-qty');
        if (el) calcLine(el);
    });

    closeCostSheetModal();

    if (importedCount > 0) {
        // Flash notification
        const flash = document.createElement('div');
        flash.style.cssText = 'position:fixed;top:20px;right:20px;background:#2e7d32;color:#fff;padding:12px 20px;border-radius:6px;z-index:9999;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.2);';
        flash.textContent = importedCount + ' <?= __('items_imported_from_cost_sheet') ?>';
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 3000);
    }
}

function toggleAllCsItems(masterChk) {
    document.querySelectorAll('.cs-item-check').forEach(chk => chk.checked = masterChk.checked);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    renumberLines();
    document.querySelectorAll('#lineItemsBody .item-row').forEach(row => {
        const el = row.querySelector('.line-qty');
        if (el) calcLine(el);
    });
});
</script>

<!-- Cost Sheet Import Modal -->
<div id="costSheetModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:5000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;width:95%;max-width:1000px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,.25);">
        <!-- Modal Header -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:15px;">&#128200; <?= __('import_from_cost_sheet') ?></h3>
            <button type="button" onclick="closeCostSheetModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#666;">&times;</button>
        </div>

        <!-- Modal Body -->
        <div style="display:flex;flex:1;overflow:hidden;min-height:0;">
            <!-- Left: Cost Sheet List -->
            <div style="width:45%;border-right:1px solid var(--color-border);display:flex;flex-direction:column;">
                <div style="padding:12px;">
                    <input type="text" id="cs-search-input" class="form-input" placeholder="<?= __('search_cost_sheet_hint') ?>"
                           oninput="searchCostSheets(this.value)" style="font-size:12px;">
                </div>
                <div id="cs-results" style="flex:1;overflow-y:auto;padding:0 12px 12px;">
                </div>
            </div>

            <!-- Right: Items Preview -->
            <div style="width:55%;display:flex;flex-direction:column;">
                <div id="cs-items-panel" style="display:none;flex:1;overflow-y:auto;flex-direction:column;" data-sheet-id="" data-items="[]">
                    <div style="padding:10px 14px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                        <label style="font-size:12px;font-weight:600;color:#666;">
                            <input type="checkbox" checked onchange="toggleAllCsItems(this)"> <?= __('select_all') ?>
                        </label>
                        <button type="button" class="btn btn-primary btn-sm" onclick="importCostSheetItems()">
                            &#128229; <?= __('import_selected') ?>
                        </button>
                    </div>
                    <div style="overflow-y:auto;flex:1;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f5f7fa;font-size:11px;">
                                    <th style="padding:4px 8px;width:30px;"></th>
                                    <th style="padding:4px 8px;text-align:left;"><?= __('description') ?></th>
                                    <th style="padding:4px 8px;text-align:left;"><?= __('supplier') ?></th>
                                    <th style="padding:4px 8px;text-align:right;"><?= __('qty') ?></th>
                                    <th style="padding:4px 8px;"><?= __('unit') ?></th>
                                    <th style="padding:4px 8px;text-align:right;"><?= __('unit_price') ?></th>
                                    <th style="padding:4px 8px;text-align:right;"><?= __('total') ?></th>
                                </tr>
                            </thead>
                            <tbody id="cs-items-body">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="cs-items-empty" style="flex:1;display:flex;align-items:center;justify-content:center;">
                    <div style="text-align:center;color:#aaa;">
                        <div style="font-size:36px;margin-bottom:8px;">&#128196;</div>
                        <div style="font-size:13px;"><?= __('select_cost_sheet_to_preview') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cs-result-item {
    padding:10px 12px; cursor:pointer; border-radius:6px; margin-bottom:4px;
    border:1px solid transparent; transition:all .15s;
}
.cs-result-item:hover { background:#f0f4ff; border-color:#d0d8e8; }
.cs-result-item.selected { background:#e3f2fd; border-color:var(--color-primary); }
</style>
