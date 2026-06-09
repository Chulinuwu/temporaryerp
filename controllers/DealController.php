<?php
/**
 * PEGASUS ERP - Deal Management Controller (案件管理)
 */

class DealController extends Controller
{
    /**
     * Deal list view
     */
    public function index()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $filters = [
            'status'      => sanitize($_GET['status'] ?? ''),
            'customer_id' => sanitize($_GET['customer_id'] ?? ''),
            'sales'       => sanitize($_GET['sales'] ?? ''),
            'deal_name'   => sanitize($_GET['deal_name'] ?? ''),
            'amount_min'  => sanitize($_GET['amount_min'] ?? ''),
            'amount_max'  => sanitize($_GET['amount_max'] ?? ''),
            'win_min'     => sanitize($_GET['win_min'] ?? ''),
            'win_max'     => sanitize($_GET['win_max'] ?? ''),
            'q'           => sanitize($_GET['q'] ?? ''),
        ];

        $sql = "SELECT d.*, c.customer_name, c.customer_name_jp, c.customer_name_th, ds.status_name, ds.win_pct, ds.color,
                       sc.category_name as solution_name,
                       e.full_name as sales_person_name,
                       (SELECT COUNT(*) FROM deal_activities da WHERE da.deal_id = d.deal_id) as activity_count,
                       (SELECT COUNT(*) FROM quotation_headers qh WHERE qh.deal_id = d.deal_id AND qh.is_deleted = FALSE) as quotation_count
                FROM deals d
                LEFT JOIN customers c ON c.customer_id = d.customer_id
                LEFT JOIN deal_statuses ds ON ds.status_id = d.status_id
                LEFT JOIN solution_categories sc ON sc.category_id = d.solution_category_id
                LEFT JOIN employees e ON e.employee_id = d.sales_person_id
                WHERE d.is_deleted = FALSE";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND ds.status_name = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $sql .= " AND d.customer_id = ?";
            $params[] = $filters['customer_id'];
        }
        if (!empty($filters['sales'])) {
            $sql .= " AND d.sales_person_id = ?";
            $params[] = $filters['sales'];
        }
        if ($filters['deal_name'] !== '') {
            $sql .= " AND d.deal_name ILIKE ?";
            $params[] = '%' . $filters['deal_name'] . '%';
        }
        if ($filters['amount_min'] !== '' && is_numeric($filters['amount_min'])) {
            $sql .= " AND d.expected_amount >= ?";
            $params[] = $filters['amount_min'];
        }
        if ($filters['amount_max'] !== '' && is_numeric($filters['amount_max'])) {
            $sql .= " AND d.expected_amount <= ?";
            $params[] = $filters['amount_max'];
        }
        if ($filters['win_min'] !== '' && is_numeric($filters['win_min'])) {
            $sql .= " AND ds.win_pct >= ?";
            $params[] = $filters['win_min'];
        }
        if ($filters['win_max'] !== '' && is_numeric($filters['win_max'])) {
            $sql .= " AND ds.win_pct <= ?";
            $params[] = $filters['win_max'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (d.deal_no ILIKE ? OR d.deal_name ILIKE ? OR d.pj_no ILIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }

        $sql .= " ORDER BY d.updated_at DESC";
        $deals = $db->fetchAll($sql, $params);

        $statuses = $db->fetchAll("SELECT * FROM deal_statuses WHERE is_deleted = FALSE ORDER BY sort_order");
        $salesPersons = $db->fetchAll(
            "SELECT employee_id, full_name FROM employees WHERE is_deleted = FALSE ORDER BY full_name"
        );
        // Only customers that have deals — keeps the dropdown short and relevant
        $customers = $db->fetchAll(
            "SELECT DISTINCT c.customer_id, c.customer_name, c.customer_name_jp, c.customer_name_th
             FROM customers c
             INNER JOIN deals d ON d.customer_id = c.customer_id AND d.is_deleted = FALSE
             WHERE c.is_deleted = FALSE
             ORDER BY c.customer_name"
        );

        $this->render('sales/deals', [
            'pageTitle' => __('deals'),
            'deals'     => $deals ?: [],
            'filters'   => $filters,
            'statuses'  => $statuses ?: [],
            'salesPersons' => $salesPersons ?: [],
            'customers' => $customers ?: [],
        ]);
    }

    /**
     * Kanban board view
     */
    public function kanban()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $statuses = $db->fetchAll("SELECT * FROM deal_statuses WHERE is_deleted = FALSE ORDER BY sort_order");
        $deals = $db->fetchAll(
            "SELECT d.*, c.customer_name, ds.status_name, ds.win_pct, ds.color,
                    sc.category_name as solution_name,
                    e.full_name as sales_person_name
             FROM deals d
             LEFT JOIN customers c ON c.customer_id = d.customer_id
             LEFT JOIN deal_statuses ds ON ds.status_id = d.status_id
             LEFT JOIN solution_categories sc ON sc.category_id = d.solution_category_id
             LEFT JOIN employees e ON e.employee_id = d.sales_person_id
             WHERE d.is_deleted = FALSE
             ORDER BY d.updated_at DESC"
        );

        $this->render('sales/deals_kanban', [
            'pageTitle' => __('deal_kanban'),
            'deals'     => $deals ?: [],
            'statuses'  => $statuses ?: [],
        ]);
    }

    /**
     * Show deal detail with activities and quotations
     */
    public function show($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $deal = $db->fetch(
            "SELECT d.*, c.customer_name, c.customer_name_jp, c.customer_name_th, c.customer_code, ds.status_name, ds.win_pct, ds.color,
                    sc.category_name as solution_name,
                    e.full_name as sales_person_name
             FROM deals d
             LEFT JOIN customers c ON c.customer_id = d.customer_id
             LEFT JOIN deal_statuses ds ON ds.status_id = d.status_id
             LEFT JOIN solution_categories sc ON sc.category_id = d.solution_category_id
             LEFT JOIN employees e ON e.employee_id = d.sales_person_id
             WHERE d.deal_id = ? AND d.is_deleted = FALSE",
            [$id]
        );

        if (!$deal) {
            flash('error', __('deal_not_found'));
            $this->redirect('/sales/deals');
            return;
        }

        $activities = $db->fetchAll(
            "SELECT da.*, ac.category_name as activity_type, ac.icon as activity_icon,
                    u.email as created_by_name
             FROM deal_activities da
             LEFT JOIN activity_categories ac ON ac.category_id = da.activity_category_id
             LEFT JOIN users u ON u.user_id = da.created_by
             WHERE da.deal_id = ?
             ORDER BY da.activity_date DESC, da.created_at DESC",
            [$id]
        );

        $quotations = $db->fetchAll(
            "SELECT qh.*, c.customer_name
             FROM quotation_headers qh
             LEFT JOIN customers c ON c.customer_id = qh.customer_id
             WHERE qh.deal_id = ? AND qh.is_deleted = FALSE
             ORDER BY qh.issue_date DESC",
            [$id]
        );

        $activityCategories = $db->fetchAll(
            "SELECT * FROM activity_categories WHERE is_deleted = FALSE ORDER BY sort_order"
        );

        // #6: contacts of deal's customer for activity log
        $dealContacts = $db->fetchAll(
            "SELECT contact_id, full_name, full_name_local, title, department
             FROM customer_contacts
             WHERE customer_id = ? AND is_deleted = FALSE
             ORDER BY is_primary DESC, full_name",
            [$deal['customer_id']]
        );

        $this->render('sales/deal_detail', [
            'pageTitle'  => $deal['deal_no'] . ' - ' . $deal['deal_name'],
            'deal'       => $deal,
            'activities' => $activities ?: [],
            'quotations' => $quotations ?: [],
            'activityCategories' => $activityCategories ?: [],
            'dealContacts' => $dealContacts ?: [],
        ]);
    }

    /**
     * Create deal form
     */
    public function create()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $customers = $db->fetchAll("SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name");
        $statuses = $db->fetchAll("SELECT * FROM deal_statuses WHERE is_deleted = FALSE ORDER BY sort_order");
        $categories = $db->fetchAll("SELECT * FROM solution_categories WHERE is_deleted = FALSE ORDER BY sort_order");
        $salesPersons = $db->fetchAll("SELECT employee_id, full_name FROM employees WHERE is_deleted = FALSE ORDER BY full_name");
        // #5: select related project from registered sales orders
        $salesOrders = $db->fetchAll(
            "SELECT so.so_id, so.so_no, so.notes, c.customer_name
             FROM sales_order_headers so
             LEFT JOIN customers c ON c.customer_id = so.customer_id
             WHERE so.is_deleted = FALSE
             ORDER BY so.so_no DESC LIMIT 200"
        );

        $this->render('sales/deal_form', [
            'pageTitle'         => __('new_deal'),
            'deal'              => null,
            'customers'         => $customers ?: [],
            'statuses'          => $statuses ?: [],
            'categories'        => $categories ?: [],
            'solutionCategories'=> $categories ?: [],
            'salesPersons'      => $salesPersons ?: [],
            'salesOrders'       => $salesOrders ?: [],
        ]);
    }

    /**
     * Store new deal
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

            $dealNo = $this->generateDealNo($db);

            // #5: derive evaluation_profit_pct from selected solution_category if not posted
            $evalProfitPct = null;
            if (isset($_POST['evaluation_profit_pct']) && $_POST['evaluation_profit_pct'] !== '') {
                $evalProfitPct = floatval($_POST['evaluation_profit_pct']);
            } elseif (!empty($_POST['solution_category_id'])) {
                $sc = $db->fetch(
                    "SELECT evaluation_profit_pct FROM solution_categories WHERE category_id = ?",
                    [$_POST['solution_category_id']]
                );
                $evalProfitPct = $sc ? floatval($sc['evaluation_profit_pct']) : null;
            }

            $row = $db->fetch(
                "INSERT INTO deals (deal_no, deal_name, customer_id, customer_staff, touch_point,
                    status_id, solution_category_id, expected_amount, expected_close,
                    sales_person_id, pj_no, related_projects, note,
                    first_contact_date, budget_status, budget_amount, win_rate,
                    est_revenue, est_profit, eval_profit, next_action, due_date, meeting_notes,
                    sales_person_name, evaluation_profit_pct, created_by, updated_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                 RETURNING deal_id",
                [
                    $dealNo,
                    sanitize($_POST['deal_name'] ?? ''),
                    $_POST['customer_id'] ?: null,
                    sanitize($_POST['customer_staff'] ?? ''),
                    sanitize($_POST['touch_point'] ?? ''),
                    $_POST['status_id'] ?: null,
                    $_POST['solution_category_id'] ?: null,
                    floatval($_POST['expected_amount'] ?? 0),
                    $_POST['expected_close'] ?: null,
                    $_POST['sales_person_id'] ?: null,
                    sanitize($_POST['pj_no'] ?? ''),
                    sanitize($_POST['related_projects'] ?? ''),
                    sanitize($_POST['note'] ?? ''),
                    $_POST['first_contact_date'] ?: null,
                    sanitize($_POST['budget_status'] ?? 'No'),
                    floatval($_POST['budget_amount'] ?? 0),
                    intval($_POST['win_rate'] ?? 0),
                    floatval($_POST['est_revenue'] ?? 0),
                    floatval($_POST['est_profit'] ?? 0),
                    floatval($_POST['eval_profit'] ?? 0),
                    sanitize($_POST['next_action'] ?? ''),
                    $_POST['due_date'] ?: null,
                    sanitize($_POST['meeting_notes'] ?? ''),
                    sanitize($_POST['sales_person_name'] ?? ''),
                    $evalProfitPct,
                    $user['user_id'],
                    $user['user_id'],
                ]
            );

            $db->commit();
            flash('success', __('deal_created', $dealNo));
            $this->redirect('/sales/deals/' . $row['deal_id']);
        } catch (Exception $e) {
            $db->rollback();
            error_log('DealController::store - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
            $this->redirect('/sales/deals/create');
        }
    }

    /**
     * Edit deal form
     */
    public function edit($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $deal = $db->fetch("SELECT * FROM deals WHERE deal_id = ? AND is_deleted = FALSE", [$id]);
        if (!$deal) {
            flash('error', __('deal_not_found'));
            $this->redirect('/sales/deals');
            return;
        }

        $customers = $db->fetchAll("SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name");
        $statuses = $db->fetchAll("SELECT * FROM deal_statuses WHERE is_deleted = FALSE ORDER BY sort_order");
        $categories = $db->fetchAll("SELECT * FROM solution_categories WHERE is_deleted = FALSE ORDER BY sort_order");
        $salesPersons = $db->fetchAll("SELECT employee_id, full_name FROM employees WHERE is_deleted = FALSE ORDER BY full_name");
        $salesOrders = $db->fetchAll(
            "SELECT so.so_id, so.so_no, so.notes, c.customer_name
             FROM sales_order_headers so
             LEFT JOIN customers c ON c.customer_id = so.customer_id
             WHERE so.is_deleted = FALSE
             ORDER BY so.so_no DESC LIMIT 200"
        );

        $this->render('sales/deal_form', [
            'pageTitle'         => __('edit_deal'),
            'deal'              => $deal,
            'customers'         => $customers ?: [],
            'statuses'          => $statuses ?: [],
            'categories'        => $categories ?: [],
            'solutionCategories'=> $categories ?: [],
            'salesPersons'      => $salesPersons ?: [],
            'salesOrders'       => $salesOrders ?: [],
        ]);
    }

    /**
     * Update deal
     */
    public function update($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();

            // #5: derive evaluation_profit_pct
            $evalProfitPct = null;
            if (isset($_POST['evaluation_profit_pct']) && $_POST['evaluation_profit_pct'] !== '') {
                $evalProfitPct = floatval($_POST['evaluation_profit_pct']);
            } elseif (!empty($_POST['solution_category_id'])) {
                $sc = $db->fetch(
                    "SELECT evaluation_profit_pct FROM solution_categories WHERE category_id = ?",
                    [$_POST['solution_category_id']]
                );
                $evalProfitPct = $sc ? floatval($sc['evaluation_profit_pct']) : null;
            }

            $db->query(
                "UPDATE deals SET
                    deal_name = ?, customer_id = ?, customer_staff = ?, touch_point = ?,
                    status_id = ?, solution_category_id = ?, expected_amount = ?,
                    expected_close = ?, sales_person_id = ?, pj_no = ?,
                    related_projects = ?, note = ?,
                    first_contact_date = ?, budget_status = ?, budget_amount = ?, win_rate = ?,
                    est_revenue = ?, est_profit = ?, eval_profit = ?,
                    next_action = ?, due_date = ?, meeting_notes = ?,
                    sales_person_name = ?, evaluation_profit_pct = ?,
                    updated_by = ?, updated_at = NOW()
                 WHERE deal_id = ?",
                [
                    sanitize($_POST['deal_name'] ?? ''),
                    $_POST['customer_id'] ?: null,
                    sanitize($_POST['customer_staff'] ?? ''),
                    sanitize($_POST['touch_point'] ?? ''),
                    $_POST['status_id'] ?: null,
                    $_POST['solution_category_id'] ?: null,
                    floatval($_POST['expected_amount'] ?? 0),
                    $_POST['expected_close'] ?: null,
                    $_POST['sales_person_id'] ?: null,
                    sanitize($_POST['pj_no'] ?? ''),
                    sanitize($_POST['related_projects'] ?? ''),
                    sanitize($_POST['note'] ?? ''),
                    $_POST['first_contact_date'] ?: null,
                    sanitize($_POST['budget_status'] ?? 'No'),
                    floatval($_POST['budget_amount'] ?? 0),
                    intval($_POST['win_rate'] ?? 0),
                    floatval($_POST['est_revenue'] ?? 0),
                    floatval($_POST['est_profit'] ?? 0),
                    floatval($_POST['eval_profit'] ?? 0),
                    sanitize($_POST['next_action'] ?? ''),
                    $_POST['due_date'] ?: null,
                    sanitize($_POST['meeting_notes'] ?? ''),
                    sanitize($_POST['sales_person_name'] ?? ''),
                    $evalProfitPct,
                    $user['user_id'],
                    $id,
                ]
            );

            flash('success', __('deal_updated'));
            $this->redirect('/sales/deals/' . $id);
        } catch (Exception $e) {
            error_log('DealController::update - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
            $this->redirect('/sales/deals/' . $id . '/edit');
        }
    }

    /**
     * Delete deal (soft)
     */
    public function delete($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE deals SET is_deleted = TRUE, updated_by = ?, updated_at = NOW() WHERE deal_id = ?",
                [$user['user_id'], $id]
            );
            flash('success', __('deal_deleted'));
        } catch (Exception $e) {
            error_log('DealController::delete - ' . $e->getMessage());
            flash('error', __('msg_delete_error'));
        }
        $this->redirect('/sales/deals');
    }

    /**
     * API: Update deal status (for kanban drag & drop)
     */
    public function updateStatus($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $statusId = intval($_POST['status_id'] ?? 0);
            $user = $this->getCurrentUser();

            $db->query(
                "UPDATE deals SET status_id = ?, updated_by = ?, updated_at = NOW() WHERE deal_id = ?",
                [$statusId, $user['user_id'], $id]
            );
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store activity for a deal (#6: contact_id required, start/end time, auto duration)
     */
    public function storeActivity($dealId)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();

            $contactId  = !empty($_POST['contact_id']) ? intval($_POST['contact_id']) : null;
            $startTime  = $_POST['start_time'] ?: null;
            $endTime    = $_POST['end_time'] ?: null;
            $nextAction = sanitize($_POST['next_action'] ?? '');
            $nextActionDate = $_POST['next_action_date'] ?: null;
            $description = sanitize($_POST['description'] ?? ''); // 議事 (minutes)

            // ── #6 Validation ──
            if (!$contactId) {
                flash('error', __('contact_required_select_or_register'));
                $this->redirect('/sales/deals/' . $dealId);
                return;
            }
            if (!$nextAction || !$nextActionDate) {
                flash('error', __('next_action_required'));
                $this->redirect('/sales/deals/' . $dealId);
                return;
            }

            // Auto-calc duration if both times present
            $duration = null;
            if ($startTime && $endTime) {
                $s = strtotime($startTime);
                $e = strtotime($endTime);
                if ($e > $s) $duration = (int) round(($e - $s) / 60);
            } elseif (!empty($_POST['duration_min'])) {
                $duration = intval($_POST['duration_min']);
            }

            // Pull contact name to also fill legacy contact_person column
            $contactRow = $db->fetch("SELECT full_name FROM customer_contacts WHERE contact_id = ?", [$contactId]);
            $contactName = $contactRow['full_name'] ?? '';

            $db->query(
                "INSERT INTO deal_activities (deal_id, activity_category_id, activity_date,
                    subject, description, contact_person, contact_id, duration_min,
                    start_time, end_time, next_action, next_action_date, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $dealId,
                    $_POST['activity_category_id'] ?: null,
                    $_POST['activity_date'] ?: date('Y-m-d'),
                    sanitize($_POST['subject'] ?? ''),
                    $description,
                    $contactName,
                    $contactId,
                    $duration,
                    $startTime,
                    $endTime,
                    $nextAction,
                    $nextActionDate,
                    $user['user_id'],
                ]
            );

            // Update deal updated_at
            $db->query("UPDATE deals SET updated_at = NOW() WHERE deal_id = ?", [$dealId]);

            flash('success', __('activity_saved'));
        } catch (Exception $e) {
            error_log('DealController::storeActivity - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/sales/deals/' . $dealId);
    }

    /**
     * Delete activity
     */
    public function deleteActivity($dealId, $activityId)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $db->query("DELETE FROM deal_activities WHERE activity_id = ? AND deal_id = ?", [$activityId, $dealId]);
            flash('success', __('activity_deleted'));
        } catch (Exception $e) {
            flash('error', __('msg_delete_error'));
        }
        $this->redirect('/sales/deals/' . $dealId);
    }

    /**
     * API: Get deals for pipeline/dashboard
     */
    public function apiDeals()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $deals = $db->fetchAll(
            "SELECT d.deal_id, d.deal_no, d.deal_name, d.expected_amount,
                    c.customer_name, ds.status_name, ds.win_pct, ds.color
             FROM deals d
             LEFT JOIN customers c ON c.customer_id = d.customer_id
             LEFT JOIN deal_statuses ds ON ds.status_id = d.status_id
             WHERE d.is_deleted = FALSE
             ORDER BY ds.sort_order, d.expected_amount DESC"
        );

        $this->json($deals ?: []);
    }

    // ── Standalone Activity Log ────────────────────────────────

    /**
     * Activity Log page (standalone - all activities across deals)
     */
    public function activityList()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $filters = [
            'date_from' => sanitize($_GET['date_from'] ?? ''),
            'date_to'   => sanitize($_GET['date_to'] ?? ''),
            'sales'     => sanitize($_GET['sales'] ?? ''),
            'type'      => sanitize($_GET['type'] ?? ''),
            'q'         => sanitize($_GET['q'] ?? ''),
        ];

        $sql = "SELECT da.*, ac.category_name as action_type, ac.icon as action_icon,
                       c.customer_name,
                       d.deal_no, d.deal_name
                FROM deal_activities da
                LEFT JOIN activity_categories ac ON ac.category_id = da.activity_category_id
                LEFT JOIN customers c ON c.customer_id = da.customer_id
                LEFT JOIN deals d ON d.deal_id = da.deal_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND da.activity_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND da.activity_date <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['sales'])) {
            $sql .= " AND (da.sales_person_name ILIKE ? OR da.sales_person_id = ?)";
            $params[] = '%' . $filters['sales'] . '%';
            $params[] = intval($filters['sales']);
        }
        if (!empty($filters['type'])) {
            $sql .= " AND ac.category_name = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (da.subject ILIKE ? OR da.company_name ILIKE ? OR da.contact_person ILIKE ? OR c.customer_name ILIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }

        $sql .= " ORDER BY da.activity_date DESC, da.created_at DESC LIMIT 500";
        $activities = $db->fetchAll($sql, $params);

        $activityCategories = $db->fetchAll(
            "SELECT * FROM activity_categories WHERE is_deleted = FALSE ORDER BY sort_order"
        );
        $customers = $db->fetchAll(
            "SELECT customer_id, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name LIMIT 500"
        );
        $salesPersons = $db->fetchAll(
            "SELECT employee_id, full_name FROM employees WHERE is_deleted = FALSE ORDER BY full_name"
        );

        // Count follow-ups per activity for UI badge
        $followCounts = $db->fetchAll(
            "SELECT parent_activity_id, COUNT(*) AS n
             FROM deal_activities WHERE parent_activity_id IS NOT NULL
             GROUP BY parent_activity_id"
        ) ?: [];
        $followCountMap = [];
        foreach ($followCounts as $fc) {
            $followCountMap[(int)$fc['parent_activity_id']] = (int)$fc['n'];
        }

        // Follow-up prefill: /sales/activities?followup_from={activity_id}
        $followupFrom = null;
        $prefill = null;
        if (!empty($_GET['followup_from'])) {
            $followupFrom = intval($_GET['followup_from']);
            $prefill = $db->fetch(
                "SELECT da.*, c.customer_name AS parent_customer_name
                 FROM deal_activities da
                 LEFT JOIN customers c ON c.customer_id = da.customer_id
                 WHERE da.activity_id = ?",
                [$followupFrom]
            ) ?: null;
        }

        $this->render('sales/activity_log', [
            'pageTitle'          => __('activity_log'),
            'activities'         => $activities ?: [],
            'filters'            => $filters,
            'activityCategories' => $activityCategories ?: [],
            'customers'          => $customers ?: [],
            'salesPersons'       => $salesPersons ?: [],
            'followCountMap'     => $followCountMap,
            'prefill'            => $prefill,
            'followupFrom'       => $followupFrom,
        ]);
    }

    /**
     * Store standalone activity (not tied to a deal)
     */
    public function storeStandaloneActivity()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();

            $contactId  = !empty($_POST['contact_id']) ? intval($_POST['contact_id']) : null;
            $startTime  = $_POST['start_time'] ?: null;
            $endTime    = $_POST['end_time'] ?: null;
            $nextAction = sanitize($_POST['next_action'] ?? '');
            $nextActionDate = $_POST['next_action_date'] ?: null;

            // ── #6 Validation ──
            if (!$contactId) {
                flash('error', __('contact_required_select_or_register'));
                $this->redirect('/sales/activities');
                return;
            }
            if (!$nextAction || !$nextActionDate) {
                flash('error', __('next_action_required'));
                $this->redirect('/sales/activities');
                return;
            }

            // Auto-calc duration
            $duration = null;
            if ($startTime && $endTime) {
                $s = strtotime($startTime);
                $e = strtotime($endTime);
                if ($e > $s) $duration = (int) round(($e - $s) / 60);
            }

            $contactRow = $db->fetch("SELECT full_name FROM customer_contacts WHERE contact_id = ?", [$contactId]);
            $contactName = $contactRow['full_name'] ?? '';

            $parentId = !empty($_POST['parent_activity_id']) ? intval($_POST['parent_activity_id']) : null;

            $db->query(
                "INSERT INTO deal_activities (deal_id, activity_category_id, activity_date,
                    subject, description, contact_person, contact_id, duration_min,
                    start_time, end_time,
                    next_action, next_action_date,
                    customer_id, company_name, sales_person_name, sales_person_id,
                    parent_activity_id, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $_POST['deal_id'] ?: null,
                    $_POST['activity_category_id'] ?: null,
                    $_POST['activity_date'] ?: date('Y-m-d'),
                    sanitize($_POST['subject'] ?? ''),
                    sanitize($_POST['description'] ?? ''),
                    $contactName,
                    $contactId,
                    $duration,
                    $startTime,
                    $endTime,
                    $nextAction,
                    $nextActionDate,
                    $_POST['customer_id'] ?: null,
                    sanitize($_POST['company_name'] ?? ''),
                    sanitize($_POST['sales_person_name'] ?? ''),
                    $_POST['sales_person_id'] ?: null,
                    $parentId,
                    $user['user_id'],
                ]
            );

            flash('success', __('activity_saved'));
        } catch (Exception $e) {
            error_log('DealController::storeStandaloneActivity - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/sales/activities');
    }

    /**
     * Delete standalone activity
     */
    public function deleteStandaloneActivity($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $db->query("DELETE FROM deal_activities WHERE activity_id = ?", [$id]);
            flash('success', __('activity_deleted'));
        } catch (Exception $e) {
            flash('error', __('msg_delete_error'));
        }
        $this->redirect('/sales/activities');
    }

    // ── Quotation Linking ────────────────────────────────

    /**
     * AJAX: Search quotations for linking (filtered by deal's customer)
     */
    public function searchQuotations($dealId)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $deal = $db->fetch("SELECT customer_id FROM deals WHERE deal_id = ? AND is_deleted = FALSE", [$dealId]);
        if (!$deal) {
            $this->json(['error' => 'Deal not found'], 404);
            return;
        }

        $customerId = $deal['customer_id'];
        $q = sanitize($_GET['q'] ?? '');

        $sql = "SELECT qh.quotation_id, qh.quotation_no, qh.issue_date, qh.project_name,
                       qh.subtotal_thb, qh.grand_total_thb, qh.status, qh.deal_id,
                       c.customer_name
                FROM quotation_headers qh
                LEFT JOIN customers c ON c.customer_id = qh.customer_id
                WHERE qh.is_deleted = FALSE AND qh.customer_id = ?";
        $params = [$customerId];

        if (!empty($q)) {
            $sql .= " AND (qh.quotation_no ILIKE ? OR qh.project_name ILIKE ?)";
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        $sql .= " ORDER BY qh.issue_date DESC, qh.quotation_no DESC LIMIT 50";
        $quotations = $db->fetchAll($sql, $params) ?: [];

        $this->json($quotations);
    }

    /**
     * Link selected quotations to a deal
     */
    public function linkQuotations($dealId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $deal = $db->fetch("SELECT deal_id, customer_id FROM deals WHERE deal_id = ? AND is_deleted = FALSE", [$dealId]);
            if (!$deal) {
                flash('error', __('deal_not_found'));
                $this->redirect('/sales/deals');
                return;
            }

            $quotationIds = $_POST['quotation_ids'] ?? [];
            if (empty($quotationIds)) {
                flash('error', __('msg_no_selection'));
                $this->redirect('/sales/deals/' . $dealId);
                return;
            }

            $user = $this->getCurrentUser();
            $linked = 0;
            foreach ($quotationIds as $qid) {
                $qid = intval($qid);
                // Only link quotations belonging to the same customer
                $db->query(
                    "UPDATE quotation_headers SET deal_id = ?, updated_by = ?, updated_at = NOW()
                     WHERE quotation_id = ? AND customer_id = ? AND is_deleted = FALSE",
                    [$dealId, $user['user_id'], $qid, $deal['customer_id']]
                );
                $linked++;
            }

            // Update deal expected_amount from linked quotations
            $this->updateDealAmountFromQuotations($db, $dealId);

            flash('success', __('quotations_linked', $linked));
            $this->redirect('/sales/deals/' . $dealId . '#quotations');
        } catch (Exception $e) {
            error_log('DealController::linkQuotations - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
            $this->redirect('/sales/deals/' . $dealId);
        }
    }

    /**
     * Unlink a quotation from a deal
     */
    public function unlinkQuotation($dealId, $quotationId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE quotation_headers SET deal_id = NULL, updated_by = ?, updated_at = NOW()
                 WHERE quotation_id = ? AND deal_id = ?",
                [$user['user_id'], $quotationId, $dealId]
            );

            // Update deal expected_amount
            $this->updateDealAmountFromQuotations($db, $dealId);

            flash('success', __('quotation_unlinked'));
            $this->redirect('/sales/deals/' . $dealId . '#quotations');
        } catch (Exception $e) {
            error_log('DealController::unlinkQuotation - ' . $e->getMessage());
            flash('error', __('msg_delete_error'));
            $this->redirect('/sales/deals/' . $dealId);
        }
    }

    /**
     * Convert deal to sales order (受注登録)
     * Creates a sales order from the deal and its linked quotations
     */
    public function convertToOrder($dealId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $deal = $db->fetch(
                "SELECT d.*, c.customer_name
                 FROM deals d
                 LEFT JOIN customers c ON c.customer_id = d.customer_id
                 WHERE d.deal_id = ? AND d.is_deleted = FALSE",
                [$dealId]
            );
            if (!$deal) {
                flash('error', __('deal_not_found'));
                $this->redirect('/sales/deals');
                return;
            }

            // ── #11: Prevent duplicate order registration ──
            $existingSo = $db->fetch(
                "SELECT so_id, so_no, status FROM sales_order_headers
                 WHERE deal_id = ? AND status <> 'CANCELLED'
                 ORDER BY so_id DESC LIMIT 1",
                [$dealId]
            );
            if ($existingSo) {
                flash('error', __('deal_already_converted') . ': ' . $existingSo['so_no']);
                $this->redirect('/sales/deals/' . $dealId);
                return;
            }

            // Get linked quotations
            $quotations = $db->fetchAll(
                "SELECT qh.quotation_id, qh.quotation_no, qh.subtotal_thb, qh.vat_rate,
                        qh.vat_amount, qh.grand_total_thb, qh.payment_term_id, qh.currency_code,
                        qh.exchange_rate, qh.project_name
                 FROM quotation_headers qh
                 WHERE qh.deal_id = ? AND qh.is_deleted = FALSE
                 ORDER BY qh.quotation_no",
                [$dealId]
            ) ?: [];

            if (empty($quotations)) {
                flash('error', __('msg_no_quotations_linked'));
                $this->redirect('/sales/deals/' . $dealId);
                return;
            }

            $user = $this->getCurrentUser();
            $db->beginTransaction();

            // Generate SO number
            $yearMonth = date('Ym');
            $prefix = 'SO-' . $yearMonth;
            $row = $db->fetch(
                "SELECT so_no FROM sales_order_headers WHERE so_no LIKE ? ORDER BY so_no DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = $row ? intval(substr($row['so_no'], strlen($prefix))) + 1 : 1;
            $soNo = $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);

            // Aggregate totals from all linked quotations
            $subtotal = 0;
            $vatAmount = 0;
            $grandTotal = 0;
            $qtNos = [];
            $firstQt = $quotations[0];
            foreach ($quotations as $qt) {
                $subtotal += floatval($qt['subtotal_thb'] ?? 0);
                $vatAmount += floatval($qt['vat_amount'] ?? 0);
                $grandTotal += floatval($qt['grand_total_thb'] ?? 0);
                $qtNos[] = $qt['quotation_no'];
            }

            // Create sales order header
            $soRow = $db->fetch(
                "INSERT INTO sales_order_headers
                 (so_no, order_date, customer_id, division_id, quotation_id, deal_id,
                  payment_term_id, currency_code, exchange_rate,
                  subtotal_thb, vat_rate, vat_amount, grand_total_thb,
                  notes, status, created_by)
                 VALUES (?, CURRENT_DATE, ?, 1, ?, ?, ?, ?, ?,
                         ?, ?, ?, ?, ?, 'CONFIRMED', ?)
                 RETURNING so_id",
                [
                    $soNo,
                    $deal['customer_id'],
                    $firstQt['quotation_id'],  // Primary quotation reference
                    $dealId,
                    $firstQt['payment_term_id'],
                    $firstQt['currency_code'] ?? 'THB',
                    $firstQt['exchange_rate'] ?? 1,
                    $subtotal,
                    $firstQt['vat_rate'] ?? 7,
                    $vatAmount,
                    $grandTotal,
                    $deal['deal_name'] . ' (' . implode(', ', $qtNos) . ')',
                    $user['user_id'],
                ]
            );
            $soId = $soRow['so_id'];

            // Copy quotation lines to sales order lines
            $lineNo = 1;
            foreach ($quotations as $qt) {
                $qLines = $db->fetchAll(
                    "SELECT * FROM quotation_lines
                     WHERE quotation_id = ? AND is_category_row = FALSE
                       AND (is_deleted = FALSE OR is_deleted IS NULL)
                     ORDER BY sort_order, line_no",
                    [$qt['quotation_id']]
                ) ?: [];

                foreach ($qLines as $ql) {
                    $db->query(
                        "INSERT INTO sales_order_lines
                         (so_id, line_no, item_id, item_description, quantity, unit,
                          unit_price, discount_rate, ext_price)
                         VALUES (?,?,?,?,?,?,?,?,?)",
                        [
                            $soId,
                            $lineNo++,
                            $ql['item_id'],
                            $ql['item_description'] ?? '',
                            $ql['quantity'] ?? 0,
                            $ql['unit'] ?? '',
                            $ql['unit_price'] ?? 0,
                            $ql['discount_rate'] ?? 0,
                            $ql['ext_price'] ?? 0,
                        ]
                    );
                }
            }

            // Update quotation statuses to WON
            foreach ($quotations as $qt) {
                $db->query(
                    "UPDATE quotation_headers SET status = 'WON', won_so_id = ?, updated_by = ?, updated_at = NOW()
                     WHERE quotation_id = ?",
                    [$soId, $user['user_id'], $qt['quotation_id']]
                );
            }

            // Update deal status to WON (find the "Won" or highest win_pct status)
            $wonStatus = $db->fetch(
                "SELECT status_id FROM deal_statuses WHERE win_pct >= 100 AND is_deleted = FALSE ORDER BY sort_order LIMIT 1"
            );
            if (!$wonStatus) {
                // Fallback: find status with highest win_pct
                $wonStatus = $db->fetch(
                    "SELECT status_id FROM deal_statuses WHERE is_deleted = FALSE ORDER BY win_pct DESC LIMIT 1"
                );
            }
            if ($wonStatus) {
                $db->query(
                    "UPDATE deals SET status_id = ?, updated_by = ?, updated_at = NOW() WHERE deal_id = ?",
                    [$wonStatus['status_id'], $user['user_id'], $dealId]
                );
            }

            // Auto-generate PJ number and create project record
            require_once BASE_PATH . '/controllers/ProjectController.php';
            $projectName = $deal['deal_name'];
            // Get solution category from deal for pj_segment
            $dealFull = $db->fetch(
                "SELECT sc.category_name FROM deals d
                 LEFT JOIN solution_categories sc ON sc.category_id = d.solution_category_id
                 WHERE d.deal_id = ?",
                [$dealId]
            );

            // Generate unique PJ no up-front (avoid retry inside transaction which
            // triggers SQLSTATE 25P02 "current transaction is aborted" on collision)
            $pjNo = ProjectController::generatePjNo($db);
            $attempts = 0;
            while ($attempts < 10) {
                $exists = $db->fetch("SELECT 1 FROM projects WHERE pj_no = ?", [$pjNo]);
                if (!$exists) break;
                // bump last 5 digits
                $prefix = substr($pjNo, 0, 4);
                $seq = intval(substr($pjNo, 4)) + 1;
                $pjNo = $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
                $attempts++;
            }

            $db->query(
                "INSERT INTO projects
                 (pj_no, pj_name, customer_id, sales_person_id, so_id, deal_id,
                  pj_segment, total_revenue, total_cost, gross_profit, profit_pct,
                  po_date, status, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0,
                         CURRENT_DATE, 'ACTIVE', ?, NOW())",
                [
                    $pjNo, $projectName, $deal['customer_id'], $deal['sales_person_id'],
                    $soId, $dealId, $dealFull['category_name'] ?? null,
                    $grandTotal, $grandTotal, $user['user_id'],
                ]
            );

            $db->commit();
            flash('success', __('order_created_from_deal', $soNo) . ' / PJ: ' . $pjNo);
            $this->redirect('/sales/orders/' . $soId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('DealController::convertToOrder - ' . $e->getMessage());
            flash('error', __('msg_save_error') . ': ' . $e->getMessage());
            $this->redirect('/sales/deals/' . $dealId);
        }
    }

    /**
     * Update deal expected_amount from sum of linked quotations
     */
    private function updateDealAmountFromQuotations($db, $dealId)
    {
        $sum = $db->fetch(
            "SELECT COALESCE(SUM(grand_total_thb), 0) as total
             FROM quotation_headers
             WHERE deal_id = ? AND is_deleted = FALSE",
            [$dealId]
        );
        if ($sum) {
            $db->query(
                "UPDATE deals SET expected_amount = ?, updated_at = NOW() WHERE deal_id = ?",
                [$sum['total'], $dealId]
            );
        }
    }

    /**
     * Generate deal number: DL-YYYYMM-NNNN
     */
    private function generateDealNo($db)
    {
        $prefix = 'DL';
        $yearMonth = date('Ym');
        $pattern = $prefix . '-' . $yearMonth . '-';

        $row = $db->fetch(
            "SELECT deal_no FROM deals WHERE deal_no LIKE ? ORDER BY deal_no DESC LIMIT 1",
            [$pattern . '%']
        );

        if ($row) {
            $parts = explode('-', $row['deal_no']);
            $seq = intval(end($parts)) + 1;
        } else {
            $seq = 1;
        }

        return $pattern . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
