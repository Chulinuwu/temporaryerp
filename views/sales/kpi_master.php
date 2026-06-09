<?php
/**
 * KPI Target master editor
 * Vars: $fy, $targets, $pctByKpi, $employees
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">⚙ <?= _e('kpi_target_master') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/kpi"><?= _e('sales_kpi_dashboard') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('kpi_target_master') ?></span>
        </div>
    </div>
    <a href="/sales/kpi?fy=<?= $fy ?>" class="btn btn-cancel">← <?= _e('back_to_dashboard') ?></a>
</div>

<div class="card" style="padding:12px 20px;margin-bottom:12px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <label><?= __('fiscal_year') ?>:</label>
        <select name="fy" class="form-select" style="width:140px;" onchange="this.form.submit()">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $fy == $y ? 'selected' : '' ?>>FY <?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- Add-new form (rate-based) -->
<div class="card" style="padding:16px 20px;margin-bottom:16px;background:#FFF8E1;border-left:4px solid #FB8C00;">
    <h3 style="margin:0 0 10px;font-size:14px;">+ <?= __('add_or_update_kpi') ?></h3>
    <p style="font-size:11px;color:#666;margin:0 0 10px;"><?= __('kpi_rate_model_hint') ?></p>
    <form method="POST" action="/sales/kpi/master/save" class="kpi-calc-form" style="display:grid;grid-template-columns:2fr 2fr 1.5fr 1.5fr 1.5fr auto;gap:10px;align-items:end;">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="fiscal_year" value="<?= e($fy) ?>">
        <div>
            <label class="form-label"><?= __('employee') ?></label>
            <select name="employee_id" class="form-select" required>
                <option value="">-- <?= __('select') ?> --</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= e($emp['employee_id']) ?>"><?= e($emp['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label"><?= __('annual_profit_target') ?> (THB)</label>
            <input type="number" name="annual_profit_target" class="form-input kpi-input" data-role="profit"
                   min="0" step="100000" value="2000000" required>
        </div>
        <div>
            <label class="form-label"><?= __('profit_per_order') ?> (THB)</label>
            <input type="number" name="profit_per_order" class="form-input kpi-input" data-role="ppo"
                   min="1" step="10000" value="100000" required>
        </div>
        <div>
            <label class="form-label"><?= __('close_rate_pct') ?> (%)</label>
            <input type="number" name="close_rate_pct" class="form-input kpi-input" data-role="close"
                   min="0.1" step="0.1" value="5" required>
        </div>
        <div>
            <label class="form-label"><?= __('appt_rate_pct') ?> (%)</label>
            <input type="number" name="appt_rate_pct" class="form-input kpi-input" data-role="appt"
                   min="0.1" step="0.1" value="10" required>
        </div>
        <div>
            <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
        </div>
    </form>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:10px;">
        <div style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:8px;text-align:center;">
            <div style="font-size:10px;color:#666;"><?= __('computed_orders') ?></div>
            <div style="font-size:18px;font-weight:700;color:#1976D2;" class="calc-orders">—</div>
        </div>
        <div style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:8px;text-align:center;">
            <div style="font-size:10px;color:#666;"><?= __('computed_meetings') ?></div>
            <div style="font-size:18px;font-weight:700;color:#E65100;" class="calc-meetings">—</div>
        </div>
        <div style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:8px;text-align:center;">
            <div style="font-size:10px;color:#666;"><?= __('computed_contacts') ?></div>
            <div style="font-size:18px;font-weight:700;color:#7B1FA2;" class="calc-contacts">—</div>
        </div>
    </div>
</div>

<!-- Existing targets -->
<?php if (empty($targets)): ?>
    <div class="card" style="padding:30px;text-align:center;color:#888;"><?= __('no_kpi_data') ?></div>
<?php else: foreach ($targets as $t):
    $pcts = $pctByKpi[$t['kpi_id']] ?? [];
?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="background:#E3F2FD;padding:10px 16px;">
        <h3 style="margin:0;font-size:15px;">👤 <?= e($t['full_name']) ?> — FY <?= e($t['fiscal_year']) ?></h3>
    </div>
    <div class="card-body" style="padding:16px 20px;">
        <form method="POST" action="/sales/kpi/master/save" class="kpi-calc-form">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="employee_id" value="<?= e($t['employee_id']) ?>">
            <input type="hidden" name="fiscal_year" value="<?= e($t['fiscal_year']) ?>">

            <!-- Inputs: profit + 3 rates -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:12px;">
                <div>
                    <label class="form-label"><?= __('annual_profit_target') ?> (THB)</label>
                    <input type="number" name="annual_profit_target" class="form-input kpi-input" data-role="profit"
                           min="0" step="100000" value="<?= e($t['annual_profit_target']) ?>" required>
                </div>
                <div>
                    <label class="form-label"><?= __('profit_per_order') ?> (THB)</label>
                    <input type="number" name="profit_per_order" class="form-input kpi-input" data-role="ppo"
                           min="1" step="10000" value="<?= e($t['profit_per_order'] ?? 100000) ?>" required>
                </div>
                <div>
                    <label class="form-label"><?= __('close_rate_pct') ?> (%)</label>
                    <input type="number" name="close_rate_pct" class="form-input kpi-input" data-role="close"
                           min="0.1" step="0.1" value="<?= e($t['close_rate_pct'] ?? 5) ?>" required>
                </div>
                <div>
                    <label class="form-label"><?= __('appt_rate_pct') ?> (%)</label>
                    <input type="number" name="appt_rate_pct" class="form-input kpi-input" data-role="appt"
                           min="0.1" step="0.1" value="<?= e($t['appt_rate_pct'] ?? 10) ?>" required>
                </div>
            </div>

            <!-- Computed outputs -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;">
                <div style="background:#E3F2FD;border-radius:6px;padding:10px;text-align:center;">
                    <div style="font-size:10px;color:#666;"><?= __('annual_order_target') ?></div>
                    <div style="font-size:24px;font-weight:700;color:#1976D2;" class="calc-orders"><?= e($t['annual_order_target'] ?? 0) ?></div>
                    <div style="font-size:9px;color:#888;">= <?= __('annual_profit_target') ?> ÷ <?= __('profit_per_order') ?></div>
                </div>
                <div style="background:#FFF3E0;border-radius:6px;padding:10px;text-align:center;">
                    <div style="font-size:10px;color:#666;"><?= __('annual_meeting_target') ?></div>
                    <div style="font-size:24px;font-weight:700;color:#E65100;" class="calc-meetings"><?= e($t['annual_meeting_target']) ?></div>
                    <div style="font-size:9px;color:#888;">= <?= __('annual_order_target') ?> ÷ <?= __('close_rate_pct') ?></div>
                </div>
                <div style="background:#F3E5F5;border-radius:6px;padding:10px;text-align:center;">
                    <div style="font-size:10px;color:#666;"><?= __('annual_contact_target') ?></div>
                    <div style="font-size:24px;font-weight:700;color:#7B1FA2;" class="calc-contacts"><?= e($t['annual_contact_target']) ?></div>
                    <div style="font-size:9px;color:#888;">= <?= __('annual_meeting_target') ?> ÷ <?= __('appt_rate_pct') ?></div>
                </div>
            </div>

            <h4 style="margin:12px 0 8px;font-size:13px;"><?= __('monthly_distribution_pct') ?></h4>
            <table style="width:100%;font-size:12px;margin-bottom:10px;">
                <tr>
                    <?php $months = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar']; ?>
                    <?php foreach ($months as $i => $m): ?>
                        <th class="text-center" style="padding:3px;"><?= $m ?></th>
                    <?php endforeach; ?>
                    <th class="text-center" style="padding:3px;background:#E8F5E9;"><?= __('total') ?></th>
                </tr>
                <tr>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $v = $pcts[$m] ?? (100 / 12);
                    ?>
                        <td style="padding:2px;">
                            <input type="number" step="0.001" min="0" max="100" name="pct[<?= $m ?>]"
                                   class="form-input pct-cell" value="<?= e(round($v, 3)) ?>"
                                   style="width:100%;text-align:right;padding:3px 4px;font-size:11px;">
                        </td>
                    <?php endfor; ?>
                    <td style="padding:4px;background:#E8F5E9;font-weight:700;text-align:center;" class="pct-total">—</td>
                </tr>
            </table>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:11px;color:#888;"><?= __('monthly_pct_note') ?></div>
                <button type="submit" class="btn btn-primary btn-sm"><?= _e('save') ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; endif; ?>

<script>
// Live cascade: profit → orders → meetings → contacts
document.querySelectorAll('.kpi-calc-form').forEach(function(form){
    var get = function(role) { var el = form.querySelector('[data-role="'+role+'"]'); return el ? parseFloat(el.value || 0) : 0; };
    var orders = form.querySelector('.calc-orders');
    var meets  = form.querySelector('.calc-meetings');
    var conts  = form.querySelector('.calc-contacts');
    function recalc() {
        var profit  = get('profit');
        var ppo     = Math.max(1, get('ppo'));
        var close   = Math.max(0.01, get('close'));
        var appt    = Math.max(0.01, get('appt'));
        var o = Math.round(profit / ppo);
        var m = Math.round(o / (close / 100));
        var c = Math.round(m / (appt / 100));
        if (orders) orders.textContent = o.toLocaleString();
        if (meets)  meets.textContent  = m.toLocaleString();
        if (conts)  conts.textContent  = c.toLocaleString();
    }
    form.querySelectorAll('.kpi-input').forEach(function(i){ i.addEventListener('input', recalc); });
    recalc();
});

// Live pct total (for monthly distribution)
document.querySelectorAll('form').forEach(function(form){
    var cells = form.querySelectorAll('.pct-cell');
    var total = form.querySelector('.pct-total');
    if (!cells.length || !total) return;
    function recalc() {
        var s = 0;
        cells.forEach(function(c){ s += parseFloat(c.value || 0); });
        total.textContent = s.toFixed(2) + '%';
        total.style.color = Math.abs(s - 100) < 0.1 ? '#2E7D32' : '#D32F2F';
    }
    cells.forEach(function(c){ c.addEventListener('input', recalc); });
    recalc();
});
</script>
