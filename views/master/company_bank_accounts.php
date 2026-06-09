<?php
/**
 * PEGASUS ERP — Company Bank Accounts master
 * Vars: $accounts, $currencies
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">🏦 <?= _e('menu_company_bank') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_company_bank') ?></span>
        </div>
    </div>
    <button type="button" class="btn btn-primary" onclick="openCBAModal()">+ <?= __('new_bank_account') ?></button>
</div>

<div class="card" style="padding:0;overflow-x:auto;">
<table class="data-table" style="font-size:12px;">
    <thead>
    <tr>
        <th class="text-center" style="width:40px;">#</th>
        <th><?= __('bank_name') ?></th>
        <th><?= __('branch') ?></th>
        <th><?= __('account_type') ?></th>
        <th><?= __('account_no') ?></th>
        <th><?= __('account_name') ?></th>
        <th class="text-center"><?= __('currency') ?></th>
        <th><?= __('swift_code') ?></th>
        <th class="text-center"><?= __('default') ?></th>
        <th class="text-center"><?= __('active') ?></th>
        <th class="text-center"><?= __('actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($accounts)): ?>
        <tr><td colspan="11" class="text-center" style="padding:30px;color:#888;"><?= __('no_data') ?></td></tr>
    <?php else: foreach ($accounts as $i => $a): ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td>
                <strong><?= e($a['bank_name']) ?></strong>
                <?php if (!empty($a['bank_name_th'])): ?>
                    <div style="font-size:10px;color:#666;"><?= e($a['bank_name_th']) ?></div>
                <?php endif; ?>
            </td>
            <td><?= e($a['branch'] ?? '—') ?></td>
            <td><?= e($a['account_type']) ?></td>
            <td style="font-family:Consolas,monospace;"><strong><?= e($a['account_no']) ?></strong></td>
            <td><?= e($a['account_name']) ?></td>
            <td class="text-center"><?= e($a['currency_code']) ?></td>
            <td style="font-family:Consolas,monospace;"><?= e($a['swift_code'] ?? '—') ?></td>
            <td class="text-center">
                <?php if ($a['is_default']): ?>
                    <span class="badge" style="background:#FB8C00;color:#fff;">★ <?= __('default') ?></span>
                <?php endif; ?>
            </td>
            <td class="text-center"><?= $a['is_active'] ? '✅' : '⛔' ?></td>
            <td class="text-center actions">
                <a href="#" onclick='editCBA(<?= json_encode($a, JSON_UNESCAPED_UNICODE) ?>); return false;' title="<?= __('edit') ?>">&#9998;</a>
                <form method="POST" action="/master/company-bank/<?= e($a['cba_id']) ?>/delete" style="display:inline;">
                    <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')" title="<?= __('delete') ?>" style="background:none;border:none;cursor:pointer;">&#128465;</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<!-- Modal -->
<div id="cbaModal" class="modal-overlay">
<div class="modal" style="max-width:700px;">
    <div class="modal-header">
        <h3 id="cbaModalTitle"><?= __('new_bank_account') ?></h3>
        <button type="button" class="modal-close" onclick="closeCBAModal()">&times;</button>
    </div>
    <form method="POST" action="/master/company-bank" id="cbaForm">
        <input type="hidden" name="cba_id" id="f_cba_id" value="">
        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group">
                <label class="form-label"><?= __('bank_name') ?> *</label>
                <input type="text" name="bank_name" id="f_bank_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('bank_name_th') ?></label>
                <input type="text" name="bank_name_th" id="f_bank_name_th" class="form-input" placeholder="ธนาคาร...">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('branch') ?></label>
                <input type="text" name="branch" id="f_branch" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('branch_th') ?></label>
                <input type="text" name="branch_th" id="f_branch_th" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('account_type') ?></label>
                <select name="account_type" id="f_account_type" class="form-select">
                    <option value="CURRENT">CURRENT</option>
                    <option value="SAVING">SAVING</option>
                    <option value="FIXED">FIXED</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('currency') ?></label>
                <select name="currency_code" id="f_currency_code" class="form-select">
                    <?php foreach ($currencies as $c): ?>
                        <option value="<?= e($c['currency_code']) ?>" <?= $c['currency_code'] === 'THB' ? 'selected' : '' ?>>
                            <?= e($c['currency_code']) ?> — <?= e($c['currency_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('account_no') ?> *</label>
                <input type="text" name="account_no" id="f_account_no" class="form-input" required placeholder="123-4-56789-0">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('account_name') ?> *</label>
                <input type="text" name="account_name" id="f_account_name" class="form-input" required placeholder="Tomas Tech Co., Ltd.">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('swift_code') ?></label>
                <input type="text" name="swift_code" id="f_swift_code" class="form-input" placeholder="BKKBTHBK">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('sort_order') ?></label>
                <input type="number" name="sort_order" id="f_sort_order" class="form-input" value="10">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label"><?= _e('notes') ?></label>
                <textarea name="notes" id="f_notes" class="form-input" rows="2"></textarea>
            </div>
            <div class="form-group" style="display:flex;gap:18px;">
                <label style="font-size:13px;display:flex;align-items:center;gap:4px;">
                    <input type="checkbox" name="is_default" id="f_is_default" value="1">
                    <?= __('default_account') ?>
                </label>
                <label style="font-size:13px;display:flex;align-items:center;gap:4px;">
                    <input type="checkbox" name="is_active" id="f_is_active" value="1" checked>
                    <?= __('active') ?>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="closeCBAModal()"><?= _e('cancel') ?></button>
            <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
        </div>
    </form>
</div>
</div>

<script>
function openCBAModal() { document.getElementById('cbaModal').classList.add('active'); }
function closeCBAModal() { document.getElementById('cbaModal').classList.remove('active'); }
function editCBA(a) {
    document.getElementById('cbaModalTitle').textContent = '<?= __('edit_bank_account') ?>';
    ['cba_id','bank_name','bank_name_th','branch','branch_th','account_type',
     'account_no','account_name','currency_code','swift_code','sort_order','notes']
       .forEach(function(k){ var el = document.getElementById('f_'+k); if (el) el.value = a[k] || ''; });
    document.getElementById('f_is_default').checked = a.is_default === true || a.is_default === 't';
    document.getElementById('f_is_active').checked  = a.is_active  === true || a.is_active  === 't';
    openCBAModal();
}
document.querySelector('[onclick="openCBAModal()"]').addEventListener('click', function(){
    document.getElementById('cbaModalTitle').textContent = '<?= __('new_bank_account') ?>';
    document.getElementById('cbaForm').reset();
    document.getElementById('f_cba_id').value = '';
    document.getElementById('f_is_active').checked = true;
});
</script>
