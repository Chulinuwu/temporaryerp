<?php
/**
 * PEGASUS ERP — User Management (admin only)
 * Vars: $users, $roles, $employees, $filters
 */
$lang = currentLang();
$roleLabel = function($r) use ($roles, $lang) {
    foreach ($roles as $x) {
        if ($x['role_code'] === $r) {
            return $lang === 'ja' ? $x['role_name_jp'] : ($lang === 'th' ? $x['role_name_th'] : $x['role_name']);
        }
    }
    return $r;
};
?>
<div class="page-header">
    <div>
        <h1 class="page-title">👥 <?= _e('menu_user_management') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('menu_user_management') ?></span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <button type="button" class="btn btn-cancel" onclick="document.getElementById('bulkModal').classList.add('active')">
            🔑 <?= __('bulk_reset_password') ?>
        </button>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('createModal').classList.add('active')">
            + <?= __('new_user') ?>
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card" style="padding:12px 20px;margin-bottom:12px;">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="q" class="form-input" style="width:220px;" value="<?= e($filters['q']) ?>"
               placeholder="<?= __('search') ?> (<?= __('email') ?> / <?= __('name') ?>)">
        <select name="role" class="form-select" style="width:160px;">
            <option value=""><?= __('all_roles') ?></option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= e($r['role_code']) ?>" <?= $filters['role'] === $r['role_code'] ? 'selected' : '' ?>>
                    <?= e($r['role_code']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label style="font-size:12px;display:flex;align-items:center;gap:4px;">
            <input type="checkbox" name="active" value="1" <?= $filters['active'] ? 'checked' : '' ?>>
            <?= __('active_only') ?>
        </label>
        <button type="submit" class="btn btn-primary btn-sm"><?= _e('filter') ?></button>
        <a href="/admin/users" class="btn btn-cancel btn-sm"><?= _e('clear') ?></a>
    </form>
</div>

<!-- Users list -->
<div class="card" style="padding:0;overflow-x:auto;">
<table class="data-table" style="font-size:12px;">
    <thead>
    <tr>
        <th style="width:50px;">ID</th>
        <th><?= _e('email') ?> / Username</th>
        <th><?= __('employee') ?></th>
        <th style="width:130px;"><?= __('role') ?></th>
        <th class="text-center" style="width:80px;"><?= __('active') ?></th>
        <th><?= __('last_login') ?></th>
        <th class="text-center" style="width:230px;"><?= _e('actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($users)): ?>
        <tr><td colspan="7" class="text-center" style="padding:30px;color:#888;"><?= __('no_users_found') ?></td></tr>
    <?php else: foreach ($users as $u): ?>
        <tr style="<?= !$u['is_active'] ? 'opacity:0.5;' : '' ?>">
            <td><?= e($u['user_id']) ?></td>
            <td>
                <strong><?= e($u['email']) ?></strong>
                <?php if ($u['username'] !== $u['email']): ?>
                    <div style="font-size:10px;color:#666;">user: <?= e($u['username']) ?></div>
                <?php endif; ?>
            </td>
            <td style="font-size:11px;"><?= e($u['employee_name'] ?? '—') ?></td>
            <td>
                <span class="badge" style="background:#E3F2FD;color:#0D47A1;">
                    <?= e($u['role']) ?>
                </span>
                <div style="font-size:10px;color:#666;"><?= e($roleLabel($u['role'])) ?></div>
            </td>
            <td class="text-center"><?= $u['is_active'] ? '✅' : '⛔' ?></td>
            <td style="font-size:10px;color:#888;"><?= e($u['last_login'] ?? '—') ?></td>
            <td class="text-center actions" style="white-space:nowrap;">
                <button type="button" class="btn btn-cancel btn-sm" style="padding:3px 8px;font-size:11px;"
                        onclick='openEditUser(<?= json_encode($u, JSON_UNESCAPED_UNICODE) ?>)'>
                    ⚙ <?= _e('edit') ?>
                </button>
                <button type="button" class="btn btn-cancel btn-sm" style="padding:3px 8px;font-size:11px;color:#E65100;"
                        onclick='openResetPw(<?= json_encode($u['user_id']) ?>, <?= json_encode($u['email']) ?>)'>
                    🔑 PW
                </button>
                <?php if ($u['is_active']): ?>
                <form method="POST" action="/admin/users/<?= e($u['user_id']) ?>/delete" style="display:inline;">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" onclick="return confirm('<?= __('confirm_deactivate_user') ?>')"
                            style="background:none;border:none;cursor:pointer;color:#D32F2F;font-size:14px;"
                            title="<?= __('deactivate') ?>">&#128465;</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
<div style="font-size:11px;color:#888;margin-top:8px;">
    <?= count($users) ?> <?= __('records') ?>
</div>

<!-- ========== Modal: Create new user ========== -->
<div id="createModal" class="modal-overlay">
<div class="modal" style="max-width:600px;">
    <div class="modal-header">
        <h3>+ <?= __('new_user') ?></h3>
        <button type="button" class="modal-close" onclick="document.getElementById('createModal').classList.remove('active')">&times;</button>
    </div>
    <form method="POST" action="/admin/users">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label"><?= _e('email') ?> *</label>
                <input type="email" name="email" class="form-input" required placeholder="user@tomastc.com">
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" placeholder="<?= __('username_hint') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('role') ?> *</label>
                <select name="role" class="form-select" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= e($r['role_code']) ?>" <?= $r['role_code'] === 'STAFF' ? 'selected' : '' ?>><?= e($r['role_code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label"><?= __('link_employee_optional') ?></label>
                <select name="employee_id" class="form-select">
                    <option value="">— <?= __('none') ?> —</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= e($emp['employee_id']) ?>">
                            <?= e($emp['full_name']) ?> <?= $emp['email'] ? '(' . e($emp['email']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label"><?= __('password') ?> * <span style="font-size:11px;color:#888;">(<?= __('min_6_chars') ?>)</span></label>
                <input type="text" name="password" class="form-input" required minlength="6" value="admin123">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="document.getElementById('createModal').classList.remove('active')"><?= _e('cancel') ?></button>
            <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
        </div>
    </form>
</div>
</div>

<!-- ========== Modal: Edit user role / active ========== -->
<div id="editModal" class="modal-overlay">
<div class="modal" style="max-width:520px;">
    <div class="modal-header">
        <h3>⚙ <?= __('edit_user') ?> — <span id="edit_email"></span></h3>
        <button type="button" class="modal-close" onclick="document.getElementById('editModal').classList.remove('active')">&times;</button>
    </div>
    <form method="POST" id="editForm">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group">
                <label class="form-label"><?= __('role') ?></label>
                <select name="role" id="edit_role" class="form-select">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= e($r['role_code']) ?>"><?= e($r['role_code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('active') ?></label>
                <label style="display:flex;align-items:center;gap:6px;margin-top:8px;">
                    <input type="checkbox" name="is_active" id="edit_active" value="1">
                    <?= __('active') ?>
                </label>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label"><?= __('link_employee_optional') ?></label>
                <select name="employee_id" id="edit_employee_id" class="form-select">
                    <option value="">— <?= __('none') ?> —</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= e($emp['employee_id']) ?>">
                            <?= e($emp['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="document.getElementById('editModal').classList.remove('active')"><?= _e('cancel') ?></button>
            <button type="submit" class="btn btn-primary"><?= _e('save') ?></button>
        </div>
    </form>
</div>
</div>

<!-- ========== Modal: Reset single password ========== -->
<div id="pwModal" class="modal-overlay">
<div class="modal" style="max-width:460px;">
    <div class="modal-header">
        <h3>🔑 <?= __('reset_password') ?></h3>
        <button type="button" class="modal-close" onclick="document.getElementById('pwModal').classList.remove('active')">&times;</button>
    </div>
    <form method="POST" id="pwForm">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="modal-body">
            <div style="margin-bottom:10px;font-size:13px;">
                <?= __('reset_password_for') ?>: <strong id="pw_email"></strong>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('new_password') ?> <span style="font-size:11px;color:#888;">(<?= __('min_6_chars') ?>)</span></label>
                <input type="text" name="password" class="form-input" required minlength="6" value="admin123">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="document.getElementById('pwModal').classList.remove('active')"><?= _e('cancel') ?></button>
            <button type="submit" class="btn btn-primary"><?= __('reset_password') ?></button>
        </div>
    </form>
</div>
</div>

<!-- ========== Modal: Bulk reset all ========== -->
<div id="bulkModal" class="modal-overlay">
<div class="modal" style="max-width:500px;">
    <div class="modal-header">
        <h3>🔑 <?= __('bulk_reset_password') ?></h3>
        <button type="button" class="modal-close" onclick="document.getElementById('bulkModal').classList.remove('active')">&times;</button>
    </div>
    <form method="POST" action="/admin/users/bulk-reset" onsubmit="return confirm('<?= __('confirm_bulk_reset') ?>');">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="modal-body">
            <div style="background:#FFEBEE;border-left:4px solid #D32F2F;padding:10px;font-size:12px;margin-bottom:10px;">
                ⚠ <?= __('bulk_reset_warning') ?>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('new_password') ?> <span style="font-size:11px;color:#888;">(<?= __('min_6_chars') ?>)</span></label>
                <input type="text" name="password" class="form-input" required minlength="6" value="admin123">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="document.getElementById('bulkModal').classList.remove('active')"><?= _e('cancel') ?></button>
            <button type="submit" class="btn btn-primary" style="background:#D32F2F;"><?= __('execute') ?></button>
        </div>
    </form>
</div>
</div>

<script>
function openEditUser(u) {
    document.getElementById('edit_email').textContent = u.email;
    document.getElementById('edit_role').value = u.role;
    document.getElementById('edit_active').checked = !!u.is_active;
    document.getElementById('edit_employee_id').value = u.employee_id || '';
    document.getElementById('editForm').action = '/admin/users/' + u.user_id + '/update';
    document.getElementById('editModal').classList.add('active');
}
function openResetPw(uid, email) {
    document.getElementById('pw_email').textContent = email;
    document.getElementById('pwForm').action = '/admin/users/' + uid + '/reset-password';
    document.getElementById('pwModal').classList.add('active');
}
</script>
