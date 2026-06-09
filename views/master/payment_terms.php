<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('payment_terms') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">&#9656;</span>
            <span class="breadcrumb-current"><?= _e('payment_terms') ?></span>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openTermModal()"><?= _e('new_payment_term') ?></button>
</div>

<!-- Payment Terms Table -->
<div class="table-wrapper">
    <div class="table-toolbar">
        <div class="table-search">
            <span class="search-icon">&#128269;</span>
            <input type="text" id="termSearch" placeholder="<?= _e('search_payment_terms') ?>" onkeyup="filterTermTable()">
        </div>
    </div>
    <table class="data-table" id="termTable">
        <thead>
            <tr>
                <th><?= _e('code') ?></th>
                <th><?= _e('name_en') ?></th>
                <th><?= _e('name_jp') ?></th>
                <th><?= _e('name_th') ?></th>
                <th class="text-center"><?= _e('installments') ?></th>
                <th class="text-right"><?= _e('due_days') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($paymentTerms)): ?>
                <?php foreach ($paymentTerms as $term): ?>
                    <tr>
                        <td><strong><?= e($term['term_code'] ?? '') ?></strong></td>
                        <td><?= e($term['term_name_en'] ?? '') ?></td>
                        <td><?= e($term['term_name_jp'] ?? '') ?></td>
                        <td><?= e($term['term_name_th'] ?? '') ?></td>
                        <td class="text-center">
                            <span class="badge badge-count"><?= (int)($term['installment_count'] ?? 0) ?></span>
                        </td>
                        <td class="text-right"><?= (int)($term['credit_days'] ?? 0) ?></td>
                        <td class="text-center actions">
                            <button title="Edit" onclick="editTerm(<?= e(json_encode($term)) ?>)">&#9998;</button>
                            <button title="Delete" onclick="deleteTerm('<?= e($term['term_id'] ?? '') ?>')">&#128465;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center" style="color:#9E9E9E;padding:24px;"><?= _e('no_payment_terms_found') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Payment Term Modal -->
<div class="modal-overlay" id="termModal">
    <div class="modal" style="width:860px;">
        <div class="modal-header">
            <div class="modal-title" id="termModalTitle">New Payment Term</div>
            <button class="modal-close" onclick="closeTermModal()">&times;</button>
        </div>
        <form method="POST" action="/master/payment-terms" id="termForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="term_id" id="term_id" value="">
            <div class="modal-body">
                <!-- Header Fields -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Term Code</label>
                        <input type="text" name="term_code" id="term_code" class="form-input" maxlength="20" placeholder="Auto: PMT-NNNN">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credit Days</label>
                        <input type="number" name="credit_days" id="credit_days" class="form-input" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Name (EN) <span class="required">*</span></label>
                        <input type="text" name="term_name_en" id="term_name_en" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Name (JP)</label>
                        <input type="text" name="term_name_jp" id="term_name_jp" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Name (TH)</label>
                        <input type="text" name="term_name_th" id="term_name_th" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" id="term_notes" class="form-input">
                    </div>
                </div>

                <!-- Installment Details -->
                <div style="margin-top:20px;border-top:1px solid #E0E0E0;padding-top:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <h3 style="font-size:14px;font-weight:600;color:#424242;">Installment Details</h3>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addInstallmentRow()">+ Add Installment</button>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="data-table" id="installmentTable">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th style="width:90px;">Pct (%)</th>
                                    <th>Description (EN)</th>
                                    <th>Description (JP)</th>
                                    <th>Description (TH)</th>
                                    <th style="width:160px;">Trigger Type</th>
                                    <th style="width:90px;">Credit Days</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="installmentBody">
                                <!-- Dynamic rows inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeTermModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
var installmentRowIndex = 0;

function openTermModal() {
    document.getElementById('termModalTitle').textContent = 'New Payment Term';
    document.getElementById('termForm').reset();
    document.getElementById('term_id').value = '';
    document.getElementById('installmentBody').innerHTML = '';
    installmentRowIndex = 0;
    document.getElementById('termModal').classList.add('active');
    // Pre-fill next available code
    fetch('/api/master/next-code?type=payment_term')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code && !document.getElementById('term_id').value) {
                document.getElementById('term_code').value = data.code;
            }
        })
        .catch(function() {});
}

function closeTermModal() {
    document.getElementById('termModal').classList.remove('active');
}

