<?php
/**
 * PEGASUS ERP - Accounts Payable Controller
 * AP invoices, payments with WHT calculation, and allocation
 */

class APController extends Controller
{
    // ── Invoices ──────────────────────────────────────────────────────

    public function invoices()
    {
        $this->requireAuth();
        $this->requireAccess('ap');
        $db = Database::getInstance();

        try {
            $status = sanitize($_GET['status'] ?? '');

            $sql = "SELECT ai.*, s.supplier_name
                    FROM ap_invoices ai
                    LEFT JOIN suppliers s ON s.supplier_id = ai.supplier_id
                    WHERE ai.is_deleted = FALSE";
            $params = [];

            if (!empty($status)) {
                if ($status === 'OVERDUE') {
                    $sql .= " AND ai.status IN ('OPEN','PARTIAL') AND ai.due_date < CURRENT_DATE";
                } else {
                    $sql .= " AND ai.status = ?";
                    $params[] = $status;
                }
            }

            $sql .= " ORDER BY ai.invoice_date DESC, ai.ap_invoice_no DESC";

            $invoices = $db->fetchAll($sql, $params);

            $this->render('ap/invoices', [
                'pageTitle' => 'AP Invoices',
                'invoices' => $invoices ?: [],
                'status' => $status
            ]);
        } catch (Exception $e) {
            error_log('APController::invoices - ' . $e->getMessage());
            flash('error', 'Failed to load AP invoices.');
            $this->render('ap/invoices', [
                'pageTitle' => 'AP Invoices',
                'invoices' => [],
                'status' => ''
            ]);
        }
    }

    public function createInvoice()
    {
        $this->requireAuth();
        $this->requireAccess('ap');
        $db = Database::getInstance();

        try {
            $poId = sanitize($_GET['po_id'] ?? '');
            $order = null;
            $orderLines = [];

            if ($poId) {
                $order = $db->fetch(
                    "SELECT po.*, s.supplier_name, s.supplier_id
                     FROM purchase_order_headers po
                     LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
                     WHERE po.po_id = ? AND po.is_deleted = FALSE",
                    [$poId]
                );

                if ($order) {
                    $orderLines = $db->fetchAll(
                        "SELECT pl.*, i.item_code, i.item_name
                         FROM purchase_order_lines pl
                         LEFT JOIN items i ON i.item_id = pl.item_id
                         WHERE pl.po_id = ? ORDER BY pl.line_no",
                        [$poId]
                    );
                }
            }

            $suppliers = $db->fetchAll(
                "SELECT supplier_id, supplier_code, supplier_name FROM suppliers WHERE is_deleted = FALSE ORDER BY supplier_name"
            );
            $paymentTerms = $db->fetchAll(
                "SELECT * FROM payment_terms WHERE is_deleted = FALSE ORDER BY term_name_en"
            );

            $this->render('ap/invoice_form', [
                'pageTitle' => 'Create AP Invoice',
                'invoice' => null,
                'lines' => $orderLines ?: [],
                'order' => $order,
                'suppliers' => $suppliers ?: [],
                'paymentTerms' => $paymentTerms ?: []
            ]);
        } catch (Exception $e) {
            error_log('APController::createInvoice - ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/ap/invoices');
        }
    }

