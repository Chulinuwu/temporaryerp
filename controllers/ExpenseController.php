<?php
/**
 * PEGASUS ERP - Expense Controller
 * Expense claims management with mileage support
 */

class ExpenseController extends Controller
{
    private const MILEAGE_RATE_PER_KM = 5.0; // THB per kilometer

    /**
     * List expense claims with status filter
     */
    public function index()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $statusFilter = sanitize($this->input('status', ''));
            $page = max(1, (int) $this->input('page', 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $where = ['ec.is_deleted = FALSE'];
            $params = [];

            if (!empty($statusFilter)) {
                $where[] = "ec.status = ?";
                $params[] = $statusFilter;
            }

            $whereClause = implode(' AND ', $where);

            $countRow = $db->fetch(
                "SELECT COUNT(*) as total FROM expense_claims ec WHERE {$whereClause}",
                $params
            );
            $total = (int) ($countRow['total'] ?? 0);

            $queryParams = array_merge($params, [$perPage, $offset]);
            $claims = $db->fetchAll(
                "SELECT ec.*, e.emp_code, e.full_name,
                        COALESCE(ec.total_amount_thb, 0) as total_amount_thb,
                        COALESCE(approver.full_name, '') as approved_by_name
                 FROM expense_claims ec
                 JOIN employees e ON e.employee_id = ec.employee_id
                 LEFT JOIN employees approver ON approver.employee_id = ec.approved_by
                 WHERE {$whereClause}
                 ORDER BY ec.created_at DESC
                 LIMIT ? OFFSET ?",
                $queryParams
            );

            $this->render('expense/claims', [
                'pageTitle' => 'Expense Claims',
                'claims' => $claims ?: [],
                'statusFilter' => $statusFilter,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]);
        } catch (Exception $e) {
            error_log('ExpenseController::index error: ' . $e->getMessage());
            flash('error', 'Failed to load expense claims.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Show expense claim form with category dropdown and mileage fields
     */
    public function create()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $employees = $db->fetchAll(
                "SELECT employee_id, emp_code, full_name
                 FROM employees
                 WHERE is_current = TRUE AND is_deleted = FALSE
                 ORDER BY full_name"
            );

            $this->render('expense/claim_form', [
                'pageTitle' => 'New Expense Claim',
                'employees' => $employees ?: [],
                'mileageRate' => self::MILEAGE_RATE_PER_KM
            ]);
        } catch (Exception $e) {
            error_log('ExpenseController::create error: ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/expense/claims');
        }
    }

    /**
     * Save expense claim header + lines
     * For mileage lines: set is_mileage_claim=TRUE, calculate amount = distance_km * rate_per_km
     * Auto-map account_code from expense_account_mapping table
     */
    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $employeeId = (int) ($_POST['employee_id'] ?? 0);
            $claimDate = sanitize($_POST['claim_date'] ?? date('Y-m-d'));
            $title = sanitize($_POST['title'] ?? '');
            $purpose = sanitize($_POST['purpose'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $period = sanitize($_POST['period'] ?? date('Y-m'));
            $lines = $_POST['lines'] ?? [];

            if ($employeeId <= 0 || empty($lines)) {
                flash('error', 'Employee and at least one expense line are required.');
                $this->redirect('/expense/claims/create');
                return;
            }

            // Get employee's division if not provided
            if (empty($divisionId)) {
                $empRow = $db->fetch(
                    "SELECT division_id FROM employees WHERE employee_id = ?",
                    [$employeeId]
                );
                $divisionId = $empRow['division_id'] ?? 1;
            }

            $db->beginTransaction();

            // Generate claim number
            $claimNo = $this->generateClaimNo($db);

            // Create claim header
            $row = $db->fetch(
                "INSERT INTO expense_claims (claim_no, employee_id, division_id, claim_date, period, title, purpose, status, total_amount_thb, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'DRAFT', 0, NOW(), NOW())
                 RETURNING claim_id",
                [$claimNo, $employeeId, $divisionId, $claimDate, $period, $title, $purpose]
            );

            $claimId = $row['claim_id'];
            $totalAmount = 0;
            $lineNo = 1;

            foreach ($lines as $line) {
                $expenseCategory = sanitize($line['expense_category'] ?? 'OTHER');
                $lineDescription = sanitize($line['description'] ?? '');
                $isMileage = !empty($line['is_mileage_claim']);
                $amountThb = 0;
                $distanceKm = 0;
                $ratePerKm = 0;

                // Auto-map account_code from expense_account_mapping
                $accountMapping = $db->fetch(
                    "SELECT account_code FROM expense_account_mapping
                     WHERE expense_category = ? AND is_deleted = FALSE
                     ORDER BY effective_from DESC LIMIT 1",
                    [$expenseCategory]
                );

                $accountCode = $accountMapping['account_code'] ?? null;

                if ($isMileage) {
                    // Mileage line: calculate amount = distance_km * rate_per_km
                    $distanceKm = (float) ($line['distance_km'] ?? 0);
                    $ratePerKm = self::MILEAGE_RATE_PER_KM;
                    $amountThb = round($distanceKm * $ratePerKm, 2);
                } else {
                    $amountThb = round((float) ($line['amount_thb'] ?? 0), 2);
                }

                $expenseDate = sanitize($line['expense_date'] ?? $claimDate);

                $db->query(
                    "INSERT INTO expense_claim_lines (
                        claim_id, line_no, expense_date, expense_category, description, amount_thb,
                        is_mileage_claim, distance_km, rate_per_km,
                        account_code
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $claimId, $lineNo++, $expenseDate, $expenseCategory, $lineDescription, $amountThb,
                        $isMileage ? true : false, $distanceKm, $ratePerKm,
                        $accountCode
                    ]
                );

                $totalAmount += $amountThb;
            }

            // Update header total
            $db->query(
                "UPDATE expense_claims SET total_amount_thb = ?, updated_at = NOW()
                 WHERE claim_id = ?",
                [round($totalAmount, 2), $claimId]
            );

            $db->commit();

            flash('success', 'Expense claim created successfully.');
            $this->redirect("/expense/claims/{$claimId}");
        } catch (Exception $e) {
            $db->rollback();
            error_log('ExpenseController::store error: ' . $e->getMessage());
            flash('error', 'Failed to create expense claim.');
            $this->redirect('/expense/claims/create');
        }
    }

