<?php
/**
 * PEGASUS ERP - Project Form (新規/編集)
 * Variables: $project, $customers, $salesPersons, $categories
 */
$p = $project;
$isEdit = !empty($p);
$action = $isEdit ? '/projects/' . $p['project_id'] : '/projects';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? e($p['pj_no']) . ' - ' . __('edit') : __('new_project') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/projects"><?= __('project_list') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= $isEdit ? __('edit') : __('create') ?></span>
        </div>
    </div>
</div>

<form method="POST" action="<?= $action ?>">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <!-- Basic Info -->
    <div class="card" style="margin-bottom:16px;">
        <div style="padding:12px 20px;border-bottom:1px solid var(--color-border);">
            <h4 style="margin:0;font-size:14px;"><?= __('basic_info') ?></h4>
        </div>
        <div class="card-body" style="padding:16px 20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label class="form-label">PJ No.</label>
                    <input type="text" name="pj_no" class="form-input" value="<?= e($p['pj_no'] ?? '') ?>"
                           placeholder="<?= __('auto_generate') ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column:2/4;">
                    <label class="form-label"><?= __('pj_name') ?> <span class="required">*</span></label>
                    <input type="text" name="pj_name" class="form-input" value="<?= e($p['pj_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('customer') ?></label>
                    <select name="customer_id" class="form-select">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e($c['customer_id']) ?>" <?= ($p['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>><?= e($c['customer_name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('end_user') ?></label>
                    <input type="text" name="end_user_customer" class="form-input" value="<?= e($p['end_user_customer'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('delivery_place') ?></label>
                    <input type="text" name="delivery_place" class="form-input" value="<?= e($p['delivery_place'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('pj_segment') ?></label>
                    <select name="pj_segment" class="form-select">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['category_name']) ?>" <?= ($p['pj_segment'] ?? '') === $cat['category_name'] ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('pj_category') ?></label>
                    <input type="text" name="pj_category" class="form-input" value="<?= e($p['pj_category'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('pj_classification') ?></label>
                    <input type="text" name="pj_classification" class="form-input" value="<?= e($p['pj_classification'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('related_pj') ?></label>
                    <input type="text" name="related_pj_no" class="form-input" value="<?= e($p['related_pj_no'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('sales_person') ?></label>
                    <select name="sales_person_id" class="form-select">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($salesPersons as $sp): ?>
                            <option value="<?= e($sp['employee_id']) ?>" <?= ($p['sales_person_id'] ?? '') == $sp['employee_id'] ? 'selected' : '' ?>><?= e($sp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('status') ?></label>
                    <select name="status" class="form-select">
                        <?php foreach (['ACTIVE','IN_PROGRESS','COMPLETED','ON_HOLD','CANCELLED'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($p['status'] ?? 'ACTIVE') === $s ? 'selected' : '' ?>><?= __('pj_' . strtolower($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial -->
    <div class="card" style="margin-bottom:16px;">
        <div style="padding:12px 20px;border-bottom:1px solid var(--color-border);">
            <h4 style="margin:0;font-size:14px;"><?= __('financial_info') ?></h4>
        </div>
        <div class="card-body" style="padding:16px 20px;">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                <div class="form-group">
                    <label class="form-label"><?= __('total_revenue') ?></label>
                    <input type="number" name="total_revenue" class="form-input" step="0.01" value="<?= e($p['total_revenue'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('total_cost_short') ?></label>
                    <input type="number" name="total_cost" class="form-input" step="0.01" value="<?= e($p['total_cost'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('gross_profit') ?></label>
                    <input type="number" name="gross_profit" class="form-input" step="0.01" value="<?= e($p['gross_profit'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('profit_pct') ?> %</label>
                    <input type="number" name="profit_pct" class="form-input" step="0.1" value="<?= e($p['profit_pct'] ?? 0) ?>">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:8px;">
                <div class="form-group">
                    <label class="form-label">Sales - Hardware</label>
                    <input type="number" name="sales_hardware" class="form-input" step="0.01" value="<?= e($p['sales_hardware'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sales - Software</label>
                    <input type="number" name="sales_software" class="form-input" step="0.01" value="<?= e($p['sales_software'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sales - SW Development</label>
                    <input type="number" name="sales_sw_development" class="form-input" step="0.01" value="<?= e($p['sales_sw_development'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sales - SW License</label>
                    <input type="number" name="sales_sw_license" class="form-input" step="0.01" value="<?= e($p['sales_sw_license'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sales - Installation</label>
                    <input type="number" name="sales_installation" class="form-input" step="0.01" value="<?= e($p['sales_installation'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sales - SW Install</label>
                    <input type="number" name="sales_sw_installation" class="form-input" step="0.01" value="<?= e($p['sales_sw_installation'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sales - HW+Wiring</label>
                    <input type="number" name="sales_hw_wiring" class="form-input" step="0.01" value="<?= e($p['sales_hw_wiring'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('service_cost') ?></label>
                    <input type="number" name="service_cost" class="form-input" step="0.01" value="<?= e($p['service_cost'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('engineer_cost') ?></label>
                    <input type="number" name="engineer_cost" class="form-input" step="0.01" value="<?= e($p['engineer_cost'] ?? 0) ?>">
                </div>
            </div>
            <h5 style="margin:16px 0 8px;font-size:13px;color:var(--color-text-muted);"><?= __('man_months') ?></h5>
            <div style="display:grid;grid-template-columns:repeat(3,1fr 1fr);gap:12px;">
                <div class="form-group"><label class="form-label">Programming (MM)</label><input type="number" name="mm_programming" class="form-input" step="0.1" value="<?= e($p['mm_programming'] ?? 0) ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('unit_price') ?></label><input type="number" name="unit_price_programming" class="form-input" step="0.01" value="<?= e($p['unit_price_programming'] ?? 0) ?>"></div>
                <div class="form-group"><label class="form-label">Design (MM)</label><input type="number" name="mm_design" class="form-input" step="0.1" value="<?= e($p['mm_design'] ?? 0) ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('unit_price') ?></label><input type="number" name="unit_price_design" class="form-input" step="0.01" value="<?= e($p['unit_price_design'] ?? 0) ?>"></div>
                <div class="form-group"><label class="form-label">Testing (MM)</label><input type="number" name="mm_testing" class="form-input" step="0.1" value="<?= e($p['mm_testing'] ?? 0) ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('unit_price') ?></label><input type="number" name="unit_price_testing" class="form-input" step="0.01" value="<?= e($p['unit_price_testing'] ?? 0) ?>"></div>
            </div>
        </div>
    </div>

    <!-- Dates & Payment Schedule -->
    <div class="card" style="margin-bottom:16px;">
        <div style="padding:12px 20px;border-bottom:1px solid var(--color-border);">
            <h4 style="margin:0;font-size:14px;"><?= __('schedule') ?></h4>
        </div>
        <div class="card-body" style="padding:16px 20px;">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                <div class="form-group"><label class="form-label">P/O Date (<?= __('order_date_short') ?>)</label><input type="date" name="po_date" class="form-input" value="<?= e($p['po_date'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('start_work') ?></label><input type="date" name="start_work_date" class="form-input" value="<?= e($p['start_work_date'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('finished_work') ?></label><input type="date" name="finished_work_date" class="form-input" value="<?= e($p['finished_work_date'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('plan_delivery_date') ?></label><input type="date" name="plan_delivery_date" class="form-input" value="<?= e($p['plan_delivery_date'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('delivery_date') ?></label><input type="date" name="delivery_date" class="form-input" value="<?= e($p['delivery_date'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('inspection') ?></label><input type="date" name="inspection_date" class="form-input" value="<?= e($p['inspection_date'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label"><?= __('complete') ?></label><input type="date" name="complete_date" class="form-input" value="<?= e($p['complete_date'] ?? '') ?>"></div>
            </div>
            <h5 style="margin:16px 0 8px;font-size:13px;color:var(--color-text-muted);"><?= __('payment_schedule') ?></h5>
            <div style="display:flex;gap:12px;align-items:end;margin-bottom:12px;">
                <div class="form-group" style="margin-bottom:0;flex:1;">
                    <label class="form-label" style="font-size:11px;"><?= __('payment_terms') ?></label>
                    <select name="payment_term_id" id="paymentTermSelect" class="form-select" onchange="onPaymentTermChange(this.value)">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($paymentTerms ?? [] as $pt): ?>
                            <option value="<?= e($pt['term_id']) ?>" <?= ($p['payment_term_id'] ?? '') == $pt['term_id'] ? 'selected' : '' ?>>
                                <?= e($pt['term_name_en']) ?> <?= $pt['term_name_jp'] ? '(' . e($pt['term_name_jp']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="applyPaymentTerm()" style="height:36px;white-space:nowrap;" title="<?= __('apply_payment_term') ?>">
                    &#8635; <?= __('apply') ?>
                </button>
                <button type="button" class="btn btn-sm btn-cancel" onclick="addPaymentRow()" style="height:36px;white-space:nowrap;">
                    + <?= __('add_row') ?>
                </button>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table" id="paymentScheduleTable" style="font-size:12px;">
                    <thead>
                        <tr style="background:#f5f7fa;">
                            <th style="width:40px;text-align:center;">#</th>
                            <th style="min-width:200px;"><?= __('description') ?></th>
                            <th style="width:70px;text-align:center;">%</th>
                            <th style="width:90px;text-align:center;">Credit Days</th>
                            <th style="width:130px;text-align:center;"><?= __('plan') ?></th>
                            <th style="width:130px;text-align:center;"><?= __('actual') ?></th>
                            <th style="width:130px;text-align:right;"><?= __('amount') ?></th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="paymentScheduleBody">
                        <?php if (!empty($paymentSchedules)):
                            foreach ($paymentSchedules as $idx => $ps): ?>
                        <tr>
                            <td class="text-center ps-seq"><?= $idx + 1 ?></td>
                            <td><input type="text" name="ps[<?= $idx ?>][description]" class="form-input" value="<?= e($ps['description'] ?? '') ?>" style="font-size:12px;"></td>
                            <td><input type="number" name="ps[<?= $idx ?>][percentage]" class="form-input text-center ps-pct" step="0.01" min="0" max="100" value="<?= e($ps['percentage'] ?? 0) ?>" style="font-size:12px;" onchange="recalcPaymentAmount(this)"></td>
                            <td><input type="number" name="ps[<?= $idx ?>][credit_days]" class="form-input text-center" min="0" value="<?= e($ps['credit_days'] ?? 0) ?>" style="font-size:12px;"></td>
                            <td><input type="date" name="ps[<?= $idx ?>][plan_date]" class="form-input" value="<?= e($ps['plan_date'] ?? '') ?>" style="font-size:12px;"></td>
                            <td><input type="date" name="ps[<?= $idx ?>][actual_date]" class="form-input" value="<?= e($ps['actual_date'] ?? '') ?>" style="font-size:12px;"></td>
                            <td><input type="number" name="ps[<?= $idx ?>][amount]" class="form-input text-right ps-amount" step="0.01" value="<?= e($ps['amount'] ?? 0) ?>" style="font-size:12px;"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removePaymentRow(this)" style="padding:2px 6px;font-size:11px;">&times;</button></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f5f7fa;font-weight:600;">
                            <td colspan="2" class="text-right"><?= __('total') ?></td>
                            <td class="text-center" id="psTotalPct">0</td>
                            <td></td>
                            <td colspan="2"></td>
                            <td class="text-right" id="psTotalAmount">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Remark & Hidden fields -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-body" style="padding:16px 20px;">
            <div class="form-group">
                <label class="form-label"><?= __('remark') ?></label>
                <textarea name="remark" class="form-input" rows="3"><?= e($p['remark'] ?? '') ?></textarea>
            </div>
            <?php if ($isEdit): ?>
                <input type="hidden" name="so_id" value="<?= e($p['so_id'] ?? '') ?>">
                <input type="hidden" name="deal_id" value="<?= e($p['deal_id'] ?? '') ?>">
            <?php endif; ?>
        </div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;">
        <a href="/projects" class="btn btn-cancel"><?= __('cancel') ?></a>
        <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
    </div>
</form>

<script>
var psRowIndex = <?= count($paymentSchedules ?? []) ?>;

function addPaymentRow(data) {
    data = data || {};
    var tbody = document.getElementById('paymentScheduleBody');
    var idx = psRowIndex++;
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td class="text-center ps-seq">' + (tbody.children.length + 1) + '</td>' +
        '<td><input type="text" name="ps[' + idx + '][description]" class="form-input" value="' + escHtml(data.description || '') + '" style="font-size:12px;"></td>' +
        '<td><input type="number" name="ps[' + idx + '][percentage]" class="form-input text-center ps-pct" step="0.01" min="0" max="100" value="' + (data.percentage || 0) + '" style="font-size:12px;" onchange="recalcPaymentAmount(this)"></td>' +
        '<td><input type="number" name="ps[' + idx + '][credit_days]" class="form-input text-center" min="0" value="' + (data.credit_days || 0) + '" style="font-size:12px;"></td>' +
        '<td><input type="date" name="ps[' + idx + '][plan_date]" class="form-input" value="' + (data.plan_date || '') + '" style="font-size:12px;"></td>' +
        '<td><input type="date" name="ps[' + idx + '][actual_date]" class="form-input" value="' + (data.actual_date || '') + '" style="font-size:12px;"></td>' +
        '<td><input type="number" name="ps[' + idx + '][amount]" class="form-input text-right ps-amount" step="0.01" value="' + (data.amount || 0) + '" style="font-size:12px;"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removePaymentRow(this)" style="padding:2px 6px;font-size:11px;">&times;</button></td>';
    tbody.appendChild(tr);
    renumberPaymentRows();
    updatePaymentTotals();
}

function removePaymentRow(btn) {
    btn.closest('tr').remove();
    renumberPaymentRows();
    updatePaymentTotals();
}

function renumberPaymentRows() {
    var rows = document.getElementById('paymentScheduleBody').getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        rows[i].querySelector('.ps-seq').textContent = i + 1;
    }
}

function updatePaymentTotals() {
    var rows = document.getElementById('paymentScheduleBody').getElementsByTagName('tr');
    var totalPct = 0, totalAmt = 0;
    for (var i = 0; i < rows.length; i++) {
        var pctInput = rows[i].querySelector('.ps-pct');
        var amtInput = rows[i].querySelector('.ps-amount');
        if (pctInput) totalPct += parseFloat(pctInput.value) || 0;
        if (amtInput) totalAmt += parseFloat(amtInput.value) || 0;
    }
    document.getElementById('psTotalPct').textContent = totalPct.toFixed(2);
    document.getElementById('psTotalAmount').textContent = totalAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    // Highlight if total pct != 100
    var pctEl = document.getElementById('psTotalPct');
    pctEl.style.color = (Math.abs(totalPct - 100) < 0.01 && totalPct > 0) ? '#4CAF50' : (totalPct > 0 ? '#F44336' : '');
}

function recalcPaymentAmount(input) {
    var totalRev = parseFloat(document.querySelector('input[name="total_revenue"]').value) || 0;
    if (totalRev <= 0) return;
    var pct = parseFloat(input.value) || 0;
    var amtInput = input.closest('tr').querySelector('.ps-amount');
    amtInput.value = (totalRev * pct / 100).toFixed(2);
    updatePaymentTotals();
}

function applyPaymentTerm() {
    var termId = document.getElementById('paymentTermSelect').value;
    if (!termId) {
        alert('<?= __('select_payment_term_first') ?>');
        return;
    }
    var totalRev = parseFloat(document.querySelector('input[name="total_revenue"]').value) || 0;

    fetch('/api/payment-terms/' + termId + '/installments')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.installments || data.installments.length === 0) {
                alert('No installments defined for this payment term.');
                return;
            }
            // Clear existing rows
            document.getElementById('paymentScheduleBody').innerHTML = '';
            psRowIndex = 0;

            data.installments.forEach(function(inst) {
                var amt = totalRev > 0 ? (totalRev * parseFloat(inst.percentage) / 100) : 0;
                addPaymentRow({
                    description: inst.description_en || '',
                    percentage: inst.percentage,
                    credit_days: inst.credit_days || 0,
                    amount: amt.toFixed(2),
                });
            });
            updatePaymentTotals();
        })
        .catch(function(err) {
            console.error('Failed to load payment term:', err);
            alert('Failed to load payment term installments.');
        });
}

function onPaymentTermChange(termId) {
    // Optional: auto-apply if table is empty
    if (termId && document.getElementById('paymentScheduleBody').children.length === 0) {
        applyPaymentTerm();
    }
}

function escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML.replace(/"/g, '&quot;');
}

// Initialize totals on page load
document.addEventListener('DOMContentLoaded', function() { updatePaymentTotals(); });
</script>
