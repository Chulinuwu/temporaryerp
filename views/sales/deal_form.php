<?php
/**
 * PEGASUS ERP - Deal Form (案件 作成/編集)
 * Variables: $deal, $customers, $statuses, $categories, $salesPersons
 */
$isEdit = !empty($deal);
$action = $isEdit ? '/sales/deals/' . $deal['deal_id'] : '/sales/deals';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? _e('edit_deal') : _e('new_deal') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/deals"><?= _e('deals') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= $isEdit ? e($deal['deal_no']) : _e('new_deal') ?></span>
        </div>
    </div>
    <a href="/sales/deals" class="btn btn-cancel"><?= _e('back') ?></a>
</div>

<!-- Form -->
<form method="POST" action="<?= $action ?>">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

    <!-- Basic Info -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-body" style="padding:20px;">
            <h3 style="margin:0 0 16px;font-size:15px;font-weight:600;color:var(--color-primary);"><?= _e('basic_info') ?></h3>
            <div class="form-grid-2">
                <!-- Deal Name -->
                <div class="form-group form-full">
                    <label class="form-label"><?= _e('deal_name') ?> <span class="required">*</span></label>
                    <input type="text" name="deal_name" class="form-input" required
                           value="<?= e($deal['deal_name'] ?? '') ?>"
                           placeholder="<?= __('deal_name_placeholder') ?>">
                </div>

                <!-- Customer -->
                <div class="form-group">
                    <label class="form-label"><?= _e('customer') ?></label>
                    <select name="customer_id" class="form-select">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= e($c['customer_id']) ?>"
                                <?= ($deal['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
                                <?= e($c['customer_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label class="form-label"><?= _e('possibility') ?></label>
                    <select name="status_id" class="form-select">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s['status_id']) ?>"
                                <?= ($deal['status_id'] ?? '') == $s['status_id'] ? 'selected' : '' ?>>
                                <?= e($s['status_name']) ?> (<?= $s['win_pct'] ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Solution Category -->
                <div class="form-group">
                    <label class="form-label"><?= _e('solution_category') ?></label>
                    <select name="solution_category_id" id="solution_category_id" class="form-select">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['category_id']) ?>"
                                data-eval-profit-rate="<?= e($cat['eval_profit_rate'] ?? 100) ?>"
                                <?= ($deal['solution_category_id'] ?? '') == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= e($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Customer Staff -->
                <div class="form-group">
                    <label class="form-label"><?= _e('customer_staff') ?></label>
                    <input type="text" name="customer_staff" class="form-input"
                           value="<?= e($deal['customer_staff'] ?? '') ?>">
                </div>

                <!-- Touch Point -->
                <div class="form-group">
                    <label class="form-label"><?= _e('touch_point') ?></label>
                    <input type="text" name="touch_point" class="form-input"
                           value="<?= e($deal['touch_point'] ?? '') ?>">
                </div>

                <!-- Sales Person -->
                <div class="form-group">
                    <label class="form-label"><?= _e('sales_person') ?></label>
                    <select name="sales_person_id" class="form-select">
                        <option value="">-- <?= __('select') ?> --</option>
                        <?php foreach ($salesPersons as $sp): ?>
                            <option value="<?= e($sp['employee_id']) ?>"
                                <?= ($deal['sales_person_id'] ?? '') == $sp['employee_id'] ? 'selected' : '' ?>>
                                <?= e($sp['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- #5: Sales person name field removed (sales_person dropdown is sufficient) -->
                <input type="hidden" name="sales_person_name" value="<?= e($deal['sales_person_name'] ?? '') ?>">

                <!-- PJ No -->
                <div class="form-group">
                    <label class="form-label"><?= _e('pj_no') ?></label>
                    <input type="text" name="pj_no" class="form-input"
                           value="<?= e($deal['pj_no'] ?? '') ?>">
                </div>

                <!-- Related Projects (#5: select from sales orders) -->
                <div class="form-group">
                    <label class="form-label"><?= _e('related_projects') ?></label>
                    <select name="related_projects" class="form-select">
                        <option value=""><?= __('select') ?> --</option>
                        <?php foreach (($salesOrders ?? []) as $so): ?>
                            <option value="<?= e($so['so_no']) ?>" <?= ($deal['related_projects'] ?? '') === $so['so_no'] ? 'selected' : '' ?>>
                                <?= e($so['so_no']) ?> - <?= e($so['customer_name'] ?? '') ?>
                                <?= !empty($so['notes']) ? ' (' . e(mb_substr($so['notes'], 0, 30)) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Evaluation Profit % (auto-derived from solution category) -->
                <div class="form-group">
                    <label class="form-label"><?= __('evaluation_profit_pct') ?></label>
                    <input type="number" id="evalProfitPct" class="form-input" step="0.01" min="0" max="100"
                           value="<?= e($deal['evaluation_profit_pct'] ?? '') ?>" readonly
                           style="background:#f5f5f5;" placeholder="<?= __('auto_from_solution_category') ?>">
                    <input type="hidden" name="evaluation_profit_pct" id="evalProfitPctHidden" value="<?= e($deal['evaluation_profit_pct'] ?? '') ?>">
                    <small style="color:var(--color-text-muted);font-size:11px;"><?= __('auto_from_solution_category') ?></small>
                </div>
                <script>
                    // Auto-fill evaluation_profit_pct from selected solution category
                    (function(){
                        var solSelect = document.querySelector('select[name="solution_category_id"]');
                        if (!solSelect) return;
                        var solRates = <?= json_encode(array_column(($solutionCategories ?? []), 'evaluation_profit_pct', 'category_id'), JSON_UNESCAPED_UNICODE) ?>;
                        function update() {
                            var v = solRates[solSelect.value];
                            if (v !== undefined) {
                                document.getElementById('evalProfitPct').value = v;
                                document.getElementById('evalProfitPctHidden').value = v;
                            }
                        }
                        solSelect.addEventListener('change', update);
                        if (!document.getElementById('evalProfitPct').value) update();
                    })();
                </script>
            </div>
        </div>
    </div>

    <!-- Project Management Fields -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-body" style="padding:20px;">
            <h3 style="margin:0 0 16px;font-size:15px;font-weight:600;color:var(--color-primary);"><?= _e('project_management') ?></h3>
            <div class="form-grid-2">
                <!-- First Contact Date -->
                <div class="form-group">
                    <label class="form-label"><?= _e('first_contact_date') ?></label>
                    <input type="date" name="first_contact_date" class="form-input"
                           value="<?= e($deal['first_contact_date'] ?? '') ?>">
                </div>

                <!-- Expected Close / Expected Order Date -->
                <div class="form-group">
                    <label class="form-label"><?= _e('expected_order_date') ?></label>
                    <input type="date" name="expected_close" class="form-input"
                           value="<?= e($deal['expected_close'] ?? '') ?>">
                </div>

                <!-- Budget Status -->
                <div class="form-group">
                    <label class="form-label"><?= _e('budget_status') ?></label>
                    <select name="budget_status" class="form-select">
                        <?php
                        $budgetOpts = ['No', 'Yes', 'Pending', 'Approved'];
                        foreach ($budgetOpts as $bo):
                        ?>
                            <option value="<?= $bo ?>" <?= ($deal['budget_status'] ?? 'No') === $bo ? 'selected' : '' ?>>
                                <?= $bo ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Budget Amount -->
                <div class="form-group">
                    <label class="form-label"><?= _e('budget_amount') ?></label>
                    <input type="number" name="budget_amount" class="form-input" step="0.01"
                           value="<?= e($deal['budget_amount'] ?? '') ?>">
                </div>

                <!-- Expected Amount -->
                <div class="form-group">
                    <label class="form-label"><?= _e('expected_amount') ?></label>
                    <input type="number" name="expected_amount" class="form-input" step="0.01"
                           value="<?= e($deal['expected_amount'] ?? '') ?>">
                </div>

                <!-- Win Rate % -->
                <div class="form-group">
                    <label class="form-label"><?= _e('win_rate') ?> (%)</label>
                    <input type="number" name="win_rate" class="form-input" min="0" max="100"
                           value="<?= e($deal['win_rate'] ?? '0') ?>">
                </div>

                <!-- Est. Revenue -->
                <div class="form-group">
                    <label class="form-label"><?= _e('est_revenue') ?></label>
                    <input type="number" name="est_revenue" class="form-input" step="0.01"
                           value="<?= e($deal['est_revenue'] ?? '') ?>">
                </div>

                <!-- Est. Profit -->
                <div class="form-group">
                    <label class="form-label"><?= _e('est_profit') ?></label>
                    <input type="number" name="est_profit" class="form-input" step="0.01"
                           value="<?= e($deal['est_profit'] ?? '') ?>">
                </div>

                <!-- Eval. Profit (%) - auto-calculated: est_profit * (eval_profit_rate / 100) -->
                <div class="form-group">
                    <label class="form-label"><?= _e('eval_profit') ?> (%)</label>
                    <div style="display:flex;align-items:center;gap:4px;">
                        <input type="number" name="eval_profit" id="eval_profit" class="form-input" step="0.01"
                               value="<?= e($deal['eval_profit'] ?? '') ?>" readonly
                               style="background:var(--color-bg-secondary);">
                        <span style="font-size:13px;color:var(--color-text-muted);">%</span>
                    </div>
                    <input type="hidden" name="eval_profit_rate" id="eval_profit_rate" value="">
                </div>

                <!-- Due Date -->
                <div class="form-group">
                    <label class="form-label"><?= _e('due_date') ?></label>
                    <input type="date" name="due_date" class="form-input"
                           value="<?= e($deal['due_date'] ?? '') ?>">
                </div>

                <!-- Next Action -->
                <div class="form-group form-full">
                    <label class="form-label"><?= _e('next_action') ?></label>
                    <input type="text" name="next_action" class="form-input"
                           value="<?= e($deal['next_action'] ?? '') ?>">
                </div>

                <!-- Meeting Notes -->
                <div class="form-group form-full">
                    <label class="form-label"><?= _e('meeting_notes') ?></label>
                    <textarea name="meeting_notes" class="form-input" rows="3"><?= e($deal['meeting_notes'] ?? '') ?></textarea>
                </div>

                <!-- Note -->
                <div class="form-group form-full">
                    <label class="form-label"><?= _e('note') ?></label>
                    <textarea name="note" class="form-input" rows="2"><?= e($deal['note'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
        <a href="/sales/deals" class="btn btn-cancel"><?= _e('cancel') ?></a>
        <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
    </div>
</form>

<script>
(function() {
    var categorySelect = document.getElementById('solution_category_id');
    var estProfitInput = document.querySelector('input[name="est_profit"]');
    var evalProfitInput = document.getElementById('eval_profit');
    var evalProfitRateInput = document.getElementById('eval_profit_rate');

    function getSelectedRate() {
        var selected = categorySelect.options[categorySelect.selectedIndex];
        if (!selected || !selected.value) return 0;
        return parseFloat(selected.getAttribute('data-eval-profit-rate')) || 0;
    }

    function recalcEvalProfit() {
        var rate = getSelectedRate();
        var estProfit = parseFloat(estProfitInput.value) || 0;
        evalProfitRateInput.value = rate;
        if (rate > 0 && estProfit > 0) {
            evalProfitInput.value = (estProfit * (rate / 100)).toFixed(2);
        } else {
            evalProfitInput.value = '';
        }
    }

    categorySelect.addEventListener('change', recalcEvalProfit);
    estProfitInput.addEventListener('input', recalcEvalProfit);

    // Initialize on page load
    recalcEvalProfit();
})();
</script>
