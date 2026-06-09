<?php
/**
 * PEGASUS ERP - Sales Customer Management (営業向け顧客管理)
 *
 * - Customer list with sales stats (deal count, pipeline amount, last activity)
 * - Customer detail with contacts (from business cards) and uploaded cards
 * - Business-card image upload + client-side OCR assist (Tesseract.js)
 */

class SalesCustomerController extends Controller
{
    private const UPLOAD_DIR = '/uploads/business_cards';
    private const MAX_UPLOAD_BYTES = 8 * 1024 * 1024; // 8 MB
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    /** Customer list (sales perspective) */
    public function index()
    {
        $this->requireAuth();
        $this->requireAccess('sales');

        $filters = [
            'q'           => sanitize($_GET['q'] ?? ''),
            'sales_rep'   => sanitize($_GET['sales_rep'] ?? ''),
            'has_deals'   => sanitize($_GET['has_deals'] ?? ''),
        ];

        $sql = "SELECT c.customer_id, c.customer_code, c.customer_name, c.customer_name_jp, c.customer_name_th,
                       c.phone, c.email, c.contact_person, c.sales_rep_id,
                       e.full_name AS sales_rep_name,
                       (SELECT COUNT(*) FROM customer_contacts cc WHERE cc.customer_id = c.customer_id AND cc.is_deleted = FALSE) AS contact_count,
                       (SELECT COUNT(*) FROM deals d WHERE d.customer_id = c.customer_id AND d.is_deleted = FALSE) AS deal_count,
                       (SELECT COALESCE(SUM(d.expected_amount),0) FROM deals d
                          WHERE d.customer_id = c.customer_id AND d.is_deleted = FALSE) AS pipeline_amount,
                       (SELECT MAX(da.activity_date) FROM deal_activities da
                          JOIN deals d2 ON d2.deal_id = da.deal_id
                          WHERE d2.customer_id = c.customer_id) AS last_activity_at
                FROM customers c
                LEFT JOIN employees e ON e.employee_id = c.sales_rep_id
                WHERE c.is_deleted = FALSE AND c.is_current = TRUE";
        $params = [];

        if ($filters['q'] !== '') {
            $sql .= " AND (c.customer_name ILIKE ? OR c.customer_name_jp ILIKE ? OR c.customer_name_th ILIKE ?
                          OR c.customer_code ILIKE ? OR c.contact_person ILIKE ? OR c.email ILIKE ?)";
            $like = '%' . $filters['q'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        }
        if ($filters['sales_rep'] !== '') {
            $sql .= " AND c.sales_rep_id = ?";
            $params[] = $filters['sales_rep'];
        }
        if ($filters['has_deals'] === '1') {
            $sql .= " AND EXISTS (SELECT 1 FROM deals d WHERE d.customer_id = c.customer_id AND d.is_deleted = FALSE)";
        }

        $sql .= " ORDER BY c.customer_name";
        $customers = $this->db->fetchAll($sql, $params);

        $salesPersons = $this->db->fetchAll(
            "SELECT employee_id, full_name FROM employees WHERE is_deleted = FALSE ORDER BY full_name"
        );

        $this->render('sales/customers', [
            'pageTitle'    => __('menu_sales_customers'),
            'customers'    => $customers ?: [],
            'filters'      => $filters,
            'salesPersons' => $salesPersons ?: [],
        ]);
    }

    /** Customer detail: contacts, business cards, deal summary */
    public function show($id)
    {
        $this->requireAuth();
        $this->requireAccess('sales');

        $customer = $this->db->fetch(
            "SELECT c.*, e.full_name AS sales_rep_name
             FROM customers c
             LEFT JOIN employees e ON e.employee_id = c.sales_rep_id
             WHERE c.customer_id = ? AND c.is_deleted = FALSE",
            [$id]
        );
        if (!$customer) {
            flash('error', 'Customer not found');
            $this->redirect('/sales/customers');
        }

        $contacts = $this->db->fetchAll(
            "SELECT cc.*,
                    (SELECT COALESCE(bc.thumbnail_path, bc.file_path) FROM business_cards bc
                      WHERE bc.contact_id = cc.contact_id
                      ORDER BY bc.uploaded_at DESC LIMIT 1) AS latest_card,
                    (SELECT bc.file_path FROM business_cards bc
                      WHERE bc.contact_id = cc.contact_id
                      ORDER BY bc.uploaded_at DESC LIMIT 1) AS latest_card_full
             FROM customer_contacts cc
             WHERE cc.customer_id = ? AND cc.is_deleted = FALSE
             ORDER BY cc.is_primary DESC, cc.full_name",
            [$id]
        );

        $cards = $this->db->fetchAll(
            "SELECT bc.*, cc.full_name AS contact_name
             FROM business_cards bc
             LEFT JOIN customer_contacts cc ON cc.contact_id = bc.contact_id
             WHERE bc.customer_id = ? OR cc.customer_id = ?
             ORDER BY bc.uploaded_at DESC",
            [$id, $id]
        );

        $deals = $this->db->fetchAll(
            "SELECT d.deal_id, d.deal_no, d.deal_name, d.expected_amount,
                    ds.status_name, ds.win_pct, ds.color
             FROM deals d
             LEFT JOIN deal_statuses ds ON ds.status_id = d.status_id
             WHERE d.customer_id = ? AND d.is_deleted = FALSE
             ORDER BY d.updated_at DESC
             LIMIT 50",
            [$id]
        );

        $this->render('sales/customer_detail', [
            'pageTitle' => $customer['customer_name'],
            'customer'  => $customer,
            'contacts'  => $contacts ?: [],
            'cards'     => $cards ?: [],
            'deals'     => $deals ?: [],
        ]);
    }

