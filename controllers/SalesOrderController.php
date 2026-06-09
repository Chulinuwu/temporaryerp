<?php
/**
 * PEGASUS ERP - Sales Order Controller
 * Sales order management with line items and pipeline view
 */

class SalesOrderController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            $status = sanitize($_GET['status'] ?? '');

            $sql = "SELECT so.*, c.customer_name,
                           qh.quotation_no,
                           d.deal_no, d.deal_name
                    FROM sales_order_headers so
                    LEFT JOIN customers c ON c.customer_id = so.customer_id
                    LEFT JOIN quotation_headers qh ON qh.quotation_id = so.quotation_id
                    LEFT JOIN deals d ON d.deal_id = so.deal_id
                    WHERE so.is_deleted = FALSE";
            $params = [];

            if (!empty($status)) {
                $sql .= " AND so.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY so.order_date DESC, so.so_no DESC";

            $orders = $db->fetchAll($sql, $params);

            $this->render('sales/orders', [
                'pageTitle' => 'Sales Orders',
                'orders' => $orders ?: [],
                'status' => $status
            ]);
        } catch (Exception $e) {
            error_log('SalesOrderController::index - ' . $e->getMessage());
            flash('error', 'Failed to load sales orders.');
            $this->render('sales/orders', [
                'pageTitle' => 'Sales Orders',
                'orders' => [],
                'status' => ''
            ]);
        }
    }

    public function create()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            // Check if creating from a quotation
            $quotationId = sanitize($_GET['quotation_id'] ?? '');
            $quotation = null;
            $quotationLines = [];

            if ($quotationId) {
                $quotation = $db->fetch(
                    "SELECT qh.*, c.customer_name
                     FROM quotation_headers qh
                     LEFT JOIN customers c ON c.customer_id = qh.customer_id
                     WHERE qh.quotation_id = ? AND qh.is_deleted = FALSE",
                    [$quotationId]
                );

                if ($quotation) {
                    $quotationLines = $db->fetchAll(
                        "SELECT ql.*, i.item_code, i.item_name
                         FROM quotation_lines ql
                         LEFT JOIN items i ON i.item_id = ql.item_id
                         WHERE ql.quotation_id = ? ORDER BY ql.line_no",
                        [$quotationId]
                    );
                }
            }

            $customers = $db->fetchAll(
                "SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name"
            );
            $paymentTerms = $db->fetchAll(
                "SELECT * FROM payment_terms WHERE is_deleted = FALSE ORDER BY term_name_en"
            );

            $this->render('sales/quotation_form', [
                'pageTitle' => 'Create Sales Order',
                'order' => null,
                'lines' => $quotationLines ?: [],
                'quotation' => $quotation,
                'customers' => $customers ?: [],
                'paymentTerms' => $paymentTerms ?: []
            ]);
        } catch (Exception $e) {
            error_log('SalesOrderController::create - ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/sales/orders');
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
            $orderDate = sanitize($_POST['order_date'] ?? '');
            $requestedDate = sanitize($_POST['requested_date'] ?? '') ?: null;
            $paymentTermId = sanitize($_POST['payment_term_id'] ?? '') ?: null;
            $quotationId = sanitize($_POST['quotation_id'] ?? '') ?: null;
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $exchangeRate = floatval($_POST['exchange_rate'] ?? 1);
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            $itemIds = $_POST['item_id'] ?? [];
            $descriptions = $_POST['item_description'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $units = $_POST['unit'] ?? [];
            $unitPrices = $_POST['unit_price'] ?? [];
            $discountRates = $_POST['discount_rate'] ?? [];

            if (empty($customerId) || empty($orderDate)) {
                flash('error', 'Customer and date are required.');
                $this->redirect('/sales/orders/create');
                return;
            }

            $db->beginTransaction();

            $soNo = $this->generateSoNo($db);

            // Calculate totals
            $subtotal = 0;
            $lineData = [];
            for ($i = 0; $i < count($itemIds); $i++) {
                $qty = floatval($quantities[$i] ?? 0);
                $price = floatval($unitPrices[$i] ?? 0);
                $discRate = floatval($discountRates[$i] ?? 0);
                $lineAmount = $qty * $price;
                $discountAmount = $lineAmount * ($discRate / 100);
                $extPrice = $lineAmount - $discountAmount;
                $subtotal += $extPrice;

                $lineData[] = [
                    'item_id' => $itemIds[$i] ?: null,
                    'item_description' => sanitize($descriptions[$i] ?? ''),
                    'quantity' => $qty,
                    'unit' => sanitize($units[$i] ?? ''),
                    'unit_price' => $price,
                    'discount_rate' => $discRate,
                    'ext_price' => $extPrice
                ];
            }

            $vatRate = floatval($_POST['vat_rate'] ?? 7);
            $vatAmount = $subtotal * ($vatRate / 100);
            $grandTotalThb = $subtotal + $vatAmount;

            // Insert header
            $row = $db->fetch(
                "INSERT INTO sales_order_headers
                 (so_no, order_date, requested_date, customer_id, division_id, quotation_id,
                  payment_term_id, currency_code, exchange_rate, subtotal_thb, vat_rate, vat_amount,
                  grand_total_thb, notes, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'CONFIRMED',?)
                 RETURNING so_id",
                [$soNo, $orderDate, $requestedDate, $customerId, $divisionId, $quotationId,
                 $paymentTermId, $currencyCode, $exchangeRate, $subtotal, $vatRate, $vatAmount,
                 $grandTotalThb, $notes, $user['user_id']]
            );
            $soId = $row['so_id'];

            // Insert lines
            $lineNo = 1;
            foreach ($lineData as $line) {
                $db->query(
                    "INSERT INTO sales_order_lines
                     (so_id, line_no, item_id, item_description, quantity, unit,
                      unit_price, discount_rate, ext_price)
                     VALUES (?,?,?,?,?,?,?,?,?)",
                    [$soId, $lineNo++, $line['item_id'], $line['item_description'],
                     $line['quantity'], $line['unit'], $line['unit_price'],
                     $line['discount_rate'], $line['ext_price']]
                );
            }

            // Update quotation status if created from one
            if ($quotationId) {
                $db->query(
                    "UPDATE quotation_headers SET status = 'WON', won_so_id = ?, updated_by = ?, updated_at = NOW()
                     WHERE quotation_id = ?",
                    [$soId, $user['user_id'], $quotationId]
                );
            }

            $db->commit();
            flash('success', "Sales Order {$soNo} created successfully.");
            $this->redirect('/sales/orders/' . $soId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('SalesOrderController::store - ' . $e->getMessage());
            flash('error', 'Failed to create sales order.');
            $this->redirect('/sales/orders/create');
        }
    }

    public function show($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            $order = $db->fetch(
                "SELECT so.*, c.customer_name, c.address as customer_address,
                        c.contact_person, c.tax_id as customer_tax_id,
                        pt.term_name_en as payment_term_name,
                        qh.quotation_no,
                        d.deal_id as ref_deal_id, d.deal_no, d.deal_name
                 FROM sales_order_headers so
                 LEFT JOIN customers c ON c.customer_id = so.customer_id
                 LEFT JOIN payment_terms pt ON pt.term_id = so.payment_term_id
                 LEFT JOIN quotation_headers qh ON qh.quotation_id = so.quotation_id
                 LEFT JOIN deals d ON d.deal_id = so.deal_id
                 WHERE so.so_id = ? AND so.is_deleted = FALSE",
                [$id]
            );

            if (!$order) {
                flash('error', 'Sales order not found.');
                $this->redirect('/sales/orders');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT sl.*, i.item_code, i.item_name
                 FROM sales_order_lines sl
                 LEFT JOIN items i ON i.item_id = sl.item_id
                 WHERE sl.so_id = ? AND (sl.is_deleted = FALSE OR sl.is_deleted IS NULL)
                 ORDER BY sl.line_no",
                [$id]
            );

            // Get all linked quotations if created from a deal
            $linkedQuotations = [];
            if (!empty($order['deal_id'])) {
                $linkedQuotations = $db->fetchAll(
                    "SELECT quotation_id, quotation_no, project_name, subtotal_thb, grand_total_thb, status
                     FROM quotation_headers
                     WHERE deal_id = ? AND is_deleted = FALSE
                     ORDER BY quotation_no",
                    [$order['deal_id']]
                ) ?: [];
            }

            $this->render('sales/order_detail', [
                'pageTitle' => 'SO ' . $order['so_no'],
                'order' => $order,
                'lines' => $lines ?: [],
                'linkedQuotations' => $linkedQuotations,
            ]);
        } catch (Exception $e) {
            error_log('SalesOrderController::show - ' . $e->getMessage());
            flash('error', 'Failed to load sales order.');
            $this->redirect('/sales/orders');
        }
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $status = sanitize($_POST['status'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');
            $user = $this->getCurrentUser();

            if (empty($status)) {
                flash('error', 'Status is required.');
                $this->redirect('/sales/orders/' . $id);
                return;
            }

            $db->query(
                "UPDATE sales_order_headers SET status = ?, notes = ?, updated_by = ?, updated_at = NOW()
                 WHERE so_id = ?",
                [$status, $notes, $user['user_id'], $id]
            );

            flash('success', 'Sales order updated.');
            $this->redirect('/sales/orders/' . $id);
        } catch (Exception $e) {
            error_log('SalesOrderController::update - ' . $e->getMessage());
            flash('error', 'Failed to update sales order.');
            $this->redirect('/sales/orders/' . $id);
        }
    }

    public function pipeline()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        try {
            // Pipeline statistics
            $stats = $db->fetchAll(
                "SELECT status, COUNT(*) as count, COALESCE(SUM(grand_total_thb), 0) as total
                 FROM sales_order_headers
                 WHERE is_deleted = FALSE
                 GROUP BY status ORDER BY count DESC"
            );

            // Recent orders
            $recentOrders = $db->fetchAll(
                "SELECT so.*, c.customer_name
                 FROM sales_order_headers so
                 LEFT JOIN customers c ON c.customer_id = so.customer_id
                 WHERE so.is_deleted = FALSE
                 ORDER BY so.order_date DESC LIMIT 20"
            );

            // Monthly totals
            $monthlyTotals = $db->fetchAll(
                "SELECT TO_CHAR(order_date, 'YYYY-MM') as month,
                        COUNT(*) as count,
                        COALESCE(SUM(grand_total_thb), 0) as total
                 FROM sales_order_headers
                 WHERE is_deleted = FALSE AND order_date >= (CURRENT_DATE - INTERVAL '6 months')
                 GROUP BY TO_CHAR(order_date, 'YYYY-MM')
                 ORDER BY month"
            );

            $this->render('sales/pipeline', [
                'pageTitle' => 'Sales Pipeline',
                'stats' => $stats ?: [],
                'recentOrders' => $recentOrders ?: [],
                'monthlyTotals' => $monthlyTotals ?: []
            ]);
        } catch (Exception $e) {
            error_log('SalesOrderController::pipeline - ' . $e->getMessage());
            flash('error', 'Failed to load pipeline data.');
            $this->render('sales/pipeline', [
                'pageTitle' => 'Sales Pipeline',
                'stats' => [],
                'recentOrders' => [],
                'monthlyTotals' => []
            ]);
        }
    }

    /**
     * Generate SO number: SO-{YYYY}{MM}{NNNNNN}
     */
    private function generateSoNo($db)
    {
        $yearMonth = date('Ym');
        $prefix = 'SO-' . $yearMonth;

        $row = $db->fetch(
            "SELECT so_no FROM sales_order_headers
             WHERE so_no LIKE ?
             ORDER BY so_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['so_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * #11 Cancel a sales order — admin only
     */
    public function cancel($id)
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/sales/orders/' . $id);
            return;
        }
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $so = $db->fetch("SELECT so_id, so_no, deal_id FROM sales_order_headers WHERE so_id = ?", [$id]);
            if (!$so) {
                flash('error', __('order_not_found'));
                $this->redirect('/sales/orders');
                return;
            }

            $db->query(
                "UPDATE sales_order_headers SET status = 'CANCELLED', updated_at = NOW() WHERE so_id = ?",
                [$id]
            );

            // Also cancel related project (if any)
            $db->query(
                "UPDATE projects SET status = 'CANCELLED', updated_at = NOW() WHERE so_id = ?",
                [$id]
            );

            $db->commit();
            flash('success', __('order_cancelled') . ': ' . $so['so_no']);
        } catch (Exception $e) {
            $db->rollback();
            error_log('SalesOrderController::cancel - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/sales/orders/' . $id);
    }
}
