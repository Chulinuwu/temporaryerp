<?php
/**
 * Unified approval queue
 * Vars: $entity ('customer'|'supplier'|'quotation'|'po'), $entityTitle, $rows,
 *       $status, $statusOptions (for docs), $detailUrl (callable)
 */
$actionBase = match ($entity) {
    'customer'  => '/approvals/customers',
    'supplier'  => '/approvals/suppliers',
    'quotation' => '/approvals/quotations',
    'po'        => '/approvals/purchase-orders',
    default     => '/approvals',
};

$statusColor = [
    'PENDING'          => '#FB8C00',
    'PENDING_APPROVAL' => '#FB8C00',
    'DRAFT'            => '#9E9E9E',
    'APPROVED'         => '#2E7D32',
    'REJECTED'         => '#D32F2F',
    'CANCELLED'        => '#757575',
];
$statusOptions = $statusOptions ?? ['PENDING', 'APPROVED', 'REJECTED'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">✅ <?= __('approval_queue') ?>: <?= e($entityTitle) ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= e($entityTitle) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/approvals/customers" class="btn btn-cancel btn-sm<?= $entity==='customer'?' btn-primary':'' ?>">👤 <?= __('menu_customers') ?></a>
        <a href="/approvals/suppliers" class="btn btn-cancel btn-sm<?= $entity==='supplier'?' btn-primary':'' ?>">🏭 <?= __('menu_suppliers') ?></a>
        <a href="/approvals/quotations" class="btn btn-cancel btn-sm<?= $entity==='quotation'?' btn-primary':'' ?>">📋 <?= __('menu_quotations') ?></a>
        <a href="/approvals/purchase-requests" class="btn btn-cancel btn-sm">📝 <?= __('menu_purchase_requests') ?></a>
        <a href="/approvals/purchase-orders" class="btn btn-cancel btn-sm<?= $entity==='po'?' btn-primary':'' ?>">📦 <?= __('menu_purchase_orders') ?></a>
    </div>
</div>

<div class="card" style="padding:12px 20px;margin-bottom:12px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <label><?= __('status') ?>:</label>
        <select name="status" class="form-select" style="width:200px;" onchange="this.form.submit()">
            <?php foreach ($statusOptions as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>>
                    <?= e($opt) ?>
                </option>
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
        <th style="width:120px;"><?= __('code') ?></th>
        <th><?= __('name') ?></th>
        <?php if ($entity === 'quotation'): ?>
            <th><?= __('customer') ?></th>
            <th class="text-right" style="width:130px;"><?= __('amount') ?></th>
        <?php elseif ($entity === 'po'): ?>
            <th><?= __('supplier') ?></th>
            <th class="text-right" style="width:130px;"><?= __('amount') ?></th>
        <?php else: ?>
            <th><?= __('tax_id') ?? 'Tax ID' ?></th>
            <th><?= __('country') ?></th>
        <?php endif; ?>
        <th style="width:150px;"><?= __('status') ?></th>
        <th style="width:140px;"><?= __('requested_by') ?></th>
        <th style="width:130px;"><?= __('created_at') ?></th>
        <th style="width:240px;text-align:center;"><?= __('actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-center" style="padding:40px;color:#888;">
            <?= __('no_pending_approvals') ?>
        </td></tr>
    <?php else: foreach ($rows as $r):
        $sc = $statusColor[$r['approval_status']] ?? '#888';
        $isPending = in_array($r['approval_status'], ['PENDING','PENDING_APPROVAL','PENDING_MANAGER','PENDING_CEO','DRAFT'], true);
        // Build stepper for master entities (customer / supplier)
        $stepper = null;
        if (in_array($entity, ['customer','supplier'], true)) {
            $stepper = ApprovalFlow::masterStepper($r);
        }
    ?>
        <tr>
            <td><a href="<?= e($detailUrl($r['id'])) ?>" target="_blank"><strong><?= e($r['code']) ?></strong></a></td>
            <td><?= e($r['name']) ?></td>
            <?php if ($entity === 'quotation'): ?>
                <td style="font-size:11px;"><?= e($r['customer'] ?? '—') ?></td>
                <td class="text-right"><?= number_format(floatval($r['amount']), 0) ?></td>
            <?php elseif ($entity === 'po'): ?>
                <td style="font-size:11px;"><?= e($r['supplier'] ?? '—') ?></td>
                <td class="text-right"><?= number_format(floatval($r['amount']), 0) ?></td>
            <?php else: ?>
                <td style="font-size:11px;"><?= e($r['tax_id'] ?? '—') ?></td>
                <td><?= e($r['country'] ?? '—') ?></td>
            <?php endif; ?>
            <td>
                <span class="badge" style="background:<?= e($sc) ?>;color:#fff;">
                    <?= e($r['approval_status']) ?>
                </span>
                <?php if ($entity === 'supplier' && !empty($r['attachment_count'])): ?>
                    <br><small style="color:#1976D2;">📎 <?= (int)$r['attachment_count'] ?> docs</small>
                <?php endif; ?>
            </td>
            <td style="font-size:10px;"><?= e($r['created_by_email'] ?? '—') ?></td>
            <td style="font-size:10px;"><?= e(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
            <td class="text-center">
                <a href="<?= e($detailUrl($r['id'])) ?>" target="_blank" class="btn btn-cancel btn-sm" style="padding:3px 10px;font-size:11px;">
                    👁 <?= __('view') ?>
                </a>
                <?php if ($isPending): ?>
                    <form method="POST" action="<?= e($actionBase) ?>/<?= e($r['id']) ?>/approve" style="display:inline;"
                          onsubmit="return confirm('<?= __('confirm_approve') ?>');">
                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-primary btn-sm" style="padding:3px 10px;font-size:11px;background:#2E7D32;">
                            ✓ <?= __('approve') ?>
                        </button>
                    </form>
                    <form method="POST" action="<?= e($actionBase) ?>/<?= e($r['id']) ?>/reject" style="display:inline;"
                          onsubmit="return confirm('<?= __('confirm_reject') ?>');">
                        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-cancel btn-sm" style="padding:3px 10px;font-size:11px;color:#D32F2F;">
                            ✕ <?= __('reject') ?>
                        </button>
                    </form>
                <?php else: ?>
                    <span style="font-size:10px;color:#888;">
                        <?= !empty($r['approved_at']) ? __('approved_at') . ': ' . e(date('Y-m-d', strtotime($r['approved_at']))) : '—' ?>
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($stepper): ?>
        <tr style="background:#FAFAFA;">
            <td colspan="8" style="padding:6px 16px;">
                <?php include __DIR__ . '/../partials/approval_stepper.php'; ?>
            </td>
        </tr>
        <?php endif; ?>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<div style="font-size:11px;color:#888;margin-top:10px;">
    💡 <?= __('approval_hint') ?>
</div>
