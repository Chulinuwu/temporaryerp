<?php
/**
 * PEGASUS ERP - Bill of Materials
 * Variables: $boms, $bomDetail, $components
 */
extract($viewData ?? []);
$boms       = $boms ?? [];
$bomDetail  = $bomDetail ?? null;
$components = $components ?? [];
?>

<?php if ($bomDetail): ?>
    <!-- BOM Detail View -->
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <h1 style="font-size:20px;font-weight:600;">BOM: <?= e($bomDetail['bom_code'] ?? '') ?></h1>
        <div style="display:flex;gap:8px;">
            <a href="/production/bom" class="btn btn-cancel">Back to List</a>
            <?php if ($bomDetail['is_current'] ?? true): ?>
                <a href="/production/bom/<?= e($bomDetail['bom_id']) ?>/edit" class="btn btn-primary">Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header Info -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" style="color:var(--color-text-muted);">BOM Code</label>
                    <div style="font-weight:500;"><?= e($bomDetail['bom_code'] ?? '') ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="color:var(--color-text-muted);">Item</label>
                    <div style="font-weight:500;"><?= e($bomDetail['item_name'] ?? '') ?></div>
                    <div style="font-size:12px;color:var(--color-text-muted);"><?= e($bomDetail['item_code'] ?? '') ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="color:var(--color-text-muted);">Revision</label>
                    <div style="font-weight:500;"><?= e($bomDetail['revision'] ?? '1') ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="color:var(--color-text-muted);">Yield Qty</label>
                    <div style="font-weight:500;"><?= e(number_format($bomDetail['yield_qty'] ?? 0, 2)) ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="color:var(--color-text-muted);">Status</label>
                    <div>
                        <?php if ($bomDetail['is_current'] ?? true): ?>
                            <span class="badge badge-approved">Current</span>
                        <?php else: ?>
                            <span class="badge badge-rejected">Superseded</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Components Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Components (<?= count($components) ?>)</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th><?= _e('code') ?></th>
                        <th><?= _e('component') ?></th>
                        <th class="text-right"><?= _e('qty_per') ?></th>
                        <th><?= _e('unit') ?></th>
                        <th class="text-right">Scrap Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($components)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:24px;color:var(--color-text-muted);">No components defined.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($components as $i => $comp): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td><?= e($comp['item_code'] ?? '') ?></td>
                                <td><?= e($comp['item_name'] ?? '') ?></td>
                                <td class="text-right"><?= e(number_format($comp['quantity_per'] ?? 0, 4)) ?></td>
                                <td><?= e($comp['unit'] ?? '') ?></td>
                                <td class="text-right"><?= e(number_format(($comp['scrap_rate'] ?? 0) * 100, 2)) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- BOM List View -->
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <h1 style="font-size:20px;font-weight:600;"><?= _e('bom') ?></h1>
        <a href="/production/bom/create" class="btn btn-primary"><?= _e('new_bom') ?></a>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= _e('bom_no') ?></th>
                    <th><?= _e('parent_item') ?></th>
                    <th class="text-center">Revision</th>
                    <th class="text-right"><?= _e('quantity') ?></th>
                    <th class="text-right"><?= _e('component') ?></th>
                    <th class="text-center"><?= _e('status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($boms)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                            <?= _e('no_bom_found') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($boms as $bom): ?>
                        <tr>
                            <td>
                                <a href="/production/bom/<?= e($bom['bom_id']) ?>" style="font-weight:500;">
                                    <?= e($bom['bom_code'] ?? '') ?>
                                </a>
                            </td>
                            <td>
                                <div><?= e($bom['item_name'] ?? '') ?></div>
                                <div style="font-size:11px;color:var(--color-text-muted);"><?= e($bom['item_code'] ?? '') ?></div>
                            </td>
                            <td class="text-center"><?= e($bom['revision'] ?? '1') ?></td>
                            <td class="text-right"><?= e(number_format($bom['yield_qty'] ?? 0, 2)) ?></td>
                            <td class="text-right"><?= (int) ($bom['components_count'] ?? 0) ?></td>
                            <td class="text-center">
                                <?php if ($bom['is_current'] ?? true): ?>
                                    <span class="badge badge-approved">Current</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected">Superseded</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if (!empty($boms)): ?>
            <div class="table-footer">
                <span><?= count($boms) ?> BOM(s)</span>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
