<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('chart_of_accounts') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">&#9656;</span>
            <span class="breadcrumb-current"><?= _e('chart_of_accounts') ?></span>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openAccountModal()"><?= _e('new_account') ?></button>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:14px 20px;">
    <form method="GET" action="/master/accounts" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="account_type" class="form-select" style="width:180px;" onchange="this.form.submit()">
                <option value="ALL" <?= ($filters['account_type'] ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>All Types</option>
                <option value="ASSET" <?= ($filters['account_type'] ?? '') === 'ASSET' ? 'selected' : '' ?>>Asset</option>
                <option value="LIABILITY" <?= ($filters['account_type'] ?? '') === 'LIABILITY' ? 'selected' : '' ?>>Liability</option>
                <option value="EQUITY" <?= ($filters['account_type'] ?? '') === 'EQUITY' ? 'selected' : '' ?>>Equity</option>
                <option value="REVENUE" <?= ($filters['account_type'] ?? '') === 'REVENUE' ? 'selected' : '' ?>>Revenue</option>
                <option value="COGS" <?= ($filters['account_type'] ?? '') === 'COGS' ? 'selected' : '' ?>>COGS</option>
                <option value="EXPENSE" <?= ($filters['account_type'] ?? '') === 'EXPENSE' ? 'selected' : '' ?>>Expense</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <select name="bs_pl" class="form-select" style="width:120px;" onchange="this.form.submit()">
                <option value="ALL" <?= ($filters['bs_pl'] ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>BS / PL</option>
                <option value="BS" <?= ($filters['bs_pl'] ?? '') === 'BS' ? 'selected' : '' ?>>BS</option>
                <option value="PL" <?= ($filters['bs_pl'] ?? '') === 'PL' ? 'selected' : '' ?>>PL</option>
            </select>
        </div>
        <div class="table-search" style="flex:1;max-width:280px;">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search accounts..." style="width:100%;">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
    </form>
</div>

<!-- Accounts Table -->
<div class="table-wrapper">
    <table class="data-table" id="accountsTable">
        <thead>
            <tr>
                <th><?= _e('code') ?></th>
                <th><?= _e('name') ?></th>
                <th><?= _e('name_jp') ?></th>
                <th><?= _e('name_th') ?></th>
                <th><?= _e('type') ?></th>
                <th class="text-center"><?= _e('bs_pl') ?></th>
                <th><?= _e('tax_form') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($accounts)): ?>
                <?php
                $typeColors = [
                    'ASSET' => '#E3F2FD',
                    'LIABILITY' => '#FFF3E0',
                    'EQUITY' => '#E8F5E9',
                    'REVENUE' => '#F3E5F5',
                    'COGS' => '#FFEBEE',
                    'EXPENSE' => '#F5F5F5',
                ];
                $typeBadgeColors = [
                    'ASSET' => 'badge-open',
                    'LIABILITY' => 'badge-pending',
                    'EQUITY' => 'badge-approved',
                    'REVENUE' => '',
                    'COGS' => 'badge-rejected',
                    'EXPENSE' => 'badge-draft',
                ];
                ?>
                <?php foreach ($accounts as $acct): ?>
                    <?php $acctType = $acct['account_type'] ?? ''; ?>
                    <tr style="background:<?= $typeColors[$acctType] ?? 'transparent' ?>;">
                        <td><strong><?= e($acct['account_code'] ?? '') ?></strong></td>
                        <td><?= e($acct['account_name'] ?? '') ?></td>
                        <td><?= e($acct['account_name_jp'] ?? '') ?></td>
                        <td><?= e($acct['account_name_th'] ?? '') ?></td>
                        <td>
                            <span class="badge <?= $typeBadgeColors[$acctType] ?? 'badge-draft' ?>">
                                <?= e($acctType) ?>
                            </span>
                        </td>
                        <td class="text-center"><?= e($acct['bs_pl'] ?? '') ?></td>
                        <td><?= e($acct['tax_form'] ?? '') ?></td>
                        <td class="text-center">
                            <?php if ($acct['is_current'] ?? true): ?>
                                <span class="badge badge-approved"><?= _e('status_current') ?></span>
                            <?php else: ?>
                                <span class="badge badge-rejected">Superseded</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center actions" style="white-space:nowrap;">
                            <button title="<?= _e('edit') ?>" onclick="editAccount(<?= e(json_encode($acct)) ?>)" style="background:none;border:none;cursor:pointer;">&#9998;</button>
                            <button title="<?= _e('delete') ?>" onclick="deleteAccount('<?= e($acct['account_id'] ?? '') ?>')" style="background:none;border:none;cursor:pointer;">&#128465;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center" style="color:#9E9E9E;padding:24px;"><?= _e('no_accounts_found') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Account Modal -->
<div class="modal-overlay" id="accountModal">
    <div class="modal" style="width:780px;">
        <div class="modal-header">
            <div class="modal-title" id="accountModalTitle"><?= _e('new_account') ?></div>
            <button class="modal-close" onclick="closeAccountModal()">&times;</button>
        </div>
        <form method="POST" action="/master/accounts" id="accountForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="account_id" id="acct_account_id" value="">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label"><?= _e('account_code') ?> <span class="required">*</span></label>
                        <input type="text" name="account_code" id="acct_account_code" class="form-input" required maxlength="20">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('type') ?> <span class="required">*</span></label>
                        <select name="account_type" id="acct_account_type" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="ASSET">ASSET</option>
                            <option value="LIABILITY">LIABILITY</option>
                            <option value="EQUITY">EQUITY</option>
                            <option value="REVENUE">REVENUE</option>
                            <option value="COGS">COGS</option>
                            <option value="EXPENSE">EXPENSE</option>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label"><?= _e('account_name') ?> <span class="required">*</span></label>
                        <input type="text" name="account_name" id="acct_account_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('name_jp') ?></label>
                        <input type="text" name="account_name_jp" id="acct_account_name_jp" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('name_th') ?></label>
                        <input type="text" name="account_name_th" id="acct_account_name_th" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('bs_pl') ?></label>
                        <select name="bs_pl" id="acct_bs_pl" class="form-select">
                            <option value="BS">BS</option>
                            <option value="PL">PL</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= _e('tax_form') ?></label>
                        <input type="text" name="tax_form" id="acct_tax_form" class="form-input">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeAccountModal()"><?= _e('cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openAccountModal() {
    document.getElementById('accountModalTitle').textContent = '<?= __('new_account') ?>';
    document.getElementById('accountForm').reset();
    document.getElementById('acct_account_id').value = '';
    document.getElementById('accountModal').classList.add('active');
}
function closeAccountModal() {
    document.getElementById('accountModal').classList.remove('active');
}
function editAccount(data) {
    document.getElementById('accountModalTitle').textContent = '<?= __('edit') ?>';
    document.getElementById('acct_account_id').value = data.account_id || '';
    document.getElementById('acct_account_code').value = data.account_code || '';
    document.getElementById('acct_account_name').value = data.account_name || '';
    document.getElementById('acct_account_name_jp').value = data.account_name_jp || '';
    document.getElementById('acct_account_name_th').value = data.account_name_th || '';
    document.getElementById('acct_account_type').value = data.account_type || '';
    document.getElementById('acct_bs_pl').value = data.bs_pl || 'BS';
    document.getElementById('acct_tax_form').value = data.tax_form || '';
    document.getElementById('accountModal').classList.add('active');
}
function deleteAccount(id) {
    if (confirm('<?= __('confirm_delete') ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/master/accounts/delete';
        form.innerHTML = '<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">' +
            '<input type="hidden" name="account_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
document.getElementById('accountModal').addEventListener('click', function(e) {
    if (e.target === this) closeAccountModal();
});
</script>