    public function storeInvoice()
    {
        $this->requireAuth();
        $this->requireAccess('ap');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $supplierId = sanitize($_POST['supplier_id'] ?? '');
            $supplierInvoiceNo = sanitize($_POST['supplier_invoice_no'] ?? '');
            $invoiceDate = sanitize($_POST['invoice_date'] ?? '');
            $dueDate = sanitize($_POST['due_date'] ?? '');
            $poId = sanitize($_POST['po_id'] ?? '') ?: null;
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            $itemIds = $_POST['item_id'] ?? [];
            $descriptions = $_POST['item_description'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $units = $_POST['unit'] ?? [];
            $unitPrices = $_POST['unit_price'] ?? [];

            if (empty($supplierId) || empty($invoiceDate) || empty($dueDate)) {
                flash('error', 'Supplier, invoice date and due date are required.');
                $this->redirect('/ap/invoices');
                return;
            }

            // Get supplier WHT rate
            $supplier = $db->fetch(
                "SELECT wht_rate FROM suppliers WHERE supplier_id = ?",
                [$supplierId]
            );
            $whtRate = floatval($supplier['wht_rate'] ?? 0);

            $db->beginTransaction();

            // Generate AP invoice number
            $apInvoiceNo = $this->generateApInvoiceNo($db);

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
            $whtAmount = $subtotal * ($whtRate / 100);
            $grandTotalThb = $subtotal + $vatAmount;

            // Insert header
            $row = $db->fetch(
                "INSERT INTO ap_invoices
                 (ap_invoice_no, invoice_date, due_date, supplier_id, po_id, division_id,
                  supplier_invoice_no, currency_code, subtotal_thb, vat_amount,
                  wht_amount, grand_total_thb, paid_amount_thb, balance_thb,
                  status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?,'OPEN',?)
                 RETURNING ap_invoice_id",
                [$apInvoiceNo, $invoiceDate, $dueDate, $supplierId, $poId, $divisionId,
                 $supplierInvoiceNo, $currencyCode, $subtotal, $vatAmount,
                 $whtAmount, $grandTotalThb, $grandTotalThb, $user['user_id']]
            );
            $apInvoiceId = $row['ap_invoice_id'];

            // Insert lines
            $lineNo = 1;
            foreach ($lineData as $line) {
                $db->query(
                    "INSERT INTO ap_invoice_lines
                     (ap_invoice_id, line_no, item_id, item_description, quantity, unit, unit_price, ext_price)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [$apInvoiceId, $lineNo++, $line['item_id'], $line['item_description'],
                     $line['quantity'], $line['unit'], $line['unit_price'], $line['ext_price']]
                );
            }

            $db->commit();
            flash('success', "AP Invoice {$apInvoiceNo} recorded.");
            $this->redirect('/ap/invoices/' . $apInvoiceId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('APController::storeInvoice - ' . $e->getMessage());
            flash('error', 'Failed to record AP invoice.');
            $this->redirect('/ap/invoices');
        }
    }

    public function showInvoice($id)
    {
        $this->requireAuth();
        $this->requireAccess('ap');
        $db = Database::getInstance();

        try {
            $invoice = $db->fetch(
                "SELECT ai.*, s.supplier_name, s.address as supplier_address,
                        s.tax_id as supplier_tax_id, s.contact_person,
                        pt.term_name_en as payment_term_name
                 FROM ap_invoices ai
                 LEFT JOIN suppliers s ON s.supplier_id = ai.supplier_id
                 LEFT JOIN payment_terms pt ON pt.term_id = ai.payment_term_id
                 WHERE ai.ap_invoice_id = ? AND ai.is_deleted = FALSE",
                [$id]
            );

            if (!$invoice) {
                flash('error', 'AP Invoice not found.');
                $this->redirect('/ap/invoices');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT il.*, i.item_code, i.item_name
                 FROM ap_invoice_lines il
                 LEFT JOIN items i ON i.item_id = il.item_id
                 WHERE il.ap_invoice_id = ? ORDER BY il.line_no",
                [$id]
            );

            $payments = $db->fetchAll(
                "SELECT apa.*, ap.payment_no, ap.payment_date
                 FROM ap_payment_allocations apa
                 JOIN ap_payments ap ON ap.payment_id = apa.payment_id
                 WHERE apa.ap_invoice_id = ?
                 ORDER BY ap.payment_date",
                [$id]
            );

            $this->render('ap/invoices', [
                'pageTitle' => 'AP Invoice ' . $invoice['ap_invoice_no'],
                'invoice' => $invoice,
                'lines' => $lines ?: [],
                'payments' => $payments ?: []
            ]);
        } catch (Exception $e) {
            error_log('APController::showInvoice - ' . $e->getMessage());
            flash('error', 'Failed to load AP invoice.');
            $this->redirect('/ap/invoices');
        }
    }

    // ── Payments ──────────────────────────────────────────────────────

    public function payments()
    {
        $this->requireAuth();
        $this->requireAccess('ap');
        $db = Database::getInstance();

        try {
            $payments = $db->fetchAll(
                "SELECT ap.*, s.supplier_name
                 FROM ap_payments ap
                 LEFT JOIN suppliers s ON s.supplier_id = ap.supplier_id
                 WHERE ap.is_deleted = FALSE
                 ORDER BY ap.payment_date DESC, ap.payment_no DESC"
            );

            $this->render('ap/payments', [
                'pageTitle' => 'AP Payments',
                'payments' => $payments ?: []
            ]);
        } catch (Exception $e) {
            error_log('APController::payments - ' . $e->getMessage());
            flash('error', 'Failed to load AP payments.');
            $this->render('ap/payments', [
                'pageTitle' => 'AP Payments',
                'payments' => []
            ]);
        }
    }

    public function storePayment()
    {
        $this->requireAuth();
        $this->requireAccess('ap');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $supplierId = sanitize($_POST['supplier_id'] ?? '');
            $paymentDate = sanitize($_POST['payment_date'] ?? '');
            $paymentMethod = sanitize($_POST['payment_method'] ?? '');
            $bankName = sanitize($_POST['bank_name'] ?? '');
            $referenceNo = sanitize($_POST['reference_no'] ?? '');
            $amountPaid = floatval($_POST['amount_paid'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            // Allocation arrays
            $invoiceIds = $_POST['ap_invoice_id'] ?? [];
            $allocAmounts = $_POST['alloc_amount'] ?? [];

            if (empty($supplierId) || empty($paymentDate) || $amountPaid <= 0) {
                flash('error', 'Supplier, payment date and amount are required.');
                $this->redirect('/ap/payments');
                return;
            }

            // Get supplier WHT rate for calculation
            $supplier = $db->fetch(
                "SELECT wht_rate FROM suppliers WHERE supplier_id = ?",
                [$supplierId]
            );
            $whtRate = floatval($supplier['wht_rate'] ?? 0);

            $db->beginTransaction();

            $paymentNo = $this->generatePaymentNo($db);

            // Calculate WHT on allocated amounts
            $totalAllocated = 0;
            $totalWht = 0;

            for ($i = 0; $i < count($invoiceIds); $i++) {
                $allocAmt = floatval($allocAmounts[$i] ?? 0);
                if ($allocAmt > 0) {
                    $totalAllocated += $allocAmt;
                    // WHT is calculated on the subtotal portion of allocation
                    // Approximate by backing out VAT
                    $baseAmount = $allocAmt / 1.07; // Assuming 7% VAT
                    $totalWht += $baseAmount * ($whtRate / 100);
                }
            }

            $whtAmount = round($totalWht, 2);

            // Insert payment
            $row = $db->fetch(
                "INSERT INTO ap_payments
                 (payment_no, division_id, payment_date, supplier_id, payment_method, bank_name,
                  reference_no, amount_thb, wht_amount, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)
                 RETURNING payment_id",
                [$paymentNo, $divisionId, $paymentDate, $supplierId, $paymentMethod, $bankName,
                 $referenceNo, $amountPaid, $whtAmount, $notes, $user['user_id']]
            );
            $paymentId = $row['payment_id'];

            // Allocate to invoices
            for ($i = 0; $i < count($invoiceIds); $i++) {
                $invId = sanitize($invoiceIds[$i]);
                $allocAmt = floatval($allocAmounts[$i] ?? 0);

                if ($allocAmt <= 0 || empty($invId)) continue;

                // Insert allocation
                $db->query(
                    "INSERT INTO ap_payment_allocations (payment_id, ap_invoice_id, allocated_amount)
                     VALUES (?, ?, ?)",
                    [$paymentId, $invId, $allocAmt]
                );

                // Update invoice paid/balance
                $db->query(
                    "UPDATE ap_invoices SET
                     paid_amount_thb = paid_amount_thb + ?,
                     balance_thb = balance_thb - ?,
                     updated_at = NOW()
                     WHERE ap_invoice_id = ?",
                    [$allocAmt, $allocAmt, $invId]
                );

                // Update invoice status
                $invoice = $db->fetch(
                    "SELECT balance_thb FROM ap_invoices WHERE ap_invoice_id = ?",
                    [$invId]
                );

                $newBalance = floatval($invoice['balance_thb'] ?? 0);
                $newStatus = ($newBalance <= 0.01) ? 'PAID' : 'PARTIAL';

                $db->query(
                    "UPDATE ap_invoices SET status = ?, updated_at = NOW()
                     WHERE ap_invoice_id = ?",
                    [$newStatus, $invId]
                );
            }

            $db->commit();
            flash('success', "Payment {$paymentNo} recorded. WHT: " . number_format($whtAmount, 2));
            $this->redirect('/ap/payments');
        } catch (Exception $e) {
            $db->rollback();
            error_log('APController::storePayment - ' . $e->getMessage());
            flash('error', 'Failed to record AP payment.');
            $this->redirect('/ap/payments');
        }
    }

    /**
     * Generate AP invoice number: AP-{YYYY}{MM}{NNNNNN}
     */
    private function generateApInvoiceNo($db)
    {
        $yearMonth = date('Ym');
        $prefix = 'AP-' . $yearMonth;

        $row = $db->fetch(
            "SELECT ap_invoice_no FROM ap_invoices
             WHERE ap_invoice_no LIKE ?
             ORDER BY ap_invoice_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['ap_invoice_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate AP payment number
     */
    private function generatePaymentNo($db)
    {
        $yearMonth = date('Ym');
        $prefix = 'PV-' . $yearMonth;

        $row = $db->fetch(
            "SELECT payment_no FROM ap_payments
             WHERE payment_no LIKE ?
             ORDER BY payment_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['payment_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
