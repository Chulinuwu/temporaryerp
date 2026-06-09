<?php
/**
 * PEGASUS ERP - MRP Controller
 * Material Requirements Planning - demand calculation and purchase recommendations
 */

class MRPController extends Controller
{
    /**
     * Show MRP snapshots list
     */
    public function index()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION', 'PURCHASING']);
        $db = Database::getInstance();

        try {
            $page = max(1, (int) $this->input('page', 1));
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $countRow = $db->fetch(
                "SELECT COUNT(*) as total FROM mrp_snapshots WHERE 1=1"
            );
            $total = (int) ($countRow['total'] ?? 0);

            $snapshots = $db->fetchAll(
                "SELECT ms.*,
                        u.username as created_by_name
                 FROM mrp_snapshots ms
                 LEFT JOIN users u ON u.user_id = ms.created_by
                 ORDER BY ms.created_at DESC
                 LIMIT ? OFFSET ?",
                [$perPage, $offset]
            );

            $this->render('production/mrp', [
                'pageTitle' => 'Material Requirements Planning',
                'snapshots' => $snapshots ?: [],
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]);
        } catch (Exception $e) {
            error_log('MRPController::index error: ' . $e->getMessage());
            flash('error', 'Failed to load MRP snapshots.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Run MRP calculation
     * 1. Get all SO demands for the period
     * 2. Expand BOM for each finished good
     * 3. Calculate net requirements = gross requirement - on-hand - on-order
     * 4. Generate purchase recommendations where net > 0
     */
    public function calculate()
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION', 'PURCHASING']);
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $periodFrom = sanitize($_POST['period_from'] ?? date('Y-m-d'));
            $periodTo = sanitize($_POST['period_to'] ?? date('Y-m-d', strtotime('+30 days')));
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;

            if (empty($periodFrom) || empty($periodTo) || empty($divisionId)) {
                flash('error', 'Period dates and division are required.');
                $this->redirect('/mrp');
                return;
            }

            $db->beginTransaction();

            $currentUser = $this->getCurrentUser();

            // Create MRP snapshot header
            $row = $db->fetch(
                "INSERT INTO mrp_snapshots (snapshot_date, period_from, period_to, division_id, status, created_by, created_at)
                 VALUES (CURRENT_DATE, ?, ?, ?, 'CALCULATING', ?, NOW())
                 RETURNING snapshot_id",
                [$periodFrom, $periodTo, $divisionId, $currentUser['user_id'] ?? null]
            );

            $snapshotId = $row['snapshot_id'];

            // Step 1: Get all SO demands for the period (unfulfilled sales order lines)
            $demands = $db->fetchAll(
                "SELECT sol.item_id, i.item_code, i.item_name, i.unit,
                        SUM(COALESCE(sol.quantity, 0) - COALESCE(sol.delivered_qty, 0)) as demand_qty,
                        so.requested_date
                 FROM sales_order_lines sol
                 JOIN sales_order_headers so ON so.so_id = sol.so_id
                 JOIN items i ON i.item_id = sol.item_id
                 WHERE so.status IN ('CONFIRMED', 'IN_PRODUCTION')
                   AND so.is_deleted = FALSE
                   AND sol.is_deleted = FALSE
                   AND COALESCE(so.requested_date, so.order_date) BETWEEN ? AND ?
                   AND COALESCE(sol.quantity, 0) > COALESCE(sol.delivered_qty, 0)
                 GROUP BY sol.item_id, i.item_code, i.item_name, i.unit, so.requested_date
                 ORDER BY so.requested_date, i.item_code",
                [$periodFrom, $periodTo]
            );

            // Aggregate material requirements
            $materialRequirements = [];

            foreach ($demands ?: [] as $demand) {
                $itemId = $demand['item_id'];
                $demandQty = (float) $demand['demand_qty'];
                $requiredDate = $demand['requested_date'];

                // Step 2: Expand BOM for each finished good
                $bomLines = $db->fetchAll(
                    "SELECT bl.component_item_id, bl.quantity_per,
                            COALESCE(bl.scrap_rate, 0) as scrap_rate,
                            ci.item_code as component_code, ci.item_name as component_name,
                            ci.unit as component_unit
                     FROM bom_lines bl
                     JOIN bom_headers bh ON bh.bom_id = bl.bom_id
                     JOIN items ci ON ci.item_id = bl.component_item_id
                     WHERE bh.item_id = ? AND bh.is_current = TRUE
                       AND bh.is_deleted = FALSE AND bl.is_deleted = FALSE",
                    [$itemId]
                );

                if (empty($bomLines)) {
                    // No BOM -- treat as direct requirement (raw material / purchased item)
                    $key = $itemId . '_' . $requiredDate;
                    if (!isset($materialRequirements[$key])) {
                        $materialRequirements[$key] = [
                            'item_id' => $itemId,
                            'item_code' => $demand['item_code'],
                            'item_name' => $demand['item_name'],
                            'unit' => $demand['unit'],
                            'gross_requirement' => 0,
                            'required_date' => $requiredDate
                        ];
                    }
                    $materialRequirements[$key]['gross_requirement'] += $demandQty;
                    continue;
                }

                // Explode BOM components
                foreach ($bomLines as $bl) {
                    $componentId = $bl['component_item_id'];
                    $qtyPer = (float) $bl['quantity_per'];
                    $scrapRate = (float) $bl['scrap_rate'];
                    $grossQty = $demandQty * $qtyPer * (1 + $scrapRate);

                    $key = $componentId . '_' . $requiredDate;
                    if (!isset($materialRequirements[$key])) {
                        $materialRequirements[$key] = [
                            'item_id' => $componentId,
                            'item_code' => $bl['component_code'],
                            'item_name' => $bl['component_name'],
                            'unit' => $bl['component_unit'],
                            'gross_requirement' => 0,
                            'required_date' => $requiredDate
                        ];
                    }
                    $materialRequirements[$key]['gross_requirement'] += $grossQty;
                }
            }

            // Step 3 & 4: For each material, calculate net requirement and generate recommendations
            foreach ($materialRequirements as $req) {
                // Get on-hand inventory
                $onHand = $db->fetch(
                    "SELECT COALESCE(SUM(quantity_on_hand), 0) as on_hand
                     FROM stock_balances
                     WHERE item_id = ?",
                    [$req['item_id']]
                );
                $onHandQty = (float) ($onHand['on_hand'] ?? 0);

                // Get on-order (open PO lines not yet received)
                $onOrder = $db->fetch(
                    "SELECT COALESCE(SUM(pol.quantity - COALESCE(pol.received_qty, 0)), 0) as on_order
                     FROM purchase_order_lines pol
                     JOIN purchase_order_headers po ON po.po_id = pol.po_id
                     WHERE pol.item_id = ? AND po.status IN ('APPROVED', 'SENT', 'PARTIAL_RECEIVED')
                       AND po.is_deleted = FALSE AND pol.is_deleted = FALSE",
                    [$req['item_id']]
                );
                $onOrderQty = (float) ($onOrder['on_order'] ?? 0);

                $grossReq = round($req['gross_requirement'], 4);
                $netReq = round($grossReq - $onHandQty - $onOrderQty, 4);

                // Save MRP item
                $db->query(
                    "INSERT INTO mrp_items (
                        snapshot_id, item_id, item_code,
                        stock_base_date, created_at
                     ) VALUES (?, ?, ?, ?, NOW())",
                    [
                        $snapshotId, $req['item_id'], $req['item_code'],
                        $onHandQty
                    ]
                );

                // Step 4: Generate purchase recommendation where net > 0
                if ($netReq > 0) {
                    // Get item lead time
                    $itemRow = $db->fetch(
                        "SELECT lead_time_days FROM items WHERE item_id = ?",
                        [$req['item_id']]
                    );
                    $leadTimeDays = (int) ($itemRow['lead_time_days'] ?? 7);
                    $recommendedDate = date('Y-m-d', strtotime($req['required_date'] . " -{$leadTimeDays} days"));

                    $db->query(
                        "INSERT INTO mrp_purchase_recommendations (
                            snapshot_id, item_id, recommended_qty,
                            required_date, recommended_date,
                            unit, action_status, created_at
                         ) VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())",
                        [
                            $snapshotId, $req['item_id'], round($netReq, 4),
                            $req['required_date'], $recommendedDate,
                            $req['unit']
                        ]
                    );
                }
            }

            // Update snapshot status
            $db->query(
                "UPDATE mrp_snapshots SET status = 'COMPLETED'
                 WHERE snapshot_id = ?",
                [$snapshotId]
            );

            $db->commit();

            $totalItems = count($materialRequirements);
            flash('success', "MRP calculation completed: {$totalItems} items analyzed.");
            $this->redirect("/mrp/{$snapshotId}");
        } catch (Exception $e) {
            $db->rollback();
            error_log('MRPController::calculate error: ' . $e->getMessage());
            flash('error', 'Failed to run MRP calculation: ' . $e->getMessage());
            $this->redirect('/mrp');
        }
    }

    /**
     * Show MRP calculation result
     */
    public function showSnapshot($id)
    {
        $this->requireAuth();
        $this->requireAccess('production');
        $this->requireRole(['ADMIN', 'PRODUCTION', 'PURCHASING']);
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $snapshot = $db->fetch(
                "SELECT ms.*, u.username as created_by_name
                 FROM mrp_snapshots ms
                 LEFT JOIN users u ON u.user_id = ms.created_by
                 WHERE ms.snapshot_id = ?",
                [$id]
            );

            if (!$snapshot) {
                flash('error', 'MRP snapshot not found.');
                $this->redirect('/mrp');
                return;
            }

            // Get MRP items
            $lines = $db->fetchAll(
                "SELECT ml.*, i.item_name, i.unit
                 FROM mrp_items ml
                 JOIN items i ON i.item_id = ml.item_id
                 WHERE ml.snapshot_id = ?
                 ORDER BY ml.item_code",
                [$id]
            );

            // Get purchase recommendations
            $recommendations = $db->fetchAll(
                "SELECT mr.*, i.item_code, i.item_name, i.unit,
                        COALESCE(s.supplier_name, 'No preferred supplier') as supplier_name
                 FROM mrp_purchase_recommendations mr
                 JOIN items i ON i.item_id = mr.item_id
                 LEFT JOIN suppliers s ON s.supplier_id = mr.supplier_id
                 WHERE mr.snapshot_id = ?
                 ORDER BY mr.recommended_date, i.item_code",
                [$id]
            );

            $this->render('production/mrp', [
                'pageTitle' => 'MRP Result',
                'snapshot' => $snapshot,
                'lines' => $lines ?: [],
                'recommendations' => $recommendations ?: []
            ]);
        } catch (Exception $e) {
            error_log('MRPController::showSnapshot error: ' . $e->getMessage());
            flash('error', 'Failed to load MRP snapshot.');
            $this->redirect('/mrp');
        }
    }
}
