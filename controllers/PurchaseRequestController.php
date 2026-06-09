<?php
/**
 * PEGASUS ERP — Purchase Request (PR) Controller
 *
 * Workflow:
 *   any user creates DRAFT
 *     → submit  → PENDING_PURCHASING
 *   purchasing officer (canAccess('purchasing'))
 *     → approve → PENDING_MANAGER
 *   manager/admin (isManagerOrAbove)
 *     → approve → APPROVED  (eligible for convertToPo)
 *   reject available at any approval step → REJECTED.
 *   approved PR → convertToPo opens PO form pre-filled & marks PR as CONVERTED.
 */
class PurchaseRequestController extends Controller
{
    /* ============ Helpers ============ */

    private function loadPr(int $prId): ?array
    {
        $row = $this->db->fetch(
            "SELECT pr.*,
                    e.full_name_jp AS requester_name_jp,
                    e.full_name_th AS requester_name_th,
                    s.supplier_name AS suggested_supplier_name,
                    p.pj_no, p.pj_name,
                    pu.email AS purchasing_approver_email,
                    mu.email AS manager_approver_email,
                    ru.email AS rejected_by_email
             FROM purchase_requests pr
             LEFT JOIN employees e ON e.employee_id = pr.requester_id
             LEFT JOIN suppliers s ON s.supplier_id = pr.suggested_supplier_id
             LEFT JOIN projects  p ON p.project_id = pr.project_id
             LEFT JOIN users pu ON pu.user_id = pr.purchasing_approved_by
             LEFT JOIN users mu ON mu.user_id = pr.manager_approved_by
             LEFT JOIN users ru ON ru.user_id = pr.rejected_by
             WHERE pr.pr_id = ? AND pr.is_deleted = FALSE",
            [$prId]
        );
        return $row ?: null;
    }

