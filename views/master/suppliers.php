<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('suppliers') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">&#9656;</span>
            <span class="breadcrumb-current"><?= _e('suppliers') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-outline" onclick="translateAllSuppliers()" id="btnTranslateAll" title="<?= __('auto_translate') ?>">&#127760; <?= _e('translate_all') ?></button>
        <button class="btn btn-primary" onclick="openSupplierModal()"><?= _e('new_supplier') ?></button>
    </div>
</div>

<!-- Supplier Table -->
<div class="table-wrapper">
    <div class="table-toolbar">
        <div class="table-search">
            <span class="search-icon">&#128269;</span>
            <input type="text" id="supplierSearch" placeholder="<?= _e('search_supplier') ?>" onkeyup="filterSupplierTable()">
        </div>
    </div>
    <table class="data-table" id="supplierTable">
        <thead>
            <tr>
                <th><?= _e('code') ?></th>
                <th><?= _e('name') ?></th>
                <th><?= _e('country') ?></th>
                <th><?= _e('tax_id') ?></th>
                <th><?= _e('contact_person') ?></th>
                <th><?= _e('email') ?></th>
                <th class="text-right"><?= _e('wht') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($suppliers)): ?>
                <?php foreach ($suppliers as $sup): ?>
                    <tr>
                        <td><strong><?= e($sup['supplier_code'] ?? '') ?></strong></td>
                        <td><?= e($sup['supplier_name']) ?></td>
                        <td><?= e($sup['country'] ?? '') ?></td>
                        <td><?= e($sup['tax_id'] ?? '') ?></td>
                        <td><?= e($sup['contact_person'] ?? '') ?></td>
                        <td><?= e($sup['email'] ?? '') ?></td>
                        <td class="text-right"><?= number_format((float)($sup['wht_rate'] ?? 0), 2) ?>%</td>
                        <td class="text-center actions">
                            <button title="<?= __('edit') ?>" onclick="editSupplier(<?= e(json_encode($sup)) ?>)">&#9998;</button>
                            <button title="<?= __('delete') ?>" onclick="deleteSupplier('<?= e($sup['supplier_id'] ?? '') ?>')">&#128465;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center" style="color:#9E9E9E;padding:24px;"><?= _e('no_suppliers_found') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Supplier Modal -->
