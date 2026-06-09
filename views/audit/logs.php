<?php
/**
 * PEGASUS ERP — Audit Logs (admin only)
 * Variables: $logs, $tables, $users, $filters
 */
$opLabel = ['I' => 'INSERT', 'U' => 'UPDATE', 'D' => 'DELETE'];
$opColor = ['I' => '#4CAF50', 'U' => '#FB8C00', 'D' => '#D32F2F'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('menu_audit_log') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_audit_log') ?></span>
        </div>
    </div>
</div>

<div class="card" style="padding:12px 20px;margin-bottom:16px;">
    <form method="GET" action="/admin/audit-logs" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <select name="table" class="form-select" style="width:200px;">
            <option value=""><?= __('all_tables') ?></option>
            <?php foreach ($tables as $t): ?>
                <option value="<?= e($t['table_name']) ?>" <?= ($filters['table'] ?? '') === $t['table_name'] ? 'selected' : '' ?>>
                    <?= e($t['table_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="operation" class="form-select" style="width:140px;">
            <option value=""><?= __('all_operations') ?></option>
            <option value="I" <?= ($filters['operation'] ?? '') === 'I' ? 'selected' : '' ?>>INSERT</option>
            <option value="U" <?= ($filters['operation'] ?? '') === 'U' ? 'selected' : '' ?>>UPDATE</option>
            <option value="D" <?= ($filters['operation'] ?? '') === 'D' ? 'selected' : '' ?>>DELETE</option>
        </select>
        <select name="user" class="form-select" style="width:180px;">
            <option value=""><?= __('all_users') ?></option>
            <?php foreach ($users as $u): ?>
                <option value="<?= e($u['user_id']) ?>" <?= ($filters['user'] ?? '') == $u['user_id'] ? 'selected' : '' ?>>
                    <?= e($u['display_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" class="form-input" style="width:150px;" value="<?= e($filters['date_from'] ?? '') ?>">
        <input type="date" name="date_to" class="form-input" style="width:150px;" value="<?= e($filters['date_to'] ?? '') ?>">
        <input type="text" name="q" class="form-input" style="width:180px;" value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= _e('search') ?>">
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/admin/audit-logs" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<div class="table-wrapper" style="overflow-x:auto;">
<table class="data-table" style="min-width:1200px;font-size:12px;">
<thead>
<tr>
    <th style="width:140px;"><?= __('changed_at') ?></th>
    <th style="width:160px;"><?= __('table') ?></th>
    <th style="width:80px;" class="text-center"><?= __('operation') ?></th>
    <th style="width:80px;" class="text-right"><?= __('record_id') ?></th>
    <th style="width:140px;"><?= __('changed_by') ?></th>
    <th><?= __('changed_fields') ?></th>
    <th style="width:60px;" class="text-center"><?= _e('actions') ?></th>
</tr>
</thead>
<tbody>
<?php if (empty($logs)): ?>
    <tr><td colspan="7" class="text-center" style="padding:30px;color:var(--color-text-muted);"><?= __('no_data') ?></td></tr>
<?php else: foreach ($logs as $l): ?>
    <tr>
        <td><?= e(date('Y-m-d H:i:s', strtotime($l['changed_at']))) ?></td>
        <td><strong><?= e($l['table_name']) ?></strong></td>
        <td class="text-center">
            <span class="badge" style="background:<?= e($opColor[$l['operation']] ?? '#888') ?>;color:#fff;font-size:10px;">
                <?= e($opLabel[$l['operation']] ?? $l['operation']) ?>
            </span>
        </td>
        <td class="text-right"><?= e($l['record_id']) ?></td>
        <td><?= e($l['changed_by_full_name'] ?? $l['changed_by_name'] ?? '—') ?></td>
        <td style="font-family:Consolas,monospace;font-size:11px;">
            <?php
            $fields = '';
            if (!empty($l['changed_fields'])) {
                $f = is_string($l['changed_fields']) ? json_decode($l['changed_fields'], true) : $l['changed_fields'];
                if (is_array($f)) $fields = implode(', ', array_keys($f));
            }
            ?>
            <?= e(mb_substr($fields ?: ($l['operation'] === 'I' ? '(new record)' : ($l['operation'] === 'D' ? '(deleted)' : '')), 0, 120)) ?>
        </td>
        <td class="text-center actions">
            <a href="/admin/audit-logs/<?= e($l['log_id']) ?>" title="<?= __('view') ?>">&#128065;</a>
        </td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
<div style="font-size:11px;color:var(--color-text-muted);margin-top:8px;">
    <?= count($logs) ?> <?= __('records_shown') ?> (<?= __('limit_500') ?>)
</div>
