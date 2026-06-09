<?php
/**
 * PEGASUS ERP - Purchase Order Controller
 * PO management with VAT/WHT calculations
 */

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $db = Database::getInstance();

        try {
            $filters = [
                'status'    => sanitize($_GET['status'] ?? ''),
                'date_from' => sanitize($_GET['date_from'] ?? ''),
                'date_to'   => sanitize($_GET['date_to'] ?? ''),
                'q'         => sanitize($_GET['q'] ?? ''),
                'supplier_id' => sanitize($_GET['supplier_id'] ?? ''),
            ];

            $sql = "SELECT po.*, s.supplier_name, s.supplier_name_jp, s.supplier_name_th
                    FROM purchase_order_headers po
                    LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
                    WHERE po.is_deleted = FALSE";
            $params = [];

            if ($filters['status'] !== '') {
                $sql .= " AND po.status = ?";
                $params[] = $filters['status'];
            }
            if ($filters['date_from'] !== '') {
                $sql .= " AND po.order_date >= ?";
                $params[] = $filters['date_from'];
            }
            if ($filters['date_to'] !== '') {
                $sql .= " AND po.order_date <= ?";
                $params[] = $filters['date_to'];
            }
            if ($filters['supplier_id'] !== '') {
                $sql .= " AND po.supplier_id = ?";
                $params[] = $filters['supplier_id'];
            }
            if ($filters['q'] !== '') {
                $sql .= " AND (po.po_no ILIKE ? OR s.supplier_name ILIKE ?
                              OR s.supplier_name_jp ILIKE ? OR s.supplier_name_th ILIKE ?)";
                $like = '%' . $filters['q'] . '%';
                $params = array_merge($params, [$like, $like, $like, $like]);
            }

            $sql .= " ORDER BY po.order_date DESC, po.po_no DESC";

            $orders = $db->fetchAll($sql, $params);

            // Suppliers list for dropdown filter (only those with POs)
            $suppliers = $db->fetchAll(
                "SELECT DISTINCT s.supplier_id, s.supplier_name, s.supplier_name_jp, s.supplier_name_th
                 FROM suppliers s
                 INNER JOIN purchase_order_headers po ON po.supplier_id = s.supplier_id AND po.is_deleted = FALSE
                 WHERE s.is_deleted = FALSE
                 ORDER BY s.supplier_name"
            );

            $this->render('purchasing/orders', [
                'pageTitle' => 'Purchase Orders',
                'orders' => $orders ?: [],
                'status' => $filters['status'],
                'filters' => $filters,
                'suppliers' => $suppliers ?: [],
            ]);
        } catch (Exception $e) {
            error_log('PurchaseOrderController::index - ' . $e->getMessage());
            flash('error', 'Failed to load purchase orders.');
            $this->render('purchasing/orders', [
                'pageTitle' => 'Purchase Orders',
                'orders' => [],
                'status' => '',
                'filters' => ['status'=>'','date_from'=>'','date_to'=>'','q'=>'','supplier_id'=>''],
                'suppliers' => [],
            ]);
        }
    }

    public function create()
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $db = Database::getInstance();

        try {
            $suppliers = $db->fetchAll(
                "SELECT supplier_id, supplier_code, supplier_name, wht_rate
                 FROM suppliers WHERE is_deleted = FALSE ORDER BY supplier_name"
            );
            $paymentTerms = $db->fetchAll(
                "SELECT * FROM payment_terms WHERE is_deleted = FALSE ORDER BY term_name_en"
            );
            $projects = $db->fetchAll(
                "SELECT p.project_id, p.pj_no, p.pj_name, c.customer_name
                 FROM projects p
                 LEFT JOIN customers c ON c.customer_id = p.customer_id
                 WHERE p.status <> 'CANCELLED'
                 ORDER BY p.pj_no DESC LIMIT 500"
            );

            // List of APPROVED PRs not yet converted (for the PR picker)
            $approvedPrs = $db->fetchAll(
                "SELECT pr.pr_id, pr.pr_no, pr.request_date, pr.needed_by_date,
                        pr.est_total_thb, e.full_name_jp AS requester_name,
                        s.supplier_name AS suggested_supplier_name
                 FROM purchase_requests pr
                 LEFT JOIN employees e ON e.employee_id = pr.requester_id
                 LEFT JOIN suppliers  s ON s.supplier_id = pr.suggested_supplier_id
                 WHERE pr.is_deleted = FALSE
                   AND pr.status = 'APPROVED'
                   AND pr.converted_po_id IS NULL
                 ORDER BY pr.pr_id DESC LIMIT 500"
            );

            // Pre-fill from approved Purchase Request (PR) if ?from_pr_id=N is provided
            $order = null;
            $lines = [];
            $fromPrId = isset($_GET['from_pr_id']) ? (int)$_GET['from_pr_id'] : 0;
            if ($fromPrId > 0) {
                $pr = $db->fetch(
                    "SELECT pr_id, pr_no, suggested_supplier_id, needed_by_date, status, justification
                     FROM purchase_requests WHERE pr_id = ? AND is_deleted = FALSE",
                    [$fromPrId]
                );
                if (!$pr) {
                    flash('error', 'PR not found.');
                    $this->redirect('/purchasing/requests'); return;
                }
                if ($pr['status'] !== 'APPROVED') {
                    flash('error', __('pr_must_be_approved_for_po'));
                    $this->redirect('/purchasing/requests/' . $fromPrId); return;
                }
                $prLines = $db->fetchAll(
                    "SELECT pr_line_id, line_no, item_code, item_description, quantity, unit, est_unit_price, est_line_total, needed_by_date
                     FROM purchase_request_lines WHERE pr_id = ? AND is_deleted = FALSE ORDER BY line_no",
                    [$fromPrId]
                );
                // Look up the overall-winning quote → supplier for the PO
                $winnerQuote = $db->fetch(
                    "SELECT supplier_id FROM purchase_request_quotes
                     WHERE pr_id = ? AND is_overall_winner = TRUE AND is_deleted = FALSE LIMIT 1",
                    [$fromPrId]
                );
                $supplierId = $winnerQuote['supplier_id'] ?? $pr['suggested_supplier_id'];

                // Map pr_line_id → winning quote line (cheapest selected per line)
                $winLines = $db->fetchAll(
                    "SELECT ql.pr_line_id, ql.unit_price, ql.line_total
                     FROM purchase_request_quote_lines ql
                     JOIN purchase_request_quotes q ON q.quote_id = ql.quote_id
                     WHERE q.pr_id = ? AND ql.is_winner = TRUE AND q.is_deleted = FALSE",
                    [$fromPrId]
                ) ?: [];
                $winByLine = [];
                foreach ($winLines as $w) {
                    $winByLine[(int)$w['pr_line_id']] = $w;
                }

                $order = [
                    'from_pr_id'    => $fromPrId,
                    'from_pr_no'    => $pr['pr_no'],
                    'supplier_id'   => $supplierId,
                    'delivery_date' => $pr['needed_by_date'],
                    'notes'         => 'Created from ' . $pr['pr_no'] . "\n" . ($pr['justification'] ?? ''),
                ];
                foreach ($prLines as $pl) {
                    $w = $winByLine[(int)$pl['pr_line_id']] ?? null;
                    $lines[] = [
                        'item_description' => $pl['item_description'],
                        'quantity'         => $pl['quantity'],
                        'unit'             => $pl['unit'],
                        'unit_price'       => $w ? $w['unit_price'] : $pl['est_unit_price'],
                        'ext_price'        => $w ? $w['line_total'] : $pl['est_line_total'],
                    ];
                }
            }

            $this->render('purchasing/order_form', [
                'pageTitle' => 'Create Purchase Order',
                'order' => $order,
                'lines' => $lines,
                'suppliers' => $suppliers ?: [],
                'paymentTerms' => $paymentTerms ?: [],
                'projects' => $projects ?: [],
                'approvedPrs' => $approvedPrs ?: [],
            ]);
        } catch (Exception $e) {
            error_log('PurchaseOrderController::create - ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/purchasing/orders');
        }
    }

    public function store()
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            // === Mandatory PR linkage (operational rule: PO cannot be raised without an APPROVED PR) ===
            $fromPrIdEarly = (int)($_POST['from_pr_id'] ?? 0);
            if ($fromPrIdEarly <= 0) {
                flash('error', __('po_requires_pr'));
                $this->redirect('/purchasing/orders/create');
                return;
            }
            $prRow = $db->fetch(
                "SELECT pr_id, status, converted_po_id
                 FROM purchase_requests
                 WHERE pr_id = ? AND is_deleted = FALSE",
                [$fromPrIdEarly]
            );
            if (!$prRow) {
                flash('error', __('po_pr_not_found'));
                $this->redirect('/purchasing/orders/create');
                return;
            }
            if ($prRow['status'] !== 'APPROVED') {
                flash('error', __('pr_must_be_approved_for_po'));
                $this->redirect('/purchasing/orders/create?from_pr_id=' . $fromPrIdEarly);
                return;
            }
            if (!empty($prRow['converted_po_id'])) {
                flash('error', __('po_pr_already_converted'));
                $this->redirect('/purchasing/orders/create');
                return;
            }
            // === end mandatory check ===

            $supplierId = sanitize($_POST['supplier_id'] ?? '');
            $orderDate = sanitize($_POST['order_date'] ?? '');
            $deliveryDate = sanitize($_POST['delivery_date'] ?? '') ?: null;
            $requestedDate = sanitize($_POST['requested_date'] ?? '') ?: null;
            $paymentTermId = sanitize($_POST['payment_term_id'] ?? '') ?: null;
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $exchangeRate = floatval($_POST['exchange_rate'] ?? 1);
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $projectId = sanitize($_POST['project_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            // Ensure division_id (NOT NULL constraint)
            if (empty($divisionId)) {
                $defaultDiv = $db->fetch("SELECT division_id FROM divisions WHERE is_deleted = FALSE ORDER BY division_id ASC LIMIT 1");
                $divisionId = $defaultDiv ? $defaultDiv['division_id'] : 1;
            }

            $itemIds = $_POST['item_id'] ?? [];
            $descriptions = $_POST['item_description'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $units = $_POST['unit'] ?? [];
            $unitPrices = $_POST['unit_price'] ?? [];

            if (empty($supplierId) || empty($orderDate)) {
                flash('error', 'Supplier and date are required.');
                $this->redirect('/purchasing/orders/create');
                return;
            }

            $db->beginTransaction();

            $poNo = $this->generatePoNo($db);

            // Get supplier WHT rate
            $supplier = $db->fetch(
                "SELECT wht_rate FROM suppliers WHERE supplier_id = ?",
                [$supplierId]
            );
            $whtRate = floatval($supplier['wht_rate'] ?? 0);

            // Calculate totals
            $subtotal = 0;
            $lineData = [];
            for ($i = 0; $i < count($itemIds); $i++) {
                $qty = floatval($quantities[$i] ?? 0);
                $price = floatval($unitPrices[$i] ?? 0);
                $extPrice = $qty * $price;
                $subtotal += $extPrice;

                $lineData[] = [
                    'item_id' => $itemIds[$i] ?: null,
                    'item_description' => sanitize($descriptions[$i] ?? ''),
                    'quantity' => $qty,
                    'unit' => sanitize($units[$i] ?? ''),
                    'unit_price' => $price,
                    'ext_price' => $extPrice
                ];
            }

            $vatRate = floatval($_POST['vat_rate'] ?? 7);
            $vatAmount = $subtotal * ($vatRate / 100);
            $totalBeforeWht = $subtotal + $vatAmount;
            $whtAmount = $subtotal * ($whtRate / 100);
            $paymentAmount = $totalBeforeWht - $whtAmount;

            // Insert header (PO starts in PENDING = awaiting approval)
            $row = $db->fetch(
                "INSERT INTO purchase_order_headers
                 (po_no, order_date, delivery_date, requested_date, supplier_id, division_id,
                  project_id, payment_term_id, currency_code, exchange_rate, subtotal_thb, vat_rate, vat_amount,
                  total_before_wht, wht_amount, payment_amount, notes, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'PENDING',?)
                 RETURNING po_id",
                [$poNo, $orderDate, $deliveryDate, $requestedDate, $supplierId, $divisionId,
                 $projectId, $paymentTermId, $currencyCode, $exchangeRate, $subtotal, $vatRate, $vatAmount,
                 $totalBeforeWht, $whtAmount, $paymentAmount, $notes, $user['user_id']]
            );
            $poId = $row['po_id'];

            // Insert lines
            $lineNo = 1;
            foreach ($lineData as $line) {
                $db->query(
                    "INSERT INTO purchase_order_lines
                     (po_id, line_no, item_id, item_description, quantity, unit, unit_price, ext_price)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [$poId, $lineNo++, $line['item_id'], $line['item_description'],
                     $line['quantity'], $line['unit'], $line['unit_price'], $line['ext_price']]
                );
            }

            // Link to source PR (if pre-filled from approved PR) — mark CONVERTED
            $fromPrId = isset($_POST['from_pr_id']) ? (int)$_POST['from_pr_id'] : 0;
            if ($fromPrId > 0) {
                $db->query(
                    "UPDATE purchase_order_headers SET pr_id = ? WHERE po_id = ?",
                    [$fromPrId, $poId]
                );
                $db->query(
                    "UPDATE purchase_requests
                     SET status='CONVERTED', converted_po_id=?, converted_at=NOW(), updated_at=NOW()
                     WHERE pr_id = ? AND status='APPROVED'",
                    [$poId, $fromPrId]
                );
            }

            $db->commit();
            flash('success', "Purchase Order {$poNo} created successfully.");
            $this->redirect('/purchasing/orders/' . $poId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('PurchaseOrderController::store - ' . $e->getMessage());
            flash('error', 'Failed to create purchase order.');
            $this->redirect('/purchasing/orders/create');
        }
    }

    public function show($id)
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $db = Database::getInstance();

        try {
            $order = $db->fetch(
                "SELECT po.*, s.supplier_name, s.supplier_name_jp, s.supplier_name_th, s.address as supplier_address,
                        s.contact_person, s.tax_id as supplier_tax_id,
                        pt.term_name_en as payment_term_name,
                        pr.pj_no, pr.pj_name
                 FROM purchase_order_headers po
                 LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
                 LEFT JOIN payment_terms pt ON pt.term_id = po.payment_term_id
                 LEFT JOIN projects pr ON pr.project_id = po.project_id
                 WHERE po.po_id = ? AND po.is_deleted = FALSE",
                [$id]
            );

            if (!$order) {
                flash('error', 'Purchase order not found.');
                $this->redirect('/purchasing/orders');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT pl.*, i.item_code, i.item_name
                 FROM purchase_order_lines pl
                 LEFT JOIN items i ON i.item_id = pl.item_id
                 WHERE pl.po_id = ? ORDER BY pl.line_no",
                [$id]
            );

            $this->render('purchasing/order_detail', [
                'pageTitle' => 'PO ' . $order['po_no'],
                'order' => $order,
                'lines' => $lines ?: []
            ]);
        } catch (Exception $e) {
            error_log('PurchaseOrderController::show - ' . $e->getMessage());
            flash('error', 'Failed to load purchase order.');
            $this->redirect('/purchasing/orders');
        }
    }

    public function edit($id)
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $order = $db->fetch(
                "SELECT po.*, s.supplier_name
                 FROM purchase_order_headers po
                 LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
                 WHERE po.po_id = ? AND po.is_deleted = FALSE",
                [$id]
            );

            if (!$order) {
                flash('error', 'Purchase order not found.');
                $this->redirect('/purchasing/orders');
                return;
            }

            if ($order['status'] !== 'PENDING') {
                flash('error', __('po_only_pending_editable'));
                $this->redirect('/purchasing/orders/' . $id);
                return;
            }

            $lines = $db->fetchAll(
                "SELECT pl.*, i.item_code, i.item_name
                 FROM purchase_order_lines pl
                 LEFT JOIN items i ON i.item_id = pl.item_id
                 WHERE pl.po_id = ? ORDER BY pl.line_no",
                [$id]
            );

            $order['lines'] = $lines ?: [];

            $suppliers = $db->fetchAll(
                "SELECT supplier_id, supplier_code, supplier_name, wht_rate
                 FROM suppliers WHERE is_deleted = FALSE ORDER BY supplier_name"
            );
            $paymentTerms = $db->fetchAll(
                "SELECT * FROM payment_terms WHERE is_deleted = FALSE ORDER BY term_name_en"
            );
            $projects = $db->fetchAll(
                "SELECT p.project_id, p.pj_no, p.pj_name, c.customer_name
                 FROM projects p
                 LEFT JOIN customers c ON c.customer_id = p.customer_id
                 WHERE p.status <> 'CANCELLED'
                 ORDER BY p.pj_no DESC LIMIT 500"
            );

            $this->render('purchasing/order_form', [
                'pageTitle' => 'Edit PO ' . $order['po_no'],
                'order' => $order,
                'lines' => $lines ?: [],
                'suppliers' => $suppliers ?: [],
                'paymentTerms' => $paymentTerms ?: [],
                'projects' => $projects ?: [],
            ]);
        } catch (Exception $e) {
            error_log('PurchaseOrderController::edit - ' . $e->getMessage());
            flash('error', 'Failed to load purchase order for editing.');
            $this->redirect('/purchasing/orders');
        }
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $user = $this->getCurrentUser();

            // Check if this is a status-only update (from detail page)
            if (!empty($_POST['status']) && !isset($_POST['supplier_id'])) {
                $status = sanitize($_POST['status']);
                $notes = sanitize($_POST['notes'] ?? '');

                $db->query(
                    "UPDATE purchase_order_headers SET status = ?, notes = ?, updated_by = ?, updated_at = NOW()
                     WHERE po_id = ?",
                    [$status, $notes, $user['user_id'], $id]
                );

                flash('success', 'Purchase order updated.');
                $this->redirect('/purchasing/orders/' . $id);
                return;
            }

            // Full edit update
            $supplierId = sanitize($_POST['supplier_id'] ?? '');
            $orderDate = sanitize($_POST['order_date'] ?? '');
            $deliveryDate = sanitize($_POST['delivery_date'] ?? '') ?: null;
            $requestedDate = sanitize($_POST['requested_date'] ?? '') ?: null;
            $paymentTermId = sanitize($_POST['payment_term_id'] ?? '') ?: null;
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $exchangeRate = floatval($_POST['exchange_rate'] ?? 1);
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $projectId = sanitize($_POST['project_id'] ?? '') ?: null;

            if (empty($divisionId)) {
                $defaultDiv = $db->fetch("SELECT division_id FROM divisions WHERE is_deleted = FALSE ORDER BY division_id ASC LIMIT 1");
                $divisionId = $defaultDiv ? $defaultDiv['division_id'] : 1;
            }

            if (empty($supplierId) || empty($orderDate)) {
                flash('error', 'Supplier and date are required.');
                $this->redirect('/purchasing/orders/' . $id . '/edit');
                return;
            }

            $db->beginTransaction();

            // Get supplier WHT rate
            $supplier = $db->fetch("SELECT wht_rate FROM suppliers WHERE supplier_id = ?", [$supplierId]);
            $whtRate = floatval($supplier['wht_rate'] ?? 0);

            // Process lines
            $linesData = $_POST['lines'] ?? [];
            $subtotal = 0;
            $lineItems = [];
            foreach ($linesData as $line) {
                $qty = floatval($line['quantity'] ?? 0);
                $price = floatval($line['unit_price'] ?? 0);
                $discRate = floatval($line['discount_rate'] ?? 0);
                $extPrice = $qty * $price * (1 - $discRate / 100);
                $subtotal += $extPrice;

                $lineItems[] = [
                    'item_id' => !empty($line['item_id']) ? $line['item_id'] : null,
                    'item_description' => sanitize($line['item_description'] ?? ''),
                    'quantity' => $qty,
                    'unit' => sanitize($line['unit'] ?? 'EA'),
                    'unit_price' => $price,
                    'discount_rate' => $discRate,
                    'ext_price' => $extPrice
                ];
            }

            $vatRate = floatval($_POST['vat_rate'] ?? 7);
            $discountAmount = floatval($_POST['discount_amount'] ?? 0);
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = $afterDiscount * ($vatRate / 100);
            $totalBeforeWht = $afterDiscount + $vatAmount;
            $whtAmount = $afterDiscount * ($whtRate / 100);
            $paymentAmount = $totalBeforeWht - $whtAmount;

            // Update header (include project_id)
            $db->query(
                "UPDATE purchase_order_headers SET
                    supplier_id = ?, order_date = ?, delivery_date = ?, requested_date = ?,
                    payment_term_id = ?, currency_code = ?, exchange_rate = ?, division_id = ?,
                    project_id = ?,
                    subtotal_thb = ?, vat_rate = ?, vat_amount = ?, total_before_wht = ?,
                    wht_amount = ?, payment_amount = ?, notes = ?, updated_by = ?, updated_at = NOW()
                 WHERE po_id = ?",
                [$supplierId, $orderDate, $deliveryDate, $requestedDate,
                 $paymentTermId, $currencyCode, $exchangeRate, $divisionId,
                 $projectId,
                 $subtotal, $vatRate, $vatAmount, $totalBeforeWht,
                 $whtAmount, $paymentAmount, $notes, $user['user_id'], $id]
            );

            // Delete old lines and re-insert
            $db->query("DELETE FROM purchase_order_lines WHERE po_id = ?", [$id]);

            $lineNo = 1;
            foreach ($lineItems as $line) {
                $db->query(
                    "INSERT INTO purchase_order_lines
                     (po_id, line_no, item_id, item_description, quantity, unit, unit_price, ext_price)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [$id, $lineNo++, $line['item_id'], $line['item_description'],
                     $line['quantity'], $line['unit'], $line['unit_price'], $line['ext_price']]
                );
            }

            $db->commit();
            flash('success', 'Purchase order updated successfully.');
            $this->redirect('/purchasing/orders/' . $id);
        } catch (Exception $e) {
            $db->rollback();
            error_log('PurchaseOrderController::update - ' . $e->getMessage());
            flash('error', 'Failed to update purchase order.');
            $this->redirect('/purchasing/orders/' . $id);
        }
    }

    public function delete($id)
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE purchase_order_headers SET is_deleted = TRUE, updated_by = ?, updated_at = NOW()
                 WHERE po_id = ?",
                [$user['user_id'], $id]
            );
            flash('success', 'Purchase order deleted.');
        } catch (Exception $e) {
            error_log('PurchaseOrderController::delete - ' . $e->getMessage());
            flash('error', 'Failed to delete purchase order.');
        }

        $this->redirect('/purchasing/orders');
    }

    /**
     * Submit PO for approval: DRAFT → PENDING_APPROVAL
     */
    /** Purchasing officer submits: PENDING/DRAFT → PENDING_MANAGER */
    public function submitForApproval($id)
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $this->validateCsrf();
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE purchase_order_headers
             SET status = 'PENDING_MANAGER', updated_by = ?, updated_at = NOW()
             WHERE po_id = ? AND status IN ('DRAFT','PENDING','PENDING_APPROVAL')",
            [$user['user_id'], $id]
        );
        flash('success', __('msg_submitted_for_approval'));
        $this->redirect('/purchasing/orders/' . $id);
    }

    /** Purchasing manager approves: PENDING_MANAGER → PENDING_CEO */
    public function approveManager($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/purchasing/orders/' . $id);
            return;
        }
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE purchase_order_headers
             SET status = 'PENDING_CEO',
                 manager_approved_by = ?, manager_approved_at = NOW(),
                 updated_by = ?, updated_at = NOW()
             WHERE po_id = ? AND status = 'PENDING_MANAGER'",
            [$user['user_id'], $user['user_id'], $id]
        );
        flash('success', __('po_manager_approved'));
        $this->redirect('/purchasing/orders/' . $id);
    }

    /** CEO final approval: PENDING_CEO → APPROVED */
    public function approveCeo($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!Auth::isDirectorOrAbove()) {
            flash('error', __('ceo_only'));
            $this->redirect('/purchasing/orders/' . $id);
            return;
        }
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE purchase_order_headers
             SET status = 'APPROVED',
                 ceo_approved_by = ?, ceo_approved_at = NOW(),
                 approved_by = ?, approved_at = NOW(), approval_date = CURRENT_DATE,
                 updated_by = ?, updated_at = NOW()
             WHERE po_id = ? AND status = 'PENDING_CEO'",
            [$user['user_id'], $user['user_id'], $user['user_id'], $id]
        );
        flash('success', __('po_ceo_approved'));
        $this->redirect('/purchasing/orders/' . $id);
    }

    /** Legacy alias — keep old route working: defaults to manager-step. */
    public function approve($id)
    {
        return $this->approveManager($id);
    }

    /** Reject from PENDING_MANAGER (manager+) or PENDING_CEO (CEO) → REJECTED */
    public function reject($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();
        $po = $db->fetch("SELECT status FROM purchase_order_headers WHERE po_id = ? AND is_deleted = FALSE", [$id]);
        if (!$po) {
            flash('error', __('po_not_found'));
            $this->redirect('/purchasing/orders'); return;
        }
        $allowed =
            ($po['status'] === 'PENDING_CEO' && Auth::isDirectorOrAbove()) ||
            ($po['status'] === 'PENDING_MANAGER' && Auth::isManagerOrAbove()) ||
            (in_array($po['status'], ['PENDING','PENDING_APPROVAL','DRAFT'], true) && Auth::isManagerOrAbove());
        if (!$allowed) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/purchasing/orders/' . $id); return;
        }
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') {
            flash('error', __('rejection_reason_required'));
            $this->redirect('/purchasing/orders/' . $id); return;
        }
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE purchase_order_headers
             SET status = 'REJECTED', rejection_reason = ?,
                 updated_by = ?, updated_at = NOW()
             WHERE po_id = ?",
            [$reason, $user['user_id'], $id]
        );
        flash('success', __('pr_rejected'));
        $this->redirect('/purchasing/orders/' . $id);
    }

    /** Cancel: any status → CANCELLED (creator for PENDING, admin for APPROVED) */
    public function cancel($id)
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $this->validateCsrf();
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $po = $db->fetch("SELECT status, created_by FROM purchase_order_headers WHERE po_id = ?", [$id]);
        if (!$po) {
            flash('error', __('po_not_found'));
            $this->redirect('/purchasing/orders');
            return;
        }
        // PENDING can be cancelled by creator or manager+; APPROVED only by admin
        $allowed = false;
        if ($po['status'] === 'PENDING') {
            $allowed = ($po['created_by'] == ($user['user_id'] ?? 0)) || Auth::isManagerOrAbove();
        } elseif ($po['status'] === 'APPROVED') {
            $allowed = Auth::isAdmin();
        }
        if (!$allowed) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/purchasing/orders/' . $id);
            return;
        }
        $db->query(
            "UPDATE purchase_order_headers SET status = 'CANCELLED',
                 updated_by = ?, updated_at = NOW()
             WHERE po_id = ?",
            [$user['user_id'], $id]
        );
        flash('success', __('po_cancelled'));
        $this->redirect('/purchasing/orders/' . $id);
    }

    /**
     * Duplicate a PO (header + lines) as a new DRAFT.
     */
    public function copy($id)
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');
        $this->validateCsrf();
        $db = Database::getInstance();
        $user = $this->getCurrentUser();

        try {
            $db->beginTransaction();
            $src = $db->fetch("SELECT * FROM purchase_order_headers WHERE po_id = ? AND is_deleted = FALSE", [$id]);
            if (!$src) { throw new Exception('source PO not found'); }
            $newNo = $this->generatePoNo($db);

            $row = $db->fetch(
                "INSERT INTO purchase_order_headers
                  (po_no, supplier_id, order_date, required_date,
                   currency_code, exchange_rate,
                   subtotal, vat_rate, vat_amount, grand_total,
                   payment_term_id, remarks, status, created_by, updated_by)
                 VALUES (?, ?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DRAFT', ?, ?)
                 RETURNING po_id",
                [$newNo, $src['supplier_id'], $src['required_date'],
                 $src['currency_code'], $src['exchange_rate'],
                 $src['subtotal'], $src['vat_rate'], $src['vat_amount'], $src['grand_total'],
                 $src['payment_term_id'], $src['remarks'],
                 $user['user_id'], $user['user_id']]
            );
            $newId = $row['po_id'];

            $db->query(
                "INSERT INTO purchase_order_lines
                    (po_id, line_no, item_id, description, quantity, unit, unit_price, line_total)
                 SELECT ?, line_no, item_id, description, quantity, unit, unit_price, line_total
                 FROM purchase_order_lines WHERE po_id = ?",
                [$newId, $id]
            );

            $db->commit();
            flash('success', __('msg_saved'));
            $this->redirect('/purchasing/orders/' . $newId . '/edit');
        } catch (Exception $e) {
            $db->rollback();
            error_log('PurchaseOrderController::copy - ' . $e->getMessage());
            flash('error', __('msg_save_error') . ': ' . $e->getMessage());
            $this->redirect('/purchasing/orders/' . $id);
        }
    }

    /**
     * Generate PO number: PO-{YYYY}{MM}{NNNNNN}
     */
    private function generatePoNo($db)
    {
        $yearMonth = date('Ym');
        $prefix = 'PO-' . $yearMonth;

        $row = $db->fetch(
            "SELECT po_no FROM purchase_order_headers
             WHERE po_no LIKE ?
             ORDER BY po_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['po_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
