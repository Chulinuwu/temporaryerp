<?php
/**
 * PEGASUS ERP - MRP Purchase Instructions
 * Variables: $mrpData, $filters, $dateRange
 */
extract($viewData ?? []);
$mrpData   = $mrpData ?? [];
$filters   = $filters ?? [];
$dateRange = $dateRange ?? [];
$dates     = $dates ?? [];
?>

<style>
    .mrp-grid { overflow-x: auto; }
    .mrp-grid table { min-width: 1200px; }
    .mrp-grid th, .mrp-grid td { white-space: nowrap; font-size: 12px; }
    .mrp-expand-btn { background: none; border: none; cursor: pointer; font-size: 14px; padding: 0 4px; color: var(--color-primary); }
    .mrp-child-row { display: none; }
    .mrp-child-row.visible { display: table-row; }
    .mrp-qty-cell { text-align: right; padding: 4px 6px !important; min-width: 60px; }
    .mrp-qty-cell.shortage { background: #FFEBEE; color: #E53935; font-weight: 600; }
    .mrp-qty-cell.surplus { background: #E8F5E9; color: #43A047; }
    .mrp-level { display: inline-block; padding: 1px 6px; font-size: 10px; border-radius: 3px; background: #E3F2FD; color: #1976D2; font-weight: 600; }
</style>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;"><?= _e('mrp') ?></h1>
    <button class="btn btn-primary" onclick="generatePOs()">Generate POs from Recommendations</button>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="/production/mrp" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;min-width:130px;">
                <label class="form-label"><?= _e('from_date') ?></label>
                <input type="date" name="date_from" class="form-input" value="<?= e($filters['date_from'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:130px;">
                <label class="form-label"><?= _e('to_date') ?></label>
                <input type="date" name="date_to" class="form-input" value="<?= e($filters['date_to'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:140px;">
                <label class="form-label"><?= _e('item_code') ?></label>
                <input type="text" name="item_code" class="form-input" value="<?= e($filters['item_code'] ?? '') ?>" placeholder="Search item...">
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:80px;">
                <label class="form-label">Level</label>
                <select name="level" class="form-select">
                    <option value="">All</option>
                    <option value="0" <?= ($filters['level'] ?? '') === '0' ? 'selected' : '' ?>>0 (FG)</option>
                    <option value="1" <?= ($filters['level'] ?? '') === '1' ? 'selected' : '' ?>>1</option>
                    <option value="2" <?= ($filters['level'] ?? '') === '2' ? 'selected' : '' ?>>2</option>
                    <option value="3" <?= ($filters['level'] ?? '') === '3' ? 'selected' : '' ?>>3</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:110px;">
                <label class="form-label"><?= _e('status') ?></label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="SHORTAGE" <?= ($filters['status'] ?? '') === 'SHORTAGE' ? 'selected' : '' ?>>Shortage</option>
                    <option value="OK" <?= ($filters['status'] ?? '') === 'OK' ? 'selected' : '' ?>>OK</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= _e('filter') ?></button>
            <a href="/production/mrp" class="btn btn-cancel"><?= _e('clear') ?></a>
        </form>
    </div>
</div>

<!-- MRP Grid -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="mrp-grid">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th><?= _e('item_code') ?></th>
                        <th style="width:30px;">CK</th>
                        <th style="width:40px;">Lvl</th>
                        <th class="text-right" style="width:60px;">Ratio</th>
                        <th class="text-right" style="width:60px;">Prod LT</th>
                        <th class="text-right" style="width:60px;">RM LT</th>
                        <th class="text-right" style="width:70px;">Order Pt</th>
                        <th class="text-right" style="width:70px;">Stock</th>
                        <?php foreach ($dates as $date): ?>
                            <th class="mrp-qty-cell"><?= e(date('d', strtotime($date))) ?><br><span style="font-size:9px;color:var(--color-text-muted);"><?= e(date('D', strtotime($date))) ?></span></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mrpData)): ?>
                        <tr>
                            <td colspan="<?= 9 + count($dates) ?>" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                                <?= _e('no_mrp_found') ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mrpData as $item): ?>
                            <!-- Parent Row -->
                            <tr class="mrp-parent-row" data-item="<?= e($item['item_code']) ?>">
                                <td class="text-center">
                                    <?php if (!empty($item['children'])): ?>
                                        <button class="mrp-expand-btn" onclick="toggleMrpChildren('<?= e($item['item_code']) ?>')">&#9654;</button>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:500;">
                                    <?= e($item['item_code'] ?? '') ?>
                                    <div style="font-size:10px;color:var(--color-text-muted);"><?= e($item['item_name'] ?? '') ?></div>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="mrp-select" value="<?= e($item['item_code']) ?>">
                                </td>
                                <td class="text-center">
                                    <span class="mrp-level"><?= (int) ($item['level'] ?? 0) ?></span>
                                </td>
                                <td class="text-right"><?= e(number_format($item['ratio'] ?? 1, 2)) ?></td>
                                <td class="text-right"><?= (int) ($item['production_lt'] ?? 0) ?></td>
                                <td class="text-right"><?= (int) ($item['rm_lt'] ?? 0) ?></td>
                                <td class="text-right"><?= e(number_format($item['order_point'] ?? 0, 0)) ?></td>
                                <td class="text-right" style="font-weight:500;"><?= e(number_format($item['stock'] ?? 0, 0)) ?></td>
                                <?php foreach ($dates as $date): ?>
                                    <?php
                                    $qty = $item['daily'][$date] ?? 0;
                                    $cellClass = '';
                                    if ($qty < 0) $cellClass = 'shortage';
                                    elseif ($qty > 0) $cellClass = 'surplus';
                                    ?>
                                    <td class="mrp-qty-cell <?= $cellClass ?>">
                                        <?= $qty != 0 ? e(number_format($qty, 0)) : '' ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Child Rows (Demand, Supply, Net) -->
                            <?php if (!empty($item['children'])): ?>
                                <?php foreach ($item['children'] as $childType => $child): ?>
                                    <tr class="mrp-child-row" data-parent="<?= e($item['item_code']) ?>">
                                        <td></td>
                                        <td style="padding-left:24px;font-size:11px;color:var(--color-text-secondary);">
                                            <?= e($childType) ?>
                                        </td>
                                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                        <?php foreach ($dates as $date): ?>
                                            <?php $cQty = $child[$date] ?? 0; ?>
                                            <td class="mrp-qty-cell" style="font-size:11px;color:var(--color-text-secondary);">
                                                <?= $cQty != 0 ? e(number_format($cQty, 0)) : '' ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleMrpChildren(itemCode) {
    var rows = document.querySelectorAll('.mrp-child-row[data-parent="' + itemCode + '"]');
    var btn = document.querySelector('.mrp-parent-row[data-item="' + itemCode + '"] .mrp-expand-btn');
    var isVisible = false;
    rows.forEach(function(row) {
        row.classList.toggle('visible');
        if (row.classList.contains('visible')) isVisible = true;
    });
    if (btn) btn.innerHTML = isVisible ? '&#9660;' : '&#9654;';
}

function generatePOs() {
    var checked = document.querySelectorAll('.mrp-select:checked');
    if (checked.length === 0) {
        alert('Please select items with shortages to generate POs.');
        return;
    }
    var items = [];
    checked.forEach(function(cb) { items.push(cb.value); });

    if (!confirm('Generate Purchase Orders for ' + items.length + ' item(s)?')) return;

    fetch('/production/mrp/generate-po', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ items: items })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Generated ' + (data.po_count || 0) + ' Purchase Order(s).');
            location.reload();
        } else {
            alert(data.message || 'Failed to generate POs.');
        }
    })
    .catch(function() { alert('Network error.'); });
}
</script>
