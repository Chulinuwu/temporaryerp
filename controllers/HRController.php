<?php
/**
 * PEGASUS ERP - HR Controller
 * Employee management, attendance, leave requests
 */

class HRController extends Controller
{
    /**
     * List employees with search/filter by department/status
     */
    public function employees()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $search = sanitize($this->input('search', ''));
            $department = sanitize($this->input('department', ''));
            $employmentType = sanitize($this->input('employment_type', ''));
            $page = max(1, (int) $this->input('page', 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $where = ['e.is_deleted = FALSE', 'e.is_current = TRUE'];
            $params = [];

            if (!empty($search)) {
                $where[] = "(e.full_name ILIKE ? OR e.emp_code ILIKE ? OR e.position_title ILIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            if (!empty($department)) {
                $where[] = "e.department_id = ?";
                $params[] = $department;
            }

            if (!empty($employmentType)) {
                $where[] = "e.employment_type = ?";
                $params[] = $employmentType;
            }

            $whereClause = implode(' AND ', $where);

            $countRow = $db->fetch(
                "SELECT COUNT(*) as total FROM employees e WHERE {$whereClause}",
                $params
            );
            $total = (int) ($countRow['total'] ?? 0);

            $queryParams = array_merge($params, [$perPage, $offset]);
            $employees = $db->fetchAll(
                "SELECT e.*, d.department_name
                 FROM employees e
                 LEFT JOIN departments d ON d.department_id = e.department_id
                 WHERE {$whereClause}
                 ORDER BY e.emp_code ASC
                 LIMIT ? OFFSET ?",
                $queryParams
            );

            $departments = $db->fetchAll(
                "SELECT department_id, department_name FROM departments
                 WHERE is_deleted = FALSE ORDER BY department_name"
            );

            $this->render('hr/employees', [
                'pageTitle' => 'Employees',
                'employees' => $employees ?: [],
                'departments' => $departments ?: [],
                'search' => $search,
                'department' => $department,
                'employmentType' => $employmentType,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]);
        } catch (Exception $e) {
            error_log('HRController::employees error: ' . $e->getMessage());
            flash('error', 'Failed to load employee list.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Show employee creation form
     */
    public function createEmployee()
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'HR_MANAGER']);
        $db = Database::getInstance();

        try {
            $departments = $db->fetchAll(
                "SELECT department_id, department_name FROM departments
                 WHERE is_deleted = FALSE ORDER BY department_name"
            );

            $divisions = $db->fetchAll(
                "SELECT division_id, division_name FROM divisions
                 WHERE is_deleted = FALSE ORDER BY division_name"
            );

            $this->render('hr/employee_form', [
                'pageTitle' => 'Add Employee',
                'departments' => $departments ?: [],
                'divisions' => $divisions ?: []
            ]);
        } catch (Exception $e) {
            error_log('HRController::createEmployee error: ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/hr/employees');
        }
    }