    /**
     * Show claim detail
     */
    public function show($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $claim = $db->fetch(
                "SELECT ec.*, e.emp_code, e.full_name,
                        dep.department_name,
                        COALESCE(approver.full_name, '') as approved_by_name
                 FROM expense_claims ec
                 JOIN employees e ON e.employee_id = ec.employee_id
                 LEFT JOIN departments dep ON dep.department_id = e.department_id
                 LEFT JOIN employees approver ON approver.employee_id = ec.approved_by
                 WHERE ec.claim_id = ? AND ec.is_deleted = FALSE",
                [$id]
            );

            if (!$claim) {
                flash('error', 'Expense claim not found.');
                $this->redirect('/expense/claims');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT ecl.*
                 FROM expense_claim_lines ecl
                 WHERE ecl.claim_id = ?
                 ORDER BY ecl.expense_date",
                [$id]
            );

            $this->render('expense/claims', [
                'pageTitle' => 'Expense Claim Detail',
                'claim' => $claim,
                'lines' => $lines ?: []
            ]);
        } catch (Exception $e) {
            error_log('ExpenseController::show error: ' . $e->getMessage());
            flash('error', 'Failed to load expense claim.');
            $this->redirect('/expense/claims');
        }
    }

    /**
     * Change status to SUBMITTED
     */
    public function submit($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $claim = $db->fetch(
                "SELECT * FROM expense_claims WHERE claim_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$claim) {
                flash('error', 'Expense claim not found.');
                $this->redirect('/expense/claims');
                return;
            }

            if ($claim['status'] !== 'DRAFT') {
                flash('error', 'Only draft claims can be submitted.');
                $this->redirect("/expense/claims/{$id}");
                return;
            }

            $db->query(
                "UPDATE expense_claims SET status = 'SUBMITTED', submitted_at = NOW(), updated_at = NOW()
                 WHERE claim_id = ?",
                [$id]
            );

            flash('success', 'Expense claim submitted for approval.');
            $this->redirect("/expense/claims/{$id}");
        } catch (Exception $e) {
            error_log('ExpenseController::submit error: ' . $e->getMessage());
            flash('error', 'Failed to submit expense claim.');
            $this->redirect("/expense/claims/{$id}");
        }
    }

    /**
     * Approve claim, set approved_by/at
     */
    public function approve($id)
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'MANAGER', 'FINANCE']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $currentUser = $this->getCurrentUser();

            $claim = $db->fetch(
                "SELECT * FROM expense_claims WHERE claim_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$claim) {
                flash('error', 'Expense claim not found.');
                $this->redirect('/expense/claims');
                return;
            }

            if ($claim['status'] !== 'SUBMITTED') {
                flash('error', 'Only submitted claims can be approved.');
                $this->redirect("/expense/claims/{$id}");
                return;
            }

            $db->query(
                "UPDATE expense_claims SET
                 status = 'APPROVED', approved_by = ?, approved_at = NOW(), updated_at = NOW()
                 WHERE claim_id = ?",
                [$currentUser['employee_id'] ?? null, $id]
            );

            flash('success', 'Expense claim approved.');
            $this->redirect("/expense/claims/{$id}");
        } catch (Exception $e) {
            error_log('ExpenseController::approve error: ' . $e->getMessage());
            flash('error', 'Failed to approve expense claim.');
            $this->redirect("/expense/claims/{$id}");
        }
    }

    /**
     * Reject with reason
     */
    public function reject($id)
    {
        $this->requireAuth();
        $this->requireRole(['ADMIN', 'MANAGER', 'FINANCE']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $rejectReason = sanitize($_POST['reject_reason'] ?? '');
            $currentUser = $this->getCurrentUser();

            if (empty($rejectReason)) {
                flash('error', 'Rejection reason is required.');
                $this->redirect("/expense/claims/{$id}");
                return;
            }

            $claim = $db->fetch(
                "SELECT * FROM expense_claims WHERE claim_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$claim) {
                flash('error', 'Expense claim not found.');
                $this->redirect('/expense/claims');
                return;
            }

            if ($claim['status'] !== 'SUBMITTED') {
                flash('error', 'Only submitted claims can be rejected.');
                $this->redirect("/expense/claims/{$id}");
                return;
            }

            $db->query(
                "UPDATE expense_claims SET
                 status = 'REJECTED', reject_reason = ?, approved_by = ?, approved_at = NOW(), updated_at = NOW()
                 WHERE claim_id = ?",
                [$rejectReason, $currentUser['employee_id'] ?? null, $id]
            );

            flash('success', 'Expense claim rejected.');
            $this->redirect("/expense/claims/{$id}");
        } catch (Exception $e) {
            error_log('ExpenseController::reject error: ' . $e->getMessage());
            flash('error', 'Failed to reject expense claim.');
            $this->redirect("/expense/claims/{$id}");
        }
    }

    /**
     * API endpoint - accept origin/destination, return distance estimate
     * Placeholder for Google Maps integration
     */
    public function calculateMileage()
    {
        $this->requireAuth();

        try {
            $origin = sanitize($this->input('origin', ''));
            $destination = sanitize($this->input('destination', ''));

            if (empty($origin) || empty($destination)) {
                $this->json(['error' => 'Origin and destination are required.'], 400);
                return;
            }

            // Placeholder: In production, integrate with Google Maps Distance Matrix API
            $distanceKm = 0;
            $amount = round($distanceKm * self::MILEAGE_RATE_PER_KM, 2);

            $this->json([
                'origin' => $origin,
                'destination' => $destination,
                'distance_km' => $distanceKm,
                'rate_per_km' => self::MILEAGE_RATE_PER_KM,
                'amount' => $amount,
                'currency' => 'THB'
            ]);
        } catch (Exception $e) {
            error_log('ExpenseController::calculateMileage error: ' . $e->getMessage());
            $this->json(['error' => 'Failed to calculate mileage.'], 500);
        }
    }

    /**
     * Generate claim number: EX-{YYYY}{MM}{NNNNNN}
     */
    private function generateClaimNo($db)
    {
        $yearMonth = date('Ym');
        $prefix = 'EX-' . $yearMonth;

        $row = $db->fetch(
            "SELECT claim_no FROM expense_claims
             WHERE claim_no LIKE ?
             ORDER BY claim_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['claim_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
