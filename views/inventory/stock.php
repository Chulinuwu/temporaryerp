<?php
/**
 * PEGASUS ERP - Stock Management
 * Variables: $stockItems, $warehouses, $filters, $pagination
 */
$pageTitle = __('stock_management') . ' - PEGASUS ERP';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('stock_management') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/inventory"><?= _e('inventory') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('stock') ?></span>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 20px;">
    <form method="GET" action="/inventory/stock" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <select name="warehouse_id" class="form-select" style="width:200px;">
                <option value=""><?= _e('all_warehouses') ?></option>
                <?php foreach ($warehouses ?? [] as $wh): ?>
                    <option value="<?= htmlspecialchars($wh['warehouse_id']) ?>" <?= ($filters['warehouse_id'] ?? '') == $wh['warehouse_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($wh['warehouse_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input type="text" name="search" class="form-input" style="width:260px;" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="<?= _e('search_item') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/inventory/stock" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Stock Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th class="sortable"><?= _e('item_code') ?></th>
                <th class="sortable"><?= _e('item_name') ?></th>
                <th><?= _e('warehouse') ?></th>
                <th class="text-right sortable"><?= _e('on_hand') ?></th>
                <th class="text-right"><?= _e('reserved') ?></th>
                <th class="text-right"><?= _e('available') ?></th>
                <th class="text-right"><?= _e('avg_cost') ?></th>
                <th class="text-right"><?= _e('total_value') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($stockItems)): ?>
                <?php foreach ($stockItems as $item): ?>
                    <?php $available = ($item['on_hand'] ?? 0) - ($item['reserved'] ?? 0); ?>
                    <tr>
                        <td><a href="/inventory/items/<?= htmlspecialchars($item['item_id']) ?>"><?= htmlspecialchars($item['item_code']) ?></a></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['warehouse_name']) ?></td>
                        <td class="text-right"><?= number_format($item['on_hand'], 2) ?></td>
                        <td class="text-right"><?= number_format($item['reserved'] ?? 0, 2) ?></td>
                        <td class="text-right" style="<?= $available <= 0 ? 'color:var(--color-danger);font-weight:600;' : '' ?>">
                            <?= number_format($available, 2) ?>
                        </td>
                        <td class="text-right"><?= formatMoney($item['avg_cost'] ?? 0) ?></td>
                        <td class="text-right"><strong><?= formatMoney($item['total_value'] ?? 0) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding:40px;color:var(--color-text-muted);"><?= _e('no_stock_found') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($pagination)): ?>
        <div class="table-footer">
            <span><?= __('showing_of', $pagination['from'], $pagination['to'], $pagination['total']) ?></span>
            <div style="display:flex;gap:4px;">
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="btn btn-cancel btn-sm"><?= __('prev') ?></a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="btn btn-cancel btn-sm"><?= __('next') ?></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
