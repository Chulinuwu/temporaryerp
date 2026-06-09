<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('customers') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">&#9656;</span>
            <span class="breadcrumb-current"><?= _e('customers') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-outline" onclick="translateAllCustomers()" id="btnTranslateAllCust" title="<?= __('auto_translate') ?>">&#127760; <?= _e('translate_all') ?></button>
        <button class="btn btn-primary" onclick="openCustomerModal()"><?= _e('new_customer') ?></button>
    </div>
</div>

<!-- Customer Table -->
<div class="table-wrapper">
    <div class="table-toolbar">
        <div class="table-search">
            <span class="search-icon">&#128269;</span>
            <input type="text" id="customerSearch" placeholder="<?= _e('search_customer') ?>" onkeyup="filterCustomerTable()">
        </div>
    </div>
    <table class="data-table" id="customerTable">
        <thead>
            <tr>
                <th><?= _e('code') ?></th>
                <th><?= _e('name') ?></th>
                <th><?= _e('country') ?></th>
                <th><?= _e('tax_id') ?></th>
                <th><?= _e('contact_person') ?></th>
                <th><?= _e('email') ?></th>
                <th><?= _e('payment_terms') ?></th>
                <th class="text-right"><?= _e('credit_limit') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($customers)): ?>
                <?php foreach ($customers as $cust): ?>
                    <tr>
                        <td><strong><?= e($cust['customer_code'] ?? '') ?></strong></td>
                        <td><?= e($cust['customer_name']) ?></td>
                        <td><?= e($cust['country'] ?? '') ?></td>
                        <td><?= e($cust['tax_id'] ?? '') ?></td>
                        <td><?= e($cust['contact_person'] ?? '') ?></td>
                        <td><?= e($cust['email'] ?? '') ?></td>
                        <td><?= (int)($cust['payment_terms'] ?? 30) ?> days</td>
                        <td class="text-right"><?= formatMoney($cust['credit_limit'] ?? 0) ?></td>
                        <td class="text-center actions">
                            <button title="Edit" onclick="editCustomer(<?= e(json_encode($cust)) ?>)">&#9998;</button>
                            <button title="Delete" onclick="deleteCustomer('<?= e($cust['customer_id'] ?? '') ?>')">&#128465;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center" style="color:#9E9E9E;padding:24px;"><?= _e('no_customers_found') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Customer Modal -->
<div class="modal-overlay" id="customerModal">
    <div class="modal" style="width:780px;max-height:90vh;display:flex;flex-direction:column;">
        <div class="modal-header" style="flex-shrink:0;">
            <div class="modal-title" id="customerModalTitle"><?= __('new_customer') ?></div>
            <button class="modal-close" onclick="closeCustomerModal()">&times;</button>
        </div>
        <form method="POST" action="/master/customers" id="customerForm" style="display:flex;flex-direction:column;flex:1;min-height:0;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="customer_id" id="customer_id" value="">
            <div class="modal-body" style="flex:1;overflow-y:auto;">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label"><?= __('code') ?></label>
                        <input type="text" name="customer_code" id="customer_code" class="form-input" maxlength="20" placeholder="Auto: CUS-NNNN">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('country') ?></label>
                        <select name="country" id="cust_country" class="form-select">
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
                        <label class="form-label"><?= __('customer_name') ?> (EN) <span class="required">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('customer_name_jp') ?></label>
                        <div style="display:flex;gap:4px;">
                            <input type="text" name="customer_name_jp" id="customer_name_jp" class="form-input" style="flex:1;">
                            <button type="button" class="btn btn-sm" onclick="translateCustomerName('jp')" title="<?= __('auto_translate') ?>" style="white-space:nowrap;padding:6px 8px;font-size:12px;">&#127760; JP</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('customer_name_th') ?></label>
                        <div style="display:flex;gap:4px;">
                            <input type="text" name="customer_name_th" id="customer_name_th" class="form-input" style="flex:1;">
                            <button type="button" class="btn btn-sm" onclick="translateCustomerName('th')" title="<?= __('auto_translate') ?>" style="white-space:nowrap;padding:6px 8px;font-size:12px;">&#127760; TH</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('tax_id') ?></label>
                        <input type="text" name="tax_id" id="cust_tax_id" class="form-input" maxlength="20">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('sales_rep') ?></label>
                        <select name="sales_rep_id" id="cust_sales_rep_id" class="form-select">
                            <option value="">-- <?= __('select') ?> --</option>
                            <?php foreach (($salesReps ?? []) as $sr): ?>
                                <option value="<?= (int)$sr['employee_id'] ?>">
                                    <?= e($sr['emp_code']) ?> — <?= e($sr['display_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label"><?= __('address') ?></label>
                        <textarea name="address" id="cust_address" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('contact_person') ?></label>
                        <input type="text" name="contact_person" id="cust_contact_person" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('phone') ?></label>
                        <input type="text" name="phone" id="cust_phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('email') ?></label>
                        <input type="email" name="email" id="cust_email" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('payment_terms_days') ?></label>
                        <input type="number" name="payment_terms" id="cust_payment_terms" class="form-input" min="0" value="30">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('credit_limit') ?></label>
                        <input type="number" name="credit_limit" id="cust_credit_limit" class="form-input" step="0.01" min="0" value="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('currency') ?></label>
                        <select name="currency_code" id="cust_currency_code" class="form-select">
                            <option value="THB">THB - Thai Baht</option>
                            <option value="JPY">JPY - Japanese Yen</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="CNY">CNY - Chinese Yuan</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="SGD">SGD - Singapore Dollar</option>
                            <option value="KRW">KRW - Korean Won</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('division') ?></label>
                        <select name="division_id" id="cust_division_id" class="form-select">
                            <option value="">-- <?= __('select') ?> --</option>
                            <?php if (!empty($divisionsList)): ?>
                                <?php foreach ($divisionsList as $dv): ?>
                                    <option value="<?= e($dv['division_id']) ?>"><?= e($dv['division_code'] . ' - ' . $dv['division_name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="flex-shrink:0;border-top:1px solid #e0e0e0;background:#f8f9fa;padding:12px 20px;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" class="btn btn-cancel" onclick="closeCustomerModal()"><?= __('cancel') ?></button>
                <button type="submit" class="btn btn-primary" style="min-width:120px;font-size:14px;padding:10px 24px;">
                    &#128190; <?= __('save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCustomerModal() {
    document.getElementById('customerModalTitle').textContent = '<?= __('new_customer') ?>';
    document.getElementById('customerForm').reset();
    document.getElementById('customer_id').value = '';
    document.getElementById('cust_currency_code').value = 'THB';
    document.getElementById('customerModal').classList.add('active');
    // Pre-fill next available code
    fetch('/api/master/next-code?type=customer')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code && !document.getElementById('customer_id').value) {
                document.getElementById('customer_code').value = data.code;
            }
        })
        .catch(function() {});
}

