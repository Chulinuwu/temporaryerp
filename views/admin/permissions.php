<?php
/**
 * PEGASUS ERP — Permission Master (admin)
 * Variables: $roles, $permissions, $byModule, $matrix
 */
$lang = currentLang();
$descCol = $lang === 'ja' ? 'description_jp' : ($lang === 'th' ? 'description_th' : 'description');
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('menu_permissions') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_permissions') ?></span>
        </div>
    </div>
</div>

<form method="POST" action="/admin/permissions">
<div class="card" style="padding:0;overflow-x:auto;">
    <table class="data-table" style="font-size:12px;min-width:900px;">
        <thead>
        <tr style="position:sticky;top:0;background:#E3F2FD;z-index:1;">
            <th style="width:240px;text-align:left;"><?= __('permission') ?></th>
            <?php foreach ($roles as $r): ?>
                <th class="text-center" style="width:120px;">
                    <?= e($r['role_code']) ?>
                    <div style="font-size:10px;font-weight:400;color:#666;">
                        <?= e($lang === 'ja' ? ($r['role_name_jp'] ?? $r['role_name']) : ($lang === 'th' ? ($r['role_name_th'] ?? $r['role_name']) : $r['role_name'])) ?>
                    </div>
                </th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($byModule as $module => $perms): ?>
            <tr style="background:#F5F5F5;">
                <td colspan="<?= count($roles) + 1 ?>" style="font-weight:600;text-transform:uppercase;color:#1976D2;">
                    <?= e($module) ?>
                </td>
            </tr>
            <?php foreach ($perms as $p): ?>
                <tr>
                    <td>
                        <code style="font-size:11px;"><?= e($p['permission_code']) ?></code>
                        <div style="font-size:11px;color:#666;"><?= e($p[$descCol] ?? $p['description']) ?></div>
                    </td>
                    <?php foreach ($roles as $r): ?>
                        <?php
                        $checked = !empty($matrix[$r['role_code']][$p['permission_code']]);
                        $isAdminAll = ($r['role_code'] === 'ADMIN'); // ADMIN forced on
                        ?>
                        <td class="text-center">
                            <input type="checkbox"
                                   name="perm[<?= e($r['role_code']) ?>][<?= e($p['permission_code']) ?>]"
                                   value="1"
                                   <?= $checked ? 'checked' : '' ?>
                                   <?= $isAdminAll ? 'checked disabled' : '' ?>>
                            <?php if ($isAdminAll): ?>
                                <input type="hidden" name="perm[<?= e($r['role_code']) ?>][<?= e($p['permission_code']) ?>]" value="1">
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div style="margin-top:16px;text-align:right;">
    <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
</div>
</form>
