<?php
/**
 * PEGASUS ERP - Payroll Controller
 * Thai labor law compliant payroll processing
 */

class PayrollController extends Controller
{
    /**
     * Thai Personal Income Tax progressive rates (2025)
     * Applied after personal deduction (60,000) and employment income deduction (50% up to 100,000)
     */
    private $taxBrackets = [
        ['min' => 0,         'max' => 150000,   'rate' => 0.00],
        ['min' => 150001,    'max' => 300000,    'rate' => 0.05],
        ['min' => 300001,    'max' => 500000,    'rate' => 0.10],
        ['min' => 500001,    'max' => 750000,    'rate' => 0.15],
        ['min' => 750001,    'max' => 1000000,   'rate' => 0.20],
        ['min' => 1000001,   'max' => 2000000,   'rate' => 0.25],
        ['min' => 2000001,   'max' => 5000000,   'rate' => 0.30],
        ['min' => 5000001,   'max' => PHP_INT_MAX, 'rate' => 0.35],
    ];

    private const PERSONAL_DEDUCTION = 60000;
    private const EMPLOYMENT_DEDUCTION_RATE = 0.50;
    private const EMPLOYMENT_DEDUCTION_MAX = 100000;
    private const SSO_RATE = 0.05;
    private const SSO_MAX_CONTRIBUTION = 750;
    private const SSO_SALARY_CAP = 15000;
    private const OT_MULTIPLIER = 1.5;
    private const HOLIDAY_MULTIPLIER = 2.0;
    private const NIGHT_DIFF_MULTIPLIER = 0.25;
    private const MILEAGE_RATE = 5.0;

