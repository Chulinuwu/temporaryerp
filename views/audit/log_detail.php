<?php
/**
 * PEGASUS ERP — Audit Log Detail (admin only)
 * Variables: $log
 */
$opLabel = ['I' => 'INSERT', 'U' => 'UPDATE', 'D' => 'DELETE'];
$opColor = ['I' => '#4CAF50', 'U' => '#FB8C00', 'D' => '#D32F2F'];

$old = is_string($log['old_values']) ? json_decode($log['old_values'], true) : ($log['old_values'] ?? []);
$new = is_string($log['new_values']) ? json_decode($log['new_values'], true) : ($log['new_values'] ?? []);
$old = is_array($old) ? $old : [];
$new = is_array($new) ? $new : [];

$allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
sort($allKeys);
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= __('menu_audit_log') ?> #<?= e($log['log_id']) ?></h1>
        <div class="breadcrumb">
            <a href="/admin/audit-logs"><?= __('menu_audit_log') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">#<?= e($log['log_id']) ?></span>
        </div>
    </div>
    <a href="/admin/audit-logs" class="btn btn-cancel"><?= __('back') ?></a>
</div>

<div class="card" style="padding:16px 20px;margin-bottom:16px;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;font-size:13px;">
        <div><strong><?= __('table') ?>:</strong> <?= e($log['table_name']) ?></div>
        <div><strong><?= __('record_id') ?>:</strong> <?= e($log['record_id']) ?></div>
        <div><strong><?= __('operation') ?>:</strong>
            <span class="badge" style="background:<?= e($opColor[$log['operation']] ?? '#888') ?>;color:#fff;">
                <?= e($opLabel[$log['operation']] ?? $log['operation']) ?>
            </span>
        </div>
        <div><strong><?= __('changed_at') ?>:</strong> <?= e($log['changed_at']) ?></div>
        <div><strong><?= __('changed_by') ?>:</strong> <?= e($log['changed_by_full_name'] ?? $log['changed_by_name'] ?? '—') ?></div>
        <div><strong>IP:</strong> <?= e($log['ip_address'] ?? '—') ?></div>
    </div>
</div>

<div class="card" style="padding:16px 20px;">
    <h2 style="margin-bottom:10px;"><?= __('field_changes') ?></h2>
    <table class="data-table" style="font-size:12px;">
        <thead>
        <tr>
            <th style="width:200px;"><?= __('field') ?></th>
            <th><?= __('old_value') ?></th>
            <th><?= __('new_value') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($allKeys as $k):
            $ov = $old[$k] ?? null;
            $nv = $new[$k] ?? null;
            $changed = ($ov !== $nv);
        ?>
            <tr style="<?= $changed ? 'background:#FFFDE7;' : '' ?>">
                <td style="font-weight:600;"><?= e($k) ?></td>
                <td style="font-family:Consolas,monospace;color:#D32F2F;">
                    <?= e(is_array($ov) || is_object($ov) ? json_encode($ov, JSON_UNESCAPED_UNICODE) : (string)($ov ?? '—')) ?>
                </td>
                <td style="font-family:Consolas,monospace;color:#388E3C;">
                    <?= e(is_array($nv) || is_object($nv) ? json_encode($nv, JSON_UNESCAPED_UNICODE) : (string)($nv ?? '—')) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