function closeCustomerModal() {
    document.getElementById('customerModal').classList.remove('active');
}

function editCustomer(data) {
    document.getElementById('customerModalTitle').textContent = '<?= __('edit_customer') ?>';
    document.getElementById('customer_id').value = data.customer_id || '';
    document.getElementById('customer_code').value = data.customer_code || '';
    document.getElementById('customer_name').value = data.customer_name || '';
    document.getElementById('customer_name_jp').value = data.customer_name_jp || '';
    document.getElementById('customer_name_th').value = data.customer_name_th || '';
    document.getElementById('cust_country').value = data.country || '';
    document.getElementById('cust_tax_id').value = data.tax_id || '';
    document.getElementById('cust_sales_rep_id').value = data.sales_rep_id || '';
    document.getElementById('cust_address').value = data.address || '';
    document.getElementById('cust_contact_person').value = data.contact_person || '';
    document.getElementById('cust_phone').value = data.phone || '';
    document.getElementById('cust_email').value = data.email || '';
    document.getElementById('cust_payment_terms').value = data.payment_terms || 30;
    document.getElementById('cust_credit_limit').value = data.credit_limit || 0;
    document.getElementById('cust_currency_code').value = data.currency_code || '';
    document.getElementById('cust_division_id').value = data.division_id || '';
    document.getElementById('customerModal').classList.add('active');
}

function deleteCustomer(id) {
    if (confirm('<?= __('confirm_delete') ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/master/customers/delete';
        form.innerHTML = '<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">' +
            '<input type="hidden" name="customer_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function filterCustomerTable() {
    var input = document.getElementById('customerSearch');
    var filter = input.value.toUpperCase();
    var rows = document.getElementById('customerTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var text = rows[i].textContent || rows[i].innerText;
        rows[i].style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

// Close modal on overlay click
document.getElementById('customerModal').addEventListener('click', function(e) {
    if (e.target === this) closeCustomerModal();
});

// ── Auto-translate customer name ──
function translateCustomerName(targetLang) {
    var enName = document.getElementById('customer_name').value.trim();
    var thName = document.getElementById('customer_name_th').value.trim();
    var jpName = document.getElementById('customer_name_jp').value.trim();

    var sourceText = '';
    var sourceLang = '';
    if (enName) { sourceText = enName; sourceLang = 'en'; }
    else if (thName) { sourceText = thName; sourceLang = 'th'; }
    else if (jpName) { sourceText = jpName; sourceLang = 'ja'; }

    if (!sourceText) { alert('<?= __('enter_name_first') ?>'); return; }

    var tl = (targetLang === 'jp') ? 'ja' : targetLang;
    if (sourceLang === tl) {
        if (thName && tl !== 'th') { sourceText = thName; sourceLang = 'th'; }
        else if (enName && tl !== 'en') { sourceText = enName; sourceLang = 'en'; }
        else { alert('<?= __('enter_name_first') ?>'); return; }
    }

    var targetField = (targetLang === 'jp') ? 'customer_name_jp' : 'customer_name_th';

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

// ── Batch translate all customers ──
function translateAllCustomers() {
    var btn = document.getElementById('btnTranslateAllCust');
    btn.disabled = true;
    btn.textContent = '<?= __('translating') ?>';

    fetch('/api/translate-batch?type=customer')
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
</script>