function addInstallmentRow(data) {
    data = data || {};
    var tbody = document.getElementById('installmentBody');
    installmentRowIndex++;
    var idx = installmentRowIndex;

    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td class="text-center">' + idx + '</td>' +
        '<td><input type="number" name="installments[' + idx + '][percentage]" class="form-input" style="width:80px;" step="0.01" min="0" max="100" value="' + (data.percentage || '') + '"></td>' +
        '<td><input type="text" name="installments[' + idx + '][description_en]" class="form-input" value="' + escAttr(data.description_en) + '"></td>' +
        '<td><input type="text" name="installments[' + idx + '][description_jp]" class="form-input" value="' + escAttr(data.description_jp) + '"></td>' +
        '<td><input type="text" name="installments[' + idx + '][description_th]" class="form-input" value="' + escAttr(data.description_th) + '"></td>' +
        '<td><select name="installments[' + idx + '][trigger_type]" class="form-select">' +
            '<option value="">-- Select --</option>' +
            '<option value="PO"' + (data.trigger_type === 'PO' ? ' selected' : '') + '>PO</option>' +
            '<option value="DESIGN"' + (data.trigger_type === 'DESIGN' ? ' selected' : '') + '>DESIGN</option>' +
            '<option value="DELIVERY"' + (data.trigger_type === 'DELIVERY' ? ' selected' : '') + '>DELIVERY</option>' +
            '<option value="INSTALLATION"' + (data.trigger_type === 'INSTALLATION' ? ' selected' : '') + '>INSTALLATION</option>' +
            '<option value="COMPLETION"' + (data.trigger_type === 'COMPLETION' ? ' selected' : '') + '>COMPLETION</option>' +
            '<option value="FAT"' + (data.trigger_type === 'FAT' ? ' selected' : '') + '>FAT</option>' +
            '<option value="SAT"' + (data.trigger_type === 'SAT' ? ' selected' : '') + '>SAT</option>' +
            '<option value="INVOICE"' + (data.trigger_type === 'INVOICE' ? ' selected' : '') + '>INVOICE</option>' +
            '<option value="CUSTOM"' + (data.trigger_type === 'CUSTOM' ? ' selected' : '') + '>CUSTOM</option>' +
        '</select></td>' +
        '<td><input type="number" name="installments[' + idx + '][credit_days]" class="form-input" style="width:80px;" min="0" value="' + (data.credit_days || 0) + '"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removeInstallmentRow(this)" style="padding:4px 8px;">&times;</button></td>';

    tbody.appendChild(tr);
    renumberInstallments();
}

function removeInstallmentRow(btn) {
    btn.closest('tr').remove();
    renumberInstallments();
}

function renumberInstallments() {
    var rows = document.getElementById('installmentBody').getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        rows[i].getElementsByTagName('td')[0].textContent = i + 1;
    }
}

function escAttr(val) {
    if (!val) return '';
    var div = document.createElement('div');
    div.textContent = val;
    return div.innerHTML.replace(/"/g, '&quot;');
}

function editTerm(data) {
    document.getElementById('termModalTitle').textContent = 'Edit Payment Term';
    document.getElementById('term_id').value = data.term_id || '';
    document.getElementById('term_code').value = data.term_code || '';
    document.getElementById('term_name_en').value = data.term_name_en || '';
    document.getElementById('term_name_jp').value = data.term_name_jp || '';
    document.getElementById('term_name_th').value = data.term_name_th || '';
    document.getElementById('credit_days').value = data.credit_days || 0;
    document.getElementById('term_notes').value = data.notes || '';

    // Clear and repopulate installments
    document.getElementById('installmentBody').innerHTML = '';
    installmentRowIndex = 0;

    if (data.installments && Array.isArray(data.installments)) {
        data.installments.forEach(function(inst) {
            addInstallmentRow(inst);
        });
    } else {
        // Fallback: load installments via AJAX
        fetch('/master/payment-terms/' + data.term_id + '/edit?json=1')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.installments) {
                    d.installments.forEach(function(inst) { addInstallmentRow(inst); });
                }
            })
            .catch(function() {});
    }

    document.getElementById('termModal').classList.add('active');
}

function deleteTerm(id) {
    if (confirm('<?= __('confirm_delete') ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/master/payment-terms/' + id + '/delete';
        form.innerHTML = '<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">';
        document.body.appendChild(form);
        form.submit();
    }
}

function filterTermTable() {
    var input = document.getElementById('termSearch');
    var filter = input.value.toUpperCase();
    var rows = document.getElementById('termTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var text = rows[i].textContent || rows[i].innerText;
        rows[i].style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

// Close modal on overlay click
document.getElementById('termModal').addEventListener('click', function(e) {
    if (e.target === this) closeTermModal();
});
</script>