    /** Save contact (create or update) */
    public function saveContact($customerId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');


        $user = $this->getCurrentUser();
        $contactId = $_POST['contact_id'] ?? null;
        $data = [
            'full_name'       => sanitize($_POST['full_name'] ?? ''),
            'full_name_local' => sanitize($_POST['full_name_local'] ?? ''),
            'title'           => sanitize($_POST['title'] ?? ''),
            'department'      => sanitize($_POST['department'] ?? ''),
            'company_name'    => sanitize($_POST['company_name'] ?? ''),
            'email'           => sanitize($_POST['email'] ?? ''),
            'phone'           => sanitize($_POST['phone'] ?? ''),
            'mobile'          => sanitize($_POST['mobile'] ?? ''),
            'fax'             => sanitize($_POST['fax'] ?? ''),
            'address'         => sanitize($_POST['address'] ?? ''),
            'website'         => sanitize($_POST['website'] ?? ''),
            'is_primary'      => !empty($_POST['is_primary']),
            'notes'           => sanitize($_POST['notes'] ?? ''),
        ];

        if ($data['full_name'] === '') {
            flash('error', __('contact_name_required'));
            $this->redirect('/sales/customers/' . $customerId);
        }

        // De-duplicate primary
        if ($data['is_primary']) {
            $this->db->execute(
                "UPDATE customer_contacts SET is_primary = FALSE WHERE customer_id = ?",
                [$customerId]
            );
        }

        if ($contactId) {
            $this->db->execute(
                "UPDATE customer_contacts SET
                    full_name=?, full_name_local=?, title=?, department=?, company_name=?,
                    email=?, phone=?, mobile=?, fax=?, address=?, website=?, is_primary=?, notes=?,
                    updated_by=?, updated_at=NOW()
                 WHERE contact_id=? AND customer_id=?",
                [$data['full_name'], $data['full_name_local'], $data['title'], $data['department'], $data['company_name'],
                 $data['email'], $data['phone'], $data['mobile'], $data['fax'], $data['address'], $data['website'],
                 $data['is_primary'] ? 't' : 'f', $data['notes'],
                 $user['user_id'] ?? null, $contactId, $customerId]
            );
            $savedId = $contactId;
        } else {
            $savedId = $this->db->insert(
                "INSERT INTO customer_contacts
                  (customer_id, full_name, full_name_local, title, department, company_name,
                   email, phone, mobile, fax, address, website, is_primary, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                 RETURNING contact_id",
                [$customerId, $data['full_name'], $data['full_name_local'], $data['title'], $data['department'],
                 $data['company_name'], $data['email'], $data['phone'], $data['mobile'], $data['fax'],
                 $data['address'], $data['website'], $data['is_primary'] ? 't' : 'f', $data['notes'],
                 $user['user_id'] ?? null]
            );
        }

        // Attach uploaded business card (if provided)
        if (!empty($_FILES['card_image']['tmp_name'])) {
            $cardPath = $this->storeCardFile($_FILES['card_image']);
            $thumbPath = $cardPath ? $this->createThumbnail($cardPath) : null;
            if ($cardPath) {
                $this->db->execute(
                    "INSERT INTO business_cards
                      (contact_id, customer_id, file_path, thumbnail_path, file_name, file_size, mime_type, ocr_raw_text, uploaded_by)
                     VALUES (?,?,?,?,?,?,?,?,?)",
                    [$savedId, $customerId, $cardPath, $thumbPath,
                     $_FILES['card_image']['name'] ?? null,
                     $_FILES['card_image']['size'] ?? null,
                     $_FILES['card_image']['type'] ?? null,
                     sanitize($_POST['ocr_raw_text'] ?? ''),
                     $user['user_id'] ?? null]
                );
            }
        }

        flash('success', __('contact_saved'));
        $this->redirect('/sales/customers/' . $customerId);
    }

    /** Soft-delete contact */
    public function deleteContact($customerId, $contactId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');


        $this->db->execute(
            "UPDATE customer_contacts SET is_deleted = TRUE, updated_at = NOW()
             WHERE contact_id = ? AND customer_id = ?",
            [$contactId, $customerId]
        );
        flash('success', __('contact_deleted'));
        $this->redirect('/sales/customers/' . $customerId);
    }

    /** Delete uploaded card image */
    public function deleteCard($customerId, $cardId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');


        $card = $this->db->fetch("SELECT file_path FROM business_cards WHERE card_id = ?", [$cardId]);
        if ($card && !empty($card['file_path'])) {
            $fsPath = __DIR__ . '/../public' . $card['file_path'];
            if (is_file($fsPath)) @unlink($fsPath);
        }
        $this->db->execute("DELETE FROM business_cards WHERE card_id = ?", [$cardId]);
        flash('success', __('card_deleted'));
        $this->redirect('/sales/customers/' . $customerId);
    }

    /**
     * Upload a card image WITHOUT a contact form (quick upload from detail page).
     * Saves a contact stub that can be edited later.
     */
    public function uploadCard($customerId)
    {
        $this->requireAuth();
        $this->requireAccess('sales');


        $user = $this->getCurrentUser();

        if (empty($_FILES['card_image']['tmp_name'])) {
            flash('error', __('no_file_selected'));
            $this->redirect('/sales/customers/' . $customerId);
        }

        $cardPath = $this->storeCardFile($_FILES['card_image']);
        $thumbPath = $cardPath ? $this->createThumbnail($cardPath) : null;

        if (!$cardPath) {
            flash('error', __('upload_failed'));
            $this->redirect('/sales/customers/' . $customerId);
        }

        $this->db->execute(
            "INSERT INTO business_cards
              (contact_id, customer_id, file_path, thumbnail_path, file_name, file_size, mime_type, uploaded_by)
             VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)",
            [$customerId, $cardPath, $thumbPath,
             $_FILES['card_image']['name'] ?? null,
             $_FILES['card_image']['size'] ?? null,
             $_FILES['card_image']['type'] ?? null,
             $user['user_id'] ?? null]
        );

        flash('success', __('card_uploaded'));
        $this->redirect('/sales/customers/' . $customerId);
    }

