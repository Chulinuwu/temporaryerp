<?php
/**
 * PEGASUS ERP - Cost Sheet Controller (原価算出)
 * Manages cost estimation sheets independently of projects.
 */

class CostSheetController extends Controller
{
    /**
     * List all cost sheets
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
        ];

        $sql = "SELECT cs.*, c.customer_name,
                       qh.quotation_no, p.pj_no,
                       (SELECT COUNT(*) FROM project_cost_items ci WHERE ci.cost_sheet_id = cs.cost_sheet_id AND ci.is_deleted = FALSE) as item_count
                FROM cost_sheets cs
                LEFT JOIN customers c ON c.customer_id = cs.customer_id
                LEFT JOIN quotation_headers qh ON qh.quotation_id = cs.quotation_id
                LEFT JOIN projects p ON p.project_id = cs.project_id
                WHERE cs.is_deleted = FALSE";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND cs.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer'])) {
            $sql .= " AND c.customer_name ILIKE ?";
            $params[] = '%' . $filters['customer'] . '%';
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (cs.sheet_no ILIKE ? OR cs.sheet_name ILIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }

        $sql .= " ORDER BY cs.cost_sheet_id DESC LIMIT 500";
        $sheets = $db->fetchAll($sql, $params) ?: [];

        $this->render('cost_sheets/list', [
            'pageTitle' => __('cost_sheet_list'),
            'sheets'    => $sheets,
            'filters'   => $filters,
        ]);
    }

    /**
     * Show cost sheet detail
     */
    public function show($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $sheet = $db->fetch(
            "SELECT cs.*, c.customer_name, c.customer_code,
                    qh.quotation_no, qh.project_name as qt_project_name,
                    p.pj_no, p.pj_name
             FROM cost_sheets cs
             LEFT JOIN customers c ON c.customer_id = cs.customer_id
             LEFT JOIN quotation_headers qh ON qh.quotation_id = cs.quotation_id
             LEFT JOIN projects p ON p.project_id = cs.project_id
             WHERE cs.cost_sheet_id = ? AND cs.is_deleted = FALSE",
            [$id]
        );
        if (!$sheet) {
            flash('error', __('not_found'));
            $this->redirect('/cost-sheets');
            return;
        }

        $items = $db->fetchAll(
            "SELECT * FROM project_cost_items WHERE cost_sheet_id = ? AND is_deleted = FALSE ORDER BY line_no",
            [$id]
        ) ?: [];

        $customers = $db->fetchAll(
            "SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name"
        ) ?: [];

        $this->render('cost_sheets/detail', [
            'pageTitle' => $sheet['sheet_no'] . ' - ' . $sheet['sheet_name'],
            'sheet'     => $sheet,
            'items'     => $items,
            'customers' => $customers,
        ]);
    }

    /**
     * Create form
     */
    public function create()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $db = Database::getInstance();

        $customers = $db->fetchAll("SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name") ?: [];

