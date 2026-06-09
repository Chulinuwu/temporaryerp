<?php
/**
 * PEGASUS ERP - Manufacturing Orders
 * Variables: $orders, $filters
 */
extract($viewData ?? []);
$orders  = $orders ?? [];
$filters = $filters ?? [];
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;"><?= _e('manufacturing_orders') ?></h1>
    <a href="/production/orders/create" class="btn btn-primary"><?= _e('new_mo') ?></a>
</div>

<!-- Orders Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= _e('mo_no') ?></th>
                <th><?= _e('product') ?></th>
                <th class="text-right"><?= _e('planned_qty') ?></th>
                <th class="text-right"><?= _e('actual_qty') ?></th>
                <th><?= _e('start_date') ?></th>
                <th><?= _e('end_date') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                        <?= _e('no_mo_found') ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $mo): ?>
                    <tr>
                        <td>
                            <a href="/production/orders/<?= e($mo['mo_id']) ?>" style="font-weight:500;">
                                <?= e($mo['mo_no'] ?? '') ?>
                            </a>
                        </td>
                        <td>
                            <div><?= e($mo['item_name'] ?? '') ?></div>
                            <div style="font-size:11px;color:var(--color-text-muted);"><?= e($mo['item_code'] ?? '') ?></div>
                        </td>
                        <td class="text-right"><?= e(number_format($mo['planned_qty'] ?? 0, 2)) ?></td>
                        <td class="text-right"><?= e(number_format($mo['completed_qty'] ?? 0, 2)) ?></td>
                        <td><?= e(formatDate($mo['planned_start'] ?? '', 'd/m/Y')) ?></td>
                        <td><?= e(formatDate($mo['planned_end'] ?? '', 'd/m/Y')) ?></td>
                        <td class="text-center">
                            <?php
                            $statusClass = match ($mo['status'] ?? '') {
                                'DRAFT'       => 'badge-draft',
                                'PLANNED'     => 'badge-open',
                                'IN_PROGRESS' => 'badge-pending',
                                'COMPLETED'   => 'badge-approved',
                                'CANCELLED'   => 'badge-rejected',
                                default       => 'badge-draft',
                            };
                            $statusLabel = match ($mo['status'] ?? '') {
                                'IN_PROGRESS' => 'In Progress',
                                default       => ucfirst(strtolower(str_replace('_', ' ', $mo['status'] ?? 'DRAFT'))),
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= e($statusLabel) ?></span>
                        </td>
                        <td class="actions text-center">
                            <a href="/production/orders/<?= e($mo['mo_id']) ?>" title="View">&#128065;</a>
                            <?php if (in_array($mo['status'] ?? '', ['DRAFT', 'PLANNED'])): ?>
                                <a href="/production/orders/<?= e($mo['mo_id']) ?>/edit" title="Edit">&#9998;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (!empty($orders)): ?>
        <div class="table-footer">
            <span><?= count($orders) ?> order(s)</span>
        </div>
    <?php endif; ?>
</div>
