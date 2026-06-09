<?php
/**
 * PEGASUS ERP — Exchange Rate Master
 * Variables: $rates, $currencies, $filters
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('menu_exchange_rates') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_exchange_rates') ?></span>
        </div>
    </div>
    <button type="button" class="btn btn-primary" onclick="openModal()">+ <?= __('new_exchange_rate') ?></button>
</div>

<!-- Filter -->
<div class="card" style="padding:12px 20px;margin-bottom:16px;">
    <form method="GET" action="/master/exchange-rates" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <select name="from" class="form-select" style="width:140px;">
            <option value="">From: <?= __('all') ?></option>
            <?php foreach ($currencies as $cu): ?>
                <option value="<?= e($cu['currency_code']) ?>" <?= ($filters['from'] ?? '') === $cu['currency_code'] ? 'selected' : '' ?>>
                    <?= e($cu['currency_code']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="to" class="form-select" style="width:140px;">
            <option value="">To: <?= __('all') ?></option>
            <?php foreach ($currencies as $cu): ?>
                <option value="<?= e($cu['currency_code']) ?>" <?= ($filters['to'] ?? '') === $cu['currency_code'] ? 'selected' : '' ?>>
                    <?= e($cu['currency_code']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date" class="form-input" style="width:160px;" value="<?= e($filters['date'] ?? '') ?>" placeholder="<?= __('on_date') ?>">
        <label style="font-size:12px;display:flex;align-items:center;gap:4px;">
            <input type="checkbox" name="active" value="1" <?= ($filters['active'] ?? '') === '1' ? 'checked' : '' ?>>
            <?= __('active_only') ?>
        </label>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/master/exchange-rates" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<div class="table-wrapper">
<table class="data-table">
<thead>
<tr>
    <th><?= __('from') ?></th>
    <th><?= __('to') ?></th>
    <th class="text-right"><?= __('rate') ?></th>
    <th><?= __('effective_from') ?></th>
    <th><?= __('effective_to') ?></th>
    <th><?= _e('notes') ?></th>
    <th style="width:90px;" class="text-center"><?= _e('actions') ?></th>
</tr>
</thead>
<tbody>
<?php if (empty($rates)): ?>
    <tr><td colspan="7" class="text-center" style="padding:30px;color:var(--color-text-muted);"><?= __('no_data') ?></td></tr>
<?php else: foreach ($rates as $r): ?>
    <tr>
        <td><strong><?= e($r['from_currency']) ?></strong> <span style="font-size:11px;color:#888;"><?= e($r['from_name'] ?? '') ?></span></td>
        <td><strong><?= e($r['to_currency']) ?></strong> <span style="font-size:11px;color:#888;"><?= e($r['to_name'] ?? '') ?></span></td>
        <td class="text-right"><?= number_format(floatval($r['rate']), 6) ?></td>
        <td><?= e($r['effective_from']) ?></td>
        <td><?= e($r['effective_to'] ?? '—') ?></td>
        <td style="font-size:12px;"><?= e($r['notes'] ?? '') ?></td>
        <td class="text-center actions">
            <a href="#" onclick='editRate(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>); return false;' title="<?= __('edit') ?>">&#9998;</a>
            <form method="POST" action="/master/exchange-rates/<?= e($r['rate_id']) ?>/delete" style="display:inline;">
                <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')" title="<?= __('delete') ?>" style="background:none;border:none;cursor:pointer;">&#128465;</button>
            </form>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<!-- Modal -->
<div id="rateModal" class="modal-overlay">
<div class="modal" style="max-width:560px;">
    <div class="modal-header">
        <h3 id="modalTitle"><?= __('new_exchange_rate') ?></h3>
        <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form method="POST" action="/master/exchange-rates" id="rateForm">
        <input type="hidden" name="rate_id" id="f_rate_id" value="">
        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group">
                <label class="form-label"><?= __('from') ?> *</label>
                <select name="from_currency" id="f_from" class="form-select" required>
                    <?php foreach ($currencies as $cu): ?>
                        <option value="<?= e($cu['currency_code']) ?>"><?= e($cu['currency_code']) ?> - <?= e($cu['currency_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('to') ?> *</label>
                <select name="to_currency" id="f_to" class="form-select" required>
                    <?php foreach ($currencies as $cu): ?>
                        <option value="<?= e($cu['currency_code']) ?>" <?= $cu['currency_code'] === 'THB' ? 'selected' : '' ?>><?= e($cu['currency_code']) ?> - <?= e($cu['currency_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label"><?= __('rate') ?> *</label>
                <input type="number" name="rate" id="f_rate" class="form-input" step="0.00000001" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('effective_from') ?> *</label>
                <input type="date" name="effective_from" id="f_eff_from" class="form-input" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('effective_to') ?></label>
                <input type="date" name="effective_to" id="f_eff_to" class="form-input">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label"><?= _e('notes') ?></label>
                <input type="text" name="notes" id="f_notes" class="form-input">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label style="font-size:13px;"><input type="checkbox" name="auto_reverse" value="1" id="f_auto_reverse" checked> <?= __('auto_create_reverse_rate') ?></label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="closeModal()"><?= _e('cancel') ?></button>
            <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
        </div>
    </form>
</div>
</div>

<script>
function openModal(){ document.getElementById('rateModal').classList.add('active'); }
function closeModal(){ document.getElementById('rateModal').classList.remove('active'); }
function editRate(r) {
    document.getElementById('modalTitle').textContent = '<?= __('edit_exchange_rate') ?>';
    document.getElementById('f_rate_id').value = r.rate_id;
    document.getElementById('f_from').value = r.from_currency;
    document.getElementById('f_to').value = r.to_currency;
    document.getElementById('f_rate').value = r.rate;
    document.getElementById('f_eff_from').value = r.effective_from;
    document.getElementById('f_eff_to').value = r.effective_to || '';
    document.getElementById('f_notes').value = r.notes || '';
    document.getElementById('f_auto_reverse').checked = false;
    openModal();
}
document.querySelector('[onclick="openModal()"]').addEventListener('click', () => {
    document.getElementById('modalTitle').textContent = '<?= __('new_exchange_rate') ?>';
    document.getElementById('rateForm').reset();
    document.getElementById('f_rate_id').value = '';
    document.getElementById('f_eff_from').value = '<?= date('Y-m-d') ?>';
    document.getElementById('f_auto_reverse').checked = true;
});
</script>
