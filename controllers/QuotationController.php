<?php
/**
 * PEGASUS ERP - Quotation Controller
 * Quotation management with category hierarchy and cost/price separation
 */

class QuotationController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            $perPage = 50;
            $currentPage = max(1, intval($_GET['page'] ?? 1));

            $filters = [
                'status'      => sanitize($_GET['status'] ?? ''),
                'possibility' => sanitize($_GET['possibility'] ?? ''),
                'date_from'   => sanitize($_GET['date_from'] ?? ''),
                'date_to'     => sanitize($_GET['date_to'] ?? ''),
                'customer'    => sanitize($_GET['customer'] ?? ''),
                'group_by'    => sanitize($_GET['group_by'] ?? 'project'),
            ];

            // ── Sort parameter ──
            $allowedSorts = [
                'quotation_no'  => 'qh.quotation_no',
                'date'          => 'qh.issue_date',
                'customer'      => 'c.customer_name',
                'project'       => 'qh.project_name',
                'subtotal'      => 'qh.subtotal_thb',
                'cost'          => 'COALESCE(lc.total_cost, 0)',
                'gross_profit'  => 'qh.gross_profit',
                'possibility'   => 'qh.possibility',
                'status'        => 'qh.status',
                'sales_name'    => 'qh.sales_name',
                'solution'      => 'sc.category_name',
            ];
            $sortCol = sanitize($_GET['sort'] ?? 'date');
            $sortDir = strtoupper(sanitize($_GET['dir'] ?? 'DESC'));
            if (!in_array($sortDir, ['ASC', 'DESC'])) $sortDir = 'DESC';
            $sortExpr = $allowedSorts[$sortCol] ?? 'qh.issue_date';

            // ── Build WHERE clause ──
            $where = "qh.is_deleted = FALSE";
            $params = [];

            if (!empty($filters['status'])) {
                $where .= " AND qh.status = ?";
                $params[] = $filters['status'];
            }
            if (!empty($filters['possibility'])) {
                $where .= " AND qh.possibility ILIKE ?";
                $params[] = '%' . $filters['possibility'] . '%';
            }
            if (!empty($filters['date_from'])) {
                $where .= " AND qh.issue_date >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where .= " AND qh.issue_date <= ?";
                $params[] = $filters['date_to'];
            }
            if (!empty($filters['customer'])) {
                $where .= " AND c.customer_name ILIKE ?";
                $params[] = '%' . $filters['customer'] . '%';
            }

            // ── Count total ──
            $countSql = "SELECT COUNT(*)
                         FROM quotation_headers qh
                         LEFT JOIN customers c ON c.customer_id = qh.customer_id
                         WHERE $where";
            $totalRow = $db->fetch($countSql, $params);
            $totalCount = intval($totalRow['count'] ?? 0);
            $lastPage = max(1, ceil($totalCount / $perPage));
            if ($currentPage > $lastPage) $currentPage = $lastPage;
            $offset = ($currentPage - 1) * $perPage;

            // ── Fetch quotation rows ──
            $sql = "SELECT qh.*, c.customer_name, c.customer_name_jp, c.customer_name_th,
                           sc.category_name AS solution_cat_name,
                           COALESCE(lc.total_cost, 0) AS total_line_cost
                    FROM quotation_headers qh
                    LEFT JOIN customers c ON c.customer_id = qh.customer_id
                    LEFT JOIN solution_categories sc ON sc.category_id = qh.solution_category_id
                    LEFT JOIN (
                        SELECT quotation_id,
                               SUM(COALESCE(cost_total,0) * COALESCE(quantity,0)) AS total_cost
                        FROM quotation_lines
                        WHERE is_category_row = FALSE AND (is_deleted = FALSE OR is_deleted IS NULL)
                        GROUP BY quotation_id
                    ) lc ON lc.quotation_id = qh.quotation_id
                    WHERE $where
                    ORDER BY $sortExpr $sortDir NULLS LAST, qh.quotation_id DESC
                    LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;

            $quotations = $db->fetchAll($sql, $params) ?: [];

            // ── Group by customer + base project name ──
            $grouped = [];
            $projectGroups = [];

            foreach ($quotations as $q) {
                $baseName = preg_replace('/\s*\d+%\s*$/', '', $q['project_name'] ?? '');
                $custId = $q['customer_id'] ?? '';
                $pcode = ($custId && $baseName) ? ($custId . '|||' . $baseName) : '';
                $selling = floatval($q['subtotal_thb'] ?? 0);
                $cost = floatval($q['total_line_cost'] ?? 0);
                $gp = $selling - $cost;
                $gpPct = $selling > 0 ? ($gp / $selling * 100) : 0;
                $q['gross_profit'] = $gp;
                $q['gross_profit_pct'] = $gpPct;

                if ($pcode !== '' && $filters['group_by'] === 'project') {
                    if (isset($projectGroups[$pcode])) {
                        $idx = $projectGroups[$pcode];
                        $grouped[$idx]['subtotal_thb'] += $selling;
                        $grouped[$idx]['total_line_cost'] += $cost;
                        $gp2 = $grouped[$idx]['subtotal_thb'] - $grouped[$idx]['total_line_cost'];
                        $grouped[$idx]['gross_profit'] = $gp2;
                        $grouped[$idx]['gross_profit_pct'] = $grouped[$idx]['subtotal_thb'] > 0
                            ? ($gp2 / $grouped[$idx]['subtotal_thb'] * 100) : 0;
                        $grouped[$idx]['_children'][] = $q;
                        $grouped[$idx]['_child_count']++;
                        $grouped[$idx]['_qt_numbers'][] = $q['quotation_no'];
                    } else {
                        $baseName = preg_replace('/\s*\d+%\s*$/', '', $q['project_name'] ?? '');
                        $q['_project_base_name'] = $baseName ?: ($q['project_name'] ?? '');
                        $q['_is_group'] = true;
                        $q['_children'] = [$q];
                        $q['_child_count'] = 1;
                        $q['_qt_numbers'] = [$q['quotation_no']];
                        $q['total_line_cost'] = $cost;
                        $projectGroups[$pcode] = count($grouped);
                        $grouped[] = $q;
                    }
                } else {
                    $q['_is_group'] = false;
                    $q['_children'] = [];
                    $q['_child_count'] = 0;
                    $grouped[] = $q;
                }
            }

            // ── Pagination data ──
            $pagination = [
                'total'        => $totalCount,
                'per_page'     => $perPage,
                'current_page' => $currentPage,
                'last_page'    => $lastPage,
                'from'         => $totalCount > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $totalCount),
            ];

            // Load deal statuses for possibility filter/colors
            $dealStatuses = $db->fetchAll(
                "SELECT status_id, status_name, status_name_jp, status_name_th, win_pct, color
                 FROM deal_statuses WHERE is_deleted = FALSE ORDER BY sort_order"
            ) ?: [];

            $this->render('sales/quotations', [
                'pageTitle'    => __('quotations'),
                'quotations'   => $grouped,
                'filters'      => $filters,
                'pagination'   => $pagination,
                'sort'         => $sortCol,
                'dir'          => $sortDir,
                'dealStatuses' => $dealStatuses,
            ]);
        } catch (Exception $e) {
            error_log('QuotationController::index - ' . $e->getMessage());
            flash('error', 'Failed to load quotations.');
            $this->render('sales/quotations', [
                'pageTitle'  => __('quotations'),
                'quotations' => [],
                'filters'    => [],
            ]);
        }
    }

    public function create()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            $customers = $db->fetchAll(
                "SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name"
            );
            $paymentTerms = $db->fetchAll(
                "SELECT * FROM payment_terms WHERE is_deleted = FALSE ORDER BY term_name_en"
            );
            $quotationCategories = $db->fetchAll(
                "SELECT category_id, category_code, name_jp, name_en, name_th, cost_coefficient
                 FROM quotation_categories
                 WHERE is_active = TRUE AND is_deleted = FALSE
                 ORDER BY sort_order, name_jp"
            );

            $this->render('sales/quotation_form', [
                'pageTitle' => __('new') . ' ' . __('quotation'),
                'quotation' => null,
                'lines' => [],
                'customers' => $customers ?: [],
                'paymentTerms' => $paymentTerms ?: [],
                'quotationCategories' => $quotationCategories ?: [],
            ]);
        } catch (Exception $e) {
            error_log('QuotationController::create - ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/sales/quotations');
        }
    }

    public function store()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $customerId = sanitize($_POST['customer_id'] ?? '');
            $issueDate = sanitize($_POST['issue_date'] ?? '');
            $expiryDate = sanitize($_POST['expiry_date'] ?? '') ?: null;
            $paymentTermId = sanitize($_POST['payment_term_id'] ?? '') ?: null;
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $exchangeRate = floatval($_POST['exchange_rate'] ?? 1);
            $remarkText = sanitize($_POST['remark_text'] ?? '');
            $noteText = sanitize($_POST['note_text'] ?? '');
            $projectName = sanitize($_POST['project_name'] ?? '');
            $projectCode = sanitize($_POST['project_code'] ?? '');
            $attentionName = sanitize($_POST['attention_name'] ?? '');
            $attentionEmail = sanitize($_POST['attention_email'] ?? '');
            $shipToAddress = sanitize($_POST['ship_to_address'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: 1;
            $user = $this->getCurrentUser();

            // Parse lines[] array
            $rawLines = $_POST['lines'] ?? [];

            if (empty($customerId) || empty($issueDate)) {
                flash('error', __('customer') . ' / ' . __('issue_date') . ' required.');
                $this->redirect('/sales/quotations/create');
                return;
            }

            $db->beginTransaction();

            $quotationNo = $this->generateQuotationNo($db);

            // Process lines: categories numbered 1,2,3,4; items 1-1,1-2
            $subtotal = 0;
            $totalCost = 0;
            $lineData = $this->processLines($rawLines, $subtotal, $totalCost);

            $vatRate = floatval($_POST['vat_rate'] ?? 7);
            $discountAmount = floatval($_POST['discount_amount'] ?? 0);
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = $afterDiscount * ($vatRate / 100);
            $grandTotalThb = $afterDiscount + $vatAmount;

            // Insert header
            $status = sanitize($_POST['action'] ?? 'draft') === 'submit' ? 'AWAIT_APPROVAL' : 'DRAFT';
            $row = $db->fetch(
                "INSERT INTO quotation_headers
                 (quotation_no, issue_date, expiry_date, customer_id, division_id,
                  payment_term_id, currency_code, exchange_rate,
                  subtotal_thb, discount_amount, vat_rate, vat_amount,
                  grand_total_thb, remark_text, note_text, project_name, project_code,
                  attention_name, attention_email, ship_to_address,
                  status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                 RETURNING quotation_id",
                [$quotationNo, $issueDate, $expiryDate, $customerId, $divisionId,
                 $paymentTermId, $currencyCode, $exchangeRate,
                 $subtotal, $discountAmount, $vatRate, $vatAmount,
                 $grandTotalThb, $remarkText, $noteText, $projectName, $projectCode,
                 $attentionName, $attentionEmail, $shipToAddress,
                 $status, $user['user_id']]
            );
            $quotationId = $row['quotation_id'];

            // Insert lines
            $this->insertLines($db, $quotationId, $lineData);

            // Validate inspection schedule totals BEFORE saving (will throw on mismatch)
            self::assertInspectionScheduleMatches((float)$subtotal);

            // Save inspection schedule (検収スケジュール)
            $this->saveInspectionSchedule($db, $quotationId, (float)$subtotal);

            $db->commit();
            flash('success', __('quotation') . " {$quotationNo} " . __('msg_saved'));
            $this->redirect('/sales/quotations/' . $quotationId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('QuotationController::store - ' . $e->getMessage());
            flash('error', 'Failed to create quotation: ' . $e->getMessage());
            $this->redirect('/sales/quotations/create');
        }
    }

    public function show($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            $quotation = $db->fetch(
                "SELECT qh.*, c.customer_name, c.customer_name_jp, c.customer_name_th, c.address as customer_address,
                        c.contact_person, c.tax_id as customer_tax_id,
                        pt.term_name_en as payment_term_name
                 FROM quotation_headers qh
                 LEFT JOIN customers c ON c.customer_id = qh.customer_id
                 LEFT JOIN payment_terms pt ON pt.term_id = qh.payment_term_id
                 WHERE qh.quotation_id = ? AND qh.is_deleted = FALSE",
                [$id]
            );

            if (!$quotation) {
                flash('error', 'Quotation not found.');
                $this->redirect('/sales/quotations');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT ql.*, i.item_code, i.item_name
                 FROM quotation_lines ql
                 LEFT JOIN items i ON i.item_id = ql.item_id
                 WHERE ql.quotation_id = ?
                 ORDER BY ql.sort_order, ql.line_no",
                [$id]
            );

            $quotation['lines'] = $lines ?: [];

            // Load inspection schedule (検収スケジュール)
            $inspections = $db->fetchAll(
                "SELECT * FROM quotation_inspection_schedule
                 WHERE quotation_id = ? ORDER BY seq_no",
                [$id]
            );

            $this->render('sales/quotation_detail', [
                'pageTitle' => __('quotation') . ' ' . $quotation['quotation_no'],
                'quotation' => $quotation,
                'lines' => $lines ?: [],
                'inspections' => $inspections ?: [],
            ]);
        } catch (Exception $e) {
            error_log('QuotationController::show - ' . $e->getMessage());
            flash('error', 'Failed to load quotation.');
            $this->redirect('/sales/quotations');
        }
    }

    public function edit($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            $quotation = $db->fetch(
                "SELECT * FROM quotation_headers WHERE quotation_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$quotation) {
                flash('error', 'Quotation not found.');
                $this->redirect('/sales/quotations');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT ql.*, i.item_code, i.item_name
                 FROM quotation_lines ql
                 LEFT JOIN items i ON i.item_id = ql.item_id
                 WHERE ql.quotation_id = ? ORDER BY ql.sort_order, ql.line_no",
                [$id]
            );

            $quotation['lines'] = $lines ?: [];

            $customers = $db->fetchAll(
                "SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name"
            );
            $paymentTerms = $db->fetchAll(
                "SELECT * FROM payment_terms WHERE is_deleted = FALSE ORDER BY term_name_en"
            );
            $inspections = $db->fetchAll(
                "SELECT * FROM quotation_inspection_schedule WHERE quotation_id = ? ORDER BY seq_no",
                [$id]
            );
            $quotationCategories = $db->fetchAll(
                "SELECT category_id, category_code, name_jp, name_en, name_th, cost_coefficient
                 FROM quotation_categories
                 WHERE is_active = TRUE AND is_deleted = FALSE
                 ORDER BY sort_order, name_jp"
            );

            $this->render('sales/quotation_form', [
                'pageTitle' => __('edit') . ' ' . __('quotation') . ' ' . $quotation['quotation_no'],
                'quotation' => $quotation,
                'lines' => $lines ?: [],
                'customers' => $customers ?: [],
                'paymentTerms' => $paymentTerms ?: [],
                'inspections' => $inspections ?: [],
                'quotationCategories' => $quotationCategories ?: [],
            ]);
        } catch (Exception $e) {
            error_log('QuotationController::edit - ' . $e->getMessage());
            flash('error', 'Failed to load quotation.');
            $this->redirect('/sales/quotations');
        }
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $customerId = sanitize($_POST['customer_id'] ?? '');
            $issueDate = sanitize($_POST['issue_date'] ?? '');
            $expiryDate = sanitize($_POST['expiry_date'] ?? '') ?: null;
            $paymentTermId = sanitize($_POST['payment_term_id'] ?? '') ?: null;
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $exchangeRate = floatval($_POST['exchange_rate'] ?? 1);
            $remarkText = sanitize($_POST['remark_text'] ?? '');
            $noteText = sanitize($_POST['note_text'] ?? '');
            $projectName = sanitize($_POST['project_name'] ?? '');
            $projectCode = sanitize($_POST['project_code'] ?? '');
            $attentionName = sanitize($_POST['attention_name'] ?? '');
            $attentionEmail = sanitize($_POST['attention_email'] ?? '');
            $shipToAddress = sanitize($_POST['ship_to_address'] ?? '');
            $status = sanitize($_POST['status'] ?? 'DRAFT');
            $user = $this->getCurrentUser();

            $rawLines = $_POST['lines'] ?? [];

            $db->beginTransaction();

            $subtotal = 0;
            $totalCost = 0;
            $lineData = $this->processLines($rawLines, $subtotal, $totalCost);

            $vatRate = floatval($_POST['vat_rate'] ?? 7);
            $discountAmount = floatval($_POST['discount_amount'] ?? 0);
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = $afterDiscount * ($vatRate / 100);
            $grandTotalThb = $afterDiscount + $vatAmount;

            // Update header
            $db->query(
                "UPDATE quotation_headers SET
                 issue_date = ?, expiry_date = ?, customer_id = ?,
                 payment_term_id = ?, currency_code = ?, exchange_rate = ?,
                 subtotal_thb = ?, discount_amount = ?, vat_rate = ?, vat_amount = ?,
                 grand_total_thb = ?, remark_text = ?, note_text = ?,
                 project_name = ?, project_code = ?, attention_name = ?, attention_email = ?,
                 ship_to_address = ?, status = ?,
                 updated_by = ?, updated_at = NOW()
                 WHERE quotation_id = ?",
                [$issueDate, $expiryDate, $customerId, $paymentTermId, $currencyCode,
                 $exchangeRate, $subtotal, $discountAmount, $vatRate, $vatAmount,
                 $grandTotalThb, $remarkText, $noteText,
                 $projectName, $projectCode, $attentionName, $attentionEmail,
                 $shipToAddress, $status,
                 $user['user_id'], $id]
            );

            // Delete old lines and re-insert
            $db->query("DELETE FROM quotation_lines WHERE quotation_id = ?", [$id]);
            $this->insertLines($db, $id, $lineData);

            // Validate inspection schedule totals BEFORE saving
            self::assertInspectionScheduleMatches((float)$subtotal);

            // Save inspection schedule (検収スケジュール)
            $this->saveInspectionSchedule($db, $id, (float)$subtotal);

            $db->commit();
            flash('success', __('quotation') . ' ' . __('msg_saved'));
            $this->redirect('/sales/quotations/' . $id);
        } catch (Exception $e) {
            $db->rollback();
            error_log('QuotationController::update - ' . $e->getMessage());
            flash('error', 'Failed to update quotation: ' . $e->getMessage());
            $this->redirect('/sales/quotations/' . $id . '/edit');
        }
    }

    public function delete($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE quotation_headers SET is_deleted = TRUE, updated_by = ?, updated_at = NOW()
                 WHERE quotation_id = ?",
                [$user['user_id'], $id]
            );
            flash('success', __('quotation') . ' ' . __('msg_deleted'));
        } catch (Exception $e) {
            error_log('QuotationController::delete - ' . $e->getMessage());
            flash('error', __('msg_delete_error'));
        }

        $this->redirect('/sales/quotations');
    }

    /**
     * Submit quotation for approval: DRAFT → PENDING_APPROVAL
     */
    public function submitForApproval($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();
        $user = $this->getCurrentUser();

        $db->query(
            "UPDATE quotation_headers
             SET status = 'PENDING_APPROVAL', updated_by = ?, updated_at = NOW()
             WHERE quotation_id = ? AND status IN ('DRAFT','INTERNAL_REVIEW')",
            [$user['user_id'], $id]
        );
        flash('success', __('msg_submitted_for_approval'));
        $this->redirect('/sales/quotations/' . $id);
    }

    /**
     * Approve quotation: PENDING_APPROVAL → APPROVED. Director+ only.
     */
    public function approve($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        if (!Auth::isDirectorOrAbove()) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/sales/quotations/' . $id);
            return;
        }
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $approverEmpId = $user['employee_id'] ?? null;
        if (!$approverEmpId) {
            $row = $db->fetch("SELECT employee_id FROM users WHERE user_id = ?", [$user['user_id']]);
            $approverEmpId = $row['employee_id'] ?? null;
        }
        $db->query(
            "UPDATE quotation_headers
             SET status = 'APPROVED', approved_by = ?, approved_at = NOW(), updated_by = ?, updated_at = NOW()
             WHERE quotation_id = ? AND status = 'PENDING_APPROVAL'",
            [$approverEmpId, $user['user_id'], $id]
        );
        flash('success', __('msg_approved'));
        $this->redirect('/sales/quotations/' . $id);
    }

    /**
     * Reject quotation: PENDING_APPROVAL → DRAFT
     */
    public function reject($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        if (!Auth::isDirectorOrAbove()) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/sales/quotations/' . $id);
            return;
        }
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE quotation_headers
             SET status = 'DRAFT', updated_by = ?, updated_at = NOW()
             WHERE quotation_id = ? AND status = 'PENDING_APPROVAL'",
            [$user['user_id'], $id]
        );
        flash('success', __('msg_rejected'));
        $this->redirect('/sales/quotations/' . $id);
    }

    /**
     * Duplicate a quotation (header + lines) as a new DRAFT.
     */
    public function copy($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();
        $user = $this->getCurrentUser();

        try {
            $db->beginTransaction();

            $src = $db->fetch("SELECT * FROM quotation_headers WHERE quotation_id = ? AND is_deleted = FALSE", [$id]);
            if (!$src) { throw new Exception('source quotation not found'); }

            // Generate a new quotation_no using the same prefix + next sequence based on today.
            $newNo = 'QT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $row = $db->fetch(
                "INSERT INTO quotation_headers
                  (quotation_no, revision, issue_date, expiry_date, customer_id,
                   payment_term_id, currency_code, exchange_rate,
                   subtotal_thb, discount_amount, vat_rate, vat_amount, grand_total_thb,
                   remark_text, note_text, project_name, project_code,
                   attention_name, attention_email, ship_to_address,
                   status, sales_name, possibility, created_by, updated_by, project_id)
                 VALUES (?, 0, CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DRAFT', ?, ?, ?, ?, ?)
                 RETURNING quotation_id",
                [$newNo, $src['expiry_date'], $src['customer_id'],
                 $src['payment_term_id'], $src['currency_code'], $src['exchange_rate'],
                 $src['subtotal_thb'], $src['discount_amount'], $src['vat_rate'], $src['vat_amount'], $src['grand_total_thb'],
                 $src['remark_text'], $src['note_text'], $src['project_name'], $src['project_code'],
                 $src['attention_name'], $src['attention_email'], $src['ship_to_address'],
                 $src['sales_name'], $src['possibility'], $user['user_id'], $user['user_id'], $src['project_id']]
            );
            $newId = $row['quotation_id'];

            $db->query(
                "INSERT INTO quotation_lines
                    (quotation_id, line_no, parent_line_no, is_category_row, item_description,
                     quantity, unit, unit_price, discount_rate, ext_price, cost_total, sort_order)
                 SELECT ?, line_no, parent_line_no, is_category_row, item_description,
                        quantity, unit, unit_price, discount_rate, ext_price, cost_total, sort_order
                 FROM quotation_lines WHERE quotation_id = ? AND (is_deleted = FALSE OR is_deleted IS NULL)",
                [$newId, $id]
            );

            $db->commit();
            flash('success', __('msg_saved'));
            $this->redirect('/sales/quotations/' . $newId . '/edit');
        } catch (Exception $e) {
            $db->rollback();
            error_log('QuotationController::copy - ' . $e->getMessage());
            flash('error', __('msg_save_error') . ': ' . $e->getMessage());
            $this->redirect('/sales/quotations/' . $id);
        }
    }

    /**
     * Process raw lines from POST into structured line data.
     * Categories: numbered 1, 2, 3, 4
     * Items under category: 1-1, 1-2, 2-1, etc.
     * Items without category: 1, 2, 3 (plain numbering)
     */
    private function processLines(array $rawLines, float &$subtotal, float &$totalCost): array
    {
        $lineData = [];
        $sortOrder = 0;
        $catNum = 0;
        $itemNum = 0;
        $currentCatNum = 0;

        foreach ($rawLines as $line) {
            $isCat = ($line['is_category_row'] ?? '0') === '1';
            $desc = sanitize($line['item_description'] ?? '');
            $sortOrder++;

            if ($isCat) {
                $catNum++;
                $itemNum = 0;
                $currentCatNum = $catNum;

                $lineData[] = [
                    'line_no' => (string)$catNum,
                    'parent_line_no' => null,
                    'is_category_row' => true,
                    'item_id' => null,
                    'item_description' => $desc,
                    'quantity' => null,
                    'unit' => null,
                    'unit_price' => 0,
                    'discount_rate' => 0,
                    'ext_price' => 0,
                    'cost_total' => 0,
                    'sort_order' => $sortOrder,
                ];
            } else {
                $itemNum++;
                $qty = floatval($line['quantity'] ?? 0);
                $costUnit = floatval($line['cost_total'] ?? 0);
                $markupRate = floatval($line['markup_rate'] ?? 0);
                $unitPrice = floatval($line['unit_price'] ?? 0);
                $unit = sanitize($line['unit'] ?? 'EA');
                $itemId = !empty($line['item_id']) ? intval($line['item_id']) : null;

                $extPrice = $qty * $unitPrice;
                $subtotal += $extPrice;
                $totalCost += $qty * $costUnit;

                // Line number: "catNum-itemNum" if under a category, else plain number
                if ($currentCatNum > 0) {
                    $lineNo = $currentCatNum . '-' . $itemNum;
                    $parentLn = (string)$currentCatNum;
                } else {
                    $lineNo = (string)$itemNum;
                    $parentLn = null;
                }

                $lineData[] = [
                    'line_no' => $lineNo,
                    'parent_line_no' => $parentLn,
                    'is_category_row' => false,
                    'item_id' => $itemId,
                    'item_description' => $desc,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'unit_price' => $unitPrice,
                    'discount_rate' => $markupRate,
                    'ext_price' => $extPrice,
                    'cost_total' => $costUnit,
                    'sort_order' => $sortOrder,
                ];
            }
        }

        return $lineData;
    }

    /**
     * Insert line data into quotation_lines table.
     */
    /**
     * Save inspection schedule (検収スケジュール) from POST payload.
     * Expects arrays: insp_seq[], insp_desc[], insp_pct[], insp_date[], insp_status[], insp_notes[]
     * Replaces existing rows for the quotation.
     */
    /**
     * Validate the inspection schedule total matches the quotation subtotal.
     * If rows exist, percentages must sum to 100% (within 0.01% rounding tolerance).
     * Throws RuntimeException on mismatch — caller must catch & rollback.
     */
    public static function assertInspectionScheduleMatches(float $subtotal): void
    {
        $seqs = $_POST['insp_seq'] ?? [];
        $pcts = $_POST['insp_pct'] ?? [];
        $descs = $_POST['insp_desc'] ?? [];
        if (!is_array($seqs) || empty($seqs)) return;  // No schedule = OK (optional)

        $totalPct = 0.0;
        $totalAmt = 0.0;
        $hasAny = false;
        $n = count($seqs);
        for ($i = 0; $i < $n; $i++) {
            $pct = (float)($pcts[$i] ?? 0);
            $desc = trim((string)($descs[$i] ?? ''));
            // Skip totally empty rows
            if ($pct == 0.0 && $desc === '') continue;
            $hasAny = true;
            $totalPct += $pct;
            $totalAmt += round($subtotal * $pct / 100, 2);
        }
        if (!$hasAny) return;

        // Tolerance: 0.01% on pct, 1 THB on amount (rounding noise)
        if (abs($totalPct - 100.0) > 0.01) {
            throw new RuntimeException(
                __('quotation_insp_total_mismatch') . ' ('
                . __('expected') . ': 100% / '
                . __('actual') . ': ' . number_format($totalPct, 2) . '%)'
            );
        }
        if (abs($totalAmt - $subtotal) > 1.0) {
            throw new RuntimeException(
                __('quotation_insp_amount_mismatch') . ' ('
                . __('subtotal') . ': ฿' . number_format($subtotal, 2) . ' / '
                . __('insp_sum') . ': ฿' . number_format($totalAmt, 2) . ')'
            );
        }
    }

    private function saveInspectionSchedule(Database $db, int $quotationId, float $subtotal): void
    {
        $db->query("DELETE FROM quotation_inspection_schedule WHERE quotation_id = ?", [$quotationId]);

        $seqs    = $_POST['insp_seq']    ?? [];
        $descs   = $_POST['insp_desc']   ?? [];
        $pcts    = $_POST['insp_pct']    ?? [];
        $dates   = $_POST['insp_date']   ?? [];
        $stats   = $_POST['insp_status'] ?? [];
        $notes   = $_POST['insp_notes']  ?? [];

        if (!is_array($seqs) || empty($seqs)) return;

        $user = $this->getCurrentUser();
        $n = count($seqs);
        for ($i = 0; $i < $n; $i++) {
            $seq = (int)($seqs[$i] ?? ($i + 1));
            $pct = (float)($pcts[$i] ?? 0);
            $amt = round($subtotal * $pct / 100, 2);
            $desc = sanitize($descs[$i] ?? '');
            $date = $dates[$i] ?: null;
            $status = sanitize($stats[$i] ?? 'PENDING');
            $note = sanitize($notes[$i] ?? '');
            if ($seq <= 0 || ($pct <= 0 && !$desc && !$date)) continue;
            $db->query(
                "INSERT INTO quotation_inspection_schedule
                 (quotation_id, seq_no, description, percentage, amount,
                  expected_inspection_date, status, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [$quotationId, $seq, $desc, $pct, $amt, $date, $status, $note, $user['user_id'] ?? null]
            );
        }
    }

    private function insertLines(Database $db, int $quotationId, array $lineData): void
    {
        foreach ($lineData as $line) {
            $db->query(
                "INSERT INTO quotation_lines
                 (quotation_id, line_no, parent_line_no, is_category_row, item_id,
                  item_description, quantity, unit,
                  unit_price, discount_rate, ext_price, cost_total, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$quotationId, $line['line_no'], $line['parent_line_no'],
                 $line['is_category_row'] ? 'TRUE' : 'FALSE',
                 $line['item_id'],
                 $line['item_description'], $line['quantity'], $line['unit'],
                 $line['unit_price'], $line['discount_rate'], $line['ext_price'],
                 $line['cost_total'], $line['sort_order']]
            );
        }
    }

    /**
     * Generate quotation number: QT-YYYYMM-NNNN
     */
    private function generateQuotationNo($db, $customDate = null)
    {
        $yearMonth = $customDate ? date('Ym', strtotime($customDate)) : date('Ym');
        $prefix = 'QT-' . $yearMonth . '-';

        $row = $db->fetch(
            "SELECT quotation_no FROM quotation_headers
             WHERE quotation_no LIKE ?
             ORDER BY quotation_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $parts = explode('-', $row['quotation_no']);
            $seq = intval(end($parts)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
