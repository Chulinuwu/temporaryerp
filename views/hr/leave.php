<?php
/**
 * PEGASUS ERP - Leave Management
 * Variables: $leaveRequests, $employees, $filters
 */
extract($viewData ?? []);
$leaveRequests = $leaveRequests ?? [];
$employees     = $employees ?? [];
$filters       = $filters ?? [];
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;"><?= _e('leave_management') ?></h1>
    <button class="btn btn-primary" onclick="document.getElementById('leaveModal').classList.add('active')"><?= _e('new_leave') ?></button>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="/hr/leave" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;min-width:130px;">
                <label class="form-label"><?= _e('status') ?></label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="APPROVED" <?= ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                    <option value="REJECTED" <?= ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
                    <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:140px;">
                <label class="form-label"><?= _e('leave_type') ?></label>
                <select name="leave_type" class="form-select">
                    <option value=""><?= _e('all_types') ?></option>
                    <option value="ANNUAL" <?= ($filters['leave_type'] ?? '') === 'ANNUAL' ? 'selected' : '' ?>>Annual Leave</option>
                    <option value="SICK" <?= ($filters['leave_type'] ?? '') === 'SICK' ? 'selected' : '' ?>>Sick Leave</option>
                    <option value="PERSONAL" <?= ($filters['leave_type'] ?? '') === 'PERSONAL' ? 'selected' : '' ?>>Personal Leave</option>
                    <option value="MATERNITY" <?= ($filters['leave_type'] ?? '') === 'MATERNITY' ? 'selected' : '' ?>>Maternity Leave</option>
                    <option value="MILITARY" <?= ($filters['leave_type'] ?? '') === 'MILITARY' ? 'selected' : '' ?>>Military Service</option>
                    <option value="ORDINATION" <?= ($filters['leave_type'] ?? '') === 'ORDINATION' ? 'selected' : '' ?>>Ordination Leave</option>
                    <option value="TRAINING" <?= ($filters['leave_type'] ?? '') === 'TRAINING' ? 'selected' : '' ?>>Training Leave</option>
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
            <a href="/hr/leave" class="btn btn-cancel"><?= _e('clear') ?></a>
        </form>
    </div>
</div>

<!-- Leave Requests Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= _e('employee') ?></th>
                <th><?= _e('leave_type') ?></th>
                <th><?= _e('start_date') ?></th>
                <th><?= _e('end_date') ?></th>
                <th class="text-right"><?= _e('days') ?></th>
                <th class="text-center"><?= _e('status') ?></th>
                <th>Approved By</th>
                <th class="text-center"><?= _e('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($leaveRequests)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                        <?= _e('no_leave_found') ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($leaveRequests as $lr): ?>
                    <tr>
                        <td><?= e($lr['full_name'] ?? '') ?></td>
                        <td>
                            <?php
                            $typeLabels = [
                                'ANNUAL'      => 'Annual Leave',
                                'SICK'        => 'Sick Leave',
                                'PERSONAL'    => 'Personal Leave',
                                'MATERNITY'   => 'Maternity Leave',
                                'MILITARY'    => 'Military Service',
                                'ORDINATION'  => 'Ordination Leave',
                                'TRAINING'    => 'Training Leave',
                                'STERILIZATION' => 'Sterilization Leave',
                            ];
                            echo e($typeLabels[$lr['leave_type']] ?? $lr['leave_type']);
                            ?>
                        </td>
                        <td><?= e(formatDate($lr['start_date'] ?? '', 'd/m/Y')) ?></td>
                        <td><?= e(formatDate($lr['end_date'] ?? '', 'd/m/Y')) ?></td>
                        <td class="text-right"><?= e(number_format($lr['days_requested'] ?? 0, 1)) ?></td>
                        <td class="text-center">
                            <?php
                            $statusClass = match ($lr['status'] ?? '') {
                                'PENDING'   => 'badge-pending',
                                'APPROVED'  => 'badge-approved',
                                'REJECTED'  => 'badge-rejected',
                                'CANCELLED' => 'badge-draft',
                                default     => 'badge-draft',
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= e($lr['status'] ?? '') ?></span>
                        </td>
                        <td><?= e($lr['approved_by_name'] ?? '-') ?></td>
                        <td class="actions text-center">
                            <?php if (($lr['status'] ?? '') === 'PENDING'): ?>
                                <button class="btn btn-sm btn-success" onclick="leaveAction(<?= (int) $lr['leave_id'] ?>, 'approve')"><?= _e('approve') ?></button>
                                <button class="btn btn-sm btn-danger" onclick="leaveAction(<?= (int) $lr['leave_id'] ?>, 'reject')"><?= _e('reject') ?></button>
                            <?php else: ?>
                                <a href="/hr/leave/<?= e($lr['leave_id']) ?>" title="View">&#128065;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if (!empty($leaveRequests)): ?>
        <div class="table-footer">
            <span><?= count($leaveRequests) ?> request(s)</span>
        </div>
    <?php endif; ?>
</div>

<!-- New Leave Request Modal -->
<div class="modal-overlay" id="leaveModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">New Leave Request</h3>
            <button class="modal-close" onclick="document.getElementById('leaveModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" action="/hr/leave/store" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Employee <span class="required">*</span></label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= e($emp['employee_id']) ?>"><?= e($emp['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Leave Type <span class="required">*</span></label>
                        <select name="leave_type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            <option value="ANNUAL">Annual Leave (paid)</option>
                            <option value="SICK">Sick Leave (paid up to 30 days)</option>
                            <option value="PERSONAL">Personal / Business Leave (unpaid)</option>
                            <option value="MATERNITY">Maternity Leave (98 days, 45 paid)</option>
                            <option value="MILITARY">Military Service Leave (up to 60 days, paid)</option>
                            <option value="ORDINATION">Ordination Leave</option>
                            <option value="TRAINING">Training / Exam Leave</option>
                            <option value="STERILIZATION">Sterilization Leave (paid)</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Start Date <span class="required">*</span></label>
                        <input type="date" name="start_date" class="form-input" required id="leaveStartDate">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date <span class="required">*</span></label>
                        <input type="date" name="end_date" class="form-input" required id="leaveEndDate">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Days Requested <span class="required">*</span></label>
                        <input type="number" name="days_requested" class="form-input" step="0.5" min="0.5" required id="leaveDays">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="half_day" value="1" id="halfDayCheck">
                        Half Day
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-textarea" rows="3" placeholder="Reason for leave request..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Attachment</label>
                    <input type="file" name="attachment" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <span class="form-hint">Medical certificate or supporting document</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="document.getElementById('leaveModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function leaveAction(id, action) {
    if (!confirm('Are you sure you want to ' + action + ' this leave request?')) return;

    fetch('/hr/leave/' + id + '/' + action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Action failed.');
        }
    })
    .catch(function() { alert('Network error.'); });
}

// Auto-calculate days
document.addEventListener('DOMContentLoaded', function() {
    var startEl = document.getElementById('leaveStartDate');
    var endEl   = document.getElementById('leaveEndDate');
    var daysEl  = document.getElementById('leaveDays');
    var halfEl  = document.getElementById('halfDayCheck');

    function calcDays() {
        if (!startEl.value || !endEl.value) return;
        var start = new Date(startEl.value);
        var end   = new Date(endEl.value);
        if (end < start) return;
        var diff = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
        if (halfEl.checked) diff = 0.5;
        daysEl.value = diff;
    }

    startEl.addEventListener('change', calcDays);
    endEl.addEventListener('change', calcDays);
    halfEl.addEventListener('change', calcDays);
});
</script>