    private function loadAttachments(int $prId): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, u.email AS uploaded_by_email
             FROM purchase_request_attachments a
             LEFT JOIN users u ON u.user_id = a.uploaded_by
             WHERE a.pr_id = ? AND a.is_deleted = FALSE
             ORDER BY a.uploaded_at DESC",
            [$prId]
        ) ?: [];
    }

    private function loadLines(int $prId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM purchase_request_lines
             WHERE pr_id = ? AND is_deleted = FALSE
             ORDER BY line_no",
            [$prId]
        ) ?: [];
    }

    private function generatePrNo(): string
    {
        $date = date('Ymd');
        $prefix = 'PR-' . $date;
        $row = $this->db->fetch(
            "SELECT pr_no FROM purchase_requests
             WHERE pr_no LIKE ? ORDER BY pr_no DESC LIMIT 1",
            [$prefix . '%']
        );
        $seq = 1;
        if ($row && preg_match('/-(\d+)$/', $row['pr_no'], $m)) {
            $seq = ((int)$m[1]) + 1;
        }
        return sprintf('%s-%04d', $prefix, $seq);
    }

    private function isPurchasingApprover(): bool
    {
        return Auth::canAccess('purchasing') || Auth::isManagerOrAbove();
    }

    private function isOwnerOrApprover(array $pr): bool
    {
        $u = Auth::user();
        if (!$u) return false;
        if (Auth::isManagerOrAbove()) return true;
        if (Auth::canAccess('purchasing')) return true;
        return ((int)($u['employee_id'] ?? 0)) === (int)$pr['requester_id'];
    }

    /** CEO-level final approver (DIRECTOR or ADMIN). */
    private function isCeo(): bool
    {
        return Auth::isDirectorOrAbove();
    }

    /** Quotes for a PR with their per-line prices keyed by pr_line_id. */
    private function loadQuotes(int $prId): array
    {
        $quotes = $this->db->fetchAll(
            "SELECT q.*, s.supplier_code, s.supplier_name,
                    a.file_name AS quote_pdf_name
             FROM purchase_request_quotes q
             LEFT JOIN suppliers s ON s.supplier_id = q.supplier_id
             LEFT JOIN purchase_request_attachments a ON a.attachment_id = q.attachment_id
             WHERE q.pr_id = ? AND q.is_deleted = FALSE
             ORDER BY q.position",
            [$prId]
        ) ?: [];
        if (!$quotes) return [];

        $ids = array_map(fn($q) => (int)$q['quote_id'], $quotes);
        $place = implode(',', array_fill(0, count($ids), '?'));
        $qlines = $this->db->fetchAll(
            "SELECT * FROM purchase_request_quote_lines WHERE quote_id IN ({$place})",
            $ids
        ) ?: [];
        $byQuote = [];
        foreach ($qlines as $ql) {
            $byQuote[(int)$ql['quote_id']][(int)$ql['pr_line_id']] = $ql;
        }
        foreach ($quotes as &$q) {
            $q['lines'] = $byQuote[(int)$q['quote_id']] ?? [];
        }
        return $quotes;
    }

    /** Validate the PR is eligible to leave QUOTES_PENDING:
     *  - 3 quotes exist, each with a PDF attachment,
     *  - every PR line has at least one quote_line marked is_winner=TRUE.
     */
    private function quotesAreCompleteFor(int $prId, array $lines): array
    {
        $quotes = $this->loadQuotes($prId);
        $errors = [];
        if (count($quotes) < 3) {
            $errors[] = __('pr_err_need_3_quotes');
        }
        foreach ($quotes as $q) {
            if (empty($q['attachment_id'])) {
                $errors[] = sprintf(__('pr_err_quote_pdf_missing'),
                    $q['position'], $q['supplier_name'] ?: $q['supplier_name_text']);
            }
        }
        $winnerLineIds = [];
        foreach ($quotes as $q) {
            foreach ($q['lines'] as $ql) {
                if (!empty($ql['is_winner'])) {
                    $winnerLineIds[(int)$ql['pr_line_id']] = true;
                }
            }
        }
        foreach ($lines as $l) {
            if (empty($winnerLineIds[(int)$l['pr_line_id']])) {
                $errors[] = sprintf(__('pr_err_line_no_winner'), $l['line_no']);
            }
        }
        return $errors;
    }

    /* ============ Pages ============ */

    public function index()
    {
        $this->requireAuth();
        $status = sanitize($_GET['status'] ?? '');
        $q      = sanitize($_GET['q'] ?? '');

        $sql = "SELECT pr.pr_id, pr.pr_no, pr.request_date, pr.needed_by_date,
                       pr.status, pr.est_total_thb, pr.justification,
                       e.full_name_jp AS requester_name_jp,
                       e.full_name_th AS requester_name_th,
                       s.supplier_name AS suggested_supplier_name,
                       pr.converted_po_id,
                       po.po_no AS converted_po_no
                FROM purchase_requests pr
                LEFT JOIN employees e ON e.employee_id = pr.requester_id
                LEFT JOIN suppliers s ON s.supplier_id = pr.suggested_supplier_id
                LEFT JOIN purchase_order_headers po ON po.po_id = pr.converted_po_id
                WHERE pr.is_deleted = FALSE";
        $params = [];

        // Non-approvers see only their own
        if (!Auth::isManagerOrAbove() && !Auth::canAccess('purchasing')) {
            $sql .= " AND pr.requester_id = ?";
            $params[] = (int)(Auth::user()['employee_id'] ?? 0);
        }
        if ($status !== '') {
            $sql .= " AND pr.status = ?";
            $params[] = $status;
        }
        if ($q !== '') {
            $sql .= " AND (pr.pr_no ILIKE ? OR pr.justification ILIKE ?)";
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY pr.request_date DESC, pr.pr_id DESC LIMIT 300";

        $rows = $this->db->fetchAll($sql, $params) ?: [];

        $this->render('purchasing/requests', [
            'pageTitle' => __('menu_purchase_requests'),
            'rows'      => $rows,
            'status'    => $status,
            'q'         => $q,
            'canPurchasingApprove' => $this->isPurchasingApprover(),
            'canManagerApprove'    => Auth::isManagerOrAbove(),
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $suppliers = $this->db->fetchAll(
            "SELECT supplier_id, supplier_code, supplier_name
             FROM suppliers WHERE is_deleted = FALSE
               AND COALESCE(approval_status,'APPROVED') = 'APPROVED'
             ORDER BY supplier_name LIMIT 1000"
        ) ?: [];
        $projects = $this->db->fetchAll(
            "SELECT p.project_id, p.pj_no, p.pj_name, c.customer_name
             FROM projects p
             LEFT JOIN customers c ON c.customer_id = p.customer_id
             WHERE COALESCE(p.status,'ACTIVE') <> 'CANCELLED'
             ORDER BY p.pj_no DESC LIMIT 500"
        ) ?: [];
        $this->render('purchasing/request_form', [
            'pageTitle' => __('pr_new'),
            'pr'        => null,
            'lines'     => [],
            'suppliers' => $suppliers,
            'projects'  => $projects,
            'attachments' => [],
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $user = Auth::user();
        $employeeId = (int)($user['employee_id'] ?? 0);
        if ($employeeId === 0) {
            flash('error', 'Cannot create PR — your user has no employee record.');
            $this->redirect('/purchasing/requests');
            return;
        }

        $department  = sanitize($this->input('department', ''));
        $neededBy    = $this->input('needed_by_date', null) ?: null;
        $justification = trim((string)$this->input('justification', ''));
        $suggestedSupplier = (int)$this->input('suggested_supplier_id', 0) ?: null;
        $projectId   = (int)$this->input('project_id', 0) ?: null;
        $notes       = trim((string)$this->input('notes', ''));

        $linesIn = $_POST['lines'] ?? [];
        if (!is_array($linesIn) || count($linesIn) === 0) {
            flash('error', __('pr_err_no_lines'));
            $this->redirect('/purchasing/requests/create');
            return;
        }

        $this->db->beginTransaction();
        try {
            $prNo = $this->generatePrNo();
            $estTotal = 0.0;
            $cleanLines = [];
            $no = 0;
            foreach ($linesIn as $l) {
                $desc = trim((string)($l['item_description'] ?? ''));
                if ($desc === '') continue;
                $no++;
                $qty   = (float)($l['quantity']      ?? 1);
                $price = (float)($l['est_unit_price'] ?? 0);
                $total = round($qty * $price, 2);
                $estTotal += $total;
                $cleanLines[] = [
                    'line_no'         => $no,
                    'item_code'       => sanitize($l['item_code'] ?? ''),
                    'item_description'=> $desc,
                    'quantity'        => $qty,
                    'unit'            => sanitize($l['unit'] ?? 'PCS'),
                    'est_unit_price'  => $price,
                    'est_line_total'  => $total,
                    'suggested_supplier_id' => (int)($l['suggested_supplier_id'] ?? 0) ?: null,
                    'needed_by_date'  => $l['needed_by_date'] ?? null,
                    'remark'          => sanitize($l['remark'] ?? ''),
                ];
            }
            if (count($cleanLines) === 0) {
                throw new RuntimeException(__('pr_err_no_lines'));
            }

            $prRow = $this->db->fetch(
                "INSERT INTO purchase_requests
                   (pr_no, requester_id, department, request_date, needed_by_date,
                    justification, suggested_supplier_id, project_id, est_total_thb,
                    status, notes, created_by)
                 VALUES (?, ?, ?, CURRENT_DATE, ?, ?, ?, ?, ?, 'DRAFT', ?, ?)
                 RETURNING pr_id",
                [$prNo, $employeeId, $department, $neededBy, $justification,
                 $suggestedSupplier, $projectId, $estTotal, $notes, (int)$user['user_id']]
            );
            $prId = (int)$prRow['pr_id'];

            foreach ($cleanLines as $l) {
                $this->db->query(
                    "INSERT INTO purchase_request_lines
                       (pr_id, line_no, item_code, item_description, quantity, unit,
                        est_unit_price, est_line_total, suggested_supplier_id,
                        needed_by_date, remark)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$prId, $l['line_no'], $l['item_code'], $l['item_description'],
                     $l['quantity'], $l['unit'], $l['est_unit_price'], $l['est_line_total'],
                     $l['suggested_supplier_id'], $l['needed_by_date'], $l['remark']]
                );
            }

            // File uploads — supplier quotations etc.
            $this->saveUploads($prId, (int)$user['user_id']);

            $this->db->commit();
            flash('success', __('pr_created') . ': ' . $prNo);
            $this->redirect('/purchasing/requests/' . $prId);
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('PR store: ' . $e->getMessage());
            flash('error', 'Failed to create PR: ' . $e->getMessage());
            $this->redirect('/purchasing/requests/create');
        }
    }

    /**
     * Save uploaded files into public/uploads/pr_attachments/<pr_id>/
     * and record metadata in purchase_request_attachments.
     */
    private function saveUploads(int $prId, int $userId): void
    {
        if (empty($_FILES['attachments']['name'][0])) return;

        $allowed = [
            'application/pdf','image/jpeg','image/png','image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $maxBytes = 20 * 1024 * 1024; // 20 MB per file

        $baseDir = dirname(__DIR__) . '/public/uploads/pr_attachments/' . $prId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Failed to create upload directory.');
        }

        $names = $_FILES['attachments']['name'];
        $tmps  = $_FILES['attachments']['tmp_name'];
        $sizes = $_FILES['attachments']['size'];
        $types = $_FILES['attachments']['type'];
        $errs  = $_FILES['attachments']['error'];
        $descs = $_POST['attachment_descriptions'] ?? [];

        for ($i = 0; $i < count($names); $i++) {
            if ($errs[$i] === UPLOAD_ERR_NO_FILE) continue;
            if ($errs[$i] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload error on ' . $names[$i] . ' (code ' . $errs[$i] . ')');
            }
            if ($sizes[$i] > $maxBytes) {
                throw new RuntimeException('File too large: ' . $names[$i] . ' (max 20MB)');
            }

            // MIME sniff (fileinfo) with fallback to client-provided
            $mime = $types[$i];
            if (function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                $det = $fi ? finfo_file($fi, $tmps[$i]) : null;
                if ($fi) finfo_close($fi);
                if ($det) $mime = $det;
            }
            if (!in_array($mime, $allowed, true)) {
                throw new RuntimeException('Disallowed file type for ' . $names[$i] . ' (' . $mime . ')');
            }

            $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $names[$i]);
            $unique = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '_' . $safe;
            $absPath = $baseDir . '/' . $unique;
            if (!move_uploaded_file($tmps[$i], $absPath)) {
                throw new RuntimeException('Could not save ' . $names[$i]);
            }
            $relPath = 'uploads/pr_attachments/' . $prId . '/' . $unique;

            $this->db->query(
                "INSERT INTO purchase_request_attachments
                    (pr_id, file_name, stored_path, file_size, mime_type, description, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$prId, $names[$i], $relPath, (int)$sizes[$i], $mime,
                 sanitize($descs[$i] ?? ''), $userId]
            );
        }
    }

    /** Add attachments to an existing PR (any state). */
    public function uploadAttachments(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $pr = $this->loadPr($id);
        if (!$pr) { $this->redirect('/purchasing/requests'); return; }
        if (!$this->isOwnerOrApprover($pr)) { http_response_code(403); echo '403'; exit; }

        $user = Auth::user();
        try {
            $this->saveUploads($id, (int)$user['user_id']);
            flash('success', __('pr_attach_uploaded'));
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('/purchasing/requests/' . $id);
    }

    /** Stream an attachment back to the browser. */
    public function downloadAttachment(int $id, int $attId)
    {
        $this->requireAuth();
        $pr = $this->loadPr($id);
        if (!$pr || !$this->isOwnerOrApprover($pr)) {
            http_response_code(403); echo '403'; exit;
        }
        $att = $this->db->fetch(
            "SELECT * FROM purchase_request_attachments
             WHERE attachment_id = ? AND pr_id = ? AND is_deleted = FALSE",
            [$attId, $id]
        );
        if (!$att) { http_response_code(404); echo '404'; exit; }

        $abs = dirname(__DIR__) . '/public/' . $att['stored_path'];
        if (!is_file($abs)) { http_response_code(404); echo 'file missing'; exit; }

        header('Content-Type: ' . ($att['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($abs));
        header('Content-Disposition: inline; filename="' . rawurlencode($att['file_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }

    /** Soft-delete an attachment (owner or approver). */
    public function deleteAttachment(int $id, int $attId)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $pr = $this->loadPr($id);
        if (!$pr || !$this->isOwnerOrApprover($pr)) {
            http_response_code(403); echo '403'; exit;
        }
        $this->db->query(
            "UPDATE purchase_request_attachments SET is_deleted = TRUE
             WHERE attachment_id = ? AND pr_id = ?",
            [$attId, $id]
        );
        flash('success', __('pr_attach_deleted'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    public function show(int $id)
    {
        $this->requireAuth();
        $pr = $this->loadPr($id);
        if (!$pr) {
            flash('error', 'PR not found.');
            $this->redirect('/purchasing/requests');
            return;
        }
        if (!$this->isOwnerOrApprover($pr)) {
            http_response_code(403);
            echo '<h1>403</h1>'; exit;
        }
        $lines = $this->loadLines($id);
        $attachments = $this->loadAttachments($id);
        $quotes = $this->loadQuotes($id);
        // Supplier list for adding quotes
        $suppliers = $this->db->fetchAll(
            "SELECT supplier_id, supplier_code, supplier_name
             FROM suppliers WHERE is_deleted = FALSE
               AND COALESCE(approval_status,'APPROVED') = 'APPROVED'
             ORDER BY supplier_name LIMIT 1000"
        ) ?: [];

        $this->render('purchasing/request_detail', [
            'pageTitle' => $pr['pr_no'],
            'pr'        => $pr,
            'lines'     => $lines,
            'attachments' => $attachments,
            'quotes'    => $quotes,
            'suppliers' => $suppliers,
            'canPurchasingApprove' => $this->isPurchasingApprover(),
            'canManagerApprove'    => Auth::isManagerOrAbove(),
            'canCeoApprove'        => $this->isCeo(),
        ]);
    }

    /* ============ Quote CRUD (purchasing officer only) ============ */

    /** Add (or replace) a quote at a given position 1/2/3 with optional PDF. */
    public function addQuote(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!$this->isPurchasingApprover()) { http_response_code(403); echo '403'; exit; }
        $pr = $this->loadPr($id);
        if (!$pr || !in_array($pr['status'], ['QUOTES_PENDING','SUBMITTED'], true)) {
            flash('error', __('pr_err_wrong_state')); $this->redirect('/purchasing/requests/' . $id); return;
        }

        $position = max(1, min(3, (int)$this->input('position', 1)));
        $supplierId = (int)$this->input('supplier_id', 0) ?: null;
        $supplierText = trim((string)$this->input('supplier_name_text', ''));
        $quoteNo  = sanitize($this->input('quote_no', ''));
        $quoteDate= $this->input('quote_date', null) ?: null;
        $payTerms = sanitize($this->input('payment_terms', ''));
        $lead     = (int)$this->input('lead_time_days', 0) ?: null;
        $notes    = trim((string)$this->input('notes', ''));

        // Per-line prices  prices[<pr_line_id>] => unit_price
        $pricesIn = $_POST['prices'] ?? [];
        $linesAll = $this->loadLines($id);

        $this->db->beginTransaction();
        try {
            // Upload mandatory PDF (saved as PR attachment, then linked)
            $attachmentId = null;
            if (!empty($_FILES['quote_pdf']['name']) && $_FILES['quote_pdf']['error'] === UPLOAD_ERR_OK) {
                $attachmentId = $this->savePdfAsAttachment(
                    $id,
                    $_FILES['quote_pdf'],
                    'Quote #' . $position . ' — ' . ($supplierText ?: ('supplier_id=' . $supplierId)),
                    (int)Auth::user()['user_id']
                );
            } else {
                throw new RuntimeException(__('pr_err_quote_pdf_required'));
            }

            // Delete any existing quote at this position (replace semantics)
            $existing = $this->db->fetch(
                "SELECT quote_id FROM purchase_request_quotes
                 WHERE pr_id=? AND position=? AND is_deleted=FALSE",
                [$id, $position]
            );
            if ($existing) {
                $this->db->query(
                    "UPDATE purchase_request_quotes SET is_deleted=TRUE WHERE quote_id=?",
                    [$existing['quote_id']]
                );
            }

            // Compute total = sum(unit_price * qty) across PR lines
            $total = 0.0;
            $qLineRows = [];
            foreach ($linesAll as $l) {
                $up = (float)($pricesIn[(int)$l['pr_line_id']] ?? 0);
                $qty = (float)$l['quantity'];
                $lt = round($up * $qty, 2);
                $total += $lt;
                $qLineRows[] = [
                    'pr_line_id' => (int)$l['pr_line_id'],
                    'unit_price' => $up,
                    'line_total' => $lt,
                ];
            }

            $quoteRow = $this->db->fetch(
                "INSERT INTO purchase_request_quotes
                   (pr_id, position, supplier_id, supplier_name_text,
                    quote_no, quote_date, total_amount_thb,
                    payment_terms, lead_time_days, notes, attachment_id, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?) RETURNING quote_id",
                [$id, $position, $supplierId, $supplierText,
                 $quoteNo, $quoteDate, $total, $payTerms, $lead, $notes,
                 $attachmentId, (int)Auth::user()['user_id']]
            );
            $quoteId = (int)$quoteRow['quote_id'];
            foreach ($qLineRows as $r) {
                $this->db->query(
                    "INSERT INTO purchase_request_quote_lines
                       (quote_id, pr_line_id, unit_price, line_total)
                     VALUES (?,?,?,?)",
                    [$quoteId, $r['pr_line_id'], $r['unit_price'], $r['line_total']]
                );
            }

            $this->db->commit();
            flash('success', __('pr_quote_saved'));
        } catch (Throwable $e) {
            $this->db->rollBack();
            flash('error', $e->getMessage());
        }
        $this->redirect('/purchasing/requests/' . $id);
    }

    /** Save a single PDF as a PR attachment record and return its attachment_id. */
    private function savePdfAsAttachment(int $prId, array $file, string $description, int $userId): int
    {
        $maxBytes = 20 * 1024 * 1024;
        if ($file['size'] > $maxBytes) throw new RuntimeException('PDF too large (max 20MB).');

        $mime = $file['type'];
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $det = $fi ? finfo_file($fi, $file['tmp_name']) : null;
            if ($fi) finfo_close($fi);
            if ($det) $mime = $det;
        }
        $okMimes = ['application/pdf','image/jpeg','image/png'];
        if (!in_array($mime, $okMimes, true)) {
            throw new RuntimeException('Quote attachment must be PDF/JPG/PNG (got: ' . $mime . ').');
        }

        $baseDir = dirname(__DIR__) . '/public/uploads/pr_attachments/' . $prId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Failed to create upload directory.');
        }
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
        $unique = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '_' . $safe;
        $abs = $baseDir . '/' . $unique;
        if (!move_uploaded_file($file['tmp_name'], $abs)) {
            throw new RuntimeException('Could not save PDF.');
        }
        $rel = 'uploads/pr_attachments/' . $prId . '/' . $unique;

        $attRow = $this->db->fetch(
            "INSERT INTO purchase_request_attachments
               (pr_id, file_name, stored_path, file_size, mime_type, description, uploaded_by)
             VALUES (?,?,?,?,?,?,?) RETURNING attachment_id",
            [$prId, $file['name'], $rel, (int)$file['size'], $mime, $description, $userId]
        );
        return (int)$attRow['attachment_id'];
    }

    /** Soft-delete a quote. */
    public function deleteQuote(int $id, int $quoteId)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!$this->isPurchasingApprover()) { http_response_code(403); echo '403'; exit; }
        $pr = $this->loadPr($id);
        if (!$pr || !in_array($pr['status'], ['QUOTES_PENDING','SUBMITTED'], true)) {
            flash('error', __('pr_err_wrong_state')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $this->db->query(
            "UPDATE purchase_request_quotes SET is_deleted=TRUE
             WHERE quote_id=? AND pr_id=?",
            [$quoteId, $id]
        );
        flash('success', __('pr_quote_deleted'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    /** Select winners per line. POST: winners[<pr_line_id>] = quote_id */
    public function selectWinners(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!$this->isPurchasingApprover()) { http_response_code(403); echo '403'; exit; }
        $pr = $this->loadPr($id);
        if (!$pr || $pr['status'] !== 'QUOTES_PENDING') {
            flash('error', __('pr_err_wrong_state')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $winners = $_POST['winners'] ?? [];

        $this->db->beginTransaction();
        try {
            // Reset all winner flags for this PR
            $this->db->query(
                "UPDATE purchase_request_quote_lines ql
                 SET is_winner=FALSE
                 FROM purchase_request_quotes q
                 WHERE ql.quote_id = q.quote_id AND q.pr_id = ? AND q.is_deleted = FALSE",
                [$id]
            );
            foreach ($winners as $prLineId => $quoteId) {
                if (!$quoteId) continue;
                $this->db->query(
                    "UPDATE purchase_request_quote_lines
                     SET is_winner=TRUE
                     WHERE quote_id=? AND pr_line_id=?",
                    [(int)$quoteId, (int)$prLineId]
                );
            }
            // Mark overall winner = the quote that wins the most lines
            $best = $this->db->fetch(
                "SELECT q.quote_id, COUNT(*) AS wins
                 FROM purchase_request_quote_lines ql
                 JOIN purchase_request_quotes q ON q.quote_id = ql.quote_id
                 WHERE q.pr_id = ? AND ql.is_winner = TRUE AND q.is_deleted = FALSE
                 GROUP BY q.quote_id ORDER BY wins DESC LIMIT 1",
                [$id]
            );
            $this->db->query(
                "UPDATE purchase_request_quotes SET is_overall_winner = FALSE WHERE pr_id = ?",
                [$id]
            );
            if ($best) {
                $this->db->query(
                    "UPDATE purchase_request_quotes SET is_overall_winner = TRUE WHERE quote_id = ?",
                    [$best['quote_id']]
                );
            }
            $this->db->commit();
            flash('success', __('pr_winners_saved'));
        } catch (Throwable $e) {
            $this->db->rollBack();
            flash('error', $e->getMessage());
        }
        $this->redirect('/purchasing/requests/' . $id);
    }

    /* ============ Workflow actions ============ */

    /** Requester submits DRAFT → SUBMITTED (purchasing officer will pick it up). */
    public function submit(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $pr = $this->loadPr($id);
        if (!$pr || $pr['status'] !== 'DRAFT') {
            flash('error', __('pr_err_not_draft'));
            $this->redirect('/purchasing/requests/' . $id);
            return;
        }
        $this->db->query(
            "UPDATE purchase_requests SET status='SUBMITTED', updated_at=NOW() WHERE pr_id=?",
            [$id]
        );
        flash('success', __('pr_submitted'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    /** Purchasing officer accepts SUBMITTED → starts quote collection (QUOTES_PENDING). */
    public function startQuotes(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!$this->isPurchasingApprover()) {
            flash('error', __('purchasing_approver_only')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $pr = $this->loadPr($id);
        if (!$pr || $pr['status'] !== 'SUBMITTED') {
            flash('error', __('pr_err_wrong_state')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $this->db->query(
            "UPDATE purchase_requests SET status='QUOTES_PENDING', updated_at=NOW() WHERE pr_id=?",
            [$id]
        );
        flash('success', __('pr_quotes_started'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    /** Purchasing officer finishes 3-quote comparison → PENDING_MANAGER.
     *  Enforces: 3 quotes / each has PDF / every line has a winner. */
    public function submitForManagerReview(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!$this->isPurchasingApprover()) {
            flash('error', __('purchasing_approver_only')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $pr = $this->loadPr($id);
        if (!$pr || $pr['status'] !== 'QUOTES_PENDING') {
            flash('error', __('pr_err_wrong_state')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $lines = $this->loadLines($id);
        $errs = $this->quotesAreCompleteFor($id, $lines);
        if ($errs) {
            foreach ($errs as $e) { flash('error', $e); }
            $this->redirect('/purchasing/requests/' . $id); return;
        }
        $note = trim((string)$this->input('note', ''));
        $this->db->query(
            "UPDATE purchase_requests
             SET status='PENDING_MANAGER',
                 purchasing_approved_by=?, purchasing_approved_at=NOW(),
                 purchasing_note=?, updated_at=NOW()
             WHERE pr_id=?",
            [(int)Auth::user()['user_id'], $note, $id]
        );
        flash('success', __('pr_purchasing_approved'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    /** Purchasing manager (MANAGER+) approves PENDING_MANAGER → PENDING_CEO. */
    public function approveManager(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('manager_only')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $pr = $this->loadPr($id);
        if (!$pr || $pr['status'] !== 'PENDING_MANAGER') {
            flash('error', __('pr_err_wrong_state')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $note = trim((string)$this->input('note', ''));
        $this->db->query(
            "UPDATE purchase_requests
             SET status='PENDING_CEO',
                 manager_approved_by=?, manager_approved_at=NOW(),
                 manager_note=?, updated_at=NOW()
             WHERE pr_id=?",
            [(int)Auth::user()['user_id'], $note, $id]
        );
        flash('success', __('pr_manager_approved'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    /** CEO (DIRECTOR+) final approval PENDING_CEO → APPROVED. */
    public function approveCeo(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!$this->isCeo()) {
            flash('error', __('ceo_only')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $pr = $this->loadPr($id);
        if (!$pr || $pr['status'] !== 'PENDING_CEO') {
            flash('error', __('pr_err_wrong_state')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $note = trim((string)$this->input('note', ''));
        $this->db->query(
            "UPDATE purchase_requests
             SET status='APPROVED',
                 ceo_approved_by=?, ceo_approved_at=NOW(),
                 ceo_note=?, updated_at=NOW()
             WHERE pr_id=?",
            [(int)Auth::user()['user_id'], $note, $id]
        );
        flash('success', __('pr_ceo_approved'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    /** Reject from any review state — gated by role. */
    public function reject(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $pr = $this->loadPr($id);
        if (!$pr) { $this->redirect('/purchasing/requests'); return; }
        $active = ['SUBMITTED','QUOTES_PENDING','PENDING_MANAGER','PENDING_CEO'];
        if (!in_array($pr['status'], $active, true)) {
            flash('error', __('pr_err_wrong_state')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        // role gate: purchasing+ for early states, manager for PENDING_MANAGER, CEO for PENDING_CEO
        $st = $pr['status'];
        $allowed =
            ($st === 'PENDING_CEO' && $this->isCeo()) ||
            ($st === 'PENDING_MANAGER' && Auth::isManagerOrAbove()) ||
            (in_array($st, ['SUBMITTED','QUOTES_PENDING'], true) && $this->isPurchasingApprover());
        if (!$allowed) {
            flash('error', __('purchasing_approver_only')); $this->redirect('/purchasing/requests/' . $id); return;
        }
        $reason = trim((string)$this->input('reason', ''));
        if ($reason === '') {
            flash('error', __('rejection_reason_required'));
            $this->redirect('/purchasing/requests/' . $id); return;
        }
        $this->db->query(
            "UPDATE purchase_requests
             SET status='REJECTED',
                 rejected_by=?, rejected_at=NOW(), rejection_reason=?, updated_at=NOW()
             WHERE pr_id=?",
            [(int)Auth::user()['user_id'], $reason, $id]
        );
        flash('success', __('pr_rejected'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    public function cancel(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $pr = $this->loadPr($id);
        if (!$pr) { $this->redirect('/purchasing/requests'); return; }
        if (!$this->isOwnerOrApprover($pr)) { http_response_code(403); echo '403'; exit; }
        $cancelable = ['DRAFT','SUBMITTED','QUOTES_PENDING','PENDING_MANAGER','PENDING_CEO'];
        if (!in_array($pr['status'], $cancelable, true)) {
            flash('error', __('pr_err_wrong_state'));
            $this->redirect('/purchasing/requests/' . $id); return;
        }
        $this->db->query("UPDATE purchase_requests SET status='CANCELLED', updated_at=NOW() WHERE pr_id=?", [$id]);
        flash('success', __('pr_cancelled'));
        $this->redirect('/purchasing/requests/' . $id);
    }

    /**
     * Redirect to the PO create form pre-filled with this PR's content.
     * Actual conversion is finalised by PurchaseOrderController::store when ?pr_id=... is posted.
     */
    public function convertToPo(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!$this->isPurchasingApprover()) {
            http_response_code(403); echo '403'; exit;
        }
        $pr = $this->loadPr($id);
        if (!$pr) { flash('error','PR not found.'); $this->redirect('/purchasing/requests'); return; }
        if ($pr['status'] !== 'APPROVED') {
            flash('error', __('pr_must_be_approved_for_po'));
            $this->redirect('/purchasing/requests/' . $id); return;
        }
        // Hand off to PO form (it reads ?from_pr_id=)
        $this->redirect('/purchasing/orders/create?from_pr_id=' . $id);
    }
}