    /**
     * Save new employee to DB
     */
    public function storeEmployee()
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'HR_MANAGER']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $empCode = sanitize($_POST['emp_code'] ?? '');
            $fullName = sanitize($_POST['full_name'] ?? '');
            $fullNameJp = sanitize($_POST['full_name_jp'] ?? '');
            $fullNameTh = sanitize($_POST['full_name_th'] ?? '');
            $nickname = sanitize($_POST['nickname'] ?? '');
            $nationality = sanitize($_POST['nationality'] ?? 'TH');
            $thaiId = sanitize($_POST['thai_id'] ?? '');
            $passportNo = sanitize($_POST['passport_no'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '');
            $departmentId = sanitize($_POST['department_id'] ?? '');
            $hireDate = sanitize($_POST['hire_date'] ?? '');
            $employmentType = sanitize($_POST['employment_type'] ?? 'FULL_TIME');
            $positionTitle = sanitize($_POST['position_title'] ?? '');
            $positionLevel = sanitize($_POST['position_level'] ?? '');
            // Salary/bank fields removed (payroll feature not used)
            $ssoEnrolled = !empty($_POST['sso_enrolled']);
            $ssoNo = sanitize($_POST['sso_no'] ?? '');
            $annualLeaveDays = (float) ($_POST['annual_leave_days'] ?? 6);
            $sickLeaveDays = (float) ($_POST['sick_leave_days'] ?? 30);
            $workPermitNo = sanitize($_POST['work_permit_no'] ?? '');
            $workPermitExpiry = sanitize($_POST['work_permit_expiry'] ?? '') ?: null;
            $visaType = sanitize($_POST['visa_type'] ?? '');
            $visaExpiry = sanitize($_POST['visa_expiry'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            if (empty($empCode) || empty($fullName) || empty($divisionId)) {
                flash('error', 'Employee code, full name, and division are required.');
                $this->redirect('/hr/employees/create');
                return;
            }

            // Check duplicate employee code
            $existing = $db->fetch(
                "SELECT employee_id FROM employees WHERE emp_code = ? AND is_deleted = FALSE",
                [$empCode]
            );
            if ($existing) {
                flash('error', 'Employee code already exists.');
                $this->redirect('/hr/employees/create');
                return;
            }

            $db->query(
                "INSERT INTO employees (emp_code, full_name, full_name_jp, full_name_th, nickname,
                 nationality, thai_id, passport_no, email, phone,
                 division_id, department_id, hire_date, employment_type,
                 position_title, position_level,
                 sso_enrolled, sso_no, annual_leave_days, sick_leave_days,
                 work_permit_no, work_permit_expiry, visa_type, visa_expiry,
                 is_current, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE, ?, NOW(), NOW())",
                [$empCode, $fullName, $fullNameJp, $fullNameTh, $nickname,
                 $nationality, $thaiId, $passportNo, $email, $phone,
                 $divisionId, $departmentId ?: null, $hireDate ?: null, $employmentType,
                 $positionTitle, $positionLevel,
                 $ssoEnrolled, $ssoNo, $annualLeaveDays, $sickLeaveDays,
                 $workPermitNo, $workPermitExpiry, $visaType, $visaExpiry,
                 $user['user_id'] ?? null]
            );

            flash('success', 'Employee created successfully.');
            $this->redirect('/hr/employees');
        } catch (Exception $e) {
            error_log('HRController::storeEmployee error: ' . $e->getMessage());
            flash('error', 'Failed to create employee.');
            $this->redirect('/hr/employees/create');
        }
    }

    /**
     * Show employee detail
     */
    public function showEmployee($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $employee = $db->fetch(
                "SELECT e.*, d.department_name, div.division_name
                 FROM employees e
                 LEFT JOIN departments d ON d.department_id = e.department_id
                 LEFT JOIN divisions div ON div.division_id = e.division_id
                 WHERE e.employee_id = ? AND e.is_deleted = FALSE",
                [$id]
            );

            if (!$employee) {
                flash('error', 'Employee not found.');
                $this->redirect('/hr/employees');
                return;
            }

            $recentAttendance = $db->fetchAll(
                "SELECT * FROM attendance_records
                 WHERE employee_id = ? AND is_deleted = FALSE
                 ORDER BY attendance_date DESC LIMIT 10",
                [$id]
            );

            $this->render('hr/employees', [
                'pageTitle' => 'Employee Detail',
                'employee' => $employee,
                'recentAttendance' => $recentAttendance ?: []
            ]);
        } catch (Exception $e) {
            error_log('HRController::showEmployee error: ' . $e->getMessage());
            flash('error', 'Failed to load employee detail.');
            $this->redirect('/hr/employees');
        }
    }

    /**
     * Edit employee form
     */
    public function editEmployee($id)
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'HR_MANAGER']);
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $employee = $db->fetch(
                "SELECT * FROM employees WHERE employee_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$employee) {
                flash('error', 'Employee not found.');
                $this->redirect('/hr/employees');
                return;
            }

            $departments = $db->fetchAll(
                "SELECT department_id, department_name FROM departments
                 WHERE is_deleted = FALSE ORDER BY department_name"
            );

            $divisions = $db->fetchAll(
                "SELECT division_id, division_name FROM divisions
                 WHERE is_deleted = FALSE ORDER BY division_name"
            );

            $this->render('hr/employee_form', [
                'pageTitle' => 'Edit Employee',
                'employee' => $employee,
                'departments' => $departments ?: [],
                'divisions' => $divisions ?: []
            ]);
        } catch (Exception $e) {
            error_log('HRController::editEmployee error: ' . $e->getMessage());
            flash('error', 'Failed to load employee data.');
            $this->redirect('/hr/employees');
        }
    }

    /**
     * Update employee record
     */
    public function updateEmployee($id)
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'HR_MANAGER']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $fullName = sanitize($_POST['full_name'] ?? '');
            $fullNameJp = sanitize($_POST['full_name_jp'] ?? '');
            $fullNameTh = sanitize($_POST['full_name_th'] ?? '');
            $nickname = sanitize($_POST['nickname'] ?? '');
            $nationality = sanitize($_POST['nationality'] ?? 'TH');
            $thaiId = sanitize($_POST['thai_id'] ?? '');
            $passportNo = sanitize($_POST['passport_no'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '');
            $departmentId = sanitize($_POST['department_id'] ?? '');
            $employmentType = sanitize($_POST['employment_type'] ?? 'FULL_TIME');
            $positionTitle = sanitize($_POST['position_title'] ?? '');
            $positionLevel = sanitize($_POST['position_level'] ?? '');
            // Salary/bank fields removed (payroll feature not used)
            $ssoEnrolled = !empty($_POST['sso_enrolled']);
            $ssoNo = sanitize($_POST['sso_no'] ?? '');
            $annualLeaveDays = (float) ($_POST['annual_leave_days'] ?? 6);
            $sickLeaveDays = (float) ($_POST['sick_leave_days'] ?? 30);
            $workPermitNo = sanitize($_POST['work_permit_no'] ?? '');
            $workPermitExpiry = sanitize($_POST['work_permit_expiry'] ?? '') ?: null;
            $visaType = sanitize($_POST['visa_type'] ?? '');
            $visaExpiry = sanitize($_POST['visa_expiry'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            if (empty($fullName)) {
                flash('error', 'Full name is required.');
                $this->redirect("/hr/employees/{$id}/edit");
                return;
            }

            $db->query(
                "UPDATE employees SET
                 full_name = ?, full_name_jp = ?, full_name_th = ?, nickname = ?,
                 nationality = ?, thai_id = ?, passport_no = ?,
                 email = ?, phone = ?,
                 division_id = ?, department_id = ?,
                 employment_type = ?, position_title = ?, position_level = ?,
                 sso_enrolled = ?, sso_no = ?,
                 annual_leave_days = ?, sick_leave_days = ?,
                 work_permit_no = ?, work_permit_expiry = ?,
                 visa_type = ?, visa_expiry = ?,
                 updated_by = ?, updated_at = NOW()
                 WHERE employee_id = ? AND is_deleted = FALSE",
                [$fullName, $fullNameJp, $fullNameTh, $nickname,
                 $nationality, $thaiId, $passportNo,
                 $email, $phone,
                 $divisionId ?: null, $departmentId ?: null,
                 $employmentType, $positionTitle, $positionLevel,
                 $ssoEnrolled, $ssoNo,
                 $annualLeaveDays, $sickLeaveDays,
                 $workPermitNo, $workPermitExpiry,
                 $visaType, $visaExpiry,
                 $user['user_id'] ?? null, $id]
            );

            flash('success', 'Employee updated successfully.');
            $this->redirect("/hr/employees/{$id}");
        } catch (Exception $e) {
            error_log('HRController::updateEmployee error: ' . $e->getMessage());
            flash('error', 'Failed to update employee.');
            $this->redirect("/hr/employees/{$id}/edit");
        }
    }

    /**
     * Show attendance records for current month, allow date filter
     */
    public function attendance()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $monthFilter = sanitize($this->input('month', date('Y-m')));
            $departmentFilter = sanitize($this->input('department', ''));
            $page = max(1, (int) $this->input('page', 1));
            $perPage = 50;
            $offset = ($page - 1) * $perPage;

            $where = ["a.is_deleted = FALSE", "TO_CHAR(a.attendance_date, 'YYYY-MM') = ?"];
            $params = [$monthFilter];

            if (!empty($departmentFilter)) {
                $where[] = "e.department_id = ?";
                $params[] = $departmentFilter;
            }

            $whereClause = implode(' AND ', $where);

            $countRow = $db->fetch(
                "SELECT COUNT(*) as total
                 FROM attendance_records a
                 JOIN employees e ON e.employee_id = a.employee_id
                 WHERE {$whereClause}",
                $params
            );
            $total = (int) ($countRow['total'] ?? 0);

            $queryParams = array_merge($params, [$perPage, $offset]);
            $records = $db->fetchAll(
                "SELECT a.*, e.emp_code, e.full_name, d.department_name
                 FROM attendance_records a
                 JOIN employees e ON e.employee_id = a.employee_id
                 LEFT JOIN departments d ON d.department_id = e.department_id
                 WHERE {$whereClause}
                 ORDER BY a.attendance_date DESC, e.emp_code ASC
                 LIMIT ? OFFSET ?",
                $queryParams
            );

            $departments = $db->fetchAll(
                "SELECT department_id, department_name FROM departments
                 WHERE is_deleted = FALSE ORDER BY department_name"
            );

            $this->render('hr/attendance', [
                'pageTitle' => 'Attendance Records',
                'records' => $records ?: [],
                'departments' => $departments ?: [],
                'monthFilter' => $monthFilter,
                'departmentFilter' => $departmentFilter,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]);
        } catch (Exception $e) {
            error_log('HRController::attendance error: ' . $e->getMessage());
            flash('error', 'Failed to load attendance records.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Record clock-in (POST: employee_id, method=MANUAL/QR/GPS)
     */
    public function clockIn()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $employeeId = (int) ($_POST['employee_id'] ?? 0);
            $method = sanitize($_POST['method'] ?? 'MANUAL');
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');

            if (!in_array($method, ['MANUAL', 'QR', 'GPS'])) {
                $method = 'MANUAL';
            }

            if ($employeeId <= 0) {
                flash('error', 'Invalid employee.');
                $this->redirect('/hr/attendance');
                return;
            }

            // Check if already clocked in today
            $existing = $db->fetch(
                "SELECT attendance_id FROM attendance_records
                 WHERE employee_id = ? AND attendance_date = ? AND is_deleted = FALSE",
                [$employeeId, $today]
            );

            if ($existing) {
                flash('error', 'Already clocked in for today.');
                $this->redirect('/hr/attendance');
                return;
            }

            $db->query(
                "INSERT INTO attendance_records (employee_id, attendance_date, clock_in, clock_in_method, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'PRESENT', NOW(), NOW())",
                [$employeeId, $today, $now, $method]
            );

            flash('success', 'Clock-in recorded successfully.');
            $this->redirect('/hr/attendance');
        } catch (Exception $e) {
            error_log('HRController::clockIn error: ' . $e->getMessage());
            flash('error', 'Failed to record clock-in.');
            $this->redirect('/hr/attendance');
        }
    }

    /**
     * Record clock-out
     */
    public function clockOut()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $employeeId = (int) ($_POST['employee_id'] ?? 0);
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');

            if ($employeeId <= 0) {
                flash('error', 'Invalid employee.');
                $this->redirect('/hr/attendance');
                return;
            }

            // Find today's attendance record
            $attendance = $db->fetch(
                "SELECT attendance_id, clock_in FROM attendance_records
                 WHERE employee_id = ? AND attendance_date = ? AND clock_out IS NULL AND is_deleted = FALSE",
                [$employeeId, $today]
            );

            if (!$attendance) {
                flash('error', 'No active clock-in found for today.');
                $this->redirect('/hr/attendance');
                return;
            }

            // Calculate work hours
            $clockIn = new DateTime($attendance['clock_in']);
            $clockOut = new DateTime($now);
            $diff = $clockOut->diff($clockIn);
            $totalHours = round($diff->h + ($diff->i / 60), 2);

            // Calculate overtime (over 8 hours)
            $regularHours = min($totalHours, 8.0);
            $overtimeHours = max($totalHours - 8.0, 0);

            $db->query(
                "UPDATE attendance_records SET
                 clock_out = ?, regular_hours = ?,
                 overtime_hours = ?, updated_at = NOW()
                 WHERE attendance_id = ?",
                [$now, $regularHours, $overtimeHours, $attendance['attendance_id']]
            );

            flash('success', 'Clock-out recorded successfully.');
            $this->redirect('/hr/attendance');
        } catch (Exception $e) {
            error_log('HRController::clockOut error: ' . $e->getMessage());
            flash('error', 'Failed to record clock-out.');
            $this->redirect('/hr/attendance');
        }
    }

    /**
     * List leave requests with status filter
     */
    public function leaveRequests()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $statusFilter = sanitize($this->input('status', ''));
            $page = max(1, (int) $this->input('page', 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $where = ['lr.is_deleted = FALSE'];
            $params = [];

            if (!empty($statusFilter)) {
                $where[] = "lr.status = ?";
                $params[] = $statusFilter;
            }

            $whereClause = implode(' AND ', $where);

            $countRow = $db->fetch(
                "SELECT COUNT(*) as total FROM leave_requests lr WHERE {$whereClause}",
                $params
            );
            $total = (int) ($countRow['total'] ?? 0);

            $queryParams = array_merge($params, [$perPage, $offset]);
            $requests = $db->fetchAll(
                "SELECT lr.*, e.emp_code, e.full_name,
                        COALESCE(approver.full_name, '') as approved_by_name
                 FROM leave_requests lr
                 JOIN employees e ON e.employee_id = lr.employee_id
                 LEFT JOIN employees approver ON approver.employee_id = lr.approved_by
                 WHERE {$whereClause}
                 ORDER BY lr.created_at DESC
                 LIMIT ? OFFSET ?",
                $queryParams
            );

            $this->render('hr/leave', [
                'pageTitle' => 'Leave Requests',
                'requests' => $requests ?: [],
                'statusFilter' => $statusFilter,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]);
        } catch (Exception $e) {
            error_log('HRController::leaveRequests error: ' . $e->getMessage());
            flash('error', 'Failed to load leave requests.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Create leave request
     */
    public function storeLeave()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $employeeId = (int) ($_POST['employee_id'] ?? 0);
            $leaveType = sanitize($_POST['leave_type'] ?? '');
            $startDate = sanitize($_POST['start_date'] ?? '');
            $endDate = sanitize($_POST['end_date'] ?? '');
            $daysRequested = (float) ($_POST['days_requested'] ?? 0);
            $halfDay = !empty($_POST['half_day']);
            $halfDayPeriod = sanitize($_POST['half_day_period'] ?? '');
            $reason = sanitize($_POST['reason'] ?? '');

            if ($employeeId <= 0 || empty($leaveType) || empty($startDate) || empty($endDate)) {
                flash('error', 'All fields are required.');
                $this->redirect('/hr/leave');
                return;
            }

            // Calculate number of days if not provided
            if ($daysRequested <= 0) {
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $daysRequested = (float) $start->diff($end)->days + 1;
            }

            // Check leave balance from employee record
            $empLeave = $db->fetch(
                "SELECT leave_balance_annual, leave_balance_sick
                 FROM employees WHERE employee_id = ? AND is_deleted = FALSE",
                [$employeeId]
            );

            if ($empLeave && $leaveType === 'ANNUAL') {
                $remainingDays = (float) ($empLeave['leave_balance_annual'] ?? 0);
                if ($daysRequested > $remainingDays) {
                    flash('error', "Insufficient annual leave balance. Available: {$remainingDays} days, Requested: {$daysRequested} days.");
                    $this->redirect('/hr/leave');
                    return;
                }
            }

            $db->query(
                "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date,
                 days_requested, half_day, half_day_period, reason, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW(), NOW())",
                [$employeeId, $leaveType, $startDate, $endDate, $daysRequested,
                 $halfDay, $halfDayPeriod ?: null, $reason]
            );

            flash('success', 'Leave request submitted successfully.');
            $this->redirect('/hr/leave');
        } catch (Exception $e) {
            error_log('HRController::storeLeave error: ' . $e->getMessage());
            flash('error', 'Failed to submit leave request.');
            $this->redirect('/hr/leave');
        }
    }

    /**
     * Approve leave, deduct from leave balance
     */
    public function approveLeave($id)
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'HR_MANAGER']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $currentUser = $this->getCurrentUser();

            $request = $db->fetch(
                "SELECT * FROM leave_requests WHERE leave_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$request) {
                flash('error', 'Leave request not found.');
                $this->redirect('/hr/leave');
                return;
            }

            if ($request['status'] !== 'PENDING') {
                flash('error', 'Only pending requests can be approved.');
                $this->redirect('/hr/leave');
                return;
            }

            $db->beginTransaction();

            // Update leave request status
            $db->query(
                "UPDATE leave_requests SET
                 status = 'APPROVED', approved_by = ?, approved_at = NOW(), updated_at = NOW()
                 WHERE leave_id = ?",
                [$currentUser['employee_id'] ?? null, $id]
            );

            // Deduct from employee leave balance
            $leaveType = $request['leave_type'];
            $daysRequested = (float) $request['days_requested'];

            if ($leaveType === 'ANNUAL') {
                $db->query(
                    "UPDATE employees SET leave_balance_annual = leave_balance_annual - ?, updated_at = NOW()
                     WHERE employee_id = ?",
                    [$daysRequested, $request['employee_id']]
                );
            } elseif ($leaveType === 'SICK') {
                $db->query(
                    "UPDATE employees SET leave_balance_sick = leave_balance_sick - ?, updated_at = NOW()
                     WHERE employee_id = ?",
                    [$daysRequested, $request['employee_id']]
                );
            }

            $db->commit();

            flash('success', 'Leave request approved.');
            $this->redirect('/hr/leave');
        } catch (Exception $e) {
            $db->rollback();
            error_log('HRController::approveLeave error: ' . $e->getMessage());
            flash('error', 'Failed to approve leave request.');
            $this->redirect('/hr/leave');
        }
    }

    /**
     * Reject leave with reason
     */
    public function rejectLeave($id)
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'HR_MANAGER']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $rejectReason = sanitize($_POST['reject_reason'] ?? '');
            $currentUser = $this->getCurrentUser();

            if (empty($rejectReason)) {
                flash('error', 'Rejection reason is required.');
                $this->redirect('/hr/leave');
                return;
            }

            $request = $db->fetch(
                "SELECT * FROM leave_requests WHERE leave_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$request) {
                flash('error', 'Leave request not found.');
                $this->redirect('/hr/leave');
                return;
            }

            if ($request['status'] !== 'PENDING') {
                flash('error', 'Only pending requests can be rejected.');
                $this->redirect('/hr/leave');
                return;
            }

            $db->query(
                "UPDATE leave_requests SET
                 status = 'REJECTED', reject_reason = ?, approved_by = ?, approved_at = NOW(), updated_at = NOW()
                 WHERE leave_id = ?",
                [$rejectReason, $currentUser['employee_id'] ?? null, $id]
            );

            flash('success', 'Leave request rejected.');
            $this->redirect('/hr/leave');
        } catch (Exception $e) {
            error_log('HRController::rejectLeave error: ' . $e->getMessage());
            flash('error', 'Failed to reject leave request.');
            $this->redirect('/hr/leave');
        }
    }
}
