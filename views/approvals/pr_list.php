<?php
/**
 * Purchase Request approval queue.
 * Vars: $rows, $status, $statusOptions
 *
 * Approval actions live on the PR detail page (which knows the current step).
 */
$statusColor = [
    'DRAFT'              => '#9E9E9E',
    'SUBMITTED'          => '#FB8C00',
    'QUOTES_PENDING'     => '#F57C00',
    'PENDING_MANAGER'    => '#1976D2',
    'PENDING_CEO'        => '#6A1B9A',
    'APPROVED'           => '#2E7D32',
    'REJECTED'           => '#D32F2F',
    'CONVERTED'          => '#0277BD',
    'CANCELLED'          => '#757575',
];
$stepLabel = [
    'SUBMITTED'        => __('pr_step_purchasing_pickup'),
    'QUOTES_PENDING'   => __('pr_step_collect_quotes'),
    'PENDING_MANAGER'  => __('pr_step_manager'),
    'PENDING_CEO'      => __('pr_step_ceo'),
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">✅ <?= __('approval_queue_prs') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= __('approval_queue_prs') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="/approvals/customers" class="btn btn-cancel btn-sm">👤 <?= __('menu_customers') ?></a>
        <a href="/approvals/suppliers" class="btn btn-cancel btn-sm">🏭 <?= __('menu_suppliers') ?></a>
        <a href="/approvals/purchase-requests" class="btn btn-primary btn-sm">📝 <?= __('menu_purchase_requests') ?></a>
        <a href="/approvals/quotations" class="btn btn-cancel btn-sm">📋 <?= __('menu_quotations') ?></a>
        <a href="/approvals/purchase-orders" class="btn btn-cancel btn-sm">📦 <?= __('menu_purchase_orders') ?></a>
    </div>
</div>

<div class="card" style="padding:12px 20px;margin-bottom:12px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <label><?= __('status') ?>:</label>
        <select name="status" class="form-select" style="width:220px;" onchange="this.form.submit()">
            <?php foreach ($statusOptions as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
            <?php endforeach; ?>
        </select>
        <span style="font-size:12px;color:#888;">
            <?= count($rows) ?> <?= __('records') ?>
        </span>
    </form>
</div>

<div class="card" style="padding:0;overflow-x:auto;">
<table class="data-table" style="font-size:12px;">
    <thead>
    <tr>
        <th style="width:140px;"><?= __('pr_no') ?></th>
        <th><?= __('requester') ?></th>
        <th><?= __('project') ?></th>
        <th><?= __('suggested_supplier') ?></th>
        <th class="text-right" style="width:120px;"><?= __('est_total') ?></th>
        <th class="text-center" style="width:80px;"><?= __('quotes_collected') ?? 'Quotes' ?></th>
        <th style="width:170px;"><?= __('status') ?></th>
        <th style="width:160px;"><?= __('pr_current_step') ?></th>
        <th style="width:110px;"><?= __('request_date') ?></th>
        <th style="width:120px;text-align:center;"><?= __('actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr><td colspan="10" style="text-align:center;padding:24px;color:#888;"><?= __('no_data') ?></td></tr>
    <?php else: foreach ($rows as $r):
        $reqName = $r['requester_name_jp'] ?: $r['requester_name_th'] ?: '-';
        $color   = $statusColor[$r['status']] ?? '#666';
        $step    = $stepLabel[$r['status']] ?? '—';
        $quotesOk= ((int)$r['quote_count']) >= 3;
    ?>
        <tr>
            <td>
                <a href="/purchasing/requests/<?= (int)$r['pr_id'] ?>"
                   style="color:#1976D2;font-weight:600;">
                    <?= e($r['pr_no']) ?>
                </a>
                <?php $stepper = ApprovalFlow::prStepper($r); ?>
            </td>
            <td><?= e($reqName) ?></td>
            <td>
                <?php if (!empty($r['pj_no'])): ?>
                    <span style="color:#0277BD;"><?= e($r['pj_no']) ?></span>
                    <?php if (!empty($r['pj_name'])): ?>
                        <small style="color:#666;"> — <?= e(mb_substr($r['pj_name'], 0, 25)) ?></small>
                    <?php endif; ?>
                <?php else: ?><span style="color:#bbb;">—</span><?php endif; ?>
            </td>
            <td><?= e($r['suggested_supplier_name'] ?? '—') ?></td>
            <td class="text-right">฿<?= number_format((float)$r['est_total_thb'], 2) ?></td>
            <td class="text-center">
                <span style="background:<?= $quotesOk ? '#A5D6A7' : '#FFE082' ?>;
                             padding:2px 8px;border-radius:10px;font-weight:600;">
                    <?= (int)$r['quote_count'] ?>/3
                </span>
            </td>
            <td>
                <span style="background:<?= $color ?>;color:white;padding:3px 8px;
                             border-radius:3px;font-size:11px;font-weight:600;">
                    <?= e($r['status']) ?>
                </span>
            </td>
            <td><span style="color:#555;"><?= e($step) ?></span></td>
            <td><?= e($r['request_date']) ?></td>
            <td class="text-center">
                <a href="/purchasing/requests/<?= (int)$r['pr_id'] ?>"
                   class="btn btn-sm btn-primary"
                   title="<?= __('review_and_approve') ?? 'Review & approve' ?>">
                    👁 <?= __('review') ?? 'Review' ?>
                </a>
            </td>
        </tr>
        <tr style="background:#FAFAFA;">
            <td colspan="10" style="padding:6px 16px;">
                <?php include __DIR__ . '/../partials/approval_stepper.php'; ?>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<div style="margin-top:12px;padding:8px 12px;background:#F5F5F5;border-radius:4px;font-size:12px;color:#666;">
    💡 <?= __('pr_approval_hint') ?>
</div>
