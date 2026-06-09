<?php
/**
 * PEGASUS ERP - Project Detail / Budget Table
 * Variables: $project, $invoices, $purchases, $progress, $purchaseOrders
 */
$p = $project;
$statusLabels = [
    'ACTIVE'=>__('pj_active'), 'IN_PROGRESS'=>__('pj_in_progress'),
    'COMPLETED'=>__('pj_completed'), 'ON_HOLD'=>__('pj_on_hold'), 'CANCELLED'=>__('pj_cancelled'),
];
$statusClasses = [
    'ACTIVE'=>'badge-open', 'IN_PROGRESS'=>'badge-pending',
    'COMPLETED'=>'badge-approved', 'ON_HOLD'=>'badge-draft', 'CANCELLED'=>'badge-rejected',
];
$invTotal = 0; foreach ($invoices as $inv) $invTotal += floatval($inv['amount'] ?? 0);
$purTotal = 0; foreach ($purchases as $pur) $purTotal += floatval($pur['amount'] ?? 0);

// Find linked cost sheets for this project
$db = Database::getInstance();
$linkedCostSheets = $db->fetchAll(
    "SELECT cost_sheet_id, sheet_no, sheet_name, total_cost FROM cost_sheets WHERE project_id = ? AND is_deleted = FALSE ORDER BY sheet_no",
    [$p['project_id']]
) ?: [];
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($p['pj_no']) ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/projects"><?= __('project_list') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($p['pj_no']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/projects/<?= e($p['project_id']) ?>/edit" class="btn btn-cancel"><?= __('edit') ?></a>
        <a href="/projects" class="btn btn-cancel"><?= __('back') ?></a>
    </div>
</div>

<!-- ===== BUDGET TABLE Header ===== -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:16px 20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
            <div>
                <h2 style="margin:0;font-size:16px;color:#003366;">BUDGET TABLE</h2>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;"><?= e($p['pj_segment'] ?? '') ?></div>
            </div>
            <span class="badge <?= $statusClasses[$p['status']] ?? 'badge-draft' ?>" style="font-size:12px;padding:4px 12px;">
                <?= $statusLabels[$p['status']] ?? $p['status'] ?>
            </span>
        </div>

        <!-- Project Info Grid -->
        <table style="width:100%;border-collapse:collapse;font-size:13px;" class="budget-info">
            <tr>
                <td class="lbl">PJ No.</td>
                <td class="val" style="font-weight:700;color:#003366;"><?= e($p['pj_no']) ?></td>
                <td class="lbl"><?= __('pj_name') ?></td>
                <td class="val" colspan="3" style="font-weight:600;"><?= e($p['pj_name']) ?></td>
                <td class="lbl" rowspan="2" style="vertical-align:top;"><?= __('estimate') ?></td>
                <td class="lbl" rowspan="2" style="vertical-align:top;"><?= __('target') ?></td>
                <td class="lbl" rowspan="2" style="vertical-align:top;"><?= __('actual_result') ?></td>
            </tr>
            <tr>
                <td class="lbl"><?= __('customer') ?></td>
                <td class="val"><?= e($p['customer_name'] ?? '') ?></td>
                <td class="lbl">Sales</td>
                <td class="lbl"><?= __('amount') ?> (Baht)</td>
                <td class="lbl">P/O Date</td>
                <td class="lbl"><?= __('purchase_cost') ?></td>
            </tr>
            <tr>
                <td class="lbl"><?= __('delivery_place') ?></td>
                <td class="val"><?= e($p['delivery_place'] ?? '-') ?></td>
                <td class="lbl">TFB</td>
                <td class="val num"><?= formatMoney($p['total_revenue'] ?? 0) ?></td>
                <td class="val"><?= e($p['po_date'] ?? '-') ?></td>
                <td class="val num"><?= formatMoney($p['purchase_estimate'] ?? $p['cost_total'] ?? 0) ?></td>
                <td class="val num"><?= formatMoney($p['purchase_estimate'] ?? 0) ?></td>
                <td class="val num"><?= formatMoney($p['purchase_target'] ?? 0) ?></td>
                <td class="val num"><?= formatMoney($p['purchase_actual'] ?? $purTotal) ?></td>
            </tr>
            <tr>
                <td class="lbl"><?= __('order_date_short') ?></td>
                <td class="val"><?= e($p['po_date'] ?? '-') ?></td>
                <td class="lbl"><?= __('inspection') ?></td>
                <td class="val"><?= e($p['inspection_date'] ?? '-') ?></td>
                <td class="lbl"><?= __('gross_profit') ?></td>
                <td class="val num" style="color:<?= floatval($p['gross_profit'] ?? 0) >= 0 ? '#4CAF50' : '#F44336' ?>;">
                    <?= formatMoney($p['gross_profit'] ?? 0) ?>
                </td>
                <td class="val num"><?= formatMoney($p['gp_estimate'] ?? $p['gross_profit'] ?? 0) ?></td>
                <td class="val num"><?= formatMoney($p['gp_target'] ?? 0) ?></td>
                <td class="val num"><?= formatMoney($p['gp_actual'] ?? 0) ?></td>
            </tr>
            <tr>
                <td class="lbl"><?= __('delivery') ?></td>
                <td class="val"><?= e($p['delivery_date'] ?? $p['plan_delivery_date'] ?? '-') ?></td>
                <td class="lbl"><?= __('complete') ?></td>
                <td class="val"><?= e($p['complete_date'] ?? '-') ?></td>
                <td class="lbl"><?= __('profit_pct') ?></td>
                <td class="val num"><?= number_format($p['profit_pct'] ?? 0, 1) ?>%</td>
                <td class="val num"><?= floatval($p['total_revenue'] ?? 0) > 0 ? number_format(floatval($p['gp_estimate'] ?? $p['gross_profit'] ?? 0) / floatval($p['total_revenue']) * 100, 1) . '%' : '-' ?></td>
                <td class="val num">-</td>
                <td class="val num"><?= floatval($p['total_revenue'] ?? 0) > 0 ? number_format(floatval($p['gp_actual'] ?? 0) / floatval($p['total_revenue']) * 100, 1) . '%' : '-' ?></td>
            </tr>
            <?php if (!empty($p['so_no']) || !empty($p['deal_no'])): ?>
            <tr>
                <?php if (!empty($p['deal_no'])): ?>
                <td class="lbl"><?= __('deal_ref') ?></td>
                <td class="val"><a href="/sales/deals/<?= e($p['deal_id']) ?>"><?= e($p['deal_no']) ?></a></td>
                <?php else: ?><td></td><td></td><?php endif; ?>
                <?php if (!empty($p['so_no'])): ?>
                <td class="lbl"><?= __('so_no') ?></td>
                <td class="val"><a href="/sales/orders/<?= e($p['so_id']) ?>"><?= e($p['so_no']) ?></a></td>
                <?php else: ?><td></td><td></td><?php endif; ?>
                <td colspan="5"></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- ===== Payment Schedule ===== -->
