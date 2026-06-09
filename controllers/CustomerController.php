<?php
/**
 * PEGASUS ERP - Customer Controller
 * CRUD operations for customers
 */

class CustomerController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $search = sanitize($_GET['search'] ?? '');

            $sql = "SELECT * FROM customers WHERE is_deleted = FALSE";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (customer_code ILIKE ? OR customer_name ILIKE ? OR tax_id ILIKE ? OR email ILIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $sql .= " ORDER BY customer_name";

            $customers = $db->fetchAll($sql, $params);

            $divisionsList = $db->fetchAll(
                "SELECT division_id, division_code, division_name FROM divisions WHERE is_deleted = FALSE ORDER BY division_code"
            );

            // Sales reps for dropdown (employees whose role/department is sales-related, or all active)
            $salesReps = $db->fetchAll(
                "SELECT e.employee_id,
                        e.emp_code,
                        COALESCE(NULLIF(e.full_name_jp,''), NULLIF(e.full_name_th,''), e.full_name) AS display_name
                 FROM employees e
                 WHERE e.is_deleted = FALSE
                   AND e.is_current = TRUE
                   AND COALESCE(e.termination_date, '9999-12-31'::date) > CURRENT_DATE
                 ORDER BY display_name
                 LIMIT 500"
            );

            $this->render('master/customers', [
                'pageTitle' => 'Customers',
                'customers' => $customers ?: [],
                'divisionsList' => $divisionsList ?: [],
                'salesReps' => $salesReps ?: [],
                'search' => $search
            ]);
        } catch (Exception $e) {
            error_log('CustomerController::index - ' . $e->getMessage());
            flash('error', 'Failed to load customers.');
            $this->render('master/customers', [
                'pageTitle' => 'Customers',
                'customers' => [],
                'search' => ''
            ]);
        }
    }

    public function save()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = sanitize($_POST['customer_id'] ?? '');
            $customerCode = sanitize($_POST['customer_code'] ?? '');
            $customerName = sanitize($_POST['customer_name'] ?? '');
            $customerNameJp = sanitize($_POST['customer_name_jp'] ?? '');
            $customerNameTh = sanitize($_POST['customer_name_th'] ?? '');
            $country = sanitize($_POST['country'] ?? 'TH') ?: 'TH';
            $taxId = sanitize($_POST['tax_id'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            $contactPerson = sanitize($_POST['contact_person'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $creditLimit = floatval($_POST['credit_limit'] ?? 0);
            $paymentTerms = intval($_POST['payment_terms'] ?? 30);
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $salesRepId = sanitize($_POST['sales_rep_id'] ?? '') ?: null;
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            if (empty($customerName)) {
                flash('error', 'Customer name is required.');
                $this->redirect('/master/customers');
                return;
            }

            // Ensure division_id has a value (NOT NULL constraint)
            if (empty($divisionId)) {
                $defaultDiv = $db->fetch("SELECT division_id FROM divisions WHERE is_deleted = FALSE ORDER BY division_id ASC LIMIT 1");
                $divisionId = $defaultDiv ? $defaultDiv['division_id'] : 1;
            }

            // Auto-generate customer_code for new customers — pick the actual numeric max
            if (!$id && empty($customerCode)) {
                $row = $db->fetch(
                    "SELECT COALESCE(MAX(CAST(SUBSTRING(customer_code FROM 5) AS INT)), 0) AS max_n
                     FROM customers WHERE customer_code ~ '^CUS-[0-9]+$'"
                );
                $nextNum = intval($row['max_n'] ?? 0) + 1;
                $customerCode = sprintf('CUS-%04d', $nextNum);
            }

            // Reject duplicate customer_code (active rows only)
            if (!empty($customerCode)) {
                $dup = $db->fetch(
                    "SELECT customer_id, customer_name FROM customers
                     WHERE customer_code = ? AND is_deleted = FALSE AND is_current = TRUE
                       AND customer_id <> ?",
                    [$customerCode, $id ?: 0]
                );
                if ($dup) {
                    flash('error', __('customer_code_duplicate') . ': ' . $customerCode . ' (' . $dup['customer_name'] . ')');
                    $this->redirect('/master/customers');
                    return;
                }
            }

            // Per policy: ALL master saves go through 2-step approval
            //   (Admin Manager → CEO). No more auto-approval, regardless of role.
            // Existing APPROVED rows being edited: revert to PENDING_MANAGER for re-approval.
            $approvalStatus = 'PENDING_MANAGER';
            $approvedBy = null; // legacy column, kept null until CEO step

            if ($id) {
                $db->query(
                    "UPDATE customers SET customer_code = ?, customer_name = ?, customer_name_jp = ?,
                     customer_name_th = ?, country = ?, tax_id = ?,
                     address = ?, contact_person = ?, phone = ?,
                     email = ?, credit_limit = ?, payment_terms = ?, currency_code = ?,
                     sales_rep_id = ?, division_id = ?,
                     approval_status = ?, approved_by = ?, approved_at = NULL,
                     submitted_by = ?, submitted_at = NOW(),
                     manager_approved_by = NULL, manager_approved_at = NULL,
                     ceo_approved_by = NULL, ceo_approved_at = NULL,
                     rejected_by = NULL, rejected_at = NULL, rejection_reason = NULL,
                     updated_by = ?, updated_at = NOW()
                     WHERE customer_id = ?",
                    [$customerCode, $customerName, $customerNameJp, $customerNameTh,
                     $country, $taxId, $address, $contactPerson,
                     $phone, $email, $creditLimit, $paymentTerms, $currencyCode,
                     $salesRepId, $divisionId,
                     $approvalStatus, $approvedBy,
                     $user['user_id'], $user['user_id'], $id]
                );
                flash('success', __('customer_updated') . ' (' . __('msg_submitted_for_approval') . ')');
            } else {
                $db->query(
                    "INSERT INTO customers (customer_code, customer_name, customer_name_jp, customer_name_th,
                     country, tax_id, address, contact_person, phone, email,
                     credit_limit, payment_terms, currency_code, sales_rep_id, division_id, created_by,
                     approval_status, submitted_by, submitted_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                    [$customerCode, $customerName, $customerNameJp, $customerNameTh,
                     $country, $taxId, $address, $contactPerson,
                     $phone, $email, $creditLimit, $paymentTerms, $currencyCode,
                     $salesRepId, $divisionId, $user['user_id'],
                     $approvalStatus, $user['user_id']]
                );
                flash('success', __('customer_created') . ' (' . __('msg_submitted_for_approval') . ')');
            }
        } catch (Exception $e) {
            error_log('CustomerController::save - ' . $e->getMessage());
            flash('error', 'Failed to save customer. ' . $e->getMessage());
        }

        $this->redirect('/master/customers');
    }

    public function edit($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $customer = $db->fetch(
                "SELECT * FROM customers WHERE customer_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$customer) {
                flash('error', 'Customer not found.');
                $this->redirect('/master/customers');
                return;
            }

            $this->render('master/customers', [
                'pageTitle' => 'Edit Customer',
                'customer' => $customer
            ]);
        } catch (Exception $e) {
            error_log('CustomerController::edit - ' . $e->getMessage());
            flash('error', 'Failed to load customer.');
            $this->redirect('/master/customers');
        }
    }

    public function delete($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE customers SET is_deleted = TRUE, updated_by = ?, updated_at = NOW()
                 WHERE customer_id = ?",
                [$user['user_id'], $id]
            );
            flash('success', 'Customer deleted.');
        } catch (Exception $e) {
            error_log('CustomerController::delete - ' . $e->getMessage());
            flash('error', 'Failed to delete customer.');
        }

        $this->redirect('/master/customers');
    }

    /** Manager+ approves a PENDING customer */
    public function approve($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/master/customers');
            return;
        }
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE customers SET approval_status='APPROVED', approved_by=?, approved_at=NOW(),
                                 updated_by=?, updated_at=NOW()
             WHERE customer_id=? AND approval_status='PENDING'",
            [$user['user_id'], $user['user_id'], $id]
        );
        flash('success', __('msg_approved'));
        $this->redirect('/master/customers');
    }

    /** Manager+ rejects a PENDING customer */
    public function reject($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/master/customers');
            return;
        }
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE customers SET approval_status='REJECTED', approved_by=?, approved_at=NOW(),
                                 updated_by=?, updated_at=NOW()
             WHERE customer_id=? AND approval_status='PENDING'",
            [$user['user_id'], $user['user_id'], $id]
        );
        flash('success', __('msg_rejected'));
        $this->redirect('/master/customers');
    }

    public function search()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $keyword = sanitize($_GET['q'] ?? '');

            if (strlen($keyword) < 1) {
                $this->json(['results' => []]);
                return;
            }

            $customers = $db->fetchAll(
                "SELECT customer_id, customer_code, customer_name, customer_name_jp, customer_name_th, contact_person, phone
                 FROM customers
                 WHERE is_deleted = FALSE
                 AND (customer_code ILIKE ? OR customer_name ILIKE ? OR customer_name_th ILIKE ? OR tax_id ILIKE ?)
                 ORDER BY customer_name LIMIT 20",
                ["%{$keyword}%", "%{$keyword}%", "%{$keyword}%", "%{$keyword}%"]
            );

            $this->json(['results' => $customers ?: []]);
        } catch (Exception $e) {
            error_log('CustomerController::search - ' . $e->getMessage());
            $this->json(['error' => 'Search failed.'], 500);
        }
    }
}
