<?php
/**
 * PEGASUS ERP - Attendance Management
 * Variables: $attendance, $employees, $filters, $currentMonth, $currentYear
 */
extract($viewData ?? []);
$attendance   = $attendance ?? [];
$employees    = $employees ?? [];
$filters      = $filters ?? [];
$currentMonth = $currentMonth ?? (int) date('m');
$currentYear  = $currentYear ?? (int) date('Y');
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;"><?= _e('attendance') ?></h1>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-success" onclick="clockAction('in')">Clock In</button>
        <button class="btn btn-danger" onclick="clockAction('out')">Clock Out</button>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="/hr/attendance" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;min-width:100px;">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $currentMonth === $m ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:90px;">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    <?php for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $currentYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:180px;">
                <label class="form-label"><?= _e('employee') ?></label>
                <select name="employee_id" class="form-select">
                    <option value=""><?= _e('all_employees') ?></option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= e($emp['employee_id']) ?>" <?= ($filters['employee_id'] ?? '') == $emp['employee_id'] ? 'selected' : '' ?>>
                            <?= e($emp['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= _e('filter') ?></button>
        </form>
    </div>
</div>

<!-- Attendance Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= _e('date') ?></th>
                <th><?= _e('employee') ?></th>
                <th><?= _e('clock_in') ?></th>
                <th><?= _e('clock_out') ?></th>
                <th class="text-right"><?= _e('work_hours') ?></th>
                <th class="text-right"><?= _e('ot_hours') ?></th>
                <th class="text-right">Holiday</th>
                <th class="text-right">Night</th>
                <th class="text-center"><?= _e('status') ?></th>
                <th class="text-right">Late (min)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($attendance)): ?>
                <tr>
                    <td colspan="10" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                        <?= _e('no_attendance_found') ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($attendance as $row): ?>
                    <tr>
                        <td><?= e(formatDate($row['attendance_date'] ?? '', 'd/m/Y')) ?></td>
                        <td><?= e($row['full_name'] ?? '') ?></td>
                        <td><?= e($row['clock_in'] ?? '-') ?></td>
                        <td><?= e($row['clock_out'] ?? '-') ?></td>
                        <td class="text-right"><?= e(number_format($row['regular_hours'] ?? 0, 1)) ?></td>
                        <td class="text-right"><?= e(number_format($row['overtime_hours'] ?? 0, 1)) ?></td>
                        <td class="text-right"><?= e(number_format($row['holiday_hours'] ?? 0, 1)) ?></td>
                        <td class="text-right"><?= e(number_format($row['night_hours'] ?? 0, 1)) ?></td>
                        <td class="text-center">
                            <?php
                            $statusBadge = match ($row['status'] ?? '') {
                                'PRESENT' => 'background:#E8F5E9;color:#43A047;',
                                'ABSENT'  => 'background:#FFEBEE;color:#E53935;',
                                'LATE'    => 'background:#FFF3E0;color:#FB8C00;',
                                'LEAVE'   => 'background:#E3F2FD;color:#1976D2;',
                                'HOLIDAY' => 'background:#F3E5F5;color:#8E24AA;',
                                default   => 'background:#F5F5F5;color:#757575;',
                            };
                            ?>
                            <span class="badge" style="<?= $statusBadge ?>"><?= e($row['status'] ?? '-') ?></span>
                        </td>
                        <td class="text-right"><?= (int) ($row['late_minutes'] ?? 0) > 0 ? e($row['late_minutes']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (!empty($attendance)): ?>
        <div class="table-footer">
            <span><?= count($attendance) ?> record(s)</span>
        </div>
    <?php endif; ?>
</div>

<script>
function clockAction(type) {
    if (!confirm('Confirm clock ' + type + '?')) return;

    fetch('/hr/attendance/clock', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ action: type })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Clock action failed.');
        }
    })
    .catch(function() { alert('Network error.'); });
}
</script>