<div class="card" style="margin-bottom:16px;">
    <div style="padding:10px 20px;border-bottom:1px solid var(--color-border);">
        <h4 style="margin:0;font-size:14px;"><?= __('payment_schedule') ?>
            <?php if (!empty($paymentSchedules)): ?>
                <span style="font-size:12px;color:var(--color-text-muted);font-weight:normal;margin-left:8px;">(<?= count($paymentSchedules) ?> <?= __('installments') ?>)</span>
            <?php endif; ?>
        </h4>
    </div>
    <div style="padding:12px 20px;">
        <?php if (!empty($paymentSchedules)): ?>
        <?php
            $psTotalPct = 0; $psTotalAmt = 0;
            foreach ($paymentSchedules as $ps) {
                $psTotalPct += floatval($ps['percentage'] ?? 0);
                $psTotalAmt += floatval($ps['amount'] ?? 0);
            }
        ?>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f5f7fa;">
                    <th style="padding:6px 8px;text-align:center;width:40px;">#</th>
                    <th style="padding:6px 8px;text-align:left;"><?= __('description') ?></th>
                    <th style="padding:6px 8px;text-align:center;width:60px;">%</th>
                    <th style="padding:6px 8px;text-align:center;width:80px;">Credit</th>
                    <th style="padding:6px 8px;text-align:center;width:110px;"><?= __('plan') ?></th>
                    <th style="padding:6px 8px;text-align:center;width:110px;"><?= __('actual') ?></th>
                    <th style="padding:6px 8px;text-align:right;width:130px;"><?= __('amount') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paymentSchedules as $ps):
                    $hasActual = !empty($ps['actual_date']);
                ?>
                <tr>
                    <td class="text-center" style="padding:6px 8px;"><?= e($ps['seq_no']) ?></td>
                    <td style="padding:6px 8px;"><?= e($ps['description'] ?? '') ?></td>
                    <td class="text-center" style="padding:6px 8px;"><?= number_format($ps['percentage'] ?? 0, 1) ?>%</td>
                    <td class="text-center" style="padding:6px 8px;"><?= intval($ps['credit_days'] ?? 0) ?> <?= __('days_short') ?></td>
                    <td class="text-center" style="padding:6px 8px;"><?= e($ps['plan_date'] ?? '-') ?></td>
                    <td class="text-center" style="padding:6px 8px;">
                        <?php if ($hasActual): ?>
                            <span style="color:#4CAF50;font-weight:600;"><?= e($ps['actual_date']) ?></span>
                        <?php else: ?>
                            <span style="color:var(--color-text-muted);">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right" style="padding:6px 8px;font-weight:600;">
                        <?= floatval($ps['amount'] ?? 0) > 0 ? formatMoney($ps['amount']) : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;background:#f5f7fa;border-top:2px solid #ddd;">
                    <td colspan="2" class="text-right" style="padding:6px 8px;"><?= __('total') ?></td>
                    <td class="text-center" style="padding:6px 8px;color:<?= abs($psTotalPct - 100) < 0.01 ? '#4CAF50' : '#F44336' ?>;"><?= number_format($psTotalPct, 1) ?>%</td>
                    <td></td>
                    <td colspan="2"></td>
                    <td class="text-right" style="padding:6px 8px;color:#003366;font-size:14px;"><?= formatMoney($psTotalAmt) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
            <div style="text-align:center;color:var(--color-text-muted);padding:16px;font-size:13px;">
                <?= __('no_payment_schedule') ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== Linked Cost Sheets ===== -->
