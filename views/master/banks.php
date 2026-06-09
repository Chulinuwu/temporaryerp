<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">銀行マスタ / Banks</h1>
        <div class="breadcrumb">
            <a href="/dashboard">ホーム</a>
            <span class="breadcrumb-separator">&#9656;</span>
            <span class="breadcrumb-current">銀行</span>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openBankModal()">＋ 新規銀行</button>
</div>

<!-- Filter -->
<div class="card" style="padding:14px 20px;margin-bottom:16px;">
    <form method="GET" action="/master/banks" style="display:flex;gap:10px;align-items:center;">
        <input type="text" name="search" class="form-input"
               placeholder="銀行コード / 銀行名 / SWIFT で検索"
               value="<?= e($search ?? '') ?>"
               style="flex:1;max-width:400px;">
        <button type="submit" class="btn btn-primary">絞り込み</button>
        <?php if (!empty($search)): ?>
            <a href="/master/banks" class="btn btn-cancel">クリア</a>
        <?php endif; ?>
    </form>
</div>

<!-- Banks Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:120px;">コード</th>
                <th>銀行名 (EN)</th>
                <th>銀行名 (TH)</th>
                <th style="width:160px;">SWIFT コード</th>
                <th class="text-center" style="width:120px;">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($banks)): ?>
                <?php foreach ($banks as $bank): ?>
                    <tr>
                        <td><strong><?= e($bank['bank_code'] ?? '') ?></strong></td>
                        <td><?= e($bank['bank_name'] ?? '') ?></td>
                        <td><?= e($bank['bank_name_th'] ?? '') ?></td>
                        <td><?= e($bank['swift_code'] ?? '') ?></td>
                        <td class="text-center actions">
                            <button title="編集" onclick='editBank(<?= json_encode($bank, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>&#9998;</button>
                            <form method="POST" action="/master/banks/<?= e($bank['bank_id']) ?>/delete" style="display:inline;" onsubmit="return confirm('削除してよろしいですか?');">
                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" title="削除" style="background:none;border:none;cursor:pointer;">&#128465;</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center" style="color:#9E9E9E;padding:24px;">銀行が登録されていません。</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Bank Modal -->
<div class="modal-overlay" id="bankModal">
    <div class="modal" style="width:560px;">
        <div class="modal-header">
            <div class="modal-title" id="bankModalTitle">新規銀行</div>
            <button class="modal-close" onclick="closeBankModal()">&times;</button>
        </div>
        <form method="POST" action="/master/banks" id="bankForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="bank_id" id="bank_id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">銀行コード <span style="color:red;">*</span></label>
                    <input type="text" name="bank_code" id="bank_code" class="form-input" maxlength="10" required>
                </div>
                <div class="form-group">
                    <label class="form-label">銀行名 (EN) <span style="color:red;">*</span></label>
                    <input type="text" name="bank_name" id="bank_name" class="form-input" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">銀行名 (TH)</label>
                    <input type="text" name="bank_name_th" id="bank_name_th" class="form-input" maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">SWIFT コード</label>
                    <input type="text" name="swift_code" id="swift_code" class="form-input" maxlength="20">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeBankModal()">キャンセル</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBankModal() {
    document.getElementById('bankModalTitle').textContent = '新規銀行';
    document.getElementById('bankForm').reset();
    document.getElementById('bank_id').value = '';
    document.getElementById('bankModal').classList.add('show');
}
function closeBankModal() {
    document.getElementById('bankModal').classList.remove('show');
}
function editBank(bank) {
    document.getElementById('bankModalTitle').textContent = '銀行編集';
    document.getElementById('bank_id').value       = bank.bank_id || '';
    document.getElementById('bank_code').value     = bank.bank_code || '';
    document.getElementById('bank_name').value     = bank.bank_name || '';
    document.getElementById('bank_name_th').value  = bank.bank_name_th || '';
    document.getElementById('swift_code').value    = bank.swift_code || '';
    document.getElementById('bankModal').classList.add('show');
}
</script>
