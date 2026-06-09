<?php
/**
 * PEGASUS ERP - Project Management Controller (プロジェクト管理)
 * PJ list, Budget Table (invoices + purchases), progress tracking
 */

class ProjectController extends Controller
{
    /**
     * PJ List (プロジェクト一覧)
     */
    public function index()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $filters = [
            'status'   => sanitize($_GET['status'] ?? ''),
            'customer' => sanitize($_GET['customer'] ?? ''),
            'q'        => sanitize($_GET['q'] ?? ''),
            'year'     => sanitize($_GET['year'] ?? ''),
        ];

        $sql = "SELECT p.*, c.customer_name, c.customer_name_jp, c.customer_name_th,
                       e.full_name as sales_person_full,
                       so.so_no,
                       d.deal_no, d.deal_name,
                       (SELECT COALESCE(SUM(amount),0) FROM project_invoices pi2 WHERE pi2.project_id = p.project_id AND pi2.is_deleted = FALSE) as invoiced_total,
                       (SELECT COALESCE(SUM(amount),0) FROM project_purchases pp2 WHERE pp2.project_id = p.project_id AND pp2.is_deleted = FALSE) as purchase_total
                FROM projects p
                LEFT JOIN customers c ON c.customer_id = p.customer_id
                LEFT JOIN employees e ON e.employee_id = p.sales_person_id
                LEFT JOIN sales_order_headers so ON so.so_id = p.so_id
                LEFT JOIN deals d ON d.deal_id = p.deal_id
                WHERE p.is_deleted = FALSE";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer'])) {
            $sql .= " AND c.customer_name ILIKE ?";
            $params[] = '%' . $filters['customer'] . '%';
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (p.pj_no ILIKE ? OR p.pj_name ILIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['year'])) {
            $sql .= " AND p.pj_no LIKE ?";
            $params[] = 'PJ' . substr($filters['year'], 2, 2) . '%';
        }

        $sql .= " ORDER BY p.pj_no DESC LIMIT 500";
        $projects = $db->fetchAll($sql, $params) ?: [];

        $this->render('projects/list', [
            'pageTitle' => __('project_list'),
            'projects'  => $projects,
            'filters'   => $filters,
        ]);
    }

    /**
     * Project Detail / Budget Table
     */
    public function show($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $project = $db->fetch(
            "SELECT p.*, c.customer_name, c.customer_name_jp, c.customer_name_th, c.customer_code,
                    e.full_name as sales_person_full,
                    so.so_no, d.deal_no, d.deal_name
             FROM projects p
             LEFT JOIN customers c ON c.customer_id = p.customer_id
             LEFT JOIN employees e ON e.employee_id = p.sales_person_id
             LEFT JOIN sales_order_headers so ON so.so_id = p.so_id
             LEFT JOIN deals d ON d.deal_id = p.deal_id
             WHERE p.project_id = ? AND p.is_deleted = FALSE",
            [$id]
        );
        if (!$project) {
            flash('error', __('project_not_found'));
            $this->redirect('/projects');
            return;
        }

        $invoices = $db->fetchAll(
            "SELECT * FROM project_invoices WHERE project_id = ? AND is_deleted = FALSE ORDER BY line_no",
            [$id]
        ) ?: [];

        $purchases = $db->fetchAll(
            "SELECT * FROM project_purchases WHERE project_id = ? AND is_deleted = FALSE ORDER BY line_no",
            [$id]
        ) ?: [];

        $progress = $db->fetchAll(
            "SELECT * FROM project_progress WHERE project_id = ? ORDER BY month_date",
            [$id]
        ) ?: [];

        // Available POs for linking purchases
        $purchaseOrders = [];
        if ($project['customer_id']) {
            $purchaseOrders = $db->fetchAll(
                "SELECT po.po_id, po.po_no, s.supplier_name, po.payment_amount AS grand_total_thb, po.status
                 FROM purchase_order_headers po
                 LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
                 WHERE po.is_deleted = FALSE
                 ORDER BY po.po_no DESC LIMIT 100"
            ) ?: [];
        }

        $paymentSchedules = $db->fetchAll(
            "SELECT * FROM project_payment_schedules WHERE project_id = ? ORDER BY seq_no",
            [$id]
        ) ?: [];

        // PO header records linked directly to this project via project_id
        $linkedPOs = $db->fetchAll(
            "SELECT po.po_id, po.po_no, po.order_date, po.delivery_date, po.status,
                    po.subtotal_thb, po.vat_amount, po.payment_amount,
                    s.supplier_name, s.supplier_code
             FROM purchase_order_headers po
             LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
             WHERE po.project_id = ? AND po.is_deleted = FALSE
             ORDER BY po.order_date DESC, po.po_no DESC",
            [$id]
        ) ?: [];

        $this->render('projects/detail', [
            'pageTitle'        => $project['pj_no'] . ' - ' . $project['pj_name'],
            'project'          => $project,
            'invoices'         => $invoices,
            'purchases'        => $purchases,
            'progress'         => $progress,
            'purchaseOrders'   => $purchaseOrders,
            'paymentSchedules' => $paymentSchedules,
            'linkedPOs'        => $linkedPOs,
        ]);
    }

    /**
     * Create project form
     */
    public function create()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $customers = $db->fetchAll("SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name") ?: [];
        $salesPersons = $db->fetchAll("SELECT employee_id, full_name FROM employees WHERE is_deleted = FALSE ORDER BY full_name") ?: [];
        $categories = $db->fetchAll("SELECT * FROM solution_categories WHERE is_deleted = FALSE ORDER BY sort_order") ?: [];
        $paymentTerms = $db->fetchAll("SELECT term_id, term_code, term_name_en, term_name_jp, installment_count FROM payment_terms WHERE is_deleted = FALSE AND is_current = TRUE ORDER BY display_order, term_name_en") ?: [];

        $this->render('projects/form', [
            'pageTitle'       => __('new_project'),
            'project'         => null,
            'customers'       => $customers,
            'salesPersons'    => $salesPersons,
            'categories'      => $categories,
            'paymentTerms'    => $paymentTerms,
            'paymentSchedules' => [],
        ]);
    }

    /**
     * Store new project
     */
    public function store()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->beginTransaction();

            $pjNo = $_POST['pj_no'] ?? '';
            if (empty($pjNo)) {
                $pjNo = self::generatePjNo($db);
            }

            $row = $db->fetch(
                "INSERT INTO projects (pj_no, pj_name, customer_id, end_user_customer,
                    pj_segment, pj_category, pj_classification, related_pj_no,
                    total_revenue, total_cost, gross_profit, profit_pct,
                    sales_hardware, sales_software, sales_sw_development, sales_sw_license,
                    sales_installation, sales_sw_installation, sales_hw_wiring,
                    service_cost, engineer_cost,
                    mm_programming, mm_design, mm_testing,
                    unit_price_programming, unit_price_design, unit_price_testing,
                    po_date, start_work_date, finished_work_date,
                    plan_delivery_date, delivery_date, delivery_place,
                    inspection_date, complete_date,
                    payment_term_id,
                    sales_person_id, sales_name, so_id, deal_id,
                    status, remark, created_by, updated_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                 RETURNING project_id",
                [
                    $pjNo,
                    sanitize($_POST['pj_name'] ?? ''),
                    $_POST['customer_id'] ?: null,
                    sanitize($_POST['end_user_customer'] ?? ''),
                    sanitize($_POST['pj_segment'] ?? ''),
                    sanitize($_POST['pj_category'] ?? ''),
                    sanitize($_POST['pj_classification'] ?? ''),
                    sanitize($_POST['related_pj_no'] ?? ''),
                    floatval($_POST['total_revenue'] ?? 0),
                    floatval($_POST['total_cost'] ?? 0),
                    floatval($_POST['gross_profit'] ?? 0),
                    floatval($_POST['profit_pct'] ?? 0),
                    floatval($_POST['sales_hardware'] ?? 0),
                    floatval($_POST['sales_software'] ?? 0),
                    floatval($_POST['sales_sw_development'] ?? 0),
                    floatval($_POST['sales_sw_license'] ?? 0),
                    floatval($_POST['sales_installation'] ?? 0),
                    floatval($_POST['sales_sw_installation'] ?? 0),
                    floatval($_POST['sales_hw_wiring'] ?? 0),
                    floatval($_POST['service_cost'] ?? 0),
                    floatval($_POST['engineer_cost'] ?? 0),
                    floatval($_POST['mm_programming'] ?? 0),
                    floatval($_POST['mm_design'] ?? 0),
                    floatval($_POST['mm_testing'] ?? 0),
                    floatval($_POST['unit_price_programming'] ?? 0),
                    floatval($_POST['unit_price_design'] ?? 0),
                    floatval($_POST['unit_price_testing'] ?? 0),
                    $_POST['po_date'] ?: null,
                    $_POST['start_work_date'] ?: null,
                    $_POST['finished_work_date'] ?: null,
                    $_POST['plan_delivery_date'] ?: null,
                    $_POST['delivery_date'] ?: null,
                    sanitize($_POST['delivery_place'] ?? ''),
                    $_POST['inspection_date'] ?: null,
                    $_POST['complete_date'] ?: null,
                    $_POST['payment_term_id'] ?: null,
                    $_POST['sales_person_id'] ?: null,
                    sanitize($_POST['sales_name'] ?? ''),
                    $_POST['so_id'] ?: null,
                    $_POST['deal_id'] ?: null,
                    sanitize($_POST['status'] ?? 'ACTIVE'),
                    sanitize($_POST['remark'] ?? ''),
                    $user['user_id'],
                    $user['user_id'],
                ]
            );

            $projectId = $row['project_id'];

            // Save payment schedules
            $this->savePaymentSchedules($db, $projectId);

            $db->commit();
            flash('success', __('project_created', $pjNo));
            $this->redirect('/projects/' . $projectId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('ProjectController::store - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
            $this->redirect('/projects/create');
        }
    }

    /**
     * Edit project form
     */
    public function edit($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $project = $db->fetch("SELECT * FROM projects WHERE project_id = ? AND is_deleted = FALSE", [$id]);
        if (!$project) {
            flash('error', __('project_not_found'));
            $this->redirect('/projects');
            return;
        }

        $customers = $db->fetchAll("SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name") ?: [];
        $salesPersons = $db->fetchAll("SELECT employee_id, full_name FROM employees WHERE is_deleted = FALSE ORDER BY full_name") ?: [];
        $categories = $db->fetchAll("SELECT * FROM solution_categories WHERE is_deleted = FALSE ORDER BY sort_order") ?: [];
        $paymentTerms = $db->fetchAll("SELECT term_id, term_code, term_name_en, term_name_jp, installment_count FROM payment_terms WHERE is_deleted = FALSE AND is_current = TRUE ORDER BY display_order, term_name_en") ?: [];
        $paymentSchedules = $db->fetchAll(
            "SELECT * FROM project_payment_schedules WHERE project_id = ? ORDER BY seq_no",
            [$id]
        ) ?: [];

        $this->render('projects/form', [
            'pageTitle'       => __('edit_project'),
            'project'         => $project,
            'customers'       => $customers,
            'salesPersons'    => $salesPersons,
            'categories'      => $categories,
            'paymentTerms'    => $paymentTerms,
            'paymentSchedules' => $paymentSchedules,
        ]);
    }

    /**
     * Update project
     */
    public function update($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->beginTransaction();

            $db->query(
                "UPDATE projects SET
                    pj_name = ?, customer_id = ?, end_user_customer = ?,
                    pj_segment = ?, pj_category = ?, pj_classification = ?, related_pj_no = ?,
                    total_revenue = ?, total_cost = ?, gross_profit = ?, profit_pct = ?,
                    sales_hardware = ?, sales_software = ?, sales_sw_development = ?, sales_sw_license = ?,
                    sales_installation = ?, sales_sw_installation = ?, sales_hw_wiring = ?,
                    service_cost = ?, engineer_cost = ?,
                    mm_programming = ?, mm_design = ?, mm_testing = ?,
                    unit_price_programming = ?, unit_price_design = ?, unit_price_testing = ?,
                    po_date = ?, start_work_date = ?, finished_work_date = ?,
                    plan_delivery_date = ?, delivery_date = ?, delivery_place = ?,
                    inspection_date = ?, complete_date = ?,
                    payment_term_id = ?,
                    sales_person_id = ?, sales_name = ?,
                    status = ?, remark = ?,
                    updated_by = ?, updated_at = NOW()
                 WHERE project_id = ?",
                [
                    sanitize($_POST['pj_name'] ?? ''),
                    $_POST['customer_id'] ?: null,
                    sanitize($_POST['end_user_customer'] ?? ''),
                    sanitize($_POST['pj_segment'] ?? ''),
                    sanitize($_POST['pj_category'] ?? ''),
                    sanitize($_POST['pj_classification'] ?? ''),
                    sanitize($_POST['related_pj_no'] ?? ''),
                    floatval($_POST['total_revenue'] ?? 0),
                    floatval($_POST['total_cost'] ?? 0),
                    floatval($_POST['gross_profit'] ?? 0),
                    floatval($_POST['profit_pct'] ?? 0),
                    floatval($_POST['sales_hardware'] ?? 0),
                    floatval($_POST['sales_software'] ?? 0),
                    floatval($_POST['sales_sw_development'] ?? 0),
                    floatval($_POST['sales_sw_license'] ?? 0),
                    floatval($_POST['sales_installation'] ?? 0),
                    floatval($_POST['sales_sw_installation'] ?? 0),
                    floatval($_POST['sales_hw_wiring'] ?? 0),
                    floatval($_POST['service_cost'] ?? 0),
                    floatval($_POST['engineer_cost'] ?? 0),
                    floatval($_POST['mm_programming'] ?? 0),
                    floatval($_POST['mm_design'] ?? 0),
                    floatval($_POST['mm_testing'] ?? 0),
                    floatval($_POST['unit_price_programming'] ?? 0),
                    floatval($_POST['unit_price_design'] ?? 0),
                    floatval($_POST['unit_price_testing'] ?? 0),
                    $_POST['po_date'] ?: null,
                    $_POST['start_work_date'] ?: null,
                    $_POST['finished_work_date'] ?: null,
                    $_POST['plan_delivery_date'] ?: null,
                    $_POST['delivery_date'] ?: null,
                    sanitize($_POST['delivery_place'] ?? ''),
                    $_POST['inspection_date'] ?: null,
                    $_POST['complete_date'] ?: null,
                    $_POST['payment_term_id'] ?: null,
                    $_POST['sales_person_id'] ?: null,
                    sanitize($_POST['sales_name'] ?? ''),
                    sanitize($_POST['status'] ?? 'ACTIVE'),
                    sanitize($_POST['remark'] ?? ''),
                    $user['user_id'],
                    $id,
                ]
            );

            // Save payment schedules
            $this->savePaymentSchedules($db, $id);

            $db->commit();
            flash('success', __('project_updated'));
            $this->redirect('/projects/' . $id);
        } catch (Exception $e) {
            $db->rollback();
            error_log('ProjectController::update - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
            $this->redirect('/projects/' . $id . '/edit');
        }
    }

    // ── Payment Schedule Helper ──

    /**
     * Save dynamic payment schedule rows from POST data
     */
    private function savePaymentSchedules($db, $projectId)
    {
        // Delete existing schedules
        $db->query("DELETE FROM project_payment_schedules WHERE project_id = ?", [$projectId]);

        $schedules = $_POST['ps'] ?? [];
        if (!is_array($schedules)) return;

        $seqNo = 0;
        foreach ($schedules as $s) {
            $desc = sanitize($s['description'] ?? '');
            $pct  = floatval($s['percentage'] ?? 0);
            $days = intval($s['credit_days'] ?? 0);
            $planDate = $s['plan_date'] ?? '';
            $actualDate = $s['actual_date'] ?? '';
            $amount = floatval($s['amount'] ?? 0);
            $remark = sanitize($s['remark'] ?? '');

            // Skip completely empty rows
            if (empty($desc) && $pct <= 0 && empty($planDate) && empty($actualDate) && $amount <= 0) {
                continue;
            }

            $seqNo++;
            $db->query(
                "INSERT INTO project_payment_schedules
                 (project_id, seq_no, description, percentage, credit_days, plan_date, actual_date, amount, remark)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $projectId, $seqNo, $desc, $pct, $days,
                    $planDate ?: null,
                    $actualDate ?: null,
                    $amount,
                    $remark,
                ]
            );
        }
    }

    // ── Invoice Management ──

    public function storeInvoice($projectId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            // Get next line number
            $maxLine = $db->fetch(
                "SELECT COALESCE(MAX(line_no), 0) as max_ln FROM project_invoices WHERE project_id = ?",
                [$projectId]
            );
            $lineNo = ($maxLine['max_ln'] ?? 0) + 1;

            $db->query(
                "INSERT INTO project_invoices (project_id, line_no, invoice_date, invoice_no, customer_name, amount, remark)
                 VALUES (?,?,?,?,?,?,?)",
                [
                    $projectId,
                    $lineNo,
                    $_POST['invoice_date'] ?: null,
                    sanitize($_POST['invoice_no'] ?? ''),
                    sanitize($_POST['customer_name'] ?? ''),
                    floatval($_POST['amount'] ?? 0),
                    sanitize($_POST['remark'] ?? ''),
                ]
            );
            flash('success', __('invoice_added'));
        } catch (Exception $e) {
            error_log('ProjectController::storeInvoice - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/projects/' . $projectId . '#invoices');
    }

    public function deleteInvoice($projectId, $invoiceId)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $db->query(
                "UPDATE project_invoices SET is_deleted = TRUE WHERE invoice_id = ? AND project_id = ?",
                [$invoiceId, $projectId]
            );
            flash('success', __('invoice_deleted'));
        } catch (Exception $e) {
            flash('error', __('msg_delete_error'));
        }
        $this->redirect('/projects/' . $projectId . '#invoices');
    }

    // ── Purchase Management (発注PJ) ──

    public function storePurchase($projectId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $maxLine = $db->fetch(
                "SELECT COALESCE(MAX(line_no), 0) as max_ln FROM project_purchases WHERE project_id = ?",
                [$projectId]
            );
            $lineNo = ($maxLine['max_ln'] ?? 0) + 1;

            $db->query(
                "INSERT INTO project_purchases (project_id, line_no, purchase_date, purchase_invoice_no,
                    description, amount, payment_terms, po_no, po_id, supplier_name, remark)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $projectId,
                    $lineNo,
                    $_POST['purchase_date'] ?: null,
                    sanitize($_POST['purchase_invoice_no'] ?? ''),
                    sanitize($_POST['description'] ?? ''),
                    floatval($_POST['amount'] ?? 0),
                    sanitize($_POST['payment_terms'] ?? ''),
                    sanitize($_POST['po_no'] ?? ''),
                    $_POST['po_id'] ?: null,
                    sanitize($_POST['supplier_name'] ?? ''),
                    sanitize($_POST['remark'] ?? ''),
                ]
            );
            flash('success', __('purchase_added'));
        } catch (Exception $e) {
            error_log('ProjectController::storePurchase - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/projects/' . $projectId . '#purchases');
    }

    public function deletePurchase($projectId, $purchaseId)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $db->query(
                "UPDATE project_purchases SET is_deleted = TRUE WHERE purchase_id = ? AND project_id = ?",
                [$purchaseId, $projectId]
            );
            flash('success', __('purchase_deleted'));
        } catch (Exception $e) {
            flash('error', __('msg_delete_error'));
        }
        $this->redirect('/projects/' . $projectId . '#purchases');
    }

    // ── Link Purchase Order to Project (発注PJ選択) ──

    public function searchPurchaseOrders($projectId)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $q = sanitize($_GET['q'] ?? '');
        $sql = "SELECT po.po_id, po.po_no, po.order_date, s.supplier_name,
                       po.payment_amount AS grand_total_thb, po.status
                FROM purchase_order_headers po
                LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
                WHERE po.is_deleted = FALSE";
        $params = [];

        if (!empty($q)) {
            $sql .= " AND (po.po_no ILIKE ? OR s.supplier_name ILIKE ?)";
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        $sql .= " ORDER BY po.po_no DESC LIMIT 30";
        $this->json($db->fetchAll($sql, $params) ?: []);
    }

    /**
     * Generate PJ number: PJYYXXXXX (e.g., PJ2600001)
     */
    public static function generatePjNo($db, $year = null)
    {
        if (!$year) {
            $year = date('Y'); // Buddhist year: use last 2 digits of Western year + 543
        }
        // Use Western calendar last 2 digits as per user example PJ2600001
        $yy = substr($year, 2, 2); // e.g., "26" for 2026
        $prefix = 'PJ' . $yy;

        $row = $db->fetch(
            "SELECT pj_no FROM projects WHERE pj_no LIKE ? ORDER BY pj_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['pj_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    // ── Cost Breakdown Items (原価算出) ──

    /**
     * Add a single cost item manually
     */
    public function storeCostItem($projectId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        $maxLine = $db->fetch(
            "SELECT COALESCE(MAX(line_no), 0) as mx FROM project_cost_items WHERE project_id = ? AND is_deleted = FALSE",
            [$projectId]
        );
        $lineNo = intval($maxLine['mx']) + 1;
        $user = $this->getCurrentUser();

        $unitPrice = floatval($_POST['unit_price'] ?? 0);
        $quantity  = floatval($_POST['quantity'] ?? 0);
        $total     = $unitPrice * $quantity;
        if (!empty($_POST['total_amount'])) {
            $total = floatval($_POST['total_amount']);
        }

        $db->query(
            "INSERT INTO project_cost_items
                (project_id, line_no, category, description, supplier, brand, lead_time,
                 unit_price, quantity, total_amount, unit, remark, is_category_row, source, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE, 'MANUAL', ?)",
            [
                $projectId,
                $lineNo,
                sanitize($_POST['category'] ?? ''),
                sanitize($_POST['description'] ?? ''),
                sanitize($_POST['supplier'] ?? ''),
                sanitize($_POST['brand'] ?? ''),
                sanitize($_POST['lead_time'] ?? ''),
                $unitPrice,
                $quantity,
                $total,
                sanitize($_POST['unit'] ?? ''),
                sanitize($_POST['remark'] ?? ''),
                $user['user_id'],
            ]
        );

        flash('success', __('msg_saved'));
        $this->redirect('/projects/' . $projectId . '#costs');
    }

    /**
     * Soft-delete a cost item
     */
    public function deleteCostItem($projectId, $costId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        $db->query(
            "UPDATE project_cost_items SET is_deleted = TRUE, updated_at = NOW() WHERE cost_item_id = ? AND project_id = ?",
            [$costId, $projectId]
        );

        flash('success', __('msg_deleted'));
        $this->redirect('/projects/' . $projectId . '#costs');
    }

    /**
     * Import cost items from Excel file upload
     */
    public function importCostItems($projectId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        if (empty($_FILES['cost_file']) || $_FILES['cost_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', __('msg_upload_error'));
            $this->redirect('/projects/' . $projectId . '#costs');
            return;
        }

        $file = $_FILES['cost_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            flash('error', __('msg_invalid_file_type'));
            $this->redirect('/projects/' . $projectId . '#costs');
            return;
        }

        // Save uploaded file
        $uploadDir = BASE_PATH . '/uploads/cost_imports/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $savedPath = $uploadDir . uniqid('cost_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $savedPath);

        // Use Python+openpyxl to extract data to JSON
        $tmpJson = tempnam(sys_get_temp_dir(), 'cost_') . '.json';
        $sheetName = sanitize($_POST['sheet_name'] ?? '');

        $pyScript = <<<'PYTHON'
import sys, json, openpyxl
excel_path = sys.argv[1]
out_path = sys.argv[2]
sheet_hint = sys.argv[3] if len(sys.argv) > 3 else ''
wb = openpyxl.load_workbook(excel_path, data_only=True)
ws = None
if sheet_hint and sheet_hint in wb.sheetnames:
    ws = wb[sheet_hint]
if not ws:
    for name in wb.sheetnames:
        if 'breakdown' in name.lower():
            ws = wb[name]
            break
if not ws:
    for name in wb.sheetnames:
        if 'cost' in name.lower() and 'summary' not in name.lower():
            ws = wb[name]
            break
if not ws:
    ws = wb[wb.sheetnames[0]]
rows = []
for r in range(9, 200):
    d = ws.cell(r, 4).value
    e = ws.cell(r, 5).value
    f = ws.cell(r, 6).value
    g = ws.cell(r, 7).value
    h = ws.cell(r, 8).value
    j = ws.cell(r, 10).value
    k = ws.cell(r, 11).value
    l = ws.cell(r, 12).value
    m = ws.cell(r, 13).value
    n = ws.cell(r, 14).value
    if not any([d, e, j, k, l]):
        continue
    if j is not None and not isinstance(j, (int, float)):
        continue
    def clean(v):
        if v is None: return None
        s = str(v).strip()
        if s in ['-', 'None', '']: return None
        return s
    def num(v):
        try:
            if v is not None and isinstance(v, (int, float)): return float(v)
        except: pass
        return 0
    rows.append({
        'row': r,
        'D': clean(d), 'E': clean(e), 'F': clean(f), 'G': clean(g), 'H': clean(h),
        'J': num(j), 'K': num(k), 'L': num(l), 'M': clean(m), 'N': clean(n)
    })
with open(out_path, 'w', encoding='utf-8') as fp:
    json.dump(rows, fp, ensure_ascii=False)
PYTHON;

        $pyTmp = tempnam(sys_get_temp_dir(), 'py_') . '.py';
        file_put_contents($pyTmp, $pyScript);
        $cmd = sprintf('python3 %s %s %s %s 2>&1',
            escapeshellarg($pyTmp),
            escapeshellarg($savedPath),
            escapeshellarg($tmpJson),
            escapeshellarg($sheetName)
        );
        exec($cmd, $output, $exitCode);
        @unlink($pyTmp);

        if (!file_exists($tmpJson) || $exitCode !== 0) {
            flash('error', __('msg_import_failed') . ': ' . implode("\n", $output));
            $this->redirect('/projects/' . $projectId . '#costs');
            return;
        }

        $rows = json_decode(file_get_contents($tmpJson), true);
        @unlink($tmpJson);

        if (empty($rows)) {
            flash('error', __('msg_no_data_in_file'));
            $this->redirect('/projects/' . $projectId . '#costs');
            return;
        }

        try {
            $user = $this->getCurrentUser();
            $db->beginTransaction();

            // Clear existing IMPORT items
            $db->query(
                "DELETE FROM project_cost_items WHERE project_id = ? AND source = 'IMPORT'",
                [$projectId]
            );

            $currentCategory = null;
            $lineNo = 0;
            $totalCost = 0;
            $inserted = 0;

            foreach ($rows as $r) {
                $d = $r['D'];
                $e = $r['E'];
                $isCategory = false;

                if ($d !== null) {
                    $currentCategory = $d;
                    if ($r['J'] == 0 && $r['K'] == 0 && $r['L'] == 0) {
                        $isCategory = true;
                    }
                }

                $lineNo++;
                $total = round($r['L'], 2);

                $db->query(
                    "INSERT INTO project_cost_items
                        (project_id, line_no, category, description, supplier, brand, lead_time,
                         unit_price, quantity, total_amount, unit, remark, is_category_row, source, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'IMPORT', ?)",
                    [
                        $projectId,
                        $lineNo,
                        $isCategory ? $d : $currentCategory,
                        $isCategory ? $d : $e,
                        $r['F'],
                        $r['G'],
                        $r['H'],
                        round($r['J'], 4),
                        round($r['K'], 4),
                        $total,
                        $r['M'],
                        $r['N'],
                        $isCategory ? 'TRUE' : 'FALSE',
                        $user['user_id'],
                    ]
                );
                $inserted++;
                $totalCost += $total;
            }

            // Update project total cost
            $db->query(
                "UPDATE projects SET cost_total = ?, total_cost = ?, purchase_estimate = ?,
                        gross_profit = total_revenue - ?,
                        profit_pct = CASE WHEN total_revenue > 0
                            THEN LEAST(999.99, GREATEST(-999.99, ((total_revenue - ?) / total_revenue * 100)))
                            ELSE 0 END,
                        updated_at = NOW()
                 WHERE project_id = ?",
                [$totalCost, $totalCost, $totalCost, $totalCost, $totalCost, $projectId]
            );

            $db->commit();
            flash('success', __('msg_cost_imported', $inserted, number_format($totalCost, 2)));
        } catch (Exception $e) {
            $db->rollback();
            error_log('ProjectController::importCostItems - ' . $e->getMessage());
            flash('error', __('msg_import_failed') . ': ' . $e->getMessage());
        }

        $this->redirect('/projects/' . $projectId . '#costs');
    }

    /**
     * Import cost items from a linked quotation's line items
     */
    public function importFromQuotation($projectId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        $quotationId = intval($_POST['quotation_id'] ?? 0);
        if (!$quotationId) {
            flash('error', __('msg_no_selection'));
            $this->redirect('/projects/' . $projectId . '#costs');
            return;
        }

        $quotation = $db->fetch(
            "SELECT qh.*, c.customer_name FROM quotation_headers qh
             LEFT JOIN customers c ON c.customer_id = qh.customer_id
             WHERE qh.quotation_id = ? AND qh.is_deleted = FALSE",
            [$quotationId]
        );
        if (!$quotation) {
            flash('error', __('quotation_not_found'));
            $this->redirect('/projects/' . $projectId . '#costs');
            return;
        }

        $lines = $db->fetchAll(
            "SELECT * FROM quotation_lines
             WHERE quotation_id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)
             ORDER BY sort_order, line_no",
            [$quotationId]
        ) ?: [];

        if (empty($lines)) {
            flash('error', __('msg_no_data_in_file'));
            $this->redirect('/projects/' . $projectId . '#costs');
            return;
        }

        try {
            $user = $this->getCurrentUser();
            $db->beginTransaction();

            // Remove existing items from same quotation
            $db->query(
                "DELETE FROM project_cost_items WHERE project_id = ? AND quotation_id = ?",
                [$projectId, $quotationId]
            );

            // Get current max line_no
            $maxLine = $db->fetch(
                "SELECT COALESCE(MAX(line_no), 0) as mx FROM project_cost_items WHERE project_id = ? AND is_deleted = FALSE",
                [$projectId]
            );
            $lineNo = intval($maxLine['mx']);
            $totalCost = 0;
            $inserted = 0;
            $currentCategory = null;

            foreach ($lines as $ql) {
                $lineNo++;
                $isCategory = !empty($ql['is_category_row']);
                $total = floatval($ql['cost_total'] ?? $ql['ext_price'] ?? 0);

                if ($isCategory) {
                    $currentCategory = $ql['item_description'] ?? '';
                }

                $db->query(
                    "INSERT INTO project_cost_items
                        (project_id, line_no, category, description, supplier, brand,
                         unit_price, quantity, total_amount, unit, remark,
                         is_category_row, quotation_id, source, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'QUOTATION', ?)",
                    [
                        $projectId,
                        $lineNo,
                        $isCategory ? ($ql['item_description'] ?? '') : ($currentCategory ?? ''),
                        $ql['item_description'] ?? '',
                        null,
                        null,
                        floatval($ql['unit_price'] ?? 0),
                        floatval($ql['quantity'] ?? 0),
                        $total,
                        $ql['unit'] ?? '',
                        $quotation['quotation_no'] ?? '',
                        $isCategory ? 'TRUE' : 'FALSE',
                        $quotationId,
                        $user['user_id'],
                    ]
                );
                $inserted++;
                if (!$isCategory) $totalCost += $total;
            }

            $db->commit();
            flash('success', __('msg_cost_from_quotation', $quotation['quotation_no'], $inserted));
        } catch (Exception $e) {
            $db->rollback();
            error_log('ProjectController::importFromQuotation - ' . $e->getMessage());
            flash('error', __('msg_save_error') . ': ' . $e->getMessage());
        }

        $this->redirect('/projects/' . $projectId . '#costs');
    }

    /**
     * AJAX: Search quotations for cost import
     */
    public function searchQuotations($projectId)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $project = $db->fetch("SELECT customer_id FROM projects WHERE project_id = ?", [$projectId]);
        $q = sanitize($_GET['q'] ?? '');

        $sql = "SELECT qh.quotation_id, qh.quotation_no, qh.project_name,
                       qh.grand_total_thb, qh.status, c.customer_name,
                       (SELECT COUNT(*) FROM quotation_lines ql WHERE ql.quotation_id = qh.quotation_id AND ql.is_deleted = FALSE) as line_count
                FROM quotation_headers qh
                LEFT JOIN customers c ON c.customer_id = qh.customer_id
                WHERE qh.is_deleted = FALSE";
        $params = [];

        // Filter by customer if project has one
        if (!empty($project['customer_id'])) {
            $sql .= " AND qh.customer_id = ?";
            $params[] = $project['customer_id'];
        }

        if (!empty($q)) {
            $sql .= " AND (qh.quotation_no ILIKE ? OR qh.project_name ILIKE ?)";
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        $sql .= " ORDER BY qh.quotation_no DESC LIMIT 30";
        $this->json($db->fetchAll($sql, $params) ?: []);
    }
}