<?php if (!empty($linkedCostSheets)): ?>
<div class="card" style="margin-bottom:16px;">
    <div style="padding:10px 20px;border-bottom:1px solid var(--color-border);display:flex;justify-content:space-between;align-items:center;">
        <h4 style="margin:0;font-size:14px;">&#128200; <?= __('cost_breakdown') ?></h4>
        <a href="/cost-sheets/create" class="btn btn-cancel btn-sm">+ <?= __('new_cost_sheet') ?></a>
    </div>
    <div style="padding:12px 20px;">
        <table class="data-table" style="font-size:12px;">
            <thead><tr>
                <th><?= __('cost_sheet_no') ?></th>
                <th><?= __('cost_sheet_name') ?></th>
                <th class="text-right"><?= __('cost_total_label') ?></th>
                <th style="width:60px;"></th>
            </tr></thead>
            <tbody>
                <?php foreach ($linkedCostSheets as $cs): ?>
                <tr>
                    <td style="font-weight:600;"><a href="/cost-sheets/<?= e($cs['cost_sheet_id']) ?>"><?= e($cs['sheet_no']) ?></a></td>
                    <td><?= e($cs['sheet_name']) ?></td>
                    <td class="text-right" style="font-weight:600;"><?= formatMoney($cs['total_cost'] ?? 0) ?></td>
                    <td class="text-center"><a href="/cost-sheets/<?= e($cs['cost_sheet_id']) ?>">&#128065;</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ===== Tabs: Invoices / Purchases ===== -->
