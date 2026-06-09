<?php
/**
 * PEGASUS ERP - Employee List
 * Variables: $employees, $departments, $filters
 */
extract($viewData ?? []);
$employees   = $employees ?? [];
$departments = $departments ?? [];
$filters     = $filters ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= _e('employees') ?></h1>
        <div class="breadcrumb">
            <a href="/dashboard"><?= _e('home') ?></a>
            <span class="breadcrumb-separator">/</span>
            <a href="/hr"><?= _e('hr') ?></a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?= _e('employees') ?></span>
        </div>
    </div>
    <a href="/hr/employees/create" class="btn btn-primary"><?= _e('new_employee') ?></a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="/hr/employees" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;min-width:160px;">
                <label class="form-label"><?= _e('department') ?></label>
                <select name="department" class="form-select">
                    <option value=""><?= _e('all_departments') ?></option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= e($dept['id']) ?>" <?= ($filters['department'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                            <?= e($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:140px;">
                <label class="form-label"><?= _e('employment_type') ?></label>
                <select name="employment_type" class="form-select">
                    <option value=""><?= _e('all_types') ?></option>
                    <option value="FULL_TIME" <?= ($filters['employment_type'] ?? '') === 'FULL_TIME' ? 'selected' : '' ?>>Full Time</option>
                    <option value="PART_TIME" <?= ($filters['employment_type'] ?? '') === 'PART_TIME' ? 'selected' : '' ?>>Part Time</option>
                    <option value="CONTRACT" <?= ($filters['employment_type'] ?? '') === 'CONTRACT' ? 'selected' : '' ?>>Contract</option>
                    <option value="DAILY" <?= ($filters['employment_type'] ?? '') === 'DAILY' ? 'selected' : '' ?>>Daily</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:200px;">
                <label class="form-label"><?= _e('search') ?></label>
                <input type="text" name="search" class="form-input" placeholder="<?= _e('search_employee') ?>" value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary"><?= _e('filter') ?></button>
            <a href="/hr/employees" class="btn btn-cancel"><?= _e('clear') ?></a>
        </form>
    </div>
</div>

<!-- Employee Table -->
<div class="table-wrapper" style="overflow-x:auto;">
    <table class="data-table" style="min-width:900px;">
        <thead>
            <tr>
                <th style="width:70px;"><?= _e('employee_code') ?></th>
                <th style="min-width:140px;"><?= _e('name') ?></th>
                <th style="min-width:100px;"><?= _e('department') ?></th>
                <th style="min-width:120px;"><?= _e('position') ?></th>
                <th style="width:90px;"><?= _e('hire_date') ?></th>
                <th class="text-center" style="width:80px;"><?= _e('status') ?></th>
                <th class="text-center" style="width:110px;"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($employees)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                        <?= _e('msg_no_employees') ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><strong><?= e($emp['emp_code']) ?></strong></td>
                        <td>
                            <a href="/hr/employees/<?= e($emp['employee_id']) ?>" style="font-weight:500;">
                                <?= e($emp['full_name']) ?>
                            </a>
                            <?php if (!empty($emp['full_name_jp']) && $emp['full_name_jp'] !== '-'): ?>
                                <div style="font-size:11px;color:var(--color-text-muted);"><?= e($emp['full_name_jp']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($emp['department_name'] ?? '') ?></td>
                        <td style="font-size:12px;"><?= e($emp['position_title'] ?? '') ?></td>
                        <td><?= e($emp['hire_date'] ?? '') ?></td>
                        <td class="text-center">
                            <?php
                            $isActive = !($emp['is_deleted'] ?? false) && empty($emp['termination_date']);
                            $statusClass = $isActive ? 'badge-approved' : 'badge-rejected';
                            $statusText = $isActive ? __('status_current') : __('status_closed');
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= e($statusText) ?></span>
                        </td>
                        <td class="actions text-center" style="white-space:nowrap;">
                            <a href="/hr/employees/<?= e($emp['employee_id']) ?>" title="<?= _e('view') ?>" style="margin:0 2px;">&#128065;</a>
                            <a href="/hr/employees/<?= e($emp['employee_id']) ?>/edit" title="<?= _e('edit') ?>" style="margin:0 2px;">&#9998;</a>
                            <button type="button" title="<?= _e('delete') ?>" style="margin:0 2px;background:none;border:none;cursor:pointer;font-size:14px;" onclick="deleteEmployee('<?= e($emp['employee_id']) ?>')">&#128465;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (!empty($employees)): ?>
        <div class="table-footer">
            <span><?= count($employees) ?> <?= _e('employee') ?></span>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteEmployee(id) {
    if (confirm('<?= __('confirm_delete') ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/hr/employees/delete';
        form.innerHTML = '<input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">' +
            '<input type="hidden" name="employee_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
