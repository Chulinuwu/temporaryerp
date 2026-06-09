<?php
/**
 * PEGASUS ERP - Sales Order Detail (受注詳細)
 * Variables: $order, $lines, $linkedQuotations
 */
$statusClasses = [
    'DRAFT'            => 'badge-draft',
    'CONFIRMED'        => 'badge-open',
    'IN_PRODUCTION'    => 'badge-pending',
    'PARTIAL_SHIPPED'  => 'badge-pending',
    'SHIPPED'          => 'badge-approved',
    'INVOICED'         => 'badge-paid',
    'CLOSED'           => 'badge-draft',
    'CANCELLED'        => 'badge-rejected',
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($order['so_no']) ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/orders"><?= _e('sales_orders') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($order['so_no']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/pdf/salesorder/<?= e($order['so_id']) ?>" target="_blank" class="btn btn-cancel">&#128424; PDF</a>
        <a href="/sales/orders" class="btn btn-cancel"><?= _e('back') ?></a>
    </div>
</div>

<!-- Order Summary -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;">
    <!-- Main Info -->
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('so_no') ?>:</span>
                    <strong style="font-size:15px;"><?= e($order['so_no']) ?></strong>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('order_date') ?>:</span>
                    <strong><?= e($order['order_date'] ?? '') ?></strong>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('customer') ?>:</span>
                    <strong><?= e($order['customer_name'] ?? '') ?></strong>
                </div>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('requested_date') ?>:</span>
                    <?= e($order['requested_date'] ?? '-') ?>
                </div>
                <?php if (!empty($order['deal_no'])): ?>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('deal_ref') ?>:</span>
                    <a href="/sales/deals/<?= e($order['ref_deal_id']) ?>" style="font-weight:500;">
                        <?= e($order['deal_no']) ?>
                    </a>
                    <?php if (!empty($order['deal_name'])): ?>
                        <span style="color:var(--color-text-secondary);font-size:12px;"> - <?= e($order['deal_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['quotation_no'])): ?>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('quotation_ref') ?>:</span>
                    <a href="/sales/quotations/<?= e($order['quotation_id']) ?>"><?= e($order['quotation_no']) ?></a>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['payment_term_name'])): ?>
                <div>
                    <span style="color:var(--color-text-muted);"><?= _e('payment_terms') ?>:</span>
                    <?= e($order['payment_term_name']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['notes'])): ?>
                <div style="grid-column:1/3;">
                    <span style="color:var(--color-text-muted);"><?= _e('note') ?>:</span>
                    <?= nl2br(e($order['notes'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status & Amount -->
    <div>
        <div class="card" style="margin-bottom:12px;">
            <div class="card-body" style="padding:20px;text-align:center;">
                <div style="margin-bottom:12px;">
                    <span class="badge <?= $statusClasses[$order['status']] ?? 'badge-draft' ?>" style="font-size:14px;padding:6px 16px;">
                        <?= e($order['status']) ?>
                    </span>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;"><?= _e('grand_total') ?></div>
                <div style="font-size:28px;font-weight:700;color:var(--color-primary);">
                    <?= formatMoney($order['grand_total_thb'] ?? 0) ?>
                </div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:8px;">
                    <?= _e('subtotal') ?>: <?= formatMoney($order['subtotal_thb'] ?? 0) ?> |
                    VAT: <?= formatMoney($order['vat_amount'] ?? 0) ?>
                </div>
            </div>
        </div>

        <?php if (Auth::isAdmin() && $order['status'] !== 'CANCELLED'): ?>
        <!-- #11: Admin-only cancel -->
        <div class="card">
            <div class="card-body" style="padding:16px;">
                <form method="POST" action="/sales/orders/<?= e($order['so_id']) ?>/cancel"
                      onsubmit="return confirm('<?= __('confirm_cancel_order') ?>');">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-cancel btn-sm" style="width:100%;color:#D32F2F;">
                        &#128683; <?= __('cancel_order') ?>
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Linked Quotations (if from deal) -->
<?php if (!empty($linkedQuotations)): ?>
<div class="card" style="margin-bottom:16px;">
    <div style="padding:12px 20px;border-bottom:1px solid var(--color-border);">
        <h4 style="margin:0;font-size:14px;"><?= _e('quotations') ?> (<?= count($linkedQuotations) ?>)</h4>
    </div>
    <div style="padding:0;">
        <table class="data-table" style="margin:0;">
            <thead>
                <tr>
                    <th><?= _e('quotation_no') ?></th>
                    <th><?= _e('project') ?></th>
                    <th class="text-right"><?= _e('subtotal') ?></th>
                    <th class="text-right"><?= _e('grand_total') ?></th>
                    <th class="text-center"><?= _e('status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($linkedQuotations as $q): ?>
                <tr>
                    <td><a href="/sales/quotations/<?= e($q['quotation_id']) ?>"><?= e($q['quotation_no']) ?></a></td>
                    <td style="font-size:12px;"><?= e($q['project_name'] ?? '-') ?></td>
                    <td class="text-right"><?= formatMoney($q['subtotal_thb'] ?? 0) ?></td>
                    <td class="text-right"><strong><?= formatMoney($q['grand_total_thb'] ?? 0) ?></strong></td>
                    <td class="text-center"><span class="badge badge-approved" style="font-size:10px;"><?= e($q['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Order Lines -->
<div class="card">
    <div style="padding:12px 20px;border-bottom:1px solid var(--color-border);">
        <h4 style="margin:0;font-size:14px;"><?= _e('order_lines') ?> (<?= count($lines) ?>)</h4>
    </div>
    <div style="padding:0;">
        <?php if (!empty($lines)): ?>
        <table class="data-table" style="margin:0;">
            <thead>
                <tr>
                    <th style="width:50px;"><?= _e('no') ?></th>
                    <th><?= _e('item_description') ?></th>
                    <th class="text-right" style="width:80px;"><?= _e('quantity') ?></th>
                    <th style="width:60px;"><?= _e('unit') ?></th>
                    <th class="text-right" style="width:120px;"><?= _e('unit_price') ?></th>
                    <th class="text-right" style="width:120px;"><?= _e('amount') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $line): ?>
                <tr>
                    <td class="text-center"><?= e($line['line_no']) ?></td>
                    <td>
                        <?= e($line['item_description'] ?? '') ?>
                        <?php if (!empty($line['item_code'])): ?>
                            <div style="font-size:11px;color:var(--color-text-muted);"><?= e($line['item_code']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?= number_format($line['quantity'] ?? 0, 2) ?></td>
                    <td><?= e($line['unit'] ?? '') ?></td>
                    <td class="text-right"><?= formatMoney($line['unit_price'] ?? 0) ?></td>
                    <td class="text-right"><strong><?= formatMoney($line['ext_price'] ?? 0) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;">
                    <td colspan="5" class="text-right"><?= _e('subtotal') ?></td>
                    <td class="text-right"><?= formatMoney($order['subtotal_thb'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">VAT (<?= number_format($order['vat_rate'] ?? 7, 0) ?>%)</td>
                    <td class="text-right"><?= formatMoney($order['vat_amount'] ?? 0) ?></td>
                </tr>
                <tr style="font-weight:700;font-size:15px;">
                    <td colspan="5" class="text-right"><?= _e('grand_total') ?></td>
                    <td class="text-right" style="color:var(--color-primary);"><?= formatMoney($order['grand_total_thb'] ?? 0) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:var(--color-text-muted);"><?= __('no_items') ?></div>
        <?php endif; ?>
    </div>
</div>
