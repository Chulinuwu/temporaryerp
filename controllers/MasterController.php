<?php
/**
 * PEGASUS ERP - Master Data Controller
 * Handles: Divisions, Chart of Accounts, Items, Payment Terms, Banks
 */

class MasterController extends Controller
{
    // ── Divisions ────────────────────────────────────────────────────

    public function divisions()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $divisions = $db->fetchAll(
                "SELECT * FROM divisions WHERE is_deleted = FALSE ORDER BY division_name"
            );

            $this->render('master/divisions', [
                'pageTitle' => 'Divisions',
                'divisions' => $divisions ?: []
            ]);
        } catch (Exception $e) {
            error_log('MasterController::divisions - ' . $e->getMessage());
            flash('error', 'Failed to load divisions.');
            $this->render('master/divisions', [
                'pageTitle' => 'Divisions',
                'divisions' => []
            ]);
        }
    }

    public function saveDivision()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = sanitize($_POST['division_id'] ?? '');
            $divisionName = sanitize($_POST['division_name'] ?? '');
            $divisionCode = sanitize($_POST['division_code'] ?? '');
            $divisionType = sanitize($_POST['division_type'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            if (empty($divisionName)) {
                flash('error', 'Division name is required.');
                $this->redirect('/master/divisions');
                return;
            }

            // Auto-generate division_code for new divisions if not provided
            if (!$id && empty($divisionCode)) {
                $row = $db->fetch(
                    "SELECT division_code FROM divisions WHERE division_code LIKE 'DIV-%' ORDER BY division_code DESC LIMIT 1"
                );
                $nextNum = 1;
                if ($row && preg_match('/DIV-(\d+)/', $row['division_code'], $m)) {
                    $nextNum = intval($m[1]) + 1;
                }
                $divisionCode = sprintf('DIV-%04d', $nextNum);
            }

            if ($id) {
                $db->query(
                    "UPDATE divisions SET division_name = ?, division_code = ?, division_type = ?,
                     updated_by = ?, updated_at = NOW() WHERE division_id = ?",
                    [$divisionName, $divisionCode, $divisionType, $user['user_id'], $id]
                );
                flash('success', 'Division updated successfully.');
            } else {
                $db->query(
                    "INSERT INTO divisions (division_name, division_code, division_type, created_by)
                     VALUES (?, ?, ?, ?)",
                    [$divisionName, $divisionCode, $divisionType, $user['user_id']]
                );
                flash('success', 'Division created successfully.');
            }
        } catch (Exception $e) {
            error_log('MasterController::saveDivision - ' . $e->getMessage());
            flash('error', 'Failed to save division.');
        }

        $this->redirect('/master/divisions');
    }

    public function editDivision($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $division = $db->fetch(
                "SELECT * FROM divisions WHERE division_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$division) {
                flash('error', 'Division not found.');
                $this->redirect('/master/divisions');
                return;
            }

            $this->render('master/divisions', [
                'pageTitle' => 'Edit Division',
                'division' => $division
            ]);
        } catch (Exception $e) {
            error_log('MasterController::editDivision - ' . $e->getMessage());
            flash('error', 'Failed to load division.');
            $this->redirect('/master/divisions');
        }
    }

    public function deleteDivision($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE divisions SET is_deleted = TRUE, updated_by = ?, updated_at = NOW()
                 WHERE division_id = ?",
                [$user['user_id'], $id]
            );
            flash('success', 'Division deleted.');
        } catch (Exception $e) {
            error_log('MasterController::deleteDivision - ' . $e->getMessage());
            flash('error', 'Failed to delete division.');
        }

        $this->redirect('/master/divisions');
    }

    // ── Chart of Accounts ────────────────────────────────────────────

    public function accounts()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $search = sanitize($_GET['search'] ?? '');
            $accountType = sanitize($_GET['account_type'] ?? '');

            $sql = "SELECT * FROM accounts WHERE is_deleted = FALSE";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (account_code ILIKE ? OR account_name ILIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            if (!empty($accountType)) {
                $sql .= " AND account_type = ?";
                $params[] = $accountType;
            }

            $sql .= " ORDER BY account_code";

            $accounts = $db->fetchAll($sql, $params);

            $this->render('master/accounts', [
                'pageTitle' => 'Chart of Accounts',
                'accounts' => $accounts ?: [],
                'search' => $search,
                'accountType' => $accountType
            ]);
        } catch (Exception $e) {
            error_log('MasterController::accounts - ' . $e->getMessage());
            flash('error', 'Failed to load accounts.');
            $this->render('master/accounts', [
                'pageTitle' => 'Chart of Accounts',
                'accounts' => [],
                'search' => '',
                'accountType' => ''
            ]);
        }
    }

    public function saveAccount()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = sanitize($_POST['account_id'] ?? '');
            $accountCode = sanitize($_POST['account_code'] ?? '');
            $accountName = sanitize($_POST['account_name'] ?? '');
            $accountType = sanitize($_POST['account_type'] ?? '');
            $parentCode = sanitize($_POST['parent_code'] ?? '');
            $bsPl = sanitize($_POST['bs_pl'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            if (empty($accountCode) || empty($accountName) || empty($accountType)) {
                flash('error', 'Account code, name and type are required.');
                $this->redirect('/master/accounts');
                return;
            }

            if ($id) {
                $db->query(
                    "UPDATE accounts SET account_code = ?, account_name = ?, account_type = ?,
                     parent_code = ?, bs_pl = ?, updated_by = ?, updated_at = NOW()
                     WHERE account_id = ?",
                    [$accountCode, $accountName, $accountType, $parentCode ?: null, $bsPl, $user['user_id'], $id]
                );
                flash('success', 'Account updated successfully.');
            } else {
                $db->query(
                    "INSERT INTO accounts (account_code, account_name, account_type, parent_code, bs_pl, division_id, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$accountCode, $accountName, $accountType, $parentCode ?: null, $bsPl, $divisionId, $user['user_id']]
                );
                flash('success', 'Account created successfully.');
            }
        } catch (Exception $e) {
            error_log('MasterController::saveAccount - ' . $e->getMessage());
            flash('error', 'Failed to save account.');
        }

        $this->redirect('/master/accounts');
    }

    public function editAccount($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $account = $db->fetch(
                "SELECT * FROM accounts WHERE account_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$account) {
                flash('error', 'Account not found.');
                $this->redirect('/master/accounts');
                return;
            }

            $this->render('master/accounts', [
                'pageTitle' => 'Edit Account',
                'account' => $account
            ]);
        } catch (Exception $e) {
            error_log('MasterController::editAccount - ' . $e->getMessage());
            flash('error', 'Failed to load account.');
            $this->redirect('/master/accounts');
        }
    }

    // ── Items ─────────────────────────────────────────────────────────

    public function items()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $search = sanitize($_GET['search'] ?? '');

            $sql = "SELECT * FROM items WHERE is_deleted = FALSE";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (item_code ILIKE ? OR item_name ILIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $sql .= " ORDER BY item_code";

            $items = $db->fetchAll($sql, $params);

            $this->render('master/items', [
                'pageTitle' => 'Items',
                'items' => $items ?: [],
                'search' => $search
            ]);
        } catch (Exception $e) {
            error_log('MasterController::items - ' . $e->getMessage());
            flash('error', 'Failed to load items.');
            $this->render('master/items', [
                'pageTitle' => 'Items',
                'items' => [],
                'search' => ''
            ]);
        }
    }

    public function saveItem()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = sanitize($_POST['item_id'] ?? '');
            $itemCode = sanitize($_POST['item_code'] ?? '');
            $itemName = sanitize($_POST['item_name'] ?? '');
            $itemType = sanitize($_POST['item_type'] ?? 'RAW');
            $unit = sanitize($_POST['unit'] ?? 'EA');
            $unitPriceStd = floatval($_POST['unit_price_std'] ?? 0);
            $unitCostStd = floatval($_POST['unit_cost_std'] ?? 0);
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            if (empty($itemName)) {
                flash('error', 'Item name is required.');
                $this->redirect('/master/items');
                return;
            }

            // Auto-generate item_code for new items if not provided
            if (!$id && empty($itemCode)) {
                $row = $db->fetch(
                    "SELECT item_code FROM items WHERE item_code LIKE 'ITM-%' ORDER BY item_code DESC LIMIT 1"
                );
                $nextNum = 1;
                if ($row && preg_match('/ITM-(\d+)/', $row['item_code'], $m)) {
                    $nextNum = intval($m[1]) + 1;
                }
                $itemCode = sprintf('ITM-%04d', $nextNum);
            }

            if ($id) {
                // Version management: increment version on update
                $current = $db->fetch("SELECT version_no FROM items WHERE item_id = ?", [$id]);
                $newVersion = ($current['version_no'] ?? 0) + 1;

                $db->query(
                    "UPDATE items SET item_code = ?, item_name = ?, item_type = ?, unit = ?,
                     unit_price_std = ?, unit_cost_std = ?,
                     version_no = ?, updated_by = ?, updated_at = NOW()
                     WHERE item_id = ?",
                    [$itemCode, $itemName, $itemType, $unit, $unitPriceStd, $unitCostStd,
                     $newVersion, $user['user_id'], $id]
                );
                flash('success', 'Item updated successfully.');
            } else {
                $db->query(
                    "INSERT INTO items (item_code, item_name, item_type, unit, unit_price_std, unit_cost_std, division_id, version_no, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)",
                    [$itemCode, $itemName, $itemType, $unit, $unitPriceStd, $unitCostStd, $divisionId, $user['user_id']]
                );
                flash('success', 'Item created successfully.');
            }
        } catch (Exception $e) {
            error_log('MasterController::saveItem - ' . $e->getMessage());
            flash('error', 'Failed to save item.');
        }

        $this->redirect('/master/items');
    }

    public function editItem($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $item = $db->fetch(
                "SELECT * FROM items WHERE item_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$item) {
                flash('error', 'Item not found.');
                $this->redirect('/master/items');
                return;
            }

            $this->render('master/items', [
                'pageTitle' => 'Edit Item',
                'item' => $item
            ]);
        } catch (Exception $e) {
            error_log('MasterController::editItem - ' . $e->getMessage());
            flash('error', 'Failed to load item.');
            $this->redirect('/master/items');
        }
    }

    public function deleteItem($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE items SET is_deleted = TRUE, updated_by = ?, updated_at = NOW()
                 WHERE item_id = ?",
                [$user['user_id'], $id]
            );
            flash('success', 'Item deleted.');
        } catch (Exception $e) {
            error_log('MasterController::deleteItem - ' . $e->getMessage());
            flash('error', 'Failed to delete item.');
        }

        $this->redirect('/master/items');
    }

    // ── Payment Terms ─────────────────────────────────────────────────

    public function paymentTerms()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $terms = $db->fetchAll(
                "SELECT pt.*
                 FROM payment_terms pt
                 WHERE pt.is_deleted = FALSE
                 ORDER BY pt.display_order, pt.term_name_en"
            ) ?: [];

            // Load installments for each term (for edit modal JSON)
            foreach ($terms as &$term) {
                $term['installments'] = $db->fetchAll(
                    "SELECT * FROM payment_term_installments WHERE term_id = ? ORDER BY seq_no",
                    [$term['term_id']]
                ) ?: [];
            }
            unset($term);

            $this->render('master/payment_terms', [
                'pageTitle' => 'Payment Terms',
                'paymentTerms' => $terms,
            ]);
        } catch (Exception $e) {
            error_log('MasterController::paymentTerms - ' . $e->getMessage());
            flash('error', 'Failed to load payment terms.');
            $this->render('master/payment_terms', [
                'pageTitle' => 'Payment Terms',
                'paymentTerms' => [],
            ]);
        }
    }

    public function savePaymentTerm()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = sanitize($_POST['term_id'] ?? '');
            $termCode = sanitize($_POST['term_code'] ?? '');
            $termNameEn = sanitize($_POST['term_name_en'] ?? '');
            $creditDays = intval($_POST['credit_days'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            // Installment arrays from POST (nested format: installments[N][percentage], etc.)
            $installments = $_POST['installments'] ?? [];
            $termNameJp = sanitize($_POST['term_name_jp'] ?? '');
            $termNameTh = sanitize($_POST['term_name_th'] ?? '');

            if (empty($termNameEn)) {
                flash('error', 'Term name is required.');
                $this->redirect('/master/payment-terms');
                return;
            }

            // Auto-generate term_code for new payment terms if not provided
            if (!$id && empty($termCode)) {
                $row = $db->fetch(
                    "SELECT term_code FROM payment_terms WHERE term_code LIKE 'PMT-%' ORDER BY term_code DESC LIMIT 1"
                );
                $nextNum = 1;
                if ($row && preg_match('/PMT-(\d+)/', $row['term_code'], $m)) {
                    $nextNum = intval($m[1]) + 1;
                }
                $termCode = sprintf('PMT-%04d', $nextNum);
            }

            $db->beginTransaction();

            $installmentCount = count($installments);

            if ($id) {
                $db->query(
                    "UPDATE payment_terms SET term_code = ?, term_name_en = ?, term_name_jp = ?, term_name_th = ?,
                     installment_count = ?, credit_days = ?, notes = ?,
                     updated_by = ?, updated_at = NOW() WHERE term_id = ?",
                    [$termCode, $termNameEn, $termNameJp, $termNameTh,
                     $installmentCount, $creditDays, $notes, $user['user_id'], $id]
                );

                // Remove existing installments and re-insert
                $db->query("DELETE FROM payment_term_installments WHERE term_id = ?", [$id]);
                $termId = $id;
            } else {
                $row = $db->fetch(
                    "INSERT INTO payment_terms (term_code, term_name_en, term_name_jp, term_name_th,
                     installment_count, credit_days, notes, division_id, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING term_id",
                    [$termCode, $termNameEn, $termNameJp, $termNameTh,
                     $installmentCount, $creditDays, $notes, $divisionId, $user['user_id']]
                );
                $termId = $row['term_id'];
            }

            // Insert installments from nested array
            $seqNo = 0;
            foreach ($installments as $inst) {
                $pct = floatval($inst['percentage'] ?? 0);
                if ($pct <= 0) continue;
                $seqNo++;
                $db->query(
                    "INSERT INTO payment_term_installments (term_id, seq_no, percentage, credit_days,
                     description_en, description_jp, description_th, trigger_type)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $termId, $seqNo, $pct,
                        intval($inst['credit_days'] ?? 0),
                        sanitize($inst['description_en'] ?? ''),
                        sanitize($inst['description_jp'] ?? ''),
                        sanitize($inst['description_th'] ?? ''),
                        sanitize($inst['trigger_type'] ?? 'CUSTOM'),
                    ]
                );
            }

            $db->commit();
            flash('success', $id ? 'Payment term updated.' : 'Payment term created.');
        } catch (Exception $e) {
            $db->rollback();
            error_log('MasterController::savePaymentTerm - ' . $e->getMessage());
            flash('error', 'Failed to save payment term.');
        }

        $this->redirect('/master/payment-terms');
    }

    public function editPaymentTerm($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $term = $db->fetch(
                "SELECT * FROM payment_terms WHERE term_id = ? AND is_deleted = FALSE",
                [$id]
            );

            if (!$term) {
                // Return JSON for AJAX requests
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    $this->json(['error' => 'Not found'], 404);
                    return;
                }
                flash('error', 'Payment term not found.');
                $this->redirect('/master/payment-terms');
                return;
            }

            $installments = $db->fetchAll(
                "SELECT * FROM payment_term_installments WHERE term_id = ? ORDER BY seq_no",
                [$id]
            ) ?: [];

            // If AJAX request, return JSON with installments embedded
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['json'])) {
                $term['installments'] = $installments;
                $this->json($term);
                return;
            }

            $this->render('master/payment_terms', [
                'pageTitle' => 'Edit Payment Term',
                'term' => $term,
                'installments' => $installments,
            ]);
        } catch (Exception $e) {
            error_log('MasterController::editPaymentTerm - ' . $e->getMessage());
            flash('error', 'Failed to load payment term.');
            $this->redirect('/master/payment-terms');
        }
    }

    public function deletePaymentTerm($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $user = $this->getCurrentUser();
            $db->query(
                "UPDATE payment_terms SET is_deleted = TRUE, updated_by = ?, updated_at = NOW()
                 WHERE term_id = ?",
                [$user['user_id'], $id]
            );
            flash('success', 'Payment term deleted.');
        } catch (Exception $e) {
            error_log('MasterController::deletePaymentTerm - ' . $e->getMessage());
            flash('error', 'Failed to delete payment term.');
        }

        $this->redirect('/master/payment-terms');
    }

    /**
     * API: Get payment term installments (JSON)
     */
    public function apiPaymentTermInstallments($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $term = $db->fetch(
                "SELECT term_id, term_code, term_name_en, term_name_jp, installment_count, credit_days
                 FROM payment_terms WHERE term_id = ? AND is_deleted = FALSE",
                [$id]
            );
            if (!$term) {
                $this->json(['error' => 'Not found'], 404);
                return;
            }

            $installments = $db->fetchAll(
                "SELECT seq_no, percentage, description_en, description_jp, description_th,
                        trigger_type, credit_days
                 FROM payment_term_installments
                 WHERE term_id = ?
                 ORDER BY seq_no",
                [$id]
            ) ?: [];

            // Synthesize a single 100% installment for simple terms (NET30/NET60/COD/PREPAID...)
            if (empty($installments)) {
                $installments = [[
                    'seq_no'         => 1,
                    'percentage'     => 100,
                    'description_en' => $term['term_name_en'] ?? 'Full payment',
                    'description_jp' => $term['term_name_jp'] ?? null,
                    'description_th' => null,
                    'trigger_type'   => 'INVOICE',
                    'credit_days'    => $term['credit_days'] ?? 0,
                ]];
            }

            $this->json([
                'term' => $term,
                'installments' => $installments,
            ]);
        } catch (Exception $e) {
            error_log('MasterController::apiPaymentTermInstallments - ' . $e->getMessage());
            $this->json(['error' => 'Server error'], 500);
        }
    }

    // ── Banks ──────────────────────────────────────────────────────────

    public function banks()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $search = sanitize($_GET['search'] ?? '');
            $sql = "SELECT * FROM banks WHERE is_deleted = FALSE";
            $params = [];
            if (!empty($search)) {
                $sql .= " AND (bank_code ILIKE ? OR bank_name ILIKE ? OR bank_name_th ILIKE ? OR swift_code ILIKE ?)";
                $params = ["%{$search}%","%{$search}%","%{$search}%","%{$search}%"];
            }
            $sql .= " ORDER BY bank_name";
            $banks = $db->fetchAll($sql, $params);

            $this->render('master/banks', [
                'pageTitle' => 'Banks',
                'banks' => $banks ?: [],
                'search' => $search
            ]);
        } catch (Exception $e) {
            error_log('MasterController::banks - ' . $e->getMessage());
            flash('error', 'Failed to load banks.');
            $this->render('master/banks', [
                'pageTitle' => 'Banks',
                'banks' => [],
                'search' => ''
            ]);
        }
    }

    public function saveBank()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();
        try {
            $id        = sanitize($_POST['bank_id'] ?? '');
            $code      = sanitize($_POST['bank_code'] ?? '');
            $name      = sanitize($_POST['bank_name'] ?? '');
            $nameTh    = sanitize($_POST['bank_name_th'] ?? '');
            $swift     = sanitize($_POST['swift_code'] ?? '');

            if (empty($code) || empty($name)) {
                flash('error', 'Bank code and name are required.');
                $this->redirect('/master/banks');
                return;
            }
            if ($id) {
                $db->query("UPDATE banks SET bank_code=?, bank_name=?, bank_name_th=?, swift_code=? WHERE bank_id=?",
                    [$code, $name, $nameTh, $swift, $id]);
                flash('success', 'Bank updated.');
            } else {
                $db->query("INSERT INTO banks (bank_code, bank_name, bank_name_th, swift_code) VALUES (?, ?, ?, ?)",
                    [$code, $name, $nameTh, $swift]);
                flash('success', 'Bank created.');
            }
        } catch (Exception $e) {
            error_log('MasterController::saveBank - ' . $e->getMessage());
            flash('error', 'Failed to save bank. ' . $e->getMessage());
        }
        $this->redirect('/master/banks');
    }

    public function deleteBank($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();
        try {
            $db->query("UPDATE banks SET is_deleted = TRUE WHERE bank_id = ?", [$id]);
            flash('success', 'Bank deleted.');
        } catch (Exception $e) {
            error_log('MasterController::deleteBank - ' . $e->getMessage());
            flash('error', 'Failed to delete bank.');
        }
        $this->redirect('/master/banks');
    }

    // ── API: Next Code ───────────────────────────────────────────────

    public function nextCode()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $type = sanitize($_GET['type'] ?? '');
        $nextCode = '';

        try {
            // Helper: find numeric MAX(suffix) for a given table/column/prefix.
            // Handles non-numeric codes (e.g. SUP-UNKNOWN, CUS-FUTABA) gracefully
            // by only considering ^PREFIX-[0-9]+$ rows.
            $nextNumeric = function($table, $col, $prefix) use ($db) {
                // SUBSTRING is 1-based: SUP-0194 → length(SUP)+1 = dash position;
                // need to skip the dash → start at length(prefix)+2
                $suffixStart = strlen($prefix) + 2;
                $row = $db->fetch(
                    "SELECT COALESCE(MAX(CAST(SUBSTRING($col FROM $suffixStart) AS INT)), 0) AS max_n
                     FROM $table
                     WHERE $col ~ ('^' || ? || '-[0-9]+$')",
                    [$prefix]
                );
                return intval($row['max_n'] ?? 0) + 1;
            };

            switch ($type) {
                case 'division':
                    $nextCode = sprintf('DIV-%04d', $nextNumeric('divisions', 'division_code', 'DIV'));
                    break;
                case 'item':
                    $nextCode = sprintf('ITM-%04d', $nextNumeric('items', 'item_code', 'ITM'));
                    break;
                case 'payment_term':
                    $nextCode = sprintf('PMT-%04d', $nextNumeric('payment_terms', 'term_code', 'PMT'));
                    break;
                case 'customer':
                    $nextCode = sprintf('CUS-%04d', $nextNumeric('customers', 'customer_code', 'CUS'));
                    break;
                case 'supplier':
                    $nextCode = sprintf('SUP-%04d', $nextNumeric('suppliers', 'supplier_code', 'SUP'));
                    break;
                default:
                    $this->json(['error' => 'Unknown type'], 400);
                    return;
            }
        } catch (Exception $e) {
            error_log('MasterController::nextCode - ' . $e->getMessage());
            $this->json(['error' => 'Failed to generate code'], 500);
            return;
        }

        $this->json(['code' => $nextCode]);
    }

    // ── API Endpoints ─────────────────────────────────────────────────

    public function searchItems()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $keyword = sanitize($_GET['q'] ?? '');

            if (strlen($keyword) < 1) {
                $this->json(['results' => []]);
                return;
            }

            $items = $db->fetchAll(
                "SELECT item_id, item_code, item_name, unit, unit_price_std, unit_cost_std
                 FROM items
                 WHERE is_deleted = FALSE AND is_current = TRUE
                 AND (item_code ILIKE ? OR item_name ILIKE ?)
                 ORDER BY item_code LIMIT 20",
                ["%{$keyword}%", "%{$keyword}%"]
            );

            $this->json(['results' => $items ?: []]);
        } catch (Exception $e) {
            error_log('MasterController::searchItems - ' . $e->getMessage());
            $this->json(['error' => 'Search failed.'], 500);
        }
    }

    public function searchAccounts()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $keyword = sanitize($_GET['q'] ?? '');

            if (strlen($keyword) < 1) {
                $this->json(['results' => []]);
                return;
            }

            $accounts = $db->fetchAll(
                "SELECT account_id, account_code, account_name, account_type
                 FROM accounts
                 WHERE is_deleted = FALSE
                 AND (account_code ILIKE ? OR account_name ILIKE ?)
                 ORDER BY account_code LIMIT 20",
                ["%{$keyword}%", "%{$keyword}%"]
            );

            $this->json(['results' => $accounts ?: []]);
        } catch (Exception $e) {
            error_log('MasterController::searchAccounts - ' . $e->getMessage());
            $this->json(['error' => 'Search failed.'], 500);
        }
    }

    // ── Deal Statuses ───────────────────────────────────────────────

    public function dealStatuses()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $statuses = $db->fetchAll(
            "SELECT * FROM deal_statuses WHERE is_deleted = FALSE ORDER BY sort_order"
        );

        $this->render('master/deal_statuses', [
            'pageTitle' => __('deal_statuses'),
            'statuses'  => $statuses ?: [],
        ]);
    }

    public function saveDealStatus()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = intval($_POST['status_id'] ?? 0);
            $name = sanitize($_POST['status_name'] ?? '');
            $nameJp = sanitize($_POST['status_name_jp'] ?? '');
            $nameTh = sanitize($_POST['status_name_th'] ?? '');
            $winPct = floatval($_POST['win_pct'] ?? 0);
            $sortOrder = intval($_POST['sort_order'] ?? 0);
            $color = sanitize($_POST['color'] ?? '#757575');

            if ($id > 0) {
                $db->query(
                    "UPDATE deal_statuses SET status_name=?, status_name_jp=?, status_name_th=?, win_pct=?, sort_order=?, color=? WHERE status_id=?",
                    [$name, $nameJp, $nameTh, $winPct, $sortOrder, $color, $id]
                );
            } else {
                $db->query(
                    "INSERT INTO deal_statuses (status_name, status_name_jp, status_name_th, win_pct, sort_order, color) VALUES (?,?,?,?,?,?)",
                    [$name, $nameJp, $nameTh, $winPct, $sortOrder, $color]
                );
            }
            flash('success', __('msg_saved'));
        } catch (Exception $e) {
            error_log('MasterController::saveDealStatus - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/master/deal-statuses');
    }

    public function deleteDealStatus($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $db->query("UPDATE deal_statuses SET is_deleted = TRUE WHERE status_id = ?", [$id]);
            flash('success', __('msg_saved'));
        } catch (Exception $e) {
            flash('error', __('msg_delete_error'));
        }
        $this->redirect('/master/deal-statuses');
    }

    // ── Solution Categories ─────────────────────────────────────────

    public function solutionCategories()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $categories = $db->fetchAll(
            "SELECT * FROM solution_categories WHERE is_deleted = FALSE ORDER BY sort_order"
        );

        $this->render('master/solution_categories', [
            'pageTitle'  => __('solution_categories'),
            'categories' => $categories ?: [],
        ]);
    }

    public function saveSolutionCategory()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $id = intval($_POST['category_id'] ?? 0);
            $name = sanitize($_POST['category_name'] ?? '');
            $nameJp = sanitize($_POST['category_name_jp'] ?? '');
            $nameTh = sanitize($_POST['category_name_th'] ?? '');
            $classification = sanitize($_POST['classification'] ?? '-');
            $evalProfitRate = floatval($_POST['eval_profit_rate'] ?? 0);
            $sortOrder = intval($_POST['sort_order'] ?? 0);

            if ($id > 0) {
                $db->query(
                    "UPDATE solution_categories SET category_name=?, category_name_jp=?, category_name_th=?, classification=?, eval_profit_rate=?, sort_order=? WHERE category_id=?",
                    [$name, $nameJp, $nameTh, $classification, $evalProfitRate, $sortOrder, $id]
                );
            } else {
                $db->query(
                    "INSERT INTO solution_categories (category_name, category_name_jp, category_name_th, classification, eval_profit_rate, sort_order) VALUES (?,?,?,?,?,?)",
                    [$name, $nameJp, $nameTh, $classification, $evalProfitRate, $sortOrder]
                );
            }
            flash('success', __('msg_saved'));
        } catch (Exception $e) {
            error_log('MasterController::saveSolutionCategory - ' . $e->getMessage());
            flash('error', __('msg_save_error'));
        }
        $this->redirect('/master/solution-categories');
    }

    public function deleteSolutionCategory($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $db->query("UPDATE solution_categories SET is_deleted = TRUE WHERE category_id = ?", [$id]);
            flash('success', __('msg_saved'));
        } catch (Exception $e) {
            flash('error', __('msg_delete_error'));
        }
        $this->redirect('/master/solution-categories');
    }

    // ── API: Solution Categories (JSON) ─────────────────────────────

    public function apiSolutionCategories()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $categories = $db->fetchAll(
                "SELECT category_id, category_name, classification, eval_profit_rate
                 FROM solution_categories WHERE is_deleted = FALSE ORDER BY category_id"
            );
            header('Content-Type: application/json');
            echo json_encode($categories ?: []);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([]);
        }
        exit;
    }

    // ── Translation API ──────────────────────────────────────────────

    /**
     * GET /api/translate?text=...&from=en&to=ja
     * Returns JSON: { translated: "..." }
     */
    public function apiTranslate()
    {
        $this->requireAuth();

        $text = $_GET['text'] ?? '';
        $from = $_GET['from'] ?? 'en';
        $to   = $_GET['to']   ?? 'ja';

        if (empty(trim($text))) {
            $this->json(['error' => 'No text provided'], 400);
            return;
        }

        // Validate language codes
        $allowed = ['en', 'ja', 'th'];
        if (!in_array($from, $allowed) || !in_array($to, $allowed)) {
            $this->json(['error' => 'Invalid language code'], 400);
            return;
        }

        $translated = googleTranslate($text, $from, $to);

        if ($translated) {
            $this->json(['translated' => $translated, 'from' => $from, 'to' => $to]);
        } else {
            $this->json(['error' => 'Translation service unavailable'], 500);
        }
    }

    /**
     * POST /api/translate-batch
     * Batch translate supplier/customer names.
     * Body: { type: "supplier"|"customer" }
     * Translates all records that have a TH name but missing EN/JP names (or vice versa).
     */
    public function apiTranslateBatch()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $type = $_GET['type'] ?? $_POST['type'] ?? 'supplier';

        if ($type === 'supplier') {
            $table = 'suppliers';
            $nameCol = 'supplier_name';
            $nameJp = 'supplier_name_jp';
            $nameTh = 'supplier_name_th';
            $idCol  = 'supplier_id';
        } else {
            $table = 'customers';
            $nameCol = 'customer_name';
            $nameJp = 'customer_name_jp';
            $nameTh = 'customer_name_th';
            $idCol  = 'customer_id';
        }

        $rows = $db->fetchAll(
            "SELECT {$idCol}, {$nameCol}, {$nameJp}, {$nameTh} FROM {$table} WHERE is_deleted = FALSE"
        );

        $updated = 0;
        $errors  = 0;

        foreach ($rows as $row) {
            $en = trim($row[$nameCol] ?? '');
            $jp = trim($row[$nameJp] ?? '');
            $th = trim($row[$nameTh] ?? '');
            $changes = [];

            // Determine what needs translating
            // If we have TH but no EN, translate TH->EN
            if ($th && !$en) {
                $result = googleTranslate($th, 'th', 'en');
                if ($result) $changes[$nameCol] = $result;
            }
            // If we have EN but no TH, translate EN->TH (only for non-Thai company names)
            // Skip: Thai companies already have Thai names from PDF import

            // If we have TH but no JP, translate TH->JA
            if ($th && !$jp) {
                $result = googleTranslate($th, 'th', 'ja');
                if ($result) $changes[$nameJp] = $result;
            }
            // If we have EN but no JP, translate EN->JA
            if ($en && !$jp && !$th) {
                $result = googleTranslate($en, 'en', 'ja');
                if ($result) $changes[$nameJp] = $result;
            }
            // If we have EN but no TH, translate EN->TH
            if ($en && !$th) {
                $result = googleTranslate($en, 'en', 'th');
                if ($result) $changes[$nameTh] = $result;
            }

            if (!empty($changes)) {
                $sets = [];
                $params = [];
                foreach ($changes as $col => $val) {
                    $sets[] = "{$col} = ?";
                    $params[] = $val;
                }
                $params[] = $row[$idCol];
                try {
                    $db->query(
                        "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$idCol} = ?",
                        $params
                    );
                    $updated++;
                } catch (Exception $e) {
                    $errors++;
                }
                // Rate limit to avoid hitting Google too fast
                usleep(200000); // 200ms
            }
        }

        $this->json([
            'success' => true,
            'total'   => count($rows),
            'updated' => $updated,
            'errors'  => $errors,
        ]);
    }

    /* =============== Quotation Category Master =============== */

    public function quotationCategories()
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT * FROM quotation_categories
             WHERE is_deleted = FALSE
             ORDER BY sort_order, name_jp"
        );
        $this->render('master/quotation_categories', [
            'pageTitle' => __('menu_quotation_categories'),
            'categories' => $rows ?: [],
        ]);
    }

    public function saveQuotationCategory()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();
        try {
            $id      = (int)($_POST['category_id'] ?? 0);
            $code    = sanitize($_POST['category_code'] ?? '');
            $nameJp  = sanitize($_POST['name_jp'] ?? '');
            $nameEn  = sanitize($_POST['name_en'] ?? '');
            $nameTh  = sanitize($_POST['name_th'] ?? '');
            $desc    = sanitize($_POST['description'] ?? '');
            $sort    = (int)($_POST['sort_order'] ?? 0);
            $coeff   = (float)($_POST['cost_coefficient'] ?? 1.0);
            if ($coeff < 0) $coeff = 1.0;
            $active  = !empty($_POST['is_active']) ? 'TRUE' : 'FALSE';

            if ($code === '' || $nameJp === '') {
                flash('error', __('msg_required_fields'));
                $this->redirect('/master/quotation-categories'); return;
            }
            $user = $this->getCurrentUser();

            if ($id > 0) {
                $db->query(
                    "UPDATE quotation_categories SET category_code=?, name_jp=?, name_en=?, name_th=?,
                         description=?, sort_order=?, cost_coefficient=?, is_active=?::boolean, updated_at=NOW()
                     WHERE category_id=?",
                    [$code, $nameJp, $nameEn, $nameTh, $desc, $sort, $coeff, $active, $id]
                );
            } else {
                $db->query(
                    "INSERT INTO quotation_categories
                       (category_code, name_jp, name_en, name_th, description, sort_order, cost_coefficient, is_active, created_by)
                     VALUES (?,?,?,?,?,?,?,?::boolean,?)",
                    [$code, $nameJp, $nameEn, $nameTh, $desc, $sort, $coeff, $active, $user['user_id'] ?? null]
                );
            }
            flash('success', __('msg_saved'));
        } catch (Exception $e) {
            error_log('MasterController::saveQuotationCategory - ' . $e->getMessage());
            flash('error', __('msg_save_error') . ': ' . $e->getMessage());
        }
        $this->redirect('/master/quotation-categories');
    }

    public function deleteQuotationCategory($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();
        try {
            $db->query("UPDATE quotation_categories SET is_deleted = TRUE WHERE category_id = ?", [(int)$id]);
            flash('success', __('msg_saved'));
        } catch (Exception $e) {
            flash('error', __('msg_delete_error'));
        }
        $this->redirect('/master/quotation-categories');
    }
}
