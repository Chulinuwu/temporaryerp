<?php
/**
 * PEGASUS ERP - Warehouse List
 * Variables: $warehouses, $pagination
 */
$pageTitle = 'Warehouses - PEGASUS ERP';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('warehouses') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/inventory"><?= _e('inventory') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('warehouses') ?></span>
        </div>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('warehouseModal').classList.add('active')"><?= _e('new_warehouse') ?></button>
</div>

<!-- Warehouse Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th class="sortable"><?= _e('code') ?></th>
                <th class="sortable"><?= _e('name') ?></th>
                <th><?= _e('address') ?></th>
                <th><?= _e('manager') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-right"><?= _e('total_items') ?></th>
                <th class="text-right"><?= _e('total_value') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($warehouses)): ?>
                <?php foreach ($warehouses as $wh): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($wh['warehouse_code'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($wh['warehouse_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($wh['address'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($wh['manager_name'] ?? '-') ?></td>
                        <td class="text-center">
                            <span class="badge <?= !($wh['is_deleted'] ?? false) ? 'badge-approved' : 'badge-draft' ?>">
                                <?= !($wh['is_deleted'] ?? false) ? _e('status_current') : _e('status_closed') ?>
                            </span>
                        </td>
                        <td class="text-right"><?= number_format($wh['total_items'] ?? 0) ?></td>
                        <td class="text-right"><strong><?= formatMoney($wh['total_value'] ?? 0) ?></strong></td>
                        <td class="text-center actions">
                            <a href="/inventory/warehouses/<?= htmlspecialchars($wh['warehouse_id']) ?>" title="View">&#128065;</a>
                            <a href="/inventory/warehouses/<?= htmlspecialchars($wh['warehouse_id']) ?>/edit" title="Edit">&#9998;</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_warehouses_found') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- New Warehouse Modal -->
<div class="modal-overlay" id="warehouseModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">New Warehouse</h3>
            <button class="modal-close" onclick="document.getElementById('warehouseModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="/inventory/warehouses">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Warehouse Code <span class="required">*</span></label>
                        <input type="text" name="warehouse_code" class="form-input" required placeholder="e.g. WH-01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Warehouse Name <span class="required">*</span></label>
                        <input type="text" name="warehouse_name" class="form-input" required placeholder="Warehouse name">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-textarea" rows="2" placeholder="Warehouse address"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Division</label>
                        <select name="division_id" class="form-select" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($divisions ?? [] as $dv): ?>
                                <option value="<?= e($dv['division_id']) ?>"><?= e($dv['division_code'] . ' - ' . $dv['division_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="document.getElementById('warehouseModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Warehouse</button>
            </div>
        </form>
    </div>
</div>
