<?php
/**
 * PEGASUS ERP - Payroll Slip (Individual Employee)
 * Variables: $slip, $employee, $period, $earnings, $deductions, $attendance, $employerCosts
 */
extract($viewData ?? []);
$slip          = $slip ?? [];
$employee      = $employee ?? [];
$period        = $period ?? [];
$earnings      = $earnings ?? [];
$deductions    = $deductions ?? [];
$attendance    = $attendance ?? [];
$employerCosts = $employerCosts ?? [];

$grossPay       = (float) ($slip['gross_pay'] ?? 0);
$totalDeduction = (float) ($slip['total_deduction'] ?? 0);
$netPay         = (float) ($slip['net_pay'] ?? 0);
?>

<style>
    .slip-container { max-width: 900px; margin: 0 auto; }
    .slip-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 2px solid var(--color-primary); }
    .slip-logo { font-size: 22px; font-weight: 700; color: var(--color-primary); letter-spacing: 2px; }
    .slip-logo-sub { font-size: 11px; color: var(--color-text-muted); }
    .slip-period { text-align: right; font-size: 13px; color: var(--color-text-secondary); }
    .slip-emp-info { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; padding: 16px 24px; background: #FAFAFA; font-size: 13px; }
    .slip-emp-info dt { color: var(--color-text-muted); }
    .slip-emp-info dd { font-weight: 500; margin: 0; }
    .slip-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
    .slip-col { padding: 16px 24px; }
    .slip-col:first-child { border-right: 1px solid var(--color-border-light); }
    .slip-col h4 { font-size: 14px; font-weight: 600; margin-bottom: 12px; color: var(--color-text-primary); }
    .slip-line { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
    .slip-line.indent { padding-left: 16px; color: var(--color-text-secondary); }
    .slip-subtotal { display: flex; justify-content: space-between; padding: 8px 0 4px; font-size: 14px; font-weight: 600; border-top: 1px solid var(--color-border); margin-top: 8px; }
    .slip-net { text-align: center; padding: 20px 24px; background: var(--color-primary-light); }
    .slip-net-label { font-size: 14px; color: var(--color-text-secondary); margin-bottom: 4px; }
    .slip-net-amount { font-size: 28px; font-weight: 700; color: var(--color-primary-dark); }
    .slip-bottom { padding: 16px 24px; }
    .slip-bottom h4 { font-size: 14px; font-weight: 600; margin-bottom: 10px; }
    .slip-summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 20px; }
    .slip-summary-item { text-align: center; padding: 8px; background: #FAFAFA; border-radius: 6px; }
    .slip-summary-item .label { font-size: 11px; color: var(--color-text-muted); }
    .slip-summary-item .value { font-size: 15px; font-weight: 600; }
    .slip-employer { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    @media print {
        .no-print { display: none !important; }
        .main-content { margin: 0; padding: 0; }
        .navbar, .sidebar, .sidebar-overlay, .footer { display: none !important; }
    }
    @media (max-width: 768px) {
        .slip-columns { grid-template-columns: 1fr; }
        .slip-col:first-child { border-right: none; border-bottom: 1px solid var(--color-border-light); }
        .slip-summary-grid { grid-template-columns: repeat(3, 1fr); }
        .slip-employer { grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="page-header no-print" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;">Payroll Slip</h1>
    <div style="display:flex;gap:8px;">
        <a href="/payroll/<?= e($period['payroll_id'] ?? '') ?>" class="btn btn-cancel">Back</a>
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>
</div>

<div class="slip-container card">
    <!-- Header -->
    <div class="slip-header">
        <div>
            <div class="slip-logo">PEGASUS ERP</div>
            <div class="slip-logo-sub">Payroll Statement</div>
        </div>
        <div class="slip-period">
            <div style="font-weight:500;"><?= e($period['period'] ?? '') ?></div>
            <div>Pay Date: <?= e(formatDate($period['pay_date'] ?? '', 'd/m/Y')) ?></div>
        </div>
    </div>

    <!-- Employee Info -->
    <div class="slip-emp-info">
        <dt>Employee Code</dt>
        <dd><?= e($employee['emp_code'] ?? '') ?></dd>
        <dt>Name</dt>
        <dd><?= e($employee['full_name'] ?? '') ?></dd>
        <dt>Department</dt>
        <dd><?= e($employee['department_name'] ?? '') ?></dd>
        <dt>Position</dt>
        <dd><?= e($employee['position_title'] ?? '') ?></dd>
    </div>

    <!-- Earnings & Deductions -->
    <div class="slip-columns">
        <!-- Left: Earnings -->
        <div class="slip-col">
            <h4>Earnings</h4>
            <div class="slip-line">
                <span>Base Salary</span>
                <span><?= formatMoney($earnings['base_salary'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Overtime Pay</span>
                <span><?= formatMoney($earnings['overtime_pay'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Holiday Pay</span>
                <span><?= formatMoney($earnings['holiday_pay'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Night Differential</span>
                <span><?= formatMoney($earnings['night_diff'] ?? 0) ?></span>
            </div>
            <div class="slip-line" style="margin-top:6px;font-weight:500;">
                <span>Allowances</span>
                <span></span>
            </div>
            <div class="slip-line indent">
                <span>Position Allowance</span>
                <span><?= formatMoney($earnings['allow_position'] ?? 0) ?></span>
            </div>
            <div class="slip-line indent">
                <span>Transport Allowance</span>
                <span><?= formatMoney($earnings['allow_transport'] ?? 0) ?></span>
            </div>
            <div class="slip-line indent">
                <span>Meal Allowance</span>
                <span><?= formatMoney($earnings['allow_meal'] ?? 0) ?></span>
            </div>
            <div class="slip-line indent">
                <span>Housing Allowance</span>
                <span><?= formatMoney($earnings['allow_housing'] ?? 0) ?></span>
            </div>
            <div class="slip-line indent">
                <span>Other Allowance</span>
                <span><?= formatMoney($earnings['allow_other'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Bonus</span>
                <span><?= formatMoney($earnings['bonus'] ?? 0) ?></span>
            </div>
            <div class="slip-subtotal">
                <span>Gross Pay</span>
                <span><?= formatMoney($grossPay) ?></span>
            </div>
        </div>

        <!-- Right: Deductions -->
        <div class="slip-col">
            <h4>Deductions</h4>
            <div class="slip-line">
                <span>SSO Employee (5%)</span>
                <span><?= formatMoney($deductions['sso_employee'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Income Tax (PND1)</span>
                <span><?= formatMoney($deductions['income_tax'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Provident Fund</span>
                <span><?= formatMoney($deductions['provident_fund'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Late Deduction</span>
                <span><?= formatMoney($deductions['late_deduction'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Advance Deduction</span>
                <span><?= formatMoney($deductions['advance_deduction'] ?? 0) ?></span>
            </div>
            <div class="slip-line">
                <span>Other Deduction</span>
                <span><?= formatMoney($deductions['other_deduction'] ?? 0) ?></span>
            </div>
            <div class="slip-subtotal">
                <span>Total Deduction</span>
                <span><?= formatMoney($totalDeduction) ?></span>
            </div>
        </div>
    </div>

    <!-- Net Pay -->
    <div class="slip-net">
        <div class="slip-net-label">Net Pay</div>
        <div class="slip-net-amount"><?= formatMoney($netPay) ?></div>
    </div>

    <!-- Bottom Section -->
    <div class="slip-bottom">
        <!-- Attendance Summary -->
        <h4>Attendance Summary</h4>
        <div class="slip-summary-grid">
            <div class="slip-summary-item">
                <div class="label">Work Days</div>
                <div class="value"><?= e($attendance['work_days'] ?? 0) ?></div>
            </div>
            <div class="slip-summary-item">
                <div class="label">Regular Hours</div>
                <div class="value"><?= e(number_format($attendance['regular_hours'] ?? 0, 1)) ?></div>
            </div>
            <div class="slip-summary-item">
                <div class="label">OT Hours</div>
                <div class="value"><?= e(number_format($attendance['ot_hours'] ?? 0, 1)) ?></div>
            </div>
            <div class="slip-summary-item">
                <div class="label">Holiday Hours</div>
                <div class="value"><?= e(number_format($attendance['holiday_hours'] ?? 0, 1)) ?></div>
            </div>
            <div class="slip-summary-item">
                <div class="label">Leave Days</div>
                <div class="value"><?= e(number_format($attendance['leave_days'] ?? 0, 1)) ?></div>
            </div>
        </div>

        <!-- Employer Costs -->
        <h4>Employer Costs</h4>
        <div class="slip-employer">
            <div class="slip-summary-item">
                <div class="label">SSO Employer (5%)</div>
                <div class="value"><?= formatMoney($employerCosts['sso_employer'] ?? 0) ?></div>
            </div>
            <div class="slip-summary-item">
                <div class="label">Provident Fund ER</div>
                <div class="value"><?= formatMoney($employerCosts['provident_fund_er'] ?? 0) ?></div>
            </div>
            <div class="slip-summary-item">
                <div class="label">WCF</div>
                <div class="value"><?= formatMoney($employerCosts['wcf'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>