    /** Validate + move uploaded image → /public/uploads/business_cards/ */
    private function storeCardFile(array $file): ?string
    {
        if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) return null;
        if (($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) return null;

        // Detect MIME: use fileinfo if available, fall back to $_FILES['type'] + extension check
        $mime = '';
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']) ?: '';
        }
        if (!$mime && function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $file['tmp_name']) ?: '';
            finfo_close($fi);
        }
        if (!$mime) {
            // Fall back to browser-reported type + extension whitelist
            $mime = $file['type'] ?? '';
            $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            $extMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            if (isset($extMap[$ext])) $mime = $extMap[$ext];
        }
        if (!in_array($mime, self::ALLOWED_MIME, true)) return null;

        $destDir = __DIR__ . '/../public' . self::UPLOAD_DIR;
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

        // Compress & resize original → max 1200px, JPEG 80% quality
        $base = 'card_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $dest = $destDir . '/' . $base;

        $this->compressImage($file['tmp_name'], $dest, 1200, 85);

        return self::UPLOAD_DIR . '/' . $base;
    }

    /** Create a small thumbnail (max 300px) for card-list display */
    private function createThumbnail(string $relPath): ?string
    {
        $srcFull = __DIR__ . '/../public' . $relPath;
        if (!is_file($srcFull)) return null;

        $thumbBase = 'thumb_' . basename($relPath);
        $destDir = __DIR__ . '/../public' . self::UPLOAD_DIR;
        $thumbDest = $destDir . '/' . $thumbBase;

        $this->compressImage($srcFull, $thumbDest, 400, 70);

        return self::UPLOAD_DIR . '/' . $thumbBase;
    }

    /**
     * GD: resize image to fit within $maxPx (longest edge) and save as JPEG
     */
    private function compressImage(string $src, string $dest, int $maxPx = 1200, int $quality = 85): bool
    {
        $info = @getimagesize($src);
        if (!$info) return false;

        [$w, $h, $type] = $info;
        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
            IMAGETYPE_PNG  => @imagecreatefrompng($src),
            IMAGETYPE_WEBP => @imagecreatefromwebp($src),
            default        => null,
        };
        if (!$img) return false;

        // Resize if exceeds max dimension
        if ($w > $maxPx || $h > $maxPx) {
            $ratio = min($maxPx / $w, $maxPx / $h);
            $nw = (int) round($w * $ratio);
            $nh = (int) round($h * $ratio);
            $resized = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $resized;
        }

        $ok = imagejpeg($img, $dest, $quality);
        imagedestroy($img);
        return $ok;
    }
}
