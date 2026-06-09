<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('items') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">&#9656;</span>
            <span class="breadcrumb-current"><?= _e('items') ?></span>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openItemModal()"><?= _e('new_item') ?></button>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:14px 20px;">
    <form method="GET" action="/master/items" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="item_type" class="form-select" style="width:180px;" onchange="this.form.submit()">
                <option value="ALL" <?= ($filters['item_type'] ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>All Types</option>
                <option value="RAW" <?= ($filters['item_type'] ?? '') === 'RAW' ? 'selected' : '' ?>>Raw Material</option>
                <option value="WIP" <?= ($filters['item_type'] ?? '') === 'WIP' ? 'selected' : '' ?>>WIP</option>
                <option value="FINISHED" <?= ($filters['item_type'] ?? '') === 'FINISHED' ? 'selected' : '' ?>>Finished Goods</option>
                <option value="MERCHANDISE" <?= ($filters['item_type'] ?? '') === 'MERCHANDISE' ? 'selected' : '' ?>>Merchandise</option>
                <option value="SERVICE" <?= ($filters['item_type'] ?? '') === 'SERVICE' ? 'selected' : '' ?>>Service</option>
                <option value="SPARE" <?= ($filters['item_type'] ?? '') === 'SPARE' ? 'selected' : '' ?>>Spare Parts</option>
            </select>
        </div>
        <div class="table-search" style="flex:1;max-width:280px;">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search items...">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
    </form>
</div>

<!-- Items Table -->
<div class="table-wrapper">
    <table class="data-table" id="itemsTable">
        <thead>
            <tr>
                <th><?= _e('code') ?></th>
                <th><?= _e('name') ?></th>
                <th><?= _e('type') ?></th>
                <th><?= _e('unit') ?></th>
                <th class="text-right"><?= _e('cost_price') ?></th>
                <th class="text-right"><?= _e('sell_price') ?></th>
                <th class="text-right"><?= _e('safety_stock') ?></th>
                <th class="text-right"><?= _e('lead_time') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= e($item['item_code'] ?? '') ?></strong></td>
                        <td><?= e($item['item_name'] ?? '') ?></td>
                        <td><span class="badge badge-draft"><?= e($item['item_type'] ?? '') ?></span></td>
                        <td><?= e($item['unit'] ?? '') ?></td>
                        <td class="text-right"><?= formatMoney($item['unit_cost_std'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney($item['unit_price_std'] ?? 0) ?></td>
                        <td class="text-right"><?= number_format((float)($item['safety_stock'] ?? 0), 0) ?></td>
                        <td class="text-right"><?= (int)($item['lead_time_days'] ?? 0) ?> days</td>
                        <td class="text-center actions">
                            <button title="Edit" onclick="editItem(<?= e(json_encode($item)) ?>)">&#9998;</button>
                            <button title="Delete" onclick="deleteItem('<?= e($item['item_id'] ?? '') ?>')">&#128465;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center" style="color:#9E9E9E;padding:24px;"><?= _e('no_items_found') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Item Modal -->
<div class="modal-overlay" id="itemModal">
    <div class="modal" style="width:780px;">
        <div class="modal-header">
            <div class="modal-title" id="itemModalTitle">New Item</div>
            <button class="modal-close" onclick="closeItemModal()">&times;</button>
        </div>
        <form method="POST" action="/master/items" id="itemForm">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="item_id" id="item_id" value="">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Item Code</label>
                        <input type="text" name="item_code" id="item_code" class="form-input" maxlength="30" placeholder="Auto: ITM-NNNN">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Item Type <span class="required">*</span></label>
                        <select name="item_type" id="item_type" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="RAW">Raw Material</option>
                            <option value="WIP">WIP</option>
                            <option value="FINISHED">Finished Goods</option>
                            <option value="MERCHANDISE">Merchandise</option>
                            <option value="SERVICE">Service</option>
                            <option value="SPARE">Spare Parts</option>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Item Name <span class="required">*</span></label>
                        <input type="text" name="item_name" id="item_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Item Name (JP)</label>
                        <input type="text" name="item_name_jp" id="item_name_jp" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Item Name (TH)</label>
                        <input type="text" name="item_name_th" id="item_name_th" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" id="unit" class="form-input" placeholder="e.g. PCS, SET, KG">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Code</label>
                        <input type="text" name="tax_code" id="tax_code" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Standard Cost</label>
                        <input type="number" name="unit_cost_std" id="unit_cost_std" class="form-input" step="0.01" min="0" value="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Standard Price</label>
                        <input type="number" name="unit_price_std" id="unit_price_std" class="form-input" step="0.01" min="0" value="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Safety Stock</label>
                        <input type="number" name="safety_stock" id="safety_stock" class="form-input" step="1" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lead Time (days)</label>
                        <input type="number" name="lead_time_days" id="lead_time_days" class="form-input" step="1" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><input type="checkbox" name="lot_managed" id="lot_managed" value="1"> Lot Managed</label>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><input type="checkbox" name="serial_managed" id="serial_managed" value="1"> Serial Managed</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeItemModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openItemModal() {
    document.getElementById('itemModalTitle').textContent = 'New Item';
    document.getElementById('itemForm').reset();
    document.getElementById('item_id').value = '';
    document.getElementById('itemModal').classList.add('active');
    // Pre-fill next available code
    fetch('/api/master/next-code?type=item')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code && !document.getElementById('item_id').value) {
                document.getElementById('item_code').value = data.code;
            }
        })
        .catch(function() {});
}

function closeItemModal() {
    document.getElementById('itemModal').classList.remove('active');
}

function editItem(data) {
    document.getElementById('itemModalTitle').textContent = 'Edit Item';
    document.getElementById('item_id').value = data.item_id || '';
    document.getElementById('item_code').value = data.item_code || '';
    document.getElementById('item_name').value = data.item_name || '';
    document.getElementById('item_name_jp').value = data.item_name_jp || '';
    document.getElementById('item_name_th').value = data.item_name_th || '';
    document.getElementById('item_type').value = data.item_type || '';
    document.getElementById('unit').value = data.unit || '';
    document.getElementById('tax_code').value = data.tax_code || '';
    document.getElementById('unit_cost_std').value = data.unit_cost_std || 0;
    document.getElementById('unit_price_std').value = data.unit_price_std || 0;
    document.getElementById('safety_stock').value = data.safety_stock || 0;
    document.getElementById('lead_time_days').value = data.lead_time_days || 0;
    document.getElementById('lot_managed').checked = !!data.lot_managed;
    document.getElementById('serial_managed').checked = !!data.serial_managed;
    document.getElementById('itemModal').classList.add('active');
}

function deleteItem(id) {
    if (confirm('<?= __('confirm_delete') ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/master/items/delete';
        form.innerHTML = '<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">' +
            '<input type="hidden" name="item_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal on overlay click
document.getElementById('itemModal').addEventListener('click', function(e) {
    if (e.target === this) closeItemModal();
});
</script>