<div class="card">
    <div style="border-bottom:1px solid var(--color-border);padding:0 20px;">
        <button class="tab-btn active" onclick="showPjTab('invoices')" id="tab-invoices"
                style="padding:12px 20px;border:none;background:none;font-weight:600;font-size:13px;cursor:pointer;border-bottom:2px solid var(--color-primary);color:var(--color-primary);">
            <?= __('revenue_invoices') ?> (<?= count($invoices) ?>)
            <span style="font-size:11px;color:var(--color-text-muted);margin-left:4px;"><?= formatMoney($invTotal) ?></span>
        </button>
        <button class="tab-btn" onclick="showPjTab('purchases')" id="tab-purchases"
                style="padding:12px 20px;border:none;background:none;font-weight:600;font-size:13px;cursor:pointer;color:var(--color-text-muted);">
            <?= __('cost_purchases') ?> (<?= count($purchases) ?>)
            <span style="font-size:11px;color:var(--color-text-muted);margin-left:4px;"><?= formatMoney($purTotal) ?></span>
        </button>
        <button class="tab-btn" onclick="showPjTab('linkedpos')" id="tab-linkedpos"
                style="padding:12px 20px;border:none;background:none;font-weight:600;font-size:13px;cursor:pointer;color:var(--color-text-muted);">
            <?= __('linked_pos') ?> (<?= count($linkedPOs ?? []) ?>)
        </button>
    </div>

    <!-- ===== Invoices Tab ===== -->
    <div id="panel-invoices" class="tab-panel" style="padding:20px;">
        <!-- Add Invoice Form -->
        <div style="background:var(--color-bg-secondary);border-radius:8px;padding:14px;margin-bottom:16px;">
            <form method="POST" action="/projects/<?= e($p['project_id']) ?>/invoices">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <div style="display:grid;grid-template-columns:100px 1fr 140px 120px 1fr 80px;gap:8px;align-items:end;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('date') ?></label>
                        <input type="date" name="invoice_date" class="form-input" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;">Invoice No.</label>
                        <input type="text" name="invoice_no" class="form-input" placeholder="INV-XXXX">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('customer') ?></label>
                        <input type="text" name="customer_name" class="form-input" value="<?= e($p['customer_name'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('amount') ?></label>
                        <input type="number" name="amount" class="form-input" step="0.01" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('remark') ?></label>
                        <input type="text" name="remark" class="form-input">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="height:36px;">+ <?= __('add') ?></button>
                </div>
            </form>
        </div>

        <!-- Invoice Table -->
        <table class="data-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th style="width:40px;">Items</th>
                    <th style="width:90px;"><?= __('date') ?></th>
                    <th>Invoice No.</th>
                    <th><?= __('customer') ?></th>
                    <th class="text-right" style="width:130px;"><?= __('amount') ?></th>
                    <th><?= __('remark') ?></th>
                    <th style="width:40px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoices)): ?>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td class="text-center"><?= e($inv['line_no']) ?></td>
                        <td><?= e($inv['invoice_date'] ?? '') ?></td>
                        <td><?= e($inv['invoice_no'] ?? '') ?></td>
                        <td><?= e($inv['customer_name'] ?? '') ?></td>
                        <td class="text-right" style="font-weight:600;"><?= formatMoney($inv['amount'] ?? 0) ?></td>
                        <td style="font-size:11px;"><?= e($inv['remark'] ?? '') ?></td>
                        <td class="text-center">
                            <form method="POST" action="/projects/<?= e($p['project_id']) ?>/invoices/<?= e($inv['invoice_id']) ?>/delete" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')"
                                        style="background:none;border:none;cursor:pointer;font-size:12px;color:#F44336;">&#128465;</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;background:#f5f7fa;">
                    <td colspan="4" class="text-right"><?= __('total_revenue_amount') ?></td>
                    <td class="text-right" style="color:#003366;font-size:14px;"><?= formatMoney($invTotal) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- ===== Purchases Tab ===== -->
    <div id="panel-purchases" class="tab-panel" style="padding:20px;display:none;">
        <!-- Add Purchase Form -->
        <div style="background:var(--color-bg-secondary);border-radius:8px;padding:14px;margin-bottom:16px;">
            <form method="POST" action="/projects/<?= e($p['project_id']) ?>/purchases">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <div style="display:grid;grid-template-columns:100px 1fr 1fr 120px 120px 1fr 80px;gap:8px;align-items:end;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('date') ?></label>
                        <input type="date" name="purchase_date" class="form-input" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('purchase_desc') ?></label>
                        <input type="text" name="description" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('supplier') ?></label>
                        <input type="text" name="supplier_name" class="form-input">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('amount') ?> (Baht)</label>
                        <input type="number" name="amount" class="form-input" step="0.01" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;">PO No.</label>
                        <input type="text" name="po_no" class="form-input" placeholder="PO-XXXX">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:11px;"><?= __('payment_terms') ?></label>
                        <input type="text" name="payment_terms" class="form-input" placeholder="Credit 30 days">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="height:36px;">+ <?= __('add') ?></button>
                </div>
            </form>
        </div>

        <!-- Purchase Table -->
        <table class="data-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th style="width:40px;">Items</th>
                    <th style="width:90px;"><?= __('date') ?></th>
                    <th>Invoice No.</th>
                    <th><?= __('purchase_desc') ?></th>
                    <th class="text-right" style="width:130px;"><?= __('amount') ?> (Baht)</th>
                    <th><?= __('payment_terms') ?></th>
                    <th>PO No.</th>
                    <th style="width:40px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($purchases)): ?>
                    <?php foreach ($purchases as $pur): ?>
                    <tr>
                        <td class="text-center"><?= e($pur['line_no']) ?></td>
                        <td><?= e($pur['purchase_date'] ?? '') ?></td>
                        <td><?= e($pur['purchase_invoice_no'] ?? '') ?></td>
                        <td><?= e($pur['description'] ?? '') ?>
                            <?php if (!empty($pur['supplier_name'])): ?>
                                <div style="font-size:10px;color:var(--color-text-muted);"><?= e($pur['supplier_name'] ?? '') ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right" style="font-weight:600;"><?= formatMoney($pur['amount'] ?? 0) ?></td>
                        <td style="font-size:11px;"><?= e($pur['payment_terms'] ?? '') ?></td>
                        <td style="font-size:11px;">
                            <?php if (!empty($pur['po_id'])): ?>
                                <a href="/purchasing/orders/<?= e($pur['po_id']) ?>"><?= e($pur['po_no']) ?></a>
                            <?php else: ?>
                                <?= e($pur['po_no'] ?? '') ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="/projects/<?= e($p['project_id']) ?>/purchases/<?= e($pur['purchase_id']) ?>/delete" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" onclick="return confirm('<?= __('confirm_delete') ?>')"
                                        style="background:none;border:none;cursor:pointer;font-size:12px;color:#F44336;">&#128465;</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;background:#f5f7fa;">
                    <td colspan="4" class="text-right"><?= __('total_cost_amount') ?></td>
                    <td class="text-right" style="color:#003366;font-size:14px;"><?= formatMoney($purTotal) ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <!-- ===== Linked POs Tab ===== -->
    <div id="panel-linkedpos" class="tab-panel" style="padding:20px;display:none;">
        <?php if (empty($linkedPOs ?? [])): ?>
            <div style="padding:30px;text-align:center;color:var(--color-text-muted);">
                <?= __('no_linked_po') ?>
                <br><br>
                <span style="font-size:12px;"><?= __('linked_po_hint') ?></span>
            </div>
        <?php else:
            $poStatusBadge = [
                'PENDING'   => ['bg' => '#FF9800', 'label' => __('po_status_pending')],
                'APPROVED'  => ['bg' => '#34A853', 'label' => __('po_status_approved')],
                'CANCELLED' => ['bg' => '#9E9E9E', 'label' => __('po_status_cancelled')],
            ];
            $poGrandTotal = 0;
            foreach ($linkedPOs as $po) { if ($po['status'] !== 'CANCELLED') $poGrandTotal += floatval($po['payment_amount'] ?? 0); }
        ?>
            <div style="background:#E3F2FD;border-radius:6px;padding:10px 14px;margin-bottom:12px;display:flex;gap:24px;font-size:13px;">
                <span><strong><?= count($linkedPOs) ?></strong> <?= __('records') ?></span>
                <span><?= __('total_excl_cancelled') ?>: <strong><?= formatMoney($poGrandTotal) ?></strong></span>
            </div>
            <table class="data-table" style="font-size:12px;">
                <thead>
                <tr>
                    <th><?= __('po_no') ?></th>
                    <th><?= __('order_date') ?></th>
                    <th><?= __('supplier') ?></th>
                    <th class="text-right"><?= __('subtotal') ?></th>
                    <th class="text-right">VAT</th>
                    <th class="text-right"><?= __('payment_amount') ?></th>
                    <th class="text-center"><?= __('status') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($linkedPOs as $po):
                    $badge = $poStatusBadge[$po['status']] ?? ['bg' => '#888', 'label' => $po['status']];
                ?>
                    <tr>
                        <td>
                            <a href="/purchasing/orders/<?= e($po['po_id']) ?>" style="font-weight:600;">
                                <?= e($po['po_no']) ?>
                            </a>
                        </td>
                        <td><?= e($po['order_date']) ?></td>
                        <td><?= e($po['supplier_name']) ?> <span style="color:#888;">(<?= e($po['supplier_code']) ?>)</span></td>
                        <td class="text-right"><?= formatMoney($po['subtotal_thb'] ?? 0) ?></td>
                        <td class="text-right"><?= formatMoney($po['vat_amount'] ?? 0) ?></td>
                        <td class="text-right"><strong><?= formatMoney($po['payment_amount'] ?? 0) ?></strong></td>
                        <td class="text-center">
                            <span class="badge" style="background:<?= e($badge['bg']) ?>;color:#fff;font-size:10px;padding:3px 8px;border-radius:4px;">
                                <?= e($badge['label']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.budget-info td { padding:5px 8px; border-bottom:1px solid #eee; }
.budget-info .lbl { color:var(--color-text-muted); font-size:12px; white-space:nowrap; }
.budget-info .val { font-size:13px; }
.budget-info .val.num { text-align:right; font-family:monospace; }
</style>

<script>
function showPjTab(name) {
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.style.display = 'none'; });
    document.querySelectorAll('.tab-btn').forEach(function(b) {
        b.style.borderBottom = 'none';
        b.style.color = 'var(--color-text-muted)';
    });
    document.getElementById('panel-' + name).style.display = 'block';
    var btn = document.getElementById('tab-' + name);
    btn.style.borderBottom = '2px solid var(--color-primary)';
    btn.style.color = 'var(--color-primary)';
}
// Auto-switch tab if URL hash
if (window.location.hash === '#purchases') showPjTab('purchases');
else if (window.location.hash === '#invoices') showPjTab('invoices');
</script>