<div class="modal-overlay" id="supplierModal">
    <div class="modal" style="width:780px;max-height:90vh;display:flex;flex-direction:column;">
        <div class="modal-header" style="flex-shrink:0;">
            <div class="modal-title" id="supplierModalTitle"><?= __('new_supplier') ?></div>
            <button class="modal-close" onclick="closeSupplierModal()">&times;</button>
        </div>
        <form method="POST" action="/master/suppliers" id="supplierForm" enctype="multipart/form-data" style="display:flex;flex-direction:column;flex:1;min-height:0;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="supplier_id" id="supplier_id" value="">
            <div class="modal-body" style="flex:1;overflow-y:auto;">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label"><?= __('code') ?></label>
                        <input type="text" name="supplier_code" id="supplier_code" class="form-input" maxlength="20" placeholder="Auto: SUP-NNNN">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('country') ?></label>
                        <select name="country" id="sup_country" class="form-select">
                            <option value="">-- <?= __('select') ?> --</option>
                            <option value="TH">TH - Thailand</option>
                            <option value="JP">JP - Japan</option>
                            <option value="US">US - United States</option>
                            <option value="CN">CN - China</option>
                            <option value="DE">DE - Germany</option>
                            <option value="SG">SG - Singapore</option>
                            <option value="GB">GB - United Kingdom</option>
                            <option value="KR">KR - South Korea</option>
                            <option value="TW">TW - Taiwan</option>
                            <option value="MY">MY - Malaysia</option>
                            <option value="VN">VN - Vietnam</option>
                            <option value="IN">IN - India</option>
                            <option value="AU">AU - Australia</option>
                            <option value="FR">FR - France</option>
                            <option value="IT">IT - Italy</option>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label"><?= __('supplier_name') ?> (EN) <span class="required">*</span></label>
                        <input type="text" name="supplier_name" id="supplier_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('supplier_name_jp') ?></label>
                        <div style="display:flex;gap:4px;">
                            <input type="text" name="supplier_name_jp" id="supplier_name_jp" class="form-input" style="flex:1;">
                            <button type="button" class="btn btn-sm" onclick="translateSupplierName('jp')" title="<?= __('auto_translate') ?>" style="white-space:nowrap;padding:6px 8px;font-size:12px;">&#127760; JP</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('supplier_name_th') ?></label>
                        <div style="display:flex;gap:4px;">
                            <input type="text" name="supplier_name_th" id="supplier_name_th" class="form-input" style="flex:1;">
                            <button type="button" class="btn btn-sm" onclick="translateSupplierName('th')" title="<?= __('auto_translate') ?>" style="white-space:nowrap;padding:6px 8px;font-size:12px;">&#127760; TH</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('tax_id') ?></label>
                        <input type="text" name="tax_id" id="sup_tax_id" class="form-input" maxlength="20">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('payment_terms_days') ?></label>
                        <input type="number" name="payment_terms" id="sup_payment_terms" class="form-input" min="0" value="30">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label"><?= __('address') ?></label>
                        <textarea name="address" id="sup_address" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('contact_person') ?></label>
                        <input type="text" name="contact_person" id="sup_contact_person" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('phone') ?></label>
                        <input type="text" name="phone" id="sup_phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('email') ?></label>
                        <input type="email" name="email" id="sup_email" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('wht_rate') ?></label>
                        <input type="number" name="wht_rate" id="sup_wht_rate" class="form-input" step="0.01" min="0" max="100" value="3.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('currency') ?></label>
                        <select name="currency_code" id="sup_currency_code" class="form-select">
                            <option value="THB">THB - Thai Baht</option>
                            <option value="JPY">JPY - Japanese Yen</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="CNY">CNY - Chinese Yuan</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="SGD">SGD - Singapore Dollar</option>
                            <option value="KRW">KRW - Korean Won</option>
                            <option value="TWD">TWD - Taiwan Dollar</option>
                            <option value="MYR">MYR - Malaysian Ringgit</option>
                            <option value="AUD">AUD - Australian Dollar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('division') ?></label>
                        <select name="division_id" id="sup_division_id" class="form-select">
                            <option value="">-- <?= __('select') ?> --</option>
                            <?php if (!empty($divisionsList)): ?>
                                <?php foreach ($divisionsList as $dv): ?>
                                    <option value="<?= e($dv['division_id']) ?>"><?= e($dv['division_code'] . ' - ' . $dv['division_name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Supplier credit / business documents -->
                <hr style="margin:18px 0 12px;">
                <h4 style="margin:0 0 6px 0;color:#1976D2;">📑 <?= __('supplier_docs_required') ?></h4>
                <p style="font-size:12px;color:#666;margin:0 0 8px 0;">
                    <?= __('supplier_docs_hint') ?>
                </p>
                <table class="data-table" id="supDocsTable" style="margin-bottom:6px;">
                    <thead>
                        <tr>
                            <th style="width:180px;"><?= __('doc_type') ?></th>
                            <th><?= __('file') ?></th>
                            <th><?= __('description') ?></th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="supDocsBody"></tbody>
                </table>
                <button type="button" class="btn btn-cancel btn-sm" onclick="addSupDocRow()">+ <?= __('add_attachment') ?></button>

                <!-- Existing attachments list (populated by edit) -->
                <div id="supExistingDocs" style="margin-top:12px;display:none;">
                    <h5 style="margin:0 0 4px 0;color:#555;"><?= __('existing_attachments') ?? 'Existing files' ?>:</h5>
                    <ul id="supExistingDocsList" style="font-size:12px;margin:0;padding-left:20px;"></ul>
                </div>
            </div>
            <div class="modal-footer" style="flex-shrink:0;border-top:1px solid #e0e0e0;background:#f8f9fa;padding:12px 20px;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" class="btn btn-cancel" onclick="closeSupplierModal()"><?= __('cancel') ?></button>
                <button type="submit" class="btn btn-primary" style="min-width:120px;font-size:14px;padding:10px 24px;">
                    &#128190; <?= __('save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSupplierModal() {
    document.getElementById('supplierModalTitle').textContent = '<?= __('new_supplier') ?>';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplier_id').value = '';
    document.getElementById('sup_currency_code').value = 'THB';
    document.getElementById('supplierModal').classList.add('active');
    // Pre-fill next available code
    fetch('/api/master/next-code?type=supplier')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code && !document.getElementById('supplier_id').value) {
                document.getElementById('supplier_code').value = data.code;
            }
        })
        .catch(function() {});
}

function closeSupplierModal() {
    document.getElementById('supplierModal').classList.remove('active');
}

function editSupplier(data) {
    document.getElementById('supplierModalTitle').textContent = '<?= __('edit_supplier') ?>';
    document.getElementById('supplier_id').value = data.supplier_id || '';
    document.getElementById('supplier_code').value = data.supplier_code || '';
    document.getElementById('supplier_name').value = data.supplier_name || '';
    document.getElementById('supplier_name_jp').value = data.supplier_name_jp || '';
    document.getElementById('supplier_name_th').value = data.supplier_name_th || '';
    document.getElementById('sup_country').value = data.country || '';
    document.getElementById('sup_tax_id').value = data.tax_id || '';
    document.getElementById('sup_payment_terms').value = data.payment_terms || 30;
    document.getElementById('sup_address').value = data.address || '';
    document.getElementById('sup_contact_person').value = data.contact_person || '';
    document.getElementById('sup_phone').value = data.phone || '';
    document.getElementById('sup_email').value = data.email || '';
    document.getElementById('sup_wht_rate').value = data.wht_rate || 3;
    document.getElementById('sup_currency_code').value = data.currency_code || 'THB';
    document.getElementById('sup_division_id').value = data.division_id || '';
    document.getElementById('supplierModal').classList.add('active');
}