        $this->render('cost_sheets/form', [
            'pageTitle'  => __('new_cost_sheet'),
            'sheet'      => null,
            'customers'  => $customers,
        ]);
    }

    /**
     * Store new cost sheet
     */
    public function store()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        $user = $this->getCurrentUser();
        $sheetNo = sanitize($_POST['sheet_no'] ?? '');
        if (empty($sheetNo)) {
            $sheetNo = $this->generateSheetNo($db);
        }

        try {
            $row = $db->fetch(
                "INSERT INTO cost_sheets
                    (sheet_no, sheet_name, customer_id, notes, status, created_by)
                 VALUES (?, ?, ?, ?, 'DRAFT', ?)
                 RETURNING cost_sheet_id",
                [
                    $sheetNo,
                    sanitize($_POST['sheet_name'] ?? ''),
                    intval($_POST['customer_id'] ?? 0) ?: null,
                    sanitize($_POST['notes'] ?? ''),
                    $user['user_id'],
                ]
            );
            flash('success', __('msg_saved'));
            $this->redirect('/cost-sheets/' . $row['cost_sheet_id']);
        } catch (Exception $e) {
            error_log('CostSheetController::store - ' . $e->getMessage());
            flash('error', __('msg_save_error') . ': ' . $e->getMessage());
            $this->redirect('/cost-sheets/create');
        }
    }

    /**
     * Update cost sheet header
     */
    public function update($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();
        $user = $this->getCurrentUser();

        try {
            // Load current status to gate status changes behind Manager+ authority
            $currentRow = $db->fetch("SELECT status FROM cost_sheets WHERE cost_sheet_id = ?", [$id]);
            $currentStatus = $currentRow['status'] ?? 'DRAFT';
            $requestedStatus = sanitize($_POST['status'] ?? 'DRAFT');

            if ($requestedStatus !== $currentStatus && !Auth::isManagerOrAbove()) {
                flash('error', __('msg_no_approval_permission'));
                $this->redirect('/cost-sheets/' . $id);
                return;
            }

            $db->query(
                "UPDATE cost_sheets SET sheet_name = ?, customer_id = ?, notes = ?, status = ?,
                        updated_by = ?, updated_at = NOW()
                 WHERE cost_sheet_id = ?",
                [
                    sanitize($_POST['sheet_name'] ?? ''),
                    intval($_POST['customer_id'] ?? 0) ?: null,
                    sanitize($_POST['notes'] ?? ''),
                    $requestedStatus,
                    $user['user_id'],
                    $id,
                ]
            );
            flash('success', __('msg_saved'));
        } catch (Exception $e) {
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/cost-sheets/' . $id);
    }

    /**
     * Add a single cost item manually
     */
    public function storeItem($sheetId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();
        $user = $this->getCurrentUser();

        $maxLine = $db->fetch(
            "SELECT COALESCE(MAX(line_no), 0) as mx FROM project_cost_items WHERE cost_sheet_id = ? AND is_deleted = FALSE",
            [$sheetId]
        );
        $lineNo = intval($maxLine['mx']) + 1;

        $unitPrice = floatval($_POST['unit_price'] ?? 0);
        $quantity  = floatval($_POST['quantity'] ?? 0);
        $total     = $unitPrice * $quantity;

        $db->query(
            "INSERT INTO project_cost_items
                (cost_sheet_id, line_no, category, description, supplier, brand, lead_time,
                 unit_price, quantity, total_amount, unit, remark, is_category_row, source, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE, 'MANUAL', ?)",
            [
                $sheetId, $lineNo,
                sanitize($_POST['category'] ?? ''),
                sanitize($_POST['description'] ?? ''),
                sanitize($_POST['supplier'] ?? ''),
                sanitize($_POST['brand'] ?? ''),
                sanitize($_POST['lead_time'] ?? ''),
                $unitPrice, $quantity, $total,
                sanitize($_POST['unit'] ?? ''),
                sanitize($_POST['remark'] ?? ''),
                $user['user_id'],
            ]
        );

        $this->updateSheetTotal($db, $sheetId);
        flash('success', __('msg_saved'));
        $this->redirect('/cost-sheets/' . $sheetId);
    }

    /**
     * Delete a cost item
     */
    public function deleteItem($sheetId, $itemId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        $db->query(
            "UPDATE project_cost_items SET is_deleted = TRUE, updated_at = NOW() WHERE cost_item_id = ? AND cost_sheet_id = ?",
            [$itemId, $sheetId]
        );

        $this->updateSheetTotal($db, $sheetId);
        flash('success', __('msg_deleted'));
        $this->redirect('/cost-sheets/' . $sheetId);
    }

    /**
     * Import cost items from Excel
     */
    public function importExcel($sheetId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        if (empty($_FILES['cost_file']) || $_FILES['cost_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', __('msg_upload_error'));
            $this->redirect('/cost-sheets/' . $sheetId);
            return;
        }

        $file = $_FILES['cost_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            flash('error', __('msg_invalid_file_type'));
            $this->redirect('/cost-sheets/' . $sheetId);
            return;
        }

        $uploadDir = BASE_PATH . '/uploads/cost_imports/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $savedPath = $uploadDir . uniqid('cost_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $savedPath);

        // Extract via Python
        $tmpJson = tempnam(sys_get_temp_dir(), 'cost_') . '.json';
        $sheetName = sanitize($_POST['sheet_name_excel'] ?? '');

        $pyScript = <<<'PYTHON'
import sys, json, openpyxl
excel_path, out_path = sys.argv[1], sys.argv[2]
sheet_hint = sys.argv[3] if len(sys.argv) > 3 else ''
wb = openpyxl.load_workbook(excel_path, data_only=True)
ws = None
if sheet_hint and sheet_hint in wb.sheetnames:
    ws = wb[sheet_hint]
if not ws:
    for name in wb.sheetnames:
        if 'breakdown' in name.lower():
            ws = wb[name]; break
if not ws:
    for name in wb.sheetnames:
        if 'cost' in name.lower() and 'summary' not in name.lower():
            ws = wb[name]; break
if not ws:
    ws = wb[wb.sheetnames[0]]
rows = []
for r in range(9, 200):
    d,e,f,g,h = ws.cell(r,4).value, ws.cell(r,5).value, ws.cell(r,6).value, ws.cell(r,7).value, ws.cell(r,8).value
    j,k,l,m,n = ws.cell(r,10).value, ws.cell(r,11).value, ws.cell(r,12).value, ws.cell(r,13).value, ws.cell(r,14).value
    if not any([d,e,j,k,l]): continue
    if j is not None and not isinstance(j,(int,float)): continue
    def clean(v):
        if v is None: return None
        s=str(v).strip()
        return None if s in ['-','None',''] else s
    def num(v):
        try:
            if v is not None and isinstance(v,(int,float)): return float(v)
        except: pass
        return 0
    rows.append({'row':r,'D':clean(d),'E':clean(e),'F':clean(f),'G':clean(g),'H':clean(h),'J':num(j),'K':num(k),'L':num(l),'M':clean(m),'N':clean(n)})
with open(out_path,'w',encoding='utf-8') as fp:
    json.dump(rows,fp,ensure_ascii=False)
PYTHON;

        $pyTmp = tempnam(sys_get_temp_dir(), 'py_') . '.py';
        file_put_contents($pyTmp, $pyScript);
        exec(sprintf('python3 %s %s %s %s 2>&1',
            escapeshellarg($pyTmp), escapeshellarg($savedPath),
            escapeshellarg($tmpJson), escapeshellarg($sheetName)
        ), $output, $exitCode);
        @unlink($pyTmp);

        if (!file_exists($tmpJson) || $exitCode !== 0) {
            flash('error', __('msg_import_failed') . ': ' . implode("\n", $output));
            $this->redirect('/cost-sheets/' . $sheetId);
            return;
        }

        $rows = json_decode(file_get_contents($tmpJson), true);
        @unlink($tmpJson);

        if (empty($rows)) {
            flash('error', __('msg_no_data_in_file'));
            $this->redirect('/cost-sheets/' . $sheetId);
            return;
        }

        try {
            $user = $this->getCurrentUser();
            $db->beginTransaction();

            // Clear existing IMPORT items for this sheet
            $db->query("DELETE FROM project_cost_items WHERE cost_sheet_id = ? AND source = 'IMPORT'", [$sheetId]);

            $currentCategory = null;
            $lineNo = 0;
            $totalCost = 0;
            $inserted = 0;

            foreach ($rows as $r) {
                $d = $r['D'];
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
                        (cost_sheet_id, line_no, category, description, supplier, brand, lead_time,
                         unit_price, quantity, total_amount, unit, remark, is_category_row, source, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'IMPORT', ?)",
                    [
                        $sheetId, $lineNo,
                        $isCategory ? $d : $currentCategory,
                        $isCategory ? $d : $r['E'],
                        $r['F'], $r['G'], $r['H'],
                        round($r['J'], 4), round($r['K'], 4), $total,
                        $r['M'], $r['N'],
                        $isCategory ? 'TRUE' : 'FALSE',
                        $user['user_id'],
                    ]
                );
                $inserted++;
                $totalCost += $total;
            }

            // Save source file name
            $db->query(
                "UPDATE cost_sheets SET source_file = ?, updated_at = NOW() WHERE cost_sheet_id = ?",
                [$file['name'], $sheetId]
            );

            $db->commit();
            $this->updateSheetTotal($db, $sheetId);
            flash('success', __('msg_cost_imported', $inserted, number_format($totalCost, 2)));
        } catch (Exception $e) {
            $db->rollback();
            flash('error', __('msg_import_failed') . ': ' . $e->getMessage());
        }

        $this->redirect('/cost-sheets/' . $sheetId);
    }

    /**
     * Import from quotation line items
     */
    public function importFromQuotation($sheetId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        $quotationId = intval($_POST['quotation_id'] ?? 0);
        if (!$quotationId) {
            flash('error', __('msg_no_selection'));
            $this->redirect('/cost-sheets/' . $sheetId);
            return;
        }

        $quotation = $db->fetch(
            "SELECT * FROM quotation_headers WHERE quotation_id = ? AND is_deleted = FALSE",
            [$quotationId]
        );
        if (!$quotation) {
            flash('error', __('quotation_not_found'));
            $this->redirect('/cost-sheets/' . $sheetId);
            return;
        }

        $lines = $db->fetchAll(
            "SELECT * FROM quotation_lines WHERE quotation_id = ? AND (is_deleted = FALSE OR is_deleted IS NULL) ORDER BY sort_order, line_no",
            [$quotationId]
        ) ?: [];

        if (empty($lines)) {
            flash('error', __('msg_no_data_in_file'));
            $this->redirect('/cost-sheets/' . $sheetId);
            return;
        }

        try {
            $user = $this->getCurrentUser();
            $db->beginTransaction();

            $db->query("DELETE FROM project_cost_items WHERE cost_sheet_id = ? AND quotation_id = ?", [$sheetId, $quotationId]);

            $maxLine = $db->fetch("SELECT COALESCE(MAX(line_no), 0) as mx FROM project_cost_items WHERE cost_sheet_id = ? AND is_deleted = FALSE", [$sheetId]);
            $lineNo = intval($maxLine['mx']);
            $inserted = 0;
            $currentCategory = null;

            foreach ($lines as $ql) {
                $lineNo++;
                $isCategory = !empty($ql['is_category_row']);
                $total = floatval($ql['cost_total'] ?? $ql['ext_price'] ?? 0);
                if ($isCategory) $currentCategory = $ql['item_description'] ?? '';

                $db->query(
                    "INSERT INTO project_cost_items
                        (cost_sheet_id, line_no, category, description, unit_price, quantity, total_amount, unit, remark,
                         is_category_row, quotation_id, source, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'QUOTATION', ?)",
                    [
                        $sheetId, $lineNo,
                        $isCategory ? ($ql['item_description'] ?? '') : ($currentCategory ?? ''),
                        $ql['item_description'] ?? '',
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
            }

            // Link quotation to sheet
            $db->query("UPDATE cost_sheets SET quotation_id = ?, updated_at = NOW() WHERE cost_sheet_id = ?", [$quotationId, $sheetId]);

            $db->commit();
            $this->updateSheetTotal($db, $sheetId);
            flash('success', __('msg_cost_from_quotation', $quotation['quotation_no'], $inserted));
        } catch (Exception $e) {
            $db->rollback();
            flash('error', __('msg_save_error') . ': ' . $e->getMessage());
        }

        $this->redirect('/cost-sheets/' . $sheetId);
    }

    /**
     * AJAX: Search quotations
     */
    public function searchQuotations($sheetId)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $sheet = $db->fetch("SELECT customer_id FROM cost_sheets WHERE cost_sheet_id = ?", [$sheetId]);
        $q = sanitize($_GET['q'] ?? '');

        $sql = "SELECT qh.quotation_id, qh.quotation_no, qh.project_name,
                       qh.grand_total_thb, qh.status, c.customer_name,
                       (SELECT COUNT(*) FROM quotation_lines ql WHERE ql.quotation_id = qh.quotation_id AND ql.is_deleted = FALSE) as line_count
                FROM quotation_headers qh
                LEFT JOIN customers c ON c.customer_id = qh.customer_id
                WHERE qh.is_deleted = FALSE";
        $params = [];

        if (!empty($sheet['customer_id'])) {
            $sql .= " AND qh.customer_id = ?";
            $params[] = $sheet['customer_id'];
        }
        if (!empty($q)) {
            $sql .= " AND (qh.quotation_no ILIKE ? OR qh.project_name ILIKE ?)";
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        $sql .= " ORDER BY qh.quotation_no DESC LIMIT 30";
        $this->json($db->fetchAll($sql, $params) ?: []);
    }

    /**
     * Delete cost sheet (soft)
     */
    public function delete($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        $db->query("UPDATE cost_sheets SET is_deleted = TRUE, updated_at = NOW() WHERE cost_sheet_id = ?", [$id]);
        flash('success', __('msg_deleted'));
        $this->redirect('/cost-sheets');
    }

    /**
     * Create cost sheet + import Excel in one step (from create form)
     */
    public function storeWithImport()
    {
        $this->requireAuth();
        $this->requireAccess('sales');
        $this->validateCsrf();
        $db = Database::getInstance();

        // ── Validate file upload ──
        if (empty($_FILES['cost_file']) || $_FILES['cost_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', __('msg_upload_error'));
            $this->redirect('/cost-sheets/create');
            return;
        }
        $file = $_FILES['cost_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            flash('error', __('msg_invalid_file_type'));
            $this->redirect('/cost-sheets/create');
            return;
        }

        // ── Save uploaded file ──
        $uploadDir = BASE_PATH . '/uploads/cost_imports/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $savedPath = $uploadDir . uniqid('cost_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $savedPath);

        // ── Extract data via Python ──
        $tmpJson = tempnam(sys_get_temp_dir(), 'cost_') . '.json';
        $sheetName = sanitize($_POST['sheet_name_excel'] ?? '');

        $pyScript = <<<'PYTHON'
import sys, json, openpyxl
excel_path, out_path = sys.argv[1], sys.argv[2]
sheet_hint = sys.argv[3] if len(sys.argv) > 3 else ''
wb = openpyxl.load_workbook(excel_path, data_only=True)
ws = None
if sheet_hint and sheet_hint in wb.sheetnames:
    ws = wb[sheet_hint]
if not ws:
    for name in wb.sheetnames:
        if 'breakdown' in name.lower():
            ws = wb[name]; break
if not ws:
    for name in wb.sheetnames:
        if 'cost' in name.lower() and 'summary' not in name.lower():
            ws = wb[name]; break
if not ws:
    ws = wb[wb.sheetnames[0]]
rows = []
for r in range(9, 200):
    d,e,f,g,h = ws.cell(r,4).value, ws.cell(r,5).value, ws.cell(r,6).value, ws.cell(r,7).value, ws.cell(r,8).value
    j,k,l,m,n = ws.cell(r,10).value, ws.cell(r,11).value, ws.cell(r,12).value, ws.cell(r,13).value, ws.cell(r,14).value
    if not any([d,e,j,k,l]): continue
    if j is not None and not isinstance(j,(int,float)): continue
    def clean(v):
        if v is None: return None
        s=str(v).strip()
        return None if s in ['-','None',''] else s
    def num(v):
        try:
            if v is not None and isinstance(v,(int,float)): return float(v)
        except: pass
        return 0
    rows.append({'row':r,'D':clean(d),'E':clean(e),'F':clean(f),'G':clean(g),'H':clean(h),'J':num(j),'K':num(k),'L':num(l),'M':clean(m),'N':clean(n)})
with open(out_path,'w',encoding='utf-8') as fp:
    json.dump(rows,fp,ensure_ascii=False)
PYTHON;

        $pyTmp = tempnam(sys_get_temp_dir(), 'py_') . '.py';
        file_put_contents($pyTmp, $pyScript);
        exec(sprintf('python3 %s %s %s %s 2>&1',
            escapeshellarg($pyTmp), escapeshellarg($savedPath),
            escapeshellarg($tmpJson), escapeshellarg($sheetName)
        ), $output, $exitCode);
        @unlink($pyTmp);

        if (!file_exists($tmpJson) || $exitCode !== 0) {
            flash('error', __('msg_import_failed') . ': ' . implode("\n", $output));
            $this->redirect('/cost-sheets/create');
            return;
        }

        $rows = json_decode(file_get_contents($tmpJson), true);
        @unlink($tmpJson);

        if (empty($rows)) {
            flash('error', __('msg_no_data_in_file'));
            $this->redirect('/cost-sheets/create');
            return;
        }

        // ── Create cost sheet + import items in one transaction ──
        try {
            $user = $this->getCurrentUser();
            $db->beginTransaction();

            $sheetNo = $this->generateSheetNo($db);
            $sheetNameInput = sanitize($_POST['sheet_name'] ?? '');
            if (empty($sheetNameInput)) {
                $sheetNameInput = pathinfo($file['name'], PATHINFO_FILENAME);
            }

            $row = $db->fetch(
                "INSERT INTO cost_sheets
                    (sheet_no, sheet_name, customer_id, source_file, status, created_by)
                 VALUES (?, ?, ?, ?, 'DRAFT', ?)
                 RETURNING cost_sheet_id",
                [
                    $sheetNo,
                    $sheetNameInput,
                    intval($_POST['customer_id'] ?? 0) ?: null,
                    $file['name'],
                    $user['user_id'],
                ]
            );
            $sheetId = $row['cost_sheet_id'];

            $currentCategory = null;
            $lineNo = 0;
            $totalCost = 0;
            $inserted = 0;

            foreach ($rows as $r) {
                $d = $r['D'];
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
                        (cost_sheet_id, line_no, category, description, supplier, brand, lead_time,
                         unit_price, quantity, total_amount, unit, remark, is_category_row, source, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'IMPORT', ?)",
                    [
                        $sheetId, $lineNo,
                        $isCategory ? $d : $currentCategory,
                        $isCategory ? $d : $r['E'],
                        $r['F'], $r['G'], $r['H'],
                        round($r['J'], 4), round($r['K'], 4), $total,
                        $r['M'], $r['N'],
                        $isCategory ? 'TRUE' : 'FALSE',
                        $user['user_id'],
                    ]
                );
                $inserted++;
                $totalCost += $total;
            }

            // Update total
            $db->query("UPDATE cost_sheets SET total_cost = ?, updated_at = NOW() WHERE cost_sheet_id = ?",
                [$totalCost, $sheetId]);

            $db->commit();
            flash('success', __('msg_cost_imported', $inserted, number_format($totalCost, 2)));
            $this->redirect('/cost-sheets/' . $sheetId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('CostSheetController::storeWithImport - ' . $e->getMessage());
            flash('error', __('msg_import_failed') . ': ' . $e->getMessage());
            $this->redirect('/cost-sheets/create');
        }
    }

    /**
     * API: Search cost sheets (for quotation form modal)
     */
    public function apiSearch()
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $q = sanitize($_GET['q'] ?? '');

        $sql = "SELECT cs.cost_sheet_id, cs.sheet_no, cs.sheet_name, cs.status,
                       cs.total_cost, cs.source_file, cs.customer_id,
                       c.customer_name,
                       COUNT(pci.cost_item_id) FILTER (WHERE pci.is_deleted = FALSE) AS item_count
                FROM cost_sheets cs
                LEFT JOIN customers c ON c.customer_id = cs.customer_id
                LEFT JOIN project_cost_items pci ON pci.cost_sheet_id = cs.cost_sheet_id
                WHERE cs.is_deleted = FALSE
                  AND cs.status = 'CONFIRMED'";
        $params = [];

        // Filter by customer when caller is a quotation form tied to a specific customer
        $customerId = intval($_GET['customer_id'] ?? 0);
        if ($customerId > 0) {
            $sql .= " AND cs.customer_id = ?";
            $params[] = $customerId;
        }

        if (!empty($q)) {
            $sql .= " AND (cs.sheet_no ILIKE ? OR cs.sheet_name ILIKE ? OR c.customer_name ILIKE ?)";
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " GROUP BY cs.cost_sheet_id, cs.sheet_no, cs.sheet_name, cs.status,
                            cs.total_cost, cs.source_file, cs.customer_id, c.customer_name
                   ORDER BY cs.created_at DESC LIMIT 20";

        $results = $db->fetchAll($sql, $params) ?: [];
        $this->json(['results' => $results]);
    }

    /**
     * API: Get cost sheet items (for quotation form modal preview)
     */
    public function apiItems($sheetId)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $items = $db->fetchAll(
            "SELECT cost_item_id, line_no, category, description, supplier, brand,
                    lead_time, unit_price, quantity, total_amount, unit, remark,
                    is_category_row
             FROM project_cost_items
             WHERE cost_sheet_id = ? AND is_deleted = FALSE
             ORDER BY line_no",
            [intval($sheetId)]
        ) ?: [];

        // Normalize boolean
        foreach ($items as &$item) {
            $item['is_category_row'] = ($item['is_category_row'] === 't' || $item['is_category_row'] === true || $item['is_category_row'] === '1');
        }
        unset($item);

        // Auto-inject category header rows when category changes
        // (many imported sheets have category column but no explicit category rows)
        $result = [];
        $lastCategory = null;
        $hasCategoryRows = false;
        foreach ($items as $item) {
            if ($item['is_category_row']) { $hasCategoryRows = true; break; }
        }

        if (!$hasCategoryRows) {
            // No explicit category rows — generate them from category field changes
            foreach ($items as $item) {
                $cat = $item['category'] ?? null;
                if ($cat && $cat !== $lastCategory) {
                    $lastCategory = $cat;
                    $result[] = [
                        'cost_item_id'    => null,
                        'line_no'         => null,
                        'category'        => $cat,
                        'description'     => $cat,
                        'supplier'        => null,
                        'brand'           => null,
                        'lead_time'       => null,
                        'unit_price'      => 0,
                        'quantity'        => null,
                        'total_amount'    => 0,
                        'unit'            => null,
                        'remark'          => null,
                        'is_category_row' => true,
                    ];
                }
                $result[] = $item;
            }
        } else {
            $result = $items;
        }

        $this->json(['items' => $result]);
    }

    // ── Helpers ──

    private function updateSheetTotal($db, $sheetId)
    {
        $sum = $db->fetch(
            "SELECT COALESCE(SUM(total_amount), 0) as total
             FROM project_cost_items
             WHERE cost_sheet_id = ? AND is_deleted = FALSE AND is_category_row = FALSE",
            [$sheetId]
        );
        $db->query("UPDATE cost_sheets SET total_cost = ?, updated_at = NOW() WHERE cost_sheet_id = ?",
            [$sum['total'], $sheetId]);
    }

    private function generateSheetNo($db)
    {
        $prefix = 'CS-' . date('Ym') . '-';
        $row = $db->fetch("SELECT sheet_no FROM cost_sheets WHERE sheet_no LIKE ? ORDER BY sheet_no DESC LIMIT 1", [$prefix . '%']);
        $seq = $row ? intval(substr($row['sheet_no'], strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
