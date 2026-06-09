<?php
/**
 * PEGASUS ERP - Production Controller
 * Manufacturing orders, BOMs, and cost accounting
 */

class ProductionController extends Controller
{
    /**
     * List manufacturing orders with status filter
     */
    public function orders()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $db = Database::getInstance();

        try {
            $statusFilter = sanitize($this->input('status', ''));
            $page = max(1, (int) $this->input('page', 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $where = ['mo.is_deleted = FALSE'];
            $params = [];

            if (!empty($statusFilter)) {
                $where[] = "mo.status = ?";
                $params[] = $statusFilter;
            }

            $whereClause = implode(' AND ', $where);

            $countRow = $db->fetch(
                "SELECT COUNT(*) as total FROM mo_headers mo WHERE {$whereClause}",
                $params
            );
            $total = (int) ($countRow['total'] ?? 0);

            $queryParams = array_merge($params, [$perPage, $offset]);
            $orders = $db->fetchAll(
                "SELECT mo.*, i.item_name, i.item_code,
                        bom.bom_code,
                        COALESCE(mo.completed_qty, 0) as completed_qty
                 FROM mo_headers mo
                 JOIN items i ON i.item_id = mo.item_id
                 LEFT JOIN bom_headers bom ON bom.bom_id = mo.bom_id
                 WHERE {$whereClause}
                 ORDER BY mo.created_at DESC
                 LIMIT ? OFFSET ?",
                $queryParams
            );

            $this->render('production/orders', [
                'pageTitle' => 'Manufacturing Orders',
                'orders' => $orders ?: [],
                'statusFilter' => $statusFilter,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]);
        } catch (Exception $e) {
            error_log('ProductionController::orders error: ' . $e->getMessage());
            flash('error', 'Failed to load manufacturing orders.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Create MO form with item/BOM selection
     */
    public function createOrder()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION']);
        $db = Database::getInstance();

        try {
            $items = $db->fetchAll(
                "SELECT i.item_id, i.item_code, i.item_name
                 FROM items i
                 WHERE i.item_type = 'FINISHED' AND i.is_deleted = FALSE
                 ORDER BY i.item_code"
            );

            $boms = $db->fetchAll(
                "SELECT bh.bom_id, bh.bom_code, bh.bom_name, bh.item_id,
                        i.item_code, i.item_name
                 FROM bom_headers bh
                 JOIN items i ON i.item_id = bh.item_id
                 WHERE bh.is_current = TRUE AND bh.is_deleted = FALSE
                 ORDER BY bh.bom_code"
            );

            $this->render('production/orders', [
                'pageTitle' => 'Create Manufacturing Order',
                'items' => $items ?: [],
                'boms' => $boms ?: []
            ]);
        } catch (Exception $e) {
            error_log('ProductionController::createOrder error: ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/production/orders');
        }
    }

    /**
     * Save MO header, auto-generate MO lines from BOM
     */
    public function storeOrder()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $bomId = (int) ($_POST['bom_id'] ?? 0);
            $plannedQty = (float) ($_POST['planned_qty'] ?? 0);
            $plannedStart = sanitize($_POST['planned_start'] ?? '');
            $plannedEnd = sanitize($_POST['planned_end'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;

            if ($itemId <= 0 || $bomId <= 0 || $plannedQty <= 0 || empty($plannedStart)) {
                flash('error', 'Item, BOM, planned quantity, and planned start date are required.');
                $this->redirect('/production/orders/create');
                return;
            }

            // Verify BOM exists and matches item
            $bom = $db->fetch(
                "SELECT * FROM bom_headers WHERE bom_id = ? AND item_id = ? AND is_current = TRUE AND is_deleted = FALSE",
                [$bomId, $itemId]
            );

            if (!$bom) {
                flash('error', 'Invalid BOM selection for this item.');
                $this->redirect('/production/orders/create');
                return;
            }

            // Get division from BOM if not provided
            if (empty($divisionId)) {
                $divisionId = $bom['division_id'] ?? 1;
            }

            $db->beginTransaction();

            // Generate MO number
            $moNo = $this->generateMONo($db);
            $user = $this->getCurrentUser();

            // Create MO header
            $row = $db->fetch(
                "INSERT INTO mo_headers (
                    mo_no, division_id, item_id, bom_id, planned_qty, completed_qty,
                    unit, planned_start, planned_end, status, notes,
                    created_by, created_at, updated_at
                 ) VALUES (?, ?, ?, ?, ?, 0, 'EA', ?, ?, 'PLANNED', ?, ?, NOW(), NOW())
                 RETURNING mo_id",
                [$moNo, $divisionId, $itemId, $bomId, $plannedQty,
                 $plannedStart, $plannedEnd ?: null, $notes, $user['user_id'] ?? null]
            );

            $moId = $row['mo_id'];

            // Auto-generate MO lines from BOM components
            $bomLines = $db->fetchAll(
                "SELECT bl.*, i.item_code, i.item_name, i.unit
                 FROM bom_lines bl
                 JOIN items i ON i.item_id = bl.component_item_id
                 WHERE bl.bom_id = ? AND bl.is_deleted = FALSE
                 ORDER BY bl.line_no",
                [$bomId]
            );

            foreach ($bomLines ?: [] as $bomLine) {
                $requiredQty = round((float) $bomLine['quantity_per'] * $plannedQty, 4);
                $scrapQty = round($requiredQty * (float) ($bomLine['scrap_rate'] ?? 0), 4);
                $totalRequired = round($requiredQty + $scrapQty, 4);

                $db->query(
                    "INSERT INTO mo_lines (
                        mo_id, component_item_id, required_qty,
                        issued_qty, unit
                     ) VALUES (?, ?, ?, 0, ?)",
                    [
                        $moId, $bomLine['component_item_id'],
                        $totalRequired, $bomLine['unit'] ?? 'EA'
                    ]
                );
            }

            $db->commit();

            flash('success', "Manufacturing order {$moNo} created with " . count($bomLines ?: []) . " component lines.");
            $this->redirect("/production/orders/{$moId}");
        } catch (Exception $e) {
            $db->rollback();
            error_log('ProductionController::storeOrder error: ' . $e->getMessage());
            flash('error', 'Failed to create manufacturing order.');
            $this->redirect('/production/orders/create');
        }
    }

    /**
     * Show MO detail with component lines
     */
    public function showOrder($id)
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $order = $db->fetch(
                "SELECT mo.*, i.item_code, i.item_name, i.unit,
                        bom.bom_code, bom.bom_name
                 FROM mo_headers mo
                 JOIN items i ON i.item_id = mo.item_id
                 LEFT JOIN bom_headers bom ON bom.bom_id = mo.bom_id
                 WHERE mo.mo_id = ? AND mo.is_deleted = FALSE",
                [$id]
            );

            if (!$order) {
                flash('error', 'Manufacturing order not found.');
                $this->redirect('/production/orders');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT mol.*, i.item_code, i.item_name
                 FROM mo_lines mol
                 JOIN items i ON i.item_id = mol.component_item_id
                 WHERE mol.mo_id = ? AND mol.is_deleted = FALSE
                 ORDER BY mol.mo_line_id",
                [$id]
            );

            $this->render('production/orders', [
                'pageTitle' => 'MO: ' . ($order['mo_no'] ?? ''),
                'order' => $order,
                'lines' => $lines ?: []
            ]);
        } catch (Exception $e) {
            error_log('ProductionController::showOrder error: ' . $e->getMessage());
            flash('error', 'Failed to load manufacturing order.');
            $this->redirect('/production/orders');
        }
    }

    /**
     * Update MO status, record completed qty
     */
    public function updateOrder($id)
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = (int) $id;
            $status = sanitize($_POST['status'] ?? '');
            $completedQty = (float) ($_POST['completed_qty'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');

            $validStatuses = ['PLANNED', 'RELEASED', 'IN_PROGRESS', 'COMPLETED', 'CLOSED', 'CANCELLED'];
            if (!in_array($status, $validStatuses)) {
                flash('error', 'Invalid status.');
                $this->redirect("/production/orders/{$id}");
                return;
            }

            $order = $db->fetch(
                "SELECT * FROM mo_headers WHERE mo_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$order) {
                flash('error', 'Manufacturing order not found.');
                $this->redirect('/production/orders');
                return;
            }

            $updateFields = [
                'status' => $status,
                'completed_qty' => $completedQty,
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($status === 'IN_PROGRESS' && empty($order['actual_start'])) {
                $updateFields['actual_start'] = date('Y-m-d');
            }

            if ($status === 'COMPLETED') {
                $updateFields['actual_end'] = date('Y-m-d');
            }

            $setParts = [];
            $params = [];
            foreach ($updateFields as $col => $val) {
                $setParts[] = "{$col} = ?";
                $params[] = $val;
            }
            $params[] = $id;

            $db->query(
                "UPDATE mo_headers SET " . implode(', ', $setParts) . " WHERE mo_id = ?",
                $params
            );

            flash('success', 'Manufacturing order updated.');
            $this->redirect("/production/orders/{$id}");
        } catch (Exception $e) {
            error_log('ProductionController::updateOrder error: ' . $e->getMessage());
            flash('error', 'Failed to update manufacturing order.');
            $this->redirect("/production/orders/{$id}");
        }
    }

    /**
     * List BOMs
     */
    public function bomList()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $db = Database::getInstance();

        try {
            $page = max(1, (int) $this->input('page', 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $countRow = $db->fetch(
                "SELECT COUNT(*) as total FROM bom_headers WHERE is_deleted = FALSE"
            );
            $total = (int) ($countRow['total'] ?? 0);

            $boms = $db->fetchAll(
                "SELECT bh.*, i.item_code, i.item_name,
                        (SELECT COUNT(*) FROM bom_lines bl WHERE bl.bom_id = bh.bom_id AND bl.is_deleted = FALSE) as component_count
                 FROM bom_headers bh
                 JOIN items i ON i.item_id = bh.item_id
                 WHERE bh.is_deleted = FALSE
                 ORDER BY bh.bom_code
                 LIMIT ? OFFSET ?",
                [$perPage, $offset]
            );

            $this->render('production/bom', [
                'pageTitle' => 'Bills of Materials',
                'boms' => $boms ?: [],
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]);
        } catch (Exception $e) {
            error_log('ProductionController::bomList error: ' . $e->getMessage());
            flash('error', 'Failed to load BOM list.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Create BOM form
     */
    public function createBOM()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION']);
        $db = Database::getInstance();

        try {
            $finishedGoods = $db->fetchAll(
                "SELECT item_id, item_code, item_name
                 FROM items
                 WHERE item_type = 'FINISHED' AND is_deleted = FALSE
                 ORDER BY item_code"
            );

            $components = $db->fetchAll(
                "SELECT item_id, item_code, item_name, unit
                 FROM items
                 WHERE item_type IN ('RAW', 'WIP', 'SPARE') AND is_deleted = FALSE
                 ORDER BY item_code"
            );

            $this->render('production/bom', [
                'pageTitle' => 'Create BOM',
                'finishedGoods' => $finishedGoods ?: [],
                'components' => $components ?: []
            ]);
        } catch (Exception $e) {
            error_log('ProductionController::createBOM error: ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/production/bom');
        }
    }

    /**
     * Save BOM header + component lines
     */
    public function storeBOM()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $bomCode = sanitize($_POST['bom_code'] ?? '');
            $bomName = sanitize($_POST['bom_name'] ?? '');
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $yieldQty = (float) ($_POST['yield_qty'] ?? 1);
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $lines = $_POST['lines'] ?? [];
            $user = $this->getCurrentUser();

            if (empty($bomCode) || $itemId <= 0 || empty($lines)) {
                flash('error', 'BOM code, finished good, and at least one component are required.');
                $this->redirect('/production/bom/create');
                return;
            }

            // Get division from item if not provided
            if (empty($divisionId)) {
                $itemRow = $db->fetch("SELECT division_id FROM items WHERE item_id = ?", [$itemId]);
                $divisionId = $itemRow['division_id'] ?? 1;
            }

            // Check duplicate BOM code
            $existing = $db->fetch(
                "SELECT bom_id FROM bom_headers WHERE bom_code = ? AND is_deleted = FALSE",
                [$bomCode]
            );
            if ($existing) {
                flash('error', 'BOM code already exists.');
                $this->redirect('/production/bom/create');
                return;
            }

            $db->beginTransaction();

            $row = $db->fetch(
                "INSERT INTO bom_headers (bom_code, bom_name, item_id, division_id, yield_qty, is_current, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, TRUE, ?, NOW(), NOW())
                 RETURNING bom_id",
                [$bomCode, $bomName, $itemId, $divisionId, $yieldQty, $user['user_id'] ?? null]
            );

            $bomId = $row['bom_id'];
            $lineNo = 1;

            foreach ($lines as $line) {
                $componentItemId = (int) ($line['component_item_id'] ?? 0);
                $quantityPer = (float) ($line['quantity_per'] ?? 0);
                $scrapRate = (float) ($line['scrap_rate'] ?? 0);
                $unit = sanitize($line['unit'] ?? 'EA');

                if ($componentItemId <= 0 || $quantityPer <= 0) {
                    continue;
                }

                $db->query(
                    "INSERT INTO bom_lines (bom_id, line_no, component_item_id, quantity_per, scrap_rate, unit)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$bomId, $lineNo++, $componentItemId, $quantityPer, $scrapRate, $unit]
                );
            }

            $db->commit();

            flash('success', 'BOM created successfully.');
            $this->redirect("/production/bom/{$bomId}");
        } catch (Exception $e) {
            $db->rollback();
            error_log('ProductionController::storeBOM error: ' . $e->getMessage());
            flash('error', 'Failed to create BOM.');
            $this->redirect('/production/bom/create');
        }
    }

    /**
     * Show BOM detail
     */
    public function showBOM($id)
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $bom = $db->fetch(
                "SELECT bh.*, i.item_code, i.item_name, i.unit
                 FROM bom_headers bh
                 JOIN items i ON i.item_id = bh.item_id
                 WHERE bh.bom_id = ? AND bh.is_deleted = FALSE",
                [$id]
            );

            if (!$bom) {
                flash('error', 'BOM not found.');
                $this->redirect('/production/bom');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT bl.*, i.item_code, i.item_name, i.unit as item_unit,
                        COALESCE(sc.total_std_cost, i.unit_cost_std) as unit_cost_std
                 FROM bom_lines bl
                 JOIN items i ON i.item_id = bl.component_item_id
                 LEFT JOIN standard_costs sc ON sc.item_id = bl.component_item_id AND sc.is_current = TRUE
                 WHERE bl.bom_id = ? AND bl.is_deleted = FALSE
                 ORDER BY bl.line_no",
                [$id]
            );

            // Calculate total standard cost
            $totalStandardCost = 0;
            foreach ($lines ?: [] as $line) {
                $totalStandardCost += (float) $line['quantity_per'] * (float) ($line['unit_cost_std'] ?? 0);
            }

            $this->render('production/bom', [
                'pageTitle' => 'BOM: ' . ($bom['bom_code'] ?? ''),
                'bom' => $bom,
                'lines' => $lines ?: [],
                'totalStandardCost' => $totalStandardCost
            ]);
        } catch (Exception $e) {
            error_log('ProductionController::showBOM error: ' . $e->getMessage());
            flash('error', 'Failed to load BOM detail.');
            $this->redirect('/production/bom');
        }
    }

    /**
     * Cost analysis view with standard vs actual comparison
     */
    public function costAccounting()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION', 'FINANCE']);
        $db = Database::getInstance();

        try {
            $dateFrom = sanitize($this->input('date_from', date('Y-m-01')));
            $dateTo = sanitize($this->input('date_to', date('Y-m-d')));

            // Get completed MOs
            $costAnalysis = $db->fetchAll(
                "SELECT mo.mo_id, mo.mo_no, i.item_code, i.item_name,
                        mo.planned_qty, COALESCE(mo.completed_qty, 0) as completed_qty,
                        mo.actual_end
                 FROM mo_headers mo
                 JOIN items i ON i.item_id = mo.item_id
                 WHERE mo.status = 'COMPLETED' AND mo.is_deleted = FALSE
                   AND mo.actual_end BETWEEN ? AND ?
                 ORDER BY mo.actual_end DESC",
                [$dateFrom, $dateTo]
            );

            // Summary totals
            $summary = $db->fetch(
                "SELECT
                    COUNT(*) as total_orders,
                    COALESCE(SUM(completed_qty), 0) as total_completed
                 FROM mo_headers
                 WHERE status = 'COMPLETED' AND is_deleted = FALSE
                   AND actual_end BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            );

            $this->render('production/orders', [
                'pageTitle' => 'Cost Accounting Analysis',
                'costAnalysis' => $costAnalysis ?: [],
                'summary' => $summary ?: [],
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);
        } catch (Exception $e) {
            error_log('ProductionController::costAccounting error: ' . $e->getMessage());
            flash('error', 'Failed to load cost analysis.');
            $this->redirect('/production/orders');
        }
    }

    /**
     * Generate MO number (MO-YYMM-XXXX)
     */
    private function generateMONo($db)
    {
        $prefix = 'MO-' . date('ym') . '-';
        $latest = $db->fetch(
            "SELECT mo_no FROM mo_headers
             WHERE mo_no LIKE ? ORDER BY mo_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($latest) {
            $parts = explode('-', $latest['mo_no']);
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
