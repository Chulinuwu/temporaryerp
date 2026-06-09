<?php
/**
 * PEGASUS ERP - Quotation Detail View
 * Variables: $quotation, $lines
 */
$q = $quotation;
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= __('quotation') ?> #<?= htmlspecialchars($q['quotation_no']) ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= __('dashboard') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/sales/quotations"><?= __('quotations') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= htmlspecialchars($q['quotation_no']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php $st = $q['status'] ?? 'DRAFT'; ?>
        <?php if (in_array($st, ['DRAFT','INTERNAL_REVIEW'], true)): ?>
            <form method="POST" action="/sales/quotations/<?= $q['quotation_id'] ?>/submit" style="display:inline;" onsubmit="return confirm('<?= __('submit_for_approval') ?>?');">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-primary"><?= __('submit_for_approval') ?></button>
            </form>
        <?php elseif ($st === 'PENDING_APPROVAL' && Auth::isDirectorOrAbove()): ?>
            <form method="POST" action="/sales/quotations/<?= $q['quotation_id'] ?>/approve" style="display:inline;" onsubmit="return confirm('<?= __('approve') ?>?');">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-primary" style="background:#2e7d32;"><?= __('approve') ?></button>
            </form>
            <form method="POST" action="/sales/quotations/<?= $q['quotation_id'] ?>/reject" style="display:inline;" onsubmit="return confirm('<?= __('reject') ?>?');">
                <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-cancel" style="color:#c62828;"><?= __('reject') ?></button>
            </form>
        <?php endif; ?>
        <form method="POST" action="/sales/quotations/<?= $q['quotation_id'] ?>/copy" style="display:inline;">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-cancel">&#128203; <?= __('copy') ?></button>
        </form>
        <a href="/sales/quotations/<?= $q['quotation_id'] ?>/edit" class="btn btn-primary"><?= __('edit') ?></a>
        <a href="/pdf/quotation/<?= $q['quotation_id'] ?>" target="_blank" class="btn btn-cancel"><?= __('print_pdf') ?></a>
        <a href="/sales/quotations" class="btn btn-cancel"><?= __('back_to_list') ?></a>
    </div>
</div>

<!-- Header Info -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= __('quotation_details') ?></h3>
        <span class="badge badge-<?= strtolower($q['status'] ?? 'draft') ?>"><?= htmlspecialchars($q['status'] ?? 'DRAFT') ?></span>
    </div>
    <div class="card-body">
        <div class="info-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;">
            <div><strong><?= __('customer') ?>:</strong> <?= e($q['customer_name'] ?? '') ?></div>
            <div><strong><?= __('issue_date') ?>:</strong> <?= htmlspecialchars($q['issue_date'] ?? '') ?></div>
            <div><strong><?= __('expiry_date') ?>:</strong> <?= htmlspecialchars($q['expiry_date'] ?? '-') ?></div>
            <div><strong><?= __('currency') ?>:</strong> <?= htmlspecialchars($q['currency_code'] ?? 'THB') ?></div>
            <div><strong><?= __('payment_terms') ?>:</strong> <?= htmlspecialchars($q['payment_term_name'] ?? '-') ?></div>
            <div><strong><?= __('project_name') ?>:</strong> <?= htmlspecialchars($q['project_name'] ?? '-') ?></div>
            <div><strong><?= __('attention_name') ?>:</strong> <?= htmlspecialchars($q['attention_name'] ?? '-') ?></div>
            <div><strong><?= __('attention_email') ?>:</strong> <?= htmlspecialchars($q['attention_email'] ?? '-') ?></div>
        </div>
    </div>
</div>

<!-- Line Items -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= __('line_items') ?></h3>
    </div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th><?= __('description') ?></th>
                    <th style="width:70px;" class="text-right"><?= __('qty') ?></th>
                    <th style="width:60px;"><?= __('unit') ?></th>
                    <th style="width:110px;" class="text-right"><?= __('cost_price') ?></th>
                    <th style="width:110px;" class="text-right"><?= __('selling_price') ?></th>
                    <th style="width:120px;" class="text-right"><?= __('cost_amount') ?></th>
                    <th style="width:120px;" class="text-right"><?= __('selling_amount') ?></th>
                    <th style="width:110px;" class="text-right"><?= __('profit') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalCost = 0;
                $totalSelling = 0;
                foreach ($lines as $line):
                    $isCat = !empty($line['is_category_row']);
                    if ($isCat):
                ?>
                    <tr style="background:#e8f0fe;">
                        <td style="font-weight:700;color:#003366;"><?= htmlspecialchars($line['line_no']) ?></td>
                        <td colspan="8" style="font-weight:700;color:#003366;">
                            <?= htmlspecialchars($line['item_description']) ?>
                        </td>
                    </tr>
                <?php else:
                    $qty = floatval($line['quantity'] ?? 0);
                    $costUnit = floatval($line['cost_total'] ?? 0);
                    $unitPrice = floatval($line['unit_price'] ?? 0);
                    $costAmt = $qty * $costUnit;
                    $sellAmt = $qty * $unitPrice;
                    $profit = $sellAmt - $costAmt;
                    $totalCost += $costAmt;
                    $totalSelling += $sellAmt;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($line['line_no']) ?></td>
                        <td style="padding-left:20px;"><?= htmlspecialchars($line['item_description']) ?></td>
                        <td class="text-right"><?= number_format($qty, 2) ?></td>
                        <td><?= htmlspecialchars($line['unit'] ?? '') ?></td>
                        <td class="text-right"><?= formatMoney($costUnit) ?></td>
                        <td class="text-right"><?= formatMoney($unitPrice) ?></td>
                        <td class="text-right"><?= formatMoney($costAmt) ?></td>
                        <td class="text-right"><?= formatMoney($sellAmt) ?></td>
                        <td class="text-right" style="color:<?= $profit >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>;">
                            <?= formatMoney($profit) ?>
                        </td>
                    </tr>
                <?php endif; endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;background:var(--color-gray-50);">
                    <td colspan="6" class="text-right"><?= __('total') ?></td>
                    <td class="text-right"><?= formatMoney($totalCost) ?></td>
                    <td class="text-right"><?= formatMoney($totalSelling) ?></td>
                    <td class="text-right" style="color:<?= ($totalSelling - $totalCost) >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>;">
                        <?= formatMoney($totalSelling - $totalCost) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Summary -->
