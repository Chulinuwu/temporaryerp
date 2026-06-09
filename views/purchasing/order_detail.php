<?php
/**
 * PEGASUS ERP - Purchase Order Detail View
 * Variables: $order, $lines
 */
$o = $order;
// Simplified 3-status model: PENDING (申請中) / APPROVED (承認済) / CANCELLED (キャンセル)
$statusClasses = [
    'PENDING'   => 'badge-pending',
    'APPROVED'  => 'badge-approved',
    'CANCELLED' => 'badge-rejected',
];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= __('purchase_order') ?> #<?= e($o['po_no']) ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= __('dashboard') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/purchasing/orders"><?= __('purchase_orders') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($o['po_no']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php $st = $o['status']; ?>
        <?php if ($st === 'PENDING'): ?>
            <a href="/purchasing/orders/<?= $o['po_id'] ?>/edit" class="btn btn-cancel"><?= __('edit') ?></a>
            <?php if (Auth::isManagerOrAbove()): ?>
                <form method="POST" action="/purchasing/orders/<?= $o['po_id'] ?>/approve" style="display:inline;"
                      onsubmit="return confirm('<?= __('approve') ?>?');">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-primary" style="background:#2e7d32;">&#10003; <?= __('approve') ?></button>
                </form>
            <?php endif; ?>
            <form method="POST" action="/purchasing/orders/<?= $o['po_id'] ?>/cancel" style="display:inline;"
                  onsubmit="return confirm('<?= __('cancel_po_confirm') ?>');">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-cancel" style="color:#c62828;">&#128683; <?= __('cancel') ?></button>
            </form>
        <?php elseif ($st === 'APPROVED' && Auth::isAdmin()): ?>
            <form method="POST" action="/purchasing/orders/<?= $o['po_id'] ?>/cancel" style="display:inline;"
                  onsubmit="return confirm('<?= __('cancel_po_confirm') ?>');">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-cancel" style="color:#c62828;">&#128683; <?= __('cancel') ?></button>
            </form>
        <?php endif; ?>
        <form method="POST" action="/purchasing/orders/<?= $o['po_id'] ?>/copy" style="display:inline;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-cancel">&#128203; <?= __('copy') ?></button>
        </form>
        <a href="/pdf/po/<?= $o['po_id'] ?>" target="_blank" class="btn btn-cancel"><?= __('print_pdf') ?></a>
        <a href="/purchasing/orders" class="btn btn-cancel"><?= __('back_to_list') ?></a>
    </div>
</div>

