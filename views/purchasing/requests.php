<?php
/** Variables: $rows, $status, $q, $canPurchasingApprove, $canManagerApprove */
$statusClasses = [
    'DRAFT'              => 'badge-draft',
    'SUBMITTED'          => 'badge-pending',
    'PENDING_PURCHASING' => 'badge-pending',
    'PENDING_MANAGER'    => 'badge-open',
    'APPROVED'           => 'badge-approved',
    'REJECTED'           => 'badge-rejected',
    'CONVERTED'          => 'badge-paid',
    'CANCELLED'          => 'badge-rejected',
];
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('menu_purchase_requests') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/purchasing/orders"><?= _e('purchasing') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_purchase_requests') ?></span>
        </div>
    </div>
    <a href="/purchasing/requests/create" class="btn btn-primary"><?= _e('pr_new') ?></a>
</div>

<div class="card" style="padding:12px 20px;">
    <form method="GET" action="/purchasing/requests" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <select name="status" class="form-select" style="width:200px;">
            <option value=""><?= _e('all_statuses') ?></option>
            <?php foreach (['DRAFT','SUBMITTED','PENDING_PURCHASING','PENDING_MANAGER','APPROVED','REJECTED','CONVERTED','CANCELLED'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="q" class="form-input" placeholder="PR No / justification" style="width:280px;" value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary"><?= _e('filter') ?></button>
        <a href="/purchasing/requests" class="btn btn-secondary"><?= _e('clear') ?></a>
    </form>
</div>

<div class="card" style="margin-top:16px;">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= _e('pr_no') ?></th>
                <th><?= _e('request_date') ?></th>
                <th><?= _e('requester') ?></th>
                <th><?= _e('suggested_supplier') ?></th>
                <th style="text-align:right;"><?= _e('est_total') ?></th>
                <th><?= _e('needed_by') ?></th>
                <th><?= _e('status') ?></th>
                <th><?= _e('po_no') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" style="text-align:center;padding:32px;color:#888;"><?= _e('no_data') ?></td></tr>
            <?php else: foreach ($rows as $r):
                $reqName = $r['requester_name_jp'] ?: $r['requester_name_th'] ?: '-';
            ?>
                <tr>
                    <td><a href="/purchasing/requests/<?= (int)$r['pr_id'] ?>"><?= htmlspecialchars($r['pr_no']) ?></a></td>
                    <td><?= htmlspecialchars($r['request_date']) ?></td>
                    <td><?= htmlspecialchars($reqName) ?></td>
                    <td><?= htmlspecialchars($r['suggested_supplier_name'] ?? '-') ?></td>
                    <td style="text-align:right;">฿<?= number_format((float)$r['est_total_thb'], 2) ?></td>
                    <td><?= htmlspecialchars($r['needed_by_date'] ?? '-') ?></td>
                    <td><span class="badge <?= $statusClasses[$r['status']] ?? 'badge-draft' ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                    <td>
                        <?php if (!empty($r['converted_po_no'])): ?>
                            <a href="/purchasing/orders/<?= (int)$r['converted_po_id'] ?>"><?= htmlspecialchars($r['converted_po_no']) ?></a>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td><a href="/purchasing/requests/<?= (int)$r['pr_id'] ?>" class="btn btn-sm btn-secondary"><?= _e('view') ?></a></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