<div class="form-grid-2">
    <div class="card">
        <div class="card-header"><h3 class="card-title"><?= __('remarks') ?></h3></div>
        <div class="card-body">
            <p><?= nl2br(htmlspecialchars($q['remark_text'] ?? '-')) ?></p>
            <?php if (!empty($q['note_text'])): ?>
                <hr>
                <p><strong><?= __('internal_notes') ?>:</strong></p>
                <p><?= nl2br(htmlspecialchars($q['note_text'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><?= __('summary') ?></h3></div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;">
                    <span><?= __('subtotal') ?></span>
                    <span style="font-weight:600;"><?= formatMoney($q['subtotal_thb'] ?? 0) ?></span>
                </div>
                <?php if (floatval($q['discount_amount'] ?? 0) > 0): ?>
                <div style="display:flex;justify-content:space-between;">
                    <span><?= __('discount') ?></span>
                    <span style="font-weight:600;color:var(--color-danger);">-<?= formatMoney($q['discount_amount']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;">
                    <span><?= __('vat_rate') ?></span>
                    <span><?= htmlspecialchars($q['vat_rate'] ?? 7) ?>%</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span><?= __('vat_amount') ?></span>
                    <span style="font-weight:600;"><?= formatMoney($q['vat_amount'] ?? 0) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:2px solid var(--color-border);padding-top:10px;">
                    <span style="font-size:16px;font-weight:700;"><?= __('grand_total') ?></span>
                    <span style="font-size:16px;font-weight:700;color:var(--color-primary);"><?= formatMoney($q['grand_total_thb'] ?? 0) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:8px;">
                    <span style="color:var(--color-success);font-weight:600;"><?= __('total_profit') ?></span>
                    <span style="font-weight:600;color:<?= ($totalSelling - $totalCost) >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>;">
                        <?= formatMoney($totalSelling - $totalCost) ?>
                        (<?= $totalSelling > 0 ? number_format(($totalSelling - $totalCost) / $totalSelling * 100, 1) : '0.0' ?>%)
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inspection Schedule (検収スケジュール) -->
<?php if (!empty($inspections)): ?>
<div class="card" style="margin-top:16px;">
    <div class="card-header"><h3 class="card-title">🗓 <?= __('inspection_schedule') ?></h3></div>
    <div class="card-body" style="padding:0;">
        <table class="data-table" style="margin:0;font-size:12px;">
            <thead><tr style="background:#FAFAFA;">
                <th style="width:50px;">#</th>
                <th><?= __('description') ?></th>
                <th class="text-right" style="width:80px;">%</th>
                <th class="text-right" style="width:140px;"><?= __('amount') ?> (THB)</th>
                <th style="width:140px;"><?= __('inspection_date') ?></th>
                <th style="width:130px;" class="text-center"><?= __('status') ?></th>
            </tr></thead>
            <tbody>
            <?php
            $statusColors = [
                'PENDING'     => '#9E9E9E',
                'IN_PROGRESS' => '#1976D2',
                'DELIVERED'   => '#FB8C00',
                'INSPECTED'   => '#4CAF50',
                'CANCELLED'   => '#D32F2F',
            ];
            $tot = 0;
            foreach ($inspections as $ins):
                $tot += floatval($ins['amount']);
                $c = $statusColors[$ins['status']] ?? '#888';
            ?>
                <tr>
                    <td class="text-center"><strong><?= e($ins['seq_no']) ?></strong></td>
                    <td><?= e($ins['description'] ?? '—') ?></td>
                    <td class="text-right"><?= number_format(floatval($ins['percentage']), 2) ?>%</td>
                    <td class="text-right"><strong><?= number_format(floatval($ins['amount']), 2) ?></strong></td>
                    <td><?= e($ins['expected_inspection_date'] ?? '—') ?></td>
                    <td class="text-center">
                        <span class="badge" style="background:<?= e($c) ?>;color:#fff;"><?= e($ins['status']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#E3F2FD;font-weight:700;">
                    <td colspan="3" class="text-right"><?= __('total') ?></td>
                    <td class="text-right"><?= number_format($tot, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
.badge-draft { background:#6c757d; color:#fff; padding:4px 12px; border-radius:4px; }
.badge-submitted { background:#1a73e8; color:#fff; padding:4px 12px; border-radius:4px; }
.badge-approved { background:#34a853; color:#fff; padding:4px 12px; border-radius:4px; }
.badge-rejected { background:#ea4335; color:#fff; padding:4px 12px; border-radius:4px; }
</style>
