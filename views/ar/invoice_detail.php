<?php
/**
 * PEGASUS ERP — AR Invoice Detail (read-only view)
 * Variables: $invoice, $lines, $payments
 */
$statusColors = [
    'DRAFT'    => '#9E9E9E',
    'OPEN'     => '#1976D2',
    'PARTIAL'  => '#FB8C00',
    'PAID'     => '#4CAF50',
    'OVERDUE'  => '#D32F2F',
    'CANCELLED'=> '#757575',
    'VOID'     => '#757575',
];
$color = $statusColors[$invoice['status']] ?? '#757575';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($invoice['invoice_no']) ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/ar/invoices"><?= _e('menu_invoices') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($invoice['invoice_no']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/pdf/invoice/<?= e($invoice['invoice_id']) ?>" target="_blank" class="btn btn-cancel">&#128424; PDF</a>
        <a href="/ar/invoices" class="btn btn-cancel"><?= __('back') ?></a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;">
    <!-- Invoice details -->
    <div class="card" style="padding:16px 20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;font-size:13px;">
            <div><strong><?= __('invoice_no') ?>:</strong> <?= e($invoice['invoice_no']) ?></div>
            <div><strong><?= __('invoice_date') ?>:</strong> <?= e($invoice['invoice_date']) ?></div>
            <div><strong><?= __('payment_due_date') ?>:</strong> <?= e($invoice['due_date']) ?></div>
            <div><strong><?= __('credit_days') ?>:</strong> <?= e($invoice['credit_days']) ?></div>
            <div><strong><?= __('customer') ?>:</strong> <?= e($invoice['customer_name'] ?? '—') ?></div>
            <div><strong><?= __('payment_terms') ?>:</strong> <?= e($invoice['payment_term_name'] ?? '—') ?></div>
            <div><strong><?= __('currency') ?>:</strong> <?= e($invoice['currency_code']) ?></div>
            <div><strong><?= __('exchange_rate') ?>:</strong> <?= e($invoice['exchange_rate']) ?></div>
            <?php if (!empty($invoice['so_id'])): ?>
                <div style="grid-column:1/-1;">
                    <strong><?= __('related_so') ?>:</strong>
                    <a href="/sales/orders/<?= e($invoice['so_id']) ?>">SO #<?= e($invoice['so_id']) ?></a>
                </div>
            <?php endif; ?>
            <?php if (!empty($invoice['customer_address'])): ?>
                <div style="grid-column:1/-1;"><strong><?= __('address') ?>:</strong><br><?= nl2br(e($invoice['customer_address'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary -->
    <div class="card" style="padding:20px;text-align:center;">
        <div style="margin-bottom:10px;">
            <span class="badge" style="background:<?= e($color) ?>;color:#fff;font-size:14px;padding:6px 14px;">
                <?= e($invoice['status']) ?>
            </span>
        </div>
        <div style="color:var(--color-text-muted);font-size:11px;text-transform:uppercase;"><?= __('total_amount') ?></div>
        <div style="font-size:26px;font-weight:700;color:var(--color-primary);">
            ฿<?= number_format(floatval($invoice['grand_total_thb']), 2) ?>
        </div>
        <div style="font-size:12px;color:var(--color-text-muted);margin-top:6px;">
            <?= __('subtotal') ?>: <?= formatMoney($invoice['subtotal_thb']) ?> |
            VAT: <?= formatMoney($invoice['vat_amount']) ?>
        </div>
        <div style="border-top:1px solid #eee;margin-top:10px;padding-top:10px;font-size:13px;">
            <?= __('paid') ?>: <strong style="color:#4CAF50;"><?= formatMoney($invoice['paid_amount_thb']) ?></strong><br>
            <?= __('balance') ?>: <strong><?= formatMoney($invoice['balance_thb']) ?></strong>
        </div>
    </div>
</div>

<!-- Lines -->
<div class="card" style="padding:0;margin-bottom:16px;">
    <div style="padding:12px 20px;border-bottom:1px solid #eee;font-weight:600;"><?= __('line_items') ?></div>
    <table class="data-table">
        <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th><?= __('item_name') ?></th>
            <th><?= __('description') ?></th>
            <th class="text-right" style="width:80px;"><?= __('qty') ?></th>
            <th style="width:70px;"><?= __('unit') ?></th>
            <th class="text-right" style="width:110px;"><?= __('unit_price') ?></th>
            <th class="text-right" style="width:130px;"><?= __('amount') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($lines as $ln): ?>
            <tr>
                <td><?= e($ln['line_no']) ?></td>
                <td><?= e($ln['item_name'] ?? '—') ?></td>
                <td><?= e($ln['item_description']) ?></td>
                <td class="text-right"><?= number_format(floatval($ln['quantity']), 2) ?></td>
                <td><?= e($ln['unit']) ?></td>
                <td class="text-right"><?= number_format(floatval($ln['unit_price']), 2) ?></td>
                <td class="text-right"><?= number_format(floatval($ln['ext_price']), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Payment history -->
<?php if (!empty($payments)): ?>
<div class="card" style="padding:0;">
    <div style="padding:12px 20px;border-bottom:1px solid #eee;font-weight:600;"><?= __('payment_history') ?></div>
    <table class="data-table">
        <thead><tr>
            <th><?= __('payment_date') ?></th>
            <th><?= __('payment_no') ?></th>
            <th class="text-right"><?= __('amount') ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= e($p['payment_date']) ?></td>
                <td><a href="/ar/payments/<?= e($p['payment_id'] ?? '') ?>"><?= e($p['payment_no']) ?></a></td>
                <td class="text-right"><?= formatMoney($p['allocation_amount'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
