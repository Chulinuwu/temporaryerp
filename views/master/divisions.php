<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('divisions') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">&#9656;</span>
            <span class="breadcrumb-current"><?= _e('divisions') ?></span>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openDivisionModal()"><?= _e('new_division') ?></button>
</div>

<!-- Data Table -->
<div class="table-wrapper">
    <div class="table-toolbar">
        <div class="table-search">
            <span class="search-icon">&#128269;</span>
            <input type="text" id="divisionSearch" placeholder="<?= _e('search_divisions') ?>" onkeyup="filterTable('divisionSearch','divisionTable')">
        </div>
    </div>
    <table class="data-table" id="divisionTable">
        <thead>
            <tr>
                <th><?= _e('code') ?></th>
                <th><?= _e('name') ?></th>
                <th><?= _e('name_jp') ?></th>
                <th><?= _e('type') ?></th>
                <th><?= _e('country') ?></th>
                <th><?= _e('currency') ?></th>
                <th><?= _e('status') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($divisions)): ?>
                <?php foreach ($divisions as $div): ?>
                    <tr>
                        <td><strong><?= e($div['division_code'] ?? '') ?></strong></td>
                        <td><?= e($div['division_name'] ?? '') ?></td>
                        <td><?= e($div['division_name_jp'] ?? '') ?></td>
                        <td>
                            <span class="badge badge-draft"><?= e($div['division_type'] ?? '') ?></span>
                        </td>
                        <td><?= e($div['country_code'] ?? '') ?></td>
                        <td><?= e($div['currency_code'] ?? '') ?></td>
                        <td>
                            <?php if (!($div['is_deleted'] ?? false)): ?>
                                <span class="badge badge-approved"><?= _e('status_current') ?></span>
                            <?php else: ?>
                                <span class="badge badge-rejected"><?= _e('status_closed') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center actions">
                            <button title="Edit" onclick="editDivision(<?= e(json_encode($div)) ?>)">&#9998;</button>
                            <button title="Delete" onclick="deleteDivision('<?= e($div['division_id'] ?? '') ?>')">&#128465;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center" style="color:#9E9E9E;padding:24px;"><?= _e('no_divisions_found') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Division Modal -->
<div class="modal-overlay" id="divisionModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="divisionModalTitle">New Division</div>
            <button class="modal-close" onclick="closeDivisionModal()">&times;</button>
        </div>
        <form method="POST" action="/master/divisions" id="divisionForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="division_id" id="division_id" value="">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Division Code</label>
                        <input type="text" name="division_code" id="division_code" class="form-input" maxlength="20" placeholder="Auto: DIV-NNNN">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Division Type <span class="required">*</span></label>
                        <select name="division_type" id="division_type" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="COMPANY">COMPANY</option>
                            <option value="BRANCH">BRANCH</option>
                            <option value="DEPARTMENT">DEPARTMENT</option>
                            <option value="SECTION">SECTION</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Division Name <span class="required">*</span></label>
                        <input type="text" name="division_name" id="division_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Division Name (JP)</label>
                        <input type="text" name="division_name_jp" id="division_name_jp" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country Code</label>
                        <input type="text" name="country_code" id="country_code" class="form-input" maxlength="3" placeholder="e.g. TH, JP, US">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Currency Code</label>
                        <input type="text" name="currency_code" id="currency_code" class="form-input" maxlength="3" placeholder="e.g. THB, JPY, USD">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Tax ID</label>
                        <input type="text" name="tax_id" id="tax_id" class="form-input" maxlength="20">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeDivisionModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDivisionModal() {
    document.getElementById('divisionModalTitle').textContent = 'New Division';
    document.getElementById('divisionForm').reset();
    document.getElementById('division_id').value = '';
    document.getElementById('divisionModal').classList.add('active');
    // Pre-fill next available code
    fetch('/api/master/next-code?type=division')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code && !document.getElementById('division_id').value) {
                document.getElementById('division_code').value = data.code;
            }
        })
        .catch(function() {});
}

function closeDivisionModal() {
    document.getElementById('divisionModal').classList.remove('active');
}

function editDivision(data) {
    document.getElementById('divisionModalTitle').textContent = 'Edit Division';
    document.getElementById('division_id').value = data.division_id || '';
    document.getElementById('division_code').value = data.division_code || '';
    document.getElementById('division_name').value = data.division_name || '';
    document.getElementById('division_name_jp').value = data.division_name_jp || '';
    document.getElementById('division_type').value = data.division_type || '';
    document.getElementById('country_code').value = data.country_code || '';
    document.getElementById('currency_code').value = data.currency_code || '';
    document.getElementById('tax_id').value = data.tax_id || '';
    document.getElementById('divisionModal').classList.add('active');
}

function deleteDivision(id) {
    if (confirm('<?= __('confirm_delete') ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/master/divisions/delete';
        form.innerHTML = '<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">' +
            '<input type="hidden" name="division_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function filterTable(searchId, tableId) {
    var input = document.getElementById(searchId);
    var filter = input.value.toUpperCase();
    var table = document.getElementById(tableId);
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var text = rows[i].textContent || rows[i].innerText;
        rows[i].style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

// Close modal on overlay click
document.getElementById('divisionModal').addEventListener('click', function(e) {
    if (e.target === this) closeDivisionModal();
});
</script>
