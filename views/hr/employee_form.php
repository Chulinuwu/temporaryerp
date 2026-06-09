<?php
/**
 * PEGASUS ERP - Employee Create / Edit Form
 * Variables: $employee, $divisions, $departments, $isEdit
 */
extract($viewData ?? []);
$employee    = $employee ?? [];
$divisions   = $divisions ?? [];
$departments = $departments ?? [];
$isEdit      = $isEdit ?? false;
$title       = $isEdit ? 'Edit Employee' : 'New Employee';
$action      = $isEdit ? '/hr/employees/' . e($employee['id'] ?? '') . '/update' : '/hr/employees/store';
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;"><?= e($title) ?></h1>
    <a href="/hr/employees" class="btn btn-cancel">Back to List</a>
</div>

<form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <!-- Section 1: Personal Information -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">Personal Information</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Employee Code <span class="required">*</span></label>
                    <input type="text" name="emp_code" class="form-input" value="<?= e($employee['emp_code'] ?? old('emp_code')) ?>" required placeholder="e.g. EMP-0001">
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name (EN) <span class="required">*</span></label>
                    <input type="text" name="full_name" class="form-input" value="<?= e($employee['full_name'] ?? old('full_name')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name (JP)</label>
                    <input type="text" name="full_name_jp" class="form-input" value="<?= e($employee['full_name_jp'] ?? old('full_name_jp')) ?>" placeholder="&#27663;&#21517;">
                </div>
            </div>
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Full Name (TH)</label>
                    <input type="text" name="full_name_th" class="form-input" value="<?= e($employee['full_name_th'] ?? old('full_name_th')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Nickname</label>
                    <input type="text" name="nickname" class="form-input" value="<?= e($employee['nickname'] ?? old('nickname')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Nationality</label>
                    <select name="nationality" class="form-select">
                        <option value="">-- Select --</option>
                        <option value="TH" <?= ($employee['nationality'] ?? old('nationality')) === 'TH' ? 'selected' : '' ?>>Thai</option>
                        <option value="JP" <?= ($employee['nationality'] ?? old('nationality')) === 'JP' ? 'selected' : '' ?>>Japanese</option>
                        <option value="MM" <?= ($employee['nationality'] ?? old('nationality')) === 'MM' ? 'selected' : '' ?>>Myanmar</option>
                        <option value="KH" <?= ($employee['nationality'] ?? old('nationality')) === 'KH' ? 'selected' : '' ?>>Cambodian</option>
                        <option value="LA" <?= ($employee['nationality'] ?? old('nationality')) === 'LA' ? 'selected' : '' ?>>Lao</option>
                        <option value="OTHER" <?= ($employee['nationality'] ?? old('nationality')) === 'OTHER' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Thai ID Number</label>
                    <input type="text" name="thai_id" class="form-input" value="<?= e($employee['thai_id'] ?? old('thai_id')) ?>" maxlength="13" placeholder="13-digit ID">
                </div>
                <div class="form-group">
                    <label class="form-label">Passport Number</label>
                    <input type="text" name="passport_no" class="form-input" value="<?= e($employee['passport_no'] ?? old('passport_no')) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Employment Details -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">Employment Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Division <span class="required">*</span></label>
                    <select name="division_id" class="form-select" id="divisionSelect" required>
                        <option value="">-- Select Division --</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?= e($div['id']) ?>" <?= ($employee['division_id'] ?? old('division_id')) == $div['id'] ? 'selected' : '' ?>>
                                <?= e($div['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Department <span class="required">*</span></label>
                    <select name="department_id" class="form-select" id="departmentSelect" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= e($dept['id']) ?>" data-division="<?= e($dept['division_id'] ?? '') ?>"
                                <?= ($employee['department_id'] ?? old('department_id')) == $dept['id'] ? 'selected' : '' ?>>
                                <?= e($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Hire Date <span class="required">*</span></label>
                    <input type="date" name="hire_date" class="form-input" value="<?= e($employee['hire_date'] ?? old('hire_date')) ?>" required>
                </div>
            </div>
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Employment Type <span class="required">*</span></label>
                    <select name="employment_type" class="form-select" required>
                        <option value="">-- Select --</option>
                        <option value="FULL_TIME" <?= ($employee['employment_type'] ?? old('employment_type')) === 'FULL_TIME' ? 'selected' : '' ?>>Full Time</option>
                        <option value="PART_TIME" <?= ($employee['employment_type'] ?? old('employment_type')) === 'PART_TIME' ? 'selected' : '' ?>>Part Time</option>
                        <option value="CONTRACT" <?= ($employee['employment_type'] ?? old('employment_type')) === 'CONTRACT' ? 'selected' : '' ?>>Contract</option>
                        <option value="DAILY" <?= ($employee['employment_type'] ?? old('employment_type')) === 'DAILY' ? 'selected' : '' ?>>Daily</option>
                        <option value="PROBATION" <?= ($employee['employment_type'] ?? old('employment_type')) === 'PROBATION' ? 'selected' : '' ?>>Probation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Position Title</label>
                    <input type="text" name="position_title" class="form-input" value="<?= e($employee['position_title'] ?? old('position_title')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Position Level</label>
                    <select name="position_level" class="form-select">
                        <option value="">-- Select --</option>
                        <option value="STAFF" <?= ($employee['position_level'] ?? old('position_level')) === 'STAFF' ? 'selected' : '' ?>>Staff</option>
                        <option value="SENIOR" <?= ($employee['position_level'] ?? old('position_level')) === 'SENIOR' ? 'selected' : '' ?>>Senior</option>
                        <option value="LEAD" <?= ($employee['position_level'] ?? old('position_level')) === 'LEAD' ? 'selected' : '' ?>>Lead</option>
                        <option value="SUPERVISOR" <?= ($employee['position_level'] ?? old('position_level')) === 'SUPERVISOR' ? 'selected' : '' ?>>Supervisor</option>
                        <option value="MANAGER" <?= ($employee['position_level'] ?? old('position_level')) === 'MANAGER' ? 'selected' : '' ?>>Manager</option>
                        <option value="DIRECTOR" <?= ($employee['position_level'] ?? old('position_level')) === 'DIRECTOR' ? 'selected' : '' ?>>Director</option>
                        <option value="EXECUTIVE" <?= ($employee['position_level'] ?? old('position_level')) === 'EXECUTIVE' ? 'selected' : '' ?>>Executive</option>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?= e($employee['email'] ?? old('email')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input" value="<?= e($employee['phone'] ?? old('phone')) ?>" placeholder="e.g. 08x-xxx-xxxx">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Work Permit / Visa -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">Work Permit / Visa</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Work Permit No.</label>
                    <input type="text" name="work_permit_no" class="form-input" value="<?= e($employee['work_permit_no'] ?? old('work_permit_no')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Work Permit Expiry</label>
                    <input type="date" name="work_permit_expiry" class="form-input" value="<?= e($employee['work_permit_expiry'] ?? old('work_permit_expiry')) ?>">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Visa Type</label>
                    <select name="visa_type" class="form-select">
                        <option value="">-- Select --</option>
                        <option value="NON_B" <?= ($employee['visa_type'] ?? old('visa_type')) === 'NON_B' ? 'selected' : '' ?>>Non-Immigrant B</option>
                        <option value="NON_O" <?= ($employee['visa_type'] ?? old('visa_type')) === 'NON_O' ? 'selected' : '' ?>>Non-Immigrant O</option>
                        <option value="NON_IB" <?= ($employee['visa_type'] ?? old('visa_type')) === 'NON_IB' ? 'selected' : '' ?>>Non-Immigrant IB (BOI)</option>
                        <option value="SMART" <?= ($employee['visa_type'] ?? old('visa_type')) === 'SMART' ? 'selected' : '' ?>>Smart Visa</option>
                        <option value="OTHER" <?= ($employee['visa_type'] ?? old('visa_type')) === 'OTHER' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Visa Expiry</label>
                    <input type="date" name="visa_expiry" class="form-input" value="<?= e($employee['visa_expiry'] ?? old('visa_expiry')) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: Salary & Bank — FULLY REMOVED (payroll feature not used) -->

    <!-- Section 5: Leave Entitlement -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">Leave Entitlement</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Annual Leave Days</label>
                    <input type="number" name="annual_leave_days" class="form-input" step="0.5" min="0" value="<?= e($employee['annual_leave_days'] ?? old('annual_leave_days', '6')) ?>">
                    <span class="form-hint">Minimum 6 days per Thai labor law (after 1 year)</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Sick Leave Days</label>
                    <input type="number" name="sick_leave_days" class="form-input" step="0.5" min="0" value="<?= e($employee['sick_leave_days'] ?? old('sick_leave_days', '30')) ?>">
                    <span class="form-hint">Up to 30 days per year (paid)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 6: Social Security -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">Social Security</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="sso_enrolled" value="1"
                            <?= ($employee['sso_enrolled'] ?? old('sso_enrolled', '1')) ? 'checked' : '' ?>>
                        Enrolled in Social Security (SSO)
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">SSO Number</label>
                    <input type="text" name="sso_no" class="form-input" value="<?= e($employee['sso_no'] ?? old('sso_no')) ?>" placeholder="Social Security number">
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:40px;">
        <a href="/hr/employees" class="btn btn-cancel">Cancel</a>
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Employee' : 'Save Employee' ?></button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter departments by selected division
    const divisionSelect = document.getElementById('divisionSelect');
    const departmentSelect = document.getElementById('departmentSelect');
    const allDeptOptions = Array.from(departmentSelect.querySelectorAll('option[data-division]'));

    if (divisionSelect && departmentSelect) {
        divisionSelect.addEventListener('change', function() {
            const divId = this.value;
            departmentSelect.innerHTML = '<option value="">-- Select Department --</option>';
            allDeptOptions.forEach(function(opt) {
                if (!divId || opt.dataset.division === divId) {
                    departmentSelect.appendChild(opt.cloneNode(true));
                }
            });
        });
    }
});
</script>