<!-- Header Info -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= __('po_details') ?></h3>
        <span class="badge <?= $statusClasses[$o['status']] ?? 'badge-draft' ?>"><?= e($o['status']) ?></span>
    </div>
    <div class="card-body">
        <div class="info-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;">
            <div><strong><?= __('supplier') ?>:</strong> <?= e($o['supplier_name'] ?? '') ?></div>
            <div><strong><?= __('order_date') ?>:</strong> <?= e($o['order_date'] ?? '') ?></div>
            <div><strong><?= __('delivery_date') ?>:</strong> <?= e($o['delivery_date'] ?? '-') ?></div>
            <div><strong><?= __('requested_date') ?>:</strong> <?= e($o['requested_date'] ?? '-') ?></div>
            <div><strong><?= __('currency') ?>:</strong> <?= e($o['currency_code'] ?? 'THB') ?></div>
            <div><strong><?= __('payment_terms') ?>:</strong> <?= e($o['payment_term_name'] ?? '-') ?></div>
            <?php if (!empty($o['pj_no'])): ?>
                <div><strong><?= __('pj_no') ?>:</strong>
                    <a href="/projects/<?= e($o['project_id']) ?>" style="font-weight:500;"><?= e($o['pj_no']) ?></a>
                    <?php if (!empty($o['pj_name'])): ?>
                        <span style="color:var(--color-text-muted);font-size:12px;"> — <?= e($o['pj_name']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($o['supplier_tax_id'])): ?>
                <div><strong><?= __('tax_id') ?>:</strong> <?= e($o['supplier_tax_id']) ?></div>
            <?php endif; ?>
            <?php if (!empty($o['supplier_address'])): ?>
                <div><strong><?= __('address') ?>:</strong> <?= e($o['supplier_address']) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Line Items -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= __('line_items') ?></h3>
    </div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="data-table" style="min-width:800px;">
            <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th><?= __('description') ?></th>
                    <th style="width:80px;" class="text-right"><?= __('qty') ?></th>
                    <th style="width:60px;"><?= __('unit') ?></th>
                    <th style="width:120px;" class="text-right"><?= __('unit_price') ?></th>
                    <th style="width:130px;" class="text-right"><?= __('amount') ?></th>
                    <th style="width:100px;" class="text-right"><?= __('received') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($lines)):
                    foreach ($lines as $line):
                        $qty = floatval($line['quantity'] ?? 0);
                        $unitPrice = floatval($line['unit_price'] ?? 0);
                        $extPrice = floatval($line['ext_price'] ?? $qty * $unitPrice);
                        $received = floatval($line['received_qty'] ?? 0);
                ?>
                    <tr>
                        <td><?= e($line['line_no']) ?></td>
                        <td>
                            <?= e($line['item_description'] ?? '') ?>
                            <?php if (!empty($line['item_code'])): ?>
                                <div style="font-size:11px;color:#888;"><?= e($line['item_code']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?= number_format($qty, 2) ?></td>
                        <td><?= e($line['unit'] ?? 'EA') ?></td>
                        <td class="text-right"><?= formatMoney($unitPrice) ?></td>
                        <td class="text-right"><?= formatMoney($extPrice) ?></td>
                        <td class="text-right">
                            <?php if ($received > 0): ?>
                                <span style="color:<?= $received >= $qty ? 'var(--color-success)' : 'var(--color-warning)' ?>;">
                                    <?= number_format($received, 2) ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#ccc;">0.00</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                <?php if (empty($lines)): ?>
                    <tr><td colspan="7" class="text-center" style="padding:30px;color:#999;"><?= __('no_items') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Summary -->
<div class="form-grid-2">
    <div class="card">
        <div class="card-header"><h3 class="card-title"><?= __('remarks') ?></h3></div>
        <div class="card-body">
            <p><?= nl2br(e($o['notes'] ?? '-')) ?></p>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><?= __('summary') ?></h3></div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;">
                    <span><?= __('subtotal') ?></span>
                    <span style="font-weight:600;"><?= formatMoney($o['subtotal_thb'] ?? 0) ?></span>
                </div>
                <?php if (floatval($o['discount_amount'] ?? 0) > 0): ?>
                <div style="display:flex;justify-content:space-between;">
                    <span><?= __('discount') ?></span>
                    <span style="font-weight:600;color:var(--color-danger);">-<?= formatMoney($o['discount_amount']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;">
                    <span><?= __('vat_rate') ?></span>
                    <span><?= e($o['vat_rate'] ?? 7) ?>%</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span><?= __('vat_amount') ?></span>
                    <span style="font-weight:600;"><?= formatMoney($o['vat_amount'] ?? 0) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:8px;">
                    <span><?= __('wht') ?></span>
                    <span style="font-weight:600;color:var(--color-danger);">-<?= formatMoney($o['wht_amount'] ?? 0) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:2px solid var(--color-border);padding-top:10px;">
                    <span style="font-size:16px;font-weight:700;"><?= __('payment_amount') ?></span>
                    <span style="font-size:16px;font-weight:700;color:var(--color-primary);"><?= formatMoney($o['payment_amount'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status management is now done via explicit Approve / Cancel buttons at the top -->


<style>
.badge-draft { background:#6c757d; color:#fff; padding:4px 12px; border-radius:4px; }
.badge-pending { background:#FF9800; color:#fff; padding:4px 12px; border-radius:4px; }
.badge-approved { background:#34a853; color:#fff; padding:4px 12px; border-radius:4px; }
.badge-open { background:#1a73e8; color:#fff; padding:4px 12px; border-radius:4px; }
.badge-rejected { background:#ea4335; color:#fff; padding:4px 12px; border-radius:4px; }
</style>