    /**
     * List payroll periods with status
     */
    public function index()
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'HR_MANAGER', 'PAYROLL']);
        $db = Database::getInstance();

        try {
            $statusFilter = sanitize($this->input('status', ''));
            $page = max(1, (int) $this->input('page', 1));
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $where = ['ph.is_deleted = FALSE'];
            $params = [];

            if (!empty($statusFilter)) {
                $where[] = "ph.status = ?";
                $params[] = $statusFilter;
            }

            $whereClause = implode(' AND ', $where);

            $countRow = $db->fetch(
                "SELECT COUNT(*) as total FROM payroll_headers ph WHERE {$whereClause}",
                $params
            );
            $total = (int) ($countRow['total'] ?? 0);

            $queryParams = array_merge($params, [$perPage, $offset]);
            $payrolls = $db->fetchAll(
                "SELECT ph.*, d.division_name,
                        COALESCE(ph.total_gross_thb, 0) as total_gross_thb,
                        COALESCE(ph.total_net_thb, 0) as total_net_thb,
                        COALESCE(ph.total_deduction_thb, 0) as total_deduction_thb
                 FROM payroll_headers ph
                 LEFT JOIN divisions d ON d.division_id = ph.division_id
                 WHERE {$whereClause}
                 ORDER BY ph.pay_date DESC
                 LIMIT ? OFFSET ?",
                $queryParams
            );

            $this->render('payroll/index', [
                'pageTitle' => 'Payroll',
                'payrolls' => $payrolls ?: [],
                'statusFilter' => $statusFilter,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]);
        } catch (Exception $e) {
            error_log('PayrollController::index error: ' . $e->getMessage());
            flash('error', 'Failed to load payroll list.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Run monthly payroll calculation for all employees in a division
     */
    public function calculate()
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'PAYROLL']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $divisionId = (int) ($_POST['division_id'] ?? 0);
            $period = sanitize($_POST['period'] ?? date('Y-m'));
            $payDate = sanitize($_POST['pay_date'] ?? '');

            if ($divisionId <= 0 || empty($period) || empty($payDate)) {
                flash('error', 'Division, period, and pay date are required.');
                $this->redirect('/hr/payroll');
                return;
            }

            // Derive period start/end from period (YYYY-MM)
            $periodStart = $period . '-01';
            $periodEnd = date('Y-m-t', strtotime($periodStart));

            $db->beginTransaction();

            // Generate payroll number
            $payrollNo = $this->generatePayrollNo($db);

            // Create payroll header
            $row = $db->fetch(
                "INSERT INTO payroll_headers (payroll_no, division_id, period, pay_date, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'DRAFT', NOW(), NOW())
                 RETURNING payroll_id",
                [$payrollNo, $divisionId, $period, $payDate]
            );

            $payrollId = $row['payroll_id'];

            // Calculate working days in the month
            $startDt = new DateTime($periodStart);
            $endDt = new DateTime($periodEnd);
            $workingDays = $this->calculateWorkingDays($startDt, $endDt);

            // Step 1: Get all active employees for the division
            $employees = $db->fetchAll(
                "SELECT e.*
                 FROM employees e
                 WHERE e.division_id = ? AND e.is_current = TRUE AND e.is_deleted = FALSE",
                [$divisionId]
            );

            $totalGross = 0;
            $totalNet = 0;
            $totalDeductions = 0;
            $totalSsoEmployee = 0;
            $totalSsoEmployer = 0;
            $totalTax = 0;

            foreach ($employees ?: [] as $emp) {
                // Step 2: Get attendance summary for the period
                $attendance = $db->fetch(
                    "SELECT
                        COALESCE(SUM(regular_hours), 0) as total_regular,
                        COALESCE(SUM(overtime_hours), 0) as total_overtime,
                        COALESCE(SUM(holiday_hours), 0) as total_holiday,
                        COALESCE(SUM(night_hours), 0) as total_night,
                        COUNT(*) as days_worked
                     FROM attendance_records
                     WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? AND is_deleted = FALSE",
                    [$emp['employee_id'], $periodStart, $periodEnd]
                );

                $baseSalary = (float) ($emp['base_salary'] ?? 0);

                // Step 4: hourly_rate = base_salary / (working_days_in_month * 8)
                $hoursInMonth = $workingDays * 8;
                $hourlyRate = $hoursInMonth > 0 ? $baseSalary / $hoursInMonth : 0;

                $otHours = (float) ($attendance['total_overtime'] ?? 0);
                $holidayHours = (float) ($attendance['total_holiday'] ?? 0);
                $nightHours = (float) ($attendance['total_night'] ?? 0);

                // Step 3: Calculate pay components
                $overtimePay = round($hourlyRate * self::OT_MULTIPLIER * $otHours, 2);
                $holidayPay = round($hourlyRate * self::HOLIDAY_MULTIPLIER * $holidayHours, 2);
                $nightDifferential = round($hourlyRate * self::NIGHT_DIFF_MULTIPLIER * $nightHours, 2);

                // Step 5: gross_pay
                $grossPay = $baseSalary + $overtimePay + $holidayPay + $nightDifferential;

                // Step 6: SSO calculation
                $ssoBase = min($grossPay, self::SSO_SALARY_CAP);
                $ssoEmployee = min($ssoBase * self::SSO_RATE, self::SSO_MAX_CONTRIBUTION);
                $ssoEmployer = $ssoEmployee; // Step 7: Same calculation

                // Step 8: Income tax (PND1)
                $monthlyTax = $this->calculateMonthlyTax($grossPay);

                // Step 9: Net pay
                $empDeductions = $ssoEmployee + $monthlyTax;
                $netPay = $grossPay - $empDeductions;

                // Step 10: Save payroll_lines
                $db->query(
                    "INSERT INTO payroll_lines (
                        payroll_id, employee_id, base_salary,
                        overtime_hours, overtime_pay, holiday_hours, holiday_pay,
                        night_hours, night_differential, gross_pay,
                        sso_employee, sso_employer, income_tax_withhold,
                        total_deduction, net_pay, work_days,
                        regular_hours
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $payrollId, $emp['employee_id'], $baseSalary,
                        $otHours, $overtimePay, $holidayHours, $holidayPay,
                        $nightHours, $nightDifferential, round($grossPay, 2),
                        round($ssoEmployee, 2), round($ssoEmployer, 2), round($monthlyTax, 2),
                        round($empDeductions, 2), round($netPay, 2), $workingDays,
                        (float) ($attendance['total_regular'] ?? 0)
                    ]
                );

                $totalGross += $grossPay;
                $totalNet += $netPay;
                $totalDeductions += $empDeductions;
                $totalSsoEmployee += $ssoEmployee;
                $totalSsoEmployer += $ssoEmployer;
                $totalTax += $monthlyTax;
            }

            // Step 11: Update payroll_header totals
            $db->query(
                "UPDATE payroll_headers SET
                 total_gross_thb = ?, total_net_thb = ?,
                 total_deduction_thb = ?, total_sso_emp_thb = ?, total_sso_er_thb = ?,
                 total_tax_thb = ?, updated_at = NOW()
                 WHERE payroll_id = ?",
                [
                    round($totalGross, 2), round($totalNet, 2),
                    round($totalDeductions, 2), round($totalSsoEmployee, 2), round($totalSsoEmployer, 2),
                    round($totalTax, 2), $payrollId
                ]
            );

            $db->commit();

            flash('success', 'Payroll calculated successfully for ' . count($employees ?: []) . ' employees.');
            $this->redirect("/hr/payroll/{$payrollId}");
        } catch (Exception $e) {
            $db->rollback();
            error_log('PayrollController::calculate error: ' . $e->getMessage());
            flash('error', 'Failed to calculate payroll: ' . $e->getMessage());
            $this->redirect('/hr/payroll');
        }
    }

    /**
     * Show payroll detail with all employee lines
     */
    public function show($id)
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'HR_MANAGER', 'PAYROLL']);
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $payroll = $db->fetch(
                "SELECT ph.*, d.division_name
                 FROM payroll_headers ph
                 LEFT JOIN divisions d ON d.division_id = ph.division_id
                 WHERE ph.payroll_id = ? AND ph.is_deleted = FALSE",
                [$id]
            );

            if (!$payroll) {
                flash('error', 'Payroll record not found.');
                $this->redirect('/hr/payroll');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT pl.*, e.emp_code, e.full_name,
                        dep.department_name
                 FROM payroll_lines pl
                 JOIN employees e ON e.employee_id = pl.employee_id
                 LEFT JOIN departments dep ON dep.department_id = e.department_id
                 WHERE pl.payroll_id = ?
                 ORDER BY e.emp_code",
                [$id]
            );

            $this->render('payroll/index', [
                'pageTitle' => 'Payroll Detail - ' . ($payroll['period'] ?? ''),
                'payroll' => $payroll,
                'lines' => $lines ?: []
            ]);
        } catch (Exception $e) {
            error_log('PayrollController::show error: ' . $e->getMessage());
            flash('error', 'Failed to load payroll detail.');
            $this->redirect('/hr/payroll');
        }
    }

    /**
     * Approve payroll, change status
     */
    public function approve($id)
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'PAYROLL']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $currentUser = $this->getCurrentUser();

            $payroll = $db->fetch(
                "SELECT * FROM payroll_headers WHERE payroll_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$payroll) {
                flash('error', 'Payroll record not found.');
                $this->redirect('/hr/payroll');
                return;
            }

            if ($payroll['status'] !== 'DRAFT') {
                flash('error', 'Only draft payrolls can be approved.');
                $this->redirect("/hr/payroll/{$id}");
                return;
            }

            $db->query(
                "UPDATE payroll_headers SET
                 status = 'APPROVED', approved_by = ?, approved_at = NOW(), updated_at = NOW()
                 WHERE payroll_id = ?",
                [$currentUser['user_id'] ?? null, $id]
            );

            flash('success', 'Payroll approved successfully.');
            $this->redirect("/hr/payroll/{$id}");
        } catch (Exception $e) {
            error_log('PayrollController::approve error: ' . $e->getMessage());
            flash('error', 'Failed to approve payroll.');
            $this->redirect("/hr/payroll/{$id}");
        }
    }

    /**
     * Show individual payslip for payroll_line_id
     */
    public function slip($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $line = $db->fetch(
                "SELECT pl.*, e.emp_code, e.full_name,
                        e.bank_account_no, e.bank_code, e.thai_id, e.sso_no,
                        e.position_title,
                        dep.department_name,
                        ph.period, ph.pay_date, ph.status as payroll_status
                 FROM payroll_lines pl
                 JOIN employees e ON e.employee_id = pl.employee_id
                 JOIN payroll_headers ph ON ph.payroll_id = pl.payroll_id
                 LEFT JOIN departments dep ON dep.department_id = e.department_id
                 WHERE pl.payroll_line_id = ?",
                [$id]
            );

            if (!$line) {
                flash('error', 'Payslip not found.');
                $this->redirect('/hr/payroll');
                return;
            }

            $this->render('payroll/slip', [
                'pageTitle' => 'Payslip - ' . ($line['full_name'] ?? ''),
                'line' => $line
            ]);
        } catch (Exception $e) {
            error_log('PayrollController::slip error: ' . $e->getMessage());
            flash('error', 'Failed to load payslip.');
            $this->redirect('/hr/payroll');
        }
    }

    /**
     * Calculate monthly income tax (PND1) based on Thai progressive tax rates
     * Estimates annual income from monthly gross, applies deductions, then divides by 12
     */
    private function calculateMonthlyTax($monthlyGross)
    {
        // Estimate annual income
        $annualIncome = $monthlyGross * 12;

        // Employment income deduction: 50% of income, max 100,000 THB
        $employmentDeduction = min(
            $annualIncome * self::EMPLOYMENT_DEDUCTION_RATE,
            self::EMPLOYMENT_DEDUCTION_MAX
        );

        // Personal deduction: 60,000 THB
        $personalDeduction = self::PERSONAL_DEDUCTION;

        // Taxable income after deductions
        $taxableIncome = max($annualIncome - $employmentDeduction - $personalDeduction, 0);

        // Apply progressive tax rates
        $annualTax = 0;
        $remainingIncome = $taxableIncome;

        foreach ($this->taxBrackets as $bracket) {
            if ($remainingIncome <= 0) {
                break;
            }

            $bracketWidth = $bracket['max'] - $bracket['min'] + 1;
            $taxableInBracket = min($remainingIncome, $bracketWidth);
            $annualTax += $taxableInBracket * $bracket['rate'];
            $remainingIncome -= $taxableInBracket;
        }

        // Monthly tax = annual tax / 12
        return round($annualTax / 12, 2);
    }

    /**
     * Calculate working days between two dates (excludes weekends)
     */
    private function calculateWorkingDays(DateTime $start, DateTime $end)
    {
        $workingDays = 0;
        $current = clone $start;

        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('N');
            if ($dayOfWeek <= 5) { // Monday to Friday
                $workingDays++;
            }
            $current->modify('+1 day');
        }

        return $workingDays;
    }

    /**
     * Generate payroll number: PR-{YYYY}{MM}{NNNN}
     */
    private function generatePayrollNo($db)
    {
        $yearMonth = date('Ym');
        $prefix = 'PR-' . $yearMonth;

        $row = $db->fetch(
            "SELECT payroll_no FROM payroll_headers
             WHERE payroll_no LIKE ?
             ORDER BY payroll_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['payroll_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