function deleteSupplier(id) {
    if (confirm('<?= __('confirm_delete') ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/master/suppliers/' + id + '/delete';
        form.innerHTML = '<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">';
        document.body.appendChild(form);
        form.submit();
    }
}

function filterSupplierTable() {
    var input = document.getElementById('supplierSearch');
    var filter = input.value.toUpperCase();
    var rows = document.getElementById('supplierTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var text = rows[i].textContent || rows[i].innerText;
        rows[i].style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

// Close modal on overlay click
document.getElementById('supplierModal').addEventListener('click', function(e) {
    if (e.target === this) closeSupplierModal();
});

// ── Auto-translate supplier name ──
function translateSupplierName(targetLang) {
    // Determine source: use EN name as source, detect source language
    var enName = document.getElementById('supplier_name').value.trim();
    var thName = document.getElementById('supplier_name_th').value.trim();
    var jpName = document.getElementById('supplier_name_jp').value.trim();

    var sourceText = '';
    var sourceLang = '';
    // Priority: EN -> TH -> JP as source
    if (enName) { sourceText = enName; sourceLang = 'en'; }
    else if (thName) { sourceText = thName; sourceLang = 'th'; }
    else if (jpName) { sourceText = jpName; sourceLang = 'ja'; }

    if (!sourceText) { alert('<?= __('enter_name_first') ?>'); return; }

    var tl = (targetLang === 'jp') ? 'ja' : targetLang;
    if (sourceLang === tl) {
        // Pick another source
        if (thName && tl !== 'th') { sourceText = thName; sourceLang = 'th'; }
        else if (enName && tl !== 'en') { sourceText = enName; sourceLang = 'en'; }
        else { alert('<?= __('enter_name_first') ?>'); return; }
    }

    var targetField = (targetLang === 'jp') ? 'supplier_name_jp' : 'supplier_name_th';

    fetch('/api/translate?text=' + encodeURIComponent(sourceText) + '&from=' + sourceLang + '&to=' + tl)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.translated) {
                document.getElementById(targetField).value = data.translated;
            } else {
                alert(data.error || 'Translation failed');
            }
        })
        .catch(function(err) { alert('Translation error: ' + err.message); });
}

// ── Batch translate all suppliers ──
function translateAllSuppliers() {
    var btn = document.getElementById('btnTranslateAll');
    btn.disabled = true;
    btn.textContent = '<?= __('translating') ?>';

    fetch('/api/translate-batch?type=supplier')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '&#127760; <?= __('translate_all') ?>';
            if (data.success) {
                alert('<?= __('translate_complete') ?>'.replace('%d', data.updated));
                if (data.updated > 0) location.reload();
            } else {
                alert(data.error || 'Translation failed');
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = '&#127760; <?= __('translate_all') ?>';
            alert('Error: ' + err.message);
        });
}

// === Supplier attachments (credit/business docs) ===
const SUP_DOC_TYPES = [
    ['COMMERCIAL_REGISTRATION', '<?= __('doc_commercial_registration') ?>'],
    ['TAX_CERTIFICATE',         '<?= __('doc_tax_certificate') ?>'],
    ['BANK_BOOK',               '<?= __('doc_bank_book') ?>'],
    ['CREDIT_REPORT',           '<?= __('doc_credit_report') ?>'],
    ['FINANCIAL_STATEMENT',     '<?= __('doc_financial_statement') ?>'],
    ['NDA',                     '<?= __('doc_nda') ?>'],
    ['OTHER',                   '<?= __('other') ?>'],
];
function addSupDocRow(){
    const tb = document.getElementById('supDocsBody');
    const tr = document.createElement('tr');
    const opts = SUP_DOC_TYPES.map(([v,l]) => `<option value="${v}">${l}</option>`).join('');
    tr.innerHTML = `
      <td><select name="supplier_attachment_doc_types[]" class="form-select">${opts}</select></td>
      <td><input type="file" name="supplier_attachments[]" class="form-input"
                accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx"></td>
      <td><input type="text" name="supplier_attachment_descriptions[]" class="form-input"
                placeholder="<?= __('description') ?>"></td>
      <td><button type="button" class="btn btn-sm" onclick="this.closest('tr').remove()">×</button></td>`;
    tb.appendChild(tr);
}
// Add one row by default when modal opens
document.addEventListener('DOMContentLoaded', function(){
    if (document.getElementById('supDocsBody') && document.getElementById('supDocsBody').children.length===0) {
        addSupDocRow();
    }
});
</script>
