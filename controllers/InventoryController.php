<?php
/**
 * PEGASUS ERP - Inventory Controller
 * Stock management, goods receipt, and goods issue
 */

class InventoryController extends Controller
{
    public function stock()
    {
        $this->requireAuth();
        $this->requireAccess('inventory');
        $db = Database::getInstance();

        try {
            $search = sanitize($_GET['search'] ?? '');
            $warehouseId = sanitize($_GET['warehouse_id'] ?? '');

            $sql = "SELECT sb.*, i.item_code, i.item_name, i.unit, w.warehouse_name
                    FROM stock_balances sb
                    JOIN items i ON i.item_id = sb.item_id
                    JOIN warehouses w ON w.warehouse_id = sb.warehouse_id
                    WHERE i.is_deleted = FALSE";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (i.item_code ILIKE ? OR i.item_name ILIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            if (!empty($warehouseId)) {
                $sql .= " AND sb.warehouse_id = ?";
                $params[] = $warehouseId;
            }

            $sql .= " ORDER BY i.item_code, w.warehouse_name";

            $balances = $db->fetchAll($sql, $params);

            $warehouses = $db->fetchAll(
                "SELECT * FROM warehouses WHERE is_deleted = FALSE ORDER BY warehouse_name"
            );

            $this->render('inventory/stock', [
                'pageTitle' => 'Stock Balances',
                'balances' => $balances ?: [],
                'warehouses' => $warehouses ?: [],
                'search' => $search,
                'warehouseId' => $warehouseId
            ]);
        } catch (Exception $e) {
            error_log('InventoryController::stock - ' . $e->getMessage());
            flash('error', 'Failed to load stock data.');
            $this->render('inventory/stock', [
                'pageTitle' => 'Stock Balances',
                'balances' => [],
                'warehouses' => [],
                'search' => '',
                'warehouseId' => ''
            ]);
        }
    }

    public function warehouses()
    {
        $this->requireAuth();
        $this->requireAccess('inventory');
        $db = Database::getInstance();

        try {
            $warehouses = $db->fetchAll(
                "SELECT * FROM warehouses WHERE is_deleted = FALSE ORDER BY warehouse_name"
            );

            $this->render('inventory/warehouses', [
                'pageTitle' => 'Warehouses',
                'warehouses' => $warehouses ?: []
            ]);
        } catch (Exception $e) {
            error_log('InventoryController::warehouses - ' . $e->getMessage());
            flash('error', 'Failed to load warehouses.');
            $this->render('inventory/warehouses', [
                'pageTitle' => 'Warehouses',
                'warehouses' => []
            ]);
        }
    }

    /**
     * Process goods receipt (PO receipt) - update stock balances via inventory_transactions
     */
    public function receive()
    {
        $this->requireAuth();
        $this->requireAccess('inventory');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $poId = sanitize($_POST['po_id'] ?? '');
            $warehouseId = sanitize($_POST['warehouse_id'] ?? '');
            $receiveDate = sanitize($_POST['receive_date'] ?? date('Y-m-d'));
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '');
            $user = $this->getCurrentUser();

            // Line arrays from POST
            $lineIds = $_POST['po_line_id'] ?? [];
            $receiveQtys = $_POST['receive_qty'] ?? [];

            if (empty($poId) || empty($warehouseId)) {
                flash('error', 'PO and warehouse are required.');
                $this->redirect('/inventory/stock');
                return;
            }

            // Get division from PO if not provided
            if (empty($divisionId)) {
                $poHeader = $db->fetch(
                    "SELECT division_id FROM purchase_order_headers WHERE po_id = ?",
                    [$poId]
                );
                $divisionId = $poHeader['division_id'] ?? 1;
            }

            $db->beginTransaction();

            for ($i = 0; $i < count($lineIds); $i++) {
                $poLineId = sanitize($lineIds[$i]);
                $qty = floatval($receiveQtys[$i] ?? 0);

                if ($qty <= 0) continue;

                // Get item_id and unit_price from PO line
                $poLine = $db->fetch(
                    "SELECT item_id, unit_price FROM purchase_order_lines WHERE po_line_id = ?",
                    [$poLineId]
                );

                if (!$poLine) continue;

                $itemId = $poLine['item_id'];
                $unitCost = floatval($poLine['unit_price'] ?? 0);

                // Generate transaction number
                $txnNo = $this->generateTxnNo($db, 'RCV');

                // Insert inventory transaction
                $db->query(
                    "INSERT INTO inventory_transactions
                     (txn_no, division_id, warehouse_id, txn_type, txn_date,
                      reference_type, reference_id, item_id, quantity, unit,
                      unit_cost, total_cost, notes, created_by)
                     VALUES (?, ?, ?, 'RECEIPT', ?, 'PO', ?, ?, ?, 'EA', ?, ?, ?, ?)",
                    [$txnNo, $divisionId, $warehouseId, $receiveDate,
                     $poId, $itemId, $qty, $unitCost, round($unitCost * $qty, 2),
                     $notes, $user['user_id'] ?? null]
                );

                // Update stock balance (upsert)
                $existing = $db->fetch(
                    "SELECT balance_id FROM stock_balances
                     WHERE item_id = ? AND warehouse_id = ?",
                    [$itemId, $warehouseId]
                );

                if ($existing) {
                    $db->query(
                        "UPDATE stock_balances SET
                         quantity_on_hand = quantity_on_hand + ?,
                         quantity_available = quantity_available + ?,
                         last_updated = NOW()
                         WHERE item_id = ? AND warehouse_id = ?",
                        [$qty, $qty, $itemId, $warehouseId]
                    );
                } else {
                    $db->query(
                        "INSERT INTO stock_balances (item_id, warehouse_id, quantity_on_hand, quantity_available, avg_unit_cost)
                         VALUES (?, ?, ?, ?, ?)",
                        [$itemId, $warehouseId, $qty, $qty, $unitCost]
                    );
                }

                // Update PO line received_qty
                $db->query(
                    "UPDATE purchase_order_lines SET received_qty = COALESCE(received_qty, 0) + ?
                     WHERE po_line_id = ?",
                    [$qty, $poLineId]
                );
            }

            // Update PO status to RECEIVED if all lines received
            $db->query(
                "UPDATE purchase_order_headers SET status = 'RECEIVED', updated_by = ?, updated_at = NOW()
                 WHERE po_id = ?",
                [$user['user_id'], $poId]
            );

            $db->commit();
            flash('success', 'Goods received successfully.');
            $this->redirect('/inventory/stock');
        } catch (Exception $e) {
            $db->rollback();
            error_log('InventoryController::receive - ' . $e->getMessage());
            flash('error', 'Failed to process goods receipt.');
            $this->redirect('/inventory/stock');
        }
    }

    /**
     * Process goods issue (SO shipment) - update stock balances via inventory_transactions
     */
    public function issue()
    {
        $this->requireAuth();
        $this->requireAccess('inventory');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $soId = sanitize($_POST['so_id'] ?? '');
            $warehouseId = sanitize($_POST['warehouse_id'] ?? '');
            $issueDate = sanitize($_POST['issue_date'] ?? date('Y-m-d'));
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '');
            $user = $this->getCurrentUser();

            $lineIds = $_POST['so_line_id'] ?? [];
            $issueQtys = $_POST['issue_qty'] ?? [];

            if (empty($soId) || empty($warehouseId)) {
                flash('error', 'SO and warehouse are required.');
                $this->redirect('/inventory/stock');
                return;
            }

            // Get division from SO if not provided
            if (empty($divisionId)) {
                $soHeader = $db->fetch(
                    "SELECT division_id FROM sales_order_headers WHERE so_id = ?",
                    [$soId]
                );
                $divisionId = $soHeader['division_id'] ?? 1;
            }

            $db->beginTransaction();

            for ($i = 0; $i < count($lineIds); $i++) {
                $soLineId = sanitize($lineIds[$i]);
                $qty = floatval($issueQtys[$i] ?? 0);

                if ($qty <= 0) continue;

                // Get item_id from SO line
                $soLine = $db->fetch(
                    "SELECT item_id FROM sales_order_lines WHERE so_line_id = ?",
                    [$soLineId]
                );

                if (!$soLine) continue;

                $itemId = $soLine['item_id'];

                // Check stock availability
                $stock = $db->fetch(
                    "SELECT quantity_on_hand, quantity_available FROM stock_balances
                     WHERE item_id = ? AND warehouse_id = ?",
                    [$itemId, $warehouseId]
                );

                $currentQty = floatval($stock['quantity_available'] ?? 0);
                if ($currentQty < $qty) {
                    $db->rollback();
                    flash('error', 'Insufficient stock for item. Available: ' . $currentQty);
                    $this->redirect('/inventory/stock');
                    return;
                }

                // Generate transaction number
                $txnNo = $this->generateTxnNo($db, 'ISS');

                // Insert inventory transaction
                $db->query(
                    "INSERT INTO inventory_transactions
                     (txn_no, division_id, warehouse_id, txn_type, txn_date,
                      reference_type, reference_id, item_id, quantity, unit,
                      notes, created_by)
                     VALUES (?, ?, ?, 'ISSUE', ?, 'SO', ?, ?, ?, 'EA', ?, ?)",
                    [$txnNo, $divisionId, $warehouseId, $issueDate,
                     $soId, $itemId, $qty, $notes, $user['user_id'] ?? null]
                );

                // Update stock balance
                $db->query(
                    "UPDATE stock_balances SET
                     quantity_on_hand = quantity_on_hand - ?,
                     quantity_available = quantity_available - ?,
                     last_updated = NOW()
                     WHERE item_id = ? AND warehouse_id = ?",
                    [$qty, $qty, $itemId, $warehouseId]
                );

                // Update SO line delivered_qty
                $db->query(
                    "UPDATE sales_order_lines SET delivered_qty = COALESCE(delivered_qty, 0) + ?
                     WHERE so_line_id = ?",
                    [$qty, $soLineId]
                );
            }

            // Update SO status to SHIPPED
            $db->query(
                "UPDATE sales_order_headers SET status = 'SHIPPED', updated_by = ?, updated_at = NOW()
                 WHERE so_id = ?",
                [$user['user_id'], $soId]
            );

            $db->commit();
            flash('success', 'Goods issued successfully.');
            $this->redirect('/inventory/stock');
        } catch (Exception $e) {
            $db->rollback();
            error_log('InventoryController::issue - ' . $e->getMessage());
            flash('error', 'Failed to process goods issue.');
            $this->redirect('/inventory/stock');
        }
    }

    /**
     * Generate inventory transaction number
     */
    private function generateTxnNo($db, $prefix = 'TXN')
    {
        $datePart = date('ymd');
        $fullPrefix = $prefix . '-' . $datePart . '-';

        $row = $db->fetch(
            "SELECT txn_no FROM inventory_transactions
             WHERE txn_no LIKE ?
             ORDER BY txn_no DESC LIMIT 1",
            [$fullPrefix . '%']
        );

        if ($row) {
            $parts = explode('-', $row['txn_no']);
            $seq = intval(end($parts)) + 1;
        } else {
            $seq = 1;
        }

        return $fullPrefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
