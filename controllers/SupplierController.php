<?php
/**
 * PEGASUS ERP - Supplier Controller
 * CRUD operations for suppliers
 */

class SupplierController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $search = sanitize($_GET['search'] ?? '');

            $sql = "SELECT * FROM suppliers WHERE is_deleted = FALSE";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (supplier_code ILIKE ? OR supplier_name ILIKE ? OR tax_id ILIKE ? OR email ILIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $sql .= " ORDER BY supplier_name";

            $suppliers = $db->fetchAll($sql, $params);

            $divisionsList = $db->fetchAll(
                "SELECT division_id, division_code, division_name FROM divisions WHERE is_deleted = FALSE ORDER BY division_code"
            );

            $this->render('master/suppliers', [
                'pageTitle' => 'Suppliers',
                'suppliers' => $suppliers ?: [],
                'divisionsList' => $divisionsList ?: [],
                'search' => $search
            ]);
        } catch (Exception $e) {
            error_log('SupplierController::index - ' . $e->getMessage());
            flash('error', 'Failed to load suppliers.');
            $this->render('master/suppliers', [
                'pageTitle' => 'Suppliers',
                'suppliers' => [],
                'search' => ''
            ]);
        }
    }

    public function save()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = sanitize($_POST['supplier_id'] ?? '');
            $supplierCode = sanitize($_POST['supplier_code'] ?? '');
            $supplierName = sanitize($_POST['supplier_name'] ?? '');
            $supplierNameJp = sanitize($_POST['supplier_name_jp'] ?? '');
            $supplierNameTh = sanitize($_POST['supplier_name_th'] ?? '');
            $country = sanitize($_POST['country'] ?? 'TH') ?: 'TH';
            $taxId = sanitize($_POST['tax_id'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            $contactPerson = sanitize($_POST['contact_person'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $paymentTerms = intval($_POST['payment_terms'] ?? 30);
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $whtRate = floatval($_POST['wht_rate'] ?? 3);
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            if (empty($supplierName)) {
                flash('error', 'Supplier name is required.');
                $this->redirect('/master/suppliers');
                return;
            }

            // Ensure division_id has a value (NOT NULL constraint)
            if (empty($divisionId)) {
                $defaultDiv = $db->fetch("SELECT division_id FROM divisions WHERE is_deleted = FALSE ORDER BY division_id ASC LIMIT 1");
                $divisionId = $defaultDiv ? $defaultDiv['division_id'] : 1;
            }

            // Auto-generate supplier_code for new suppliers — use numeric MAX (safer than string sort)
            if (!$id && empty($supplierCode)) {
                $row = $db->fetch(
                    "SELECT COALESCE(MAX(CAST(SUBSTRING(supplier_code FROM 5) AS INT)), 0) AS max_n
                     FROM suppliers WHERE supplier_code ~ '^SUP-[0-9]+$'"
                );
                $nextNum = intval($row['max_n'] ?? 0) + 1;
                $supplierCode = sprintf('SUP-%04d', $nextNum);
            }

            // Reject duplicate supplier_code (active rows only)
            if (!empty($supplierCode)) {
                $dup = $db->fetch(
                    "SELECT supplier_id, supplier_name FROM suppliers
                     WHERE supplier_code = ? AND is_deleted = FALSE AND is_current = TRUE
                       AND supplier_id <> ?",
                    [$supplierCode, $id ?: 0]
                );
                if ($dup) {
                    flash('error', __('supplier_code_duplicate') . ': ' . $supplierCode . ' (' . $dup['supplier_name'] . ')');
                    $this->redirect('/master/suppliers');
                    return;
                }
            }

            // Per policy: all supplier saves go through 2-step approval (Admin Manager → CEO)
            $approvalStatus = 'PENDING_MANAGER';

            if ($id) {
                $db->query(
                    "UPDATE suppliers SET supplier_code = ?, supplier_name = ?, supplier_name_jp = ?,
                     supplier_name_th = ?, country = ?, tax_id = ?,
                     address = ?, contact_person = ?, phone = ?,
                     email = ?, payment_terms = ?, currency_code = ?, wht_rate = ?,
                     division_id = ?,
                     approval_status = ?, approved_by = NULL, approved_at = NULL,
                     submitted_by = ?, submitted_at = NOW(),
                     manager_approved_by = NULL, manager_approved_at = NULL,
                     ceo_approved_by = NULL, ceo_approved_at = NULL,
                     rejected_by = NULL, rejected_at = NULL, rejection_reason = NULL,
                     updated_by = ?, updated_at = NOW()
                     WHERE supplier_id = ?",
                    [$supplierCode, $supplierName, $supplierNameJp, $supplierNameTh,
                     $country, $taxId, $address, $contactPerson,
                     $phone, $email, $paymentTerms, $currencyCode, $whtRate,
                     $divisionId,
                     $approvalStatus,
                     $user['user_id'], $user['user_id'], $id]
                );
                flash('success', __('supplier_updated') . ' (' . __('msg_submitted_for_approval') . ')');
                $savedSupplierId = (int)$id;
            } else {
                $row = $db->fetch(
                    "INSERT INTO suppliers (supplier_code, supplier_name, supplier_name_jp, supplier_name_th,
                     country, tax_id, address, contact_person, phone, email,
                     payment_terms, currency_code, wht_rate, division_id, created_by,
                     approval_status, submitted_by, submitted_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                     RETURNING supplier_id",
                    [$supplierCode, $supplierName, $supplierNameJp, $supplierNameTh,
                     $country, $taxId, $address, $contactPerson,
                     $phone, $email, $paymentTerms, $currencyCode, $whtRate,
                     $divisionId, $user['user_id'],
                     $approvalStatus, $user['user_id']]
                );
                $savedSupplierId = (int)($row['supplier_id'] ?? 0);
                flash('success', __('supplier_created') . ' (' . __('msg_submitted_for_approval') . ')');
            }

            // Save any uploaded credit/business docs (PDF/Excel/Image)
            $this->saveSupplierAttachments($savedSupplierId, (int)$user['user_id']);
        } catch (Exception $e) {
            error_log('SupplierController::save - ' . $e->getMessage());
            flash('error', 'Failed to save supplier. ' . $e->getMessage());
        }

        $this->redirect('/master/suppliers');
    }

    public function edit($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $supplier = $db->fetch(
                "SELECT * FROM suppliers WHERE supplier_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$supplier) {
                flash('error', 'Supplier not found.');
                $this->redirect('/master/suppliers');
                return;
            }

            $this->render('master/suppliers', [
                'pageTitle' => 'Edit Supplier',
                'supplier' => $supplier
            ]);
        } catch (Exception $e) {
            error_log('SupplierController::edit - ' . $e->getMessage());
            flash('error', 'Failed to load supplier.');
            $this->redirect('/master/suppliers');
        }
    }

    public function delete($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE suppliers SET is_deleted = TRUE, updated_by = ?, updated_at = NOW()
                 WHERE supplier_id = ?",
                [$user['user_id'], $id]
            );
            flash('success', 'Supplier deleted.');
        } catch (Exception $e) {
            error_log('SupplierController::delete - ' . $e->getMessage());
            flash('error', 'Failed to delete supplier.');
        }

        $this->redirect('/master/suppliers');
    }

    /** Manager+ approves a PENDING supplier */
    public function approve($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/master/suppliers');
            return;
        }
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE suppliers SET approval_status='APPROVED', approved_by=?, approved_at=NOW(),
                                 updated_by=?, updated_at=NOW()
             WHERE supplier_id=? AND approval_status='PENDING'",
            [$user['user_id'], $user['user_id'], $id]
        );
        flash('success', __('msg_approved'));
        $this->redirect('/master/suppliers');
    }

    /** Manager+ rejects a PENDING supplier */
    public function reject($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('msg_no_approval_permission'));
            $this->redirect('/master/suppliers');
            return;
        }
        $db = Database::getInstance();
        $user = $this->getCurrentUser();
        $db->query(
            "UPDATE suppliers SET approval_status='REJECTED', approved_by=?, approved_at=NOW(),
                                 updated_by=?, updated_at=NOW()
             WHERE supplier_id=? AND approval_status='PENDING'",
            [$user['user_id'], $user['user_id'], $id]
        );
        flash('success', __('msg_rejected'));
        $this->redirect('/master/suppliers');
    }

    public function search()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $keyword = sanitize($_GET['q'] ?? '');

            if (strlen($keyword) < 1) {
                $this->json(['results' => []]);
                return;
            }

            $suppliers = $db->fetchAll(
                "SELECT supplier_id, supplier_code, supplier_name, supplier_name_jp, supplier_name_th, contact_person, phone
                 FROM suppliers
                 WHERE is_deleted = FALSE
                 AND (supplier_code ILIKE ? OR supplier_name ILIKE ? OR supplier_name_th ILIKE ? OR tax_id ILIKE ?)
                 ORDER BY supplier_name LIMIT 20",
                ["%{$keyword}%", "%{$keyword}%", "%{$keyword}%", "%{$keyword}%"]
            );

            $this->json(['results' => $suppliers ?: []]);
        } catch (Exception $e) {
            error_log('SupplierController::search - ' . $e->getMessage());
            $this->json(['error' => 'Search failed.'], 500);
        }
    }

    /* ======================= Supplier attachments =======================
     * Credit / commercial-registration / tax-cert / bank docs.
     * Saved under public/uploads/supplier_attachments/<supplier_id>/
     */

    private function saveSupplierAttachments(int $supplierId, int $userId): void
    {
        if ($supplierId <= 0 || empty($_FILES['supplier_attachments']['name'][0])) return;

        $allowed = [
            'application/pdf','image/jpeg','image/png','image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $maxBytes = 20 * 1024 * 1024;

        $baseDir = dirname(__DIR__) . '/public/uploads/supplier_attachments/' . $supplierId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            error_log('Could not create supplier upload dir: ' . $baseDir);
            return;
        }

        $names = $_FILES['supplier_attachments']['name'];
        $tmps  = $_FILES['supplier_attachments']['tmp_name'];
        $sizes = $_FILES['supplier_attachments']['size'];
        $types = $_FILES['supplier_attachments']['type'];
        $errs  = $_FILES['supplier_attachments']['error'];
        $descs = $_POST['supplier_attachment_descriptions'] ?? [];
        $kinds = $_POST['supplier_attachment_doc_types']     ?? [];

        $db = Database::getInstance();
        for ($i = 0; $i < count($names); $i++) {
            if ($errs[$i] === UPLOAD_ERR_NO_FILE) continue;
            if ($errs[$i] !== UPLOAD_ERR_OK || $sizes[$i] > $maxBytes) {
                flash('error', "Upload failed: {$names[$i]}");
                continue;
            }
            $mime = $types[$i];
            if (function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                $det = $fi ? finfo_file($fi, $tmps[$i]) : null;
                if ($fi) finfo_close($fi);
                if ($det) $mime = $det;
            }
            if (!in_array($mime, $allowed, true)) {
                flash('error', "Disallowed type: {$names[$i]} ({$mime})");
                continue;
            }
            $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $names[$i]);
            $unique = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '_' . $safe;
            $abs = $baseDir . '/' . $unique;
            if (!move_uploaded_file($tmps[$i], $abs)) {
                flash('error', "Could not save {$names[$i]}");
                continue;
            }
            $rel = 'uploads/supplier_attachments/' . $supplierId . '/' . $unique;
            $db->query(
                "INSERT INTO supplier_attachments
                    (supplier_id, doc_type, file_name, stored_path, file_size, mime_type, description, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$supplierId, sanitize($kinds[$i] ?? 'OTHER'), $names[$i], $rel,
                 (int)$sizes[$i], $mime, sanitize($descs[$i] ?? ''), $userId]
            );
        }
    }

    /** POST endpoint to upload additional supplier docs after creation. */
    public function uploadAttachments($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $id = (int)$id;
        $user = $this->getCurrentUser();
        $this->saveSupplierAttachments($id, (int)$user['user_id']);
        flash('success', __('pr_attach_uploaded'));
        $this->redirect('/master/suppliers/' . $id . '/edit');
    }

    /** Stream a supplier attachment. */
    public function downloadAttachment($id, $attId)
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $att = $db->fetch(
            "SELECT * FROM supplier_attachments
             WHERE attachment_id = ? AND supplier_id = ? AND is_deleted = FALSE",
            [(int)$attId, (int)$id]
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

    public function deleteAttachment($id, $attId)
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!Auth::isManagerOrAbove()) {
            flash('error', __('manager_only'));
            $this->redirect('/master/suppliers/' . (int)$id . '/edit'); return;
        }
        Database::getInstance()->query(
            "UPDATE supplier_attachments SET is_deleted = TRUE
             WHERE attachment_id = ? AND supplier_id = ?",
            [(int)$attId, (int)$id]
        );
        flash('success', __('pr_attach_deleted'));
        $this->redirect('/master/suppliers/' . (int)$id . '/edit');
    }
}
