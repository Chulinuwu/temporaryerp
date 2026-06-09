<?php
/**
 * PEGASUS ERP - Accounts Receivable Controller
 * AR invoices, payments, and allocation
 */

class ARController extends Controller
{
    // -- Invoices --

    public function invoices()
    {
        $this->requireAuth();
        $this->requireAccess('ar');
        $db = Database::getInstance();

        try {
            $status = sanitize($_GET['status'] ?? '');

            $sql = "SELECT ai.*, c.customer_name
                    FROM ar_invoices ai
                    LEFT JOIN customers c ON c.customer_id = ai.customer_id
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

            $sql .= " ORDER BY ai.invoice_date DESC, ai.invoice_no DESC";

            $invoices = $db->fetchAll($sql, $params);

            $this->render('ar/invoices', [
                'pageTitle' => 'AR Invoices',
                'invoices' => $invoices ?: [],
                'status' => $status
            ]);
        } catch (Exception $e) {
            error_log('ARController::invoices - ' . $e->getMessage());
            flash('error', 'Failed to load invoices.');
            $this->render('ar/invoices', [
                'pageTitle' => 'AR Invoices',
                'invoices' => [],
                'status' => ''
            ]);
        }
    }

    public function createInvoice()
    {
        $this->requireAuth();
        $this->requireAccess('ar');
        $db = Database::getInstance();

        try {
            $soId = sanitize($_GET['so_id'] ?? '');
            $order = null;
            $orderLines = [];

            $installments = [];
            $existingInvoices = [];
            if ($soId) {
                // #9: pull payment term from SO → quotation → deal's quotation (fallback chain)
                $order = $db->fetch(
                    "SELECT so.*, c.customer_name, c.customer_id,
                            COALESCE(
                                so.payment_term_id,
                                qh.payment_term_id,
                                (SELECT qh2.payment_term_id FROM quotation_headers qh2
                                 WHERE qh2.deal_id = so.deal_id AND qh2.payment_term_id IS NOT NULL
                                   AND qh2.is_deleted = FALSE
                                 ORDER BY qh2.quotation_id DESC LIMIT 1)
                            ) AS payment_term_id,
                            pt.credit_days, pt.installment_count
                     FROM sales_order_headers so
                     LEFT JOIN customers c ON c.customer_id = so.customer_id
                     LEFT JOIN quotation_headers qh ON qh.quotation_id = so.quotation_id
                     LEFT JOIN payment_terms pt ON pt.term_id = COALESCE(so.payment_term_id, qh.payment_term_id)
                     WHERE so.so_id = ? AND so.is_deleted = FALSE",
                    [$soId]
                );

                if ($order) {
                    $orderLines = $db->fetchAll(
                        "SELECT sl.*, i.item_code, i.item_name
                         FROM sales_order_lines sl
                         LEFT JOIN items i ON i.item_id = sl.item_id
                         WHERE sl.so_id = ? ORDER BY sl.line_no",
                        [$soId]
                    );

                    // #9: load installment schedule for split invoicing
                    if (!empty($order['payment_term_id'])) {
                        $installments = $db->fetchAll(
                            "SELECT * FROM payment_term_installments
                             WHERE term_id = ? ORDER BY seq_no",
                            [$order['payment_term_id']]
                        ) ?: [];
                    }

                    // Track which installments have already been invoiced (single or bundled)
                    $existingInvoices = $db->fetchAll(
                        "SELECT invoice_id, invoice_no, installment_seq, installment_seqs, grand_total_thb, status
                         FROM ar_invoices
                         WHERE so_id = ? AND is_deleted = FALSE
                         ORDER BY installment_seq NULLS LAST, invoice_id",
                        [$soId]
                    ) ?: [];
                }
            }

            $customers = $db->fetchAll(
                "SELECT customer_id, customer_code, customer_name FROM customers WHERE is_deleted = FALSE ORDER BY customer_name"
            );
            $paymentTerms = $db->fetchAll(
                "SELECT * FROM payment_terms WHERE is_deleted = FALSE ORDER BY term_name_en"
            );
            // #9: list of confirmed sales orders (not yet fully invoiced) for selection
            $availableOrders = $db->fetchAll(
                "SELECT so.so_id, so.so_no, so.order_date, so.grand_total_thb, so.status,
                        c.customer_name, c.customer_name_jp, c.customer_name_th
                 FROM sales_order_headers so
                 LEFT JOIN customers c ON c.customer_id = so.customer_id
                 WHERE so.is_deleted = FALSE
                   AND so.status IN ('CONFIRMED','INVOICED')
                 ORDER BY so.so_no DESC LIMIT 200"
            );

            $this->render('ar/invoice_form', [
                'pageTitle'        => 'Create Invoice',
                'invoice'          => null,
                'lines'            => $orderLines ?: [],
                'order'            => $order,
                'customers'        => $customers ?: [],
                'paymentTerms'     => $paymentTerms ?: [],
                'availableOrders'  => $availableOrders ?: [],
                'installments'     => $installments,
                'existingInvoices' => $existingInvoices,
            ]);
        } catch (Exception $e) {
            error_log('ARController::createInvoice - ' . $e->getMessage());
            flash('error', 'Failed to load form data.');
            $this->redirect('/ar/invoices');
        }
    }

    public function storeInvoice()
    {
        $this->requireAuth();
        $this->requireAccess('ar');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $customerId = sanitize($_POST['customer_id'] ?? '');
            $invoiceDate = sanitize($_POST['invoice_date'] ?? '');
            $dueDate = sanitize($_POST['due_date'] ?? '');
            $soId = sanitize($_POST['so_id'] ?? '') ?: null;
            $paymentTermId = sanitize($_POST['payment_term_id'] ?? '') ?: null;
            $currencyCode = sanitize($_POST['currency_code'] ?? 'THB');
            $exchangeRate = floatval($_POST['exchange_rate'] ?? 1);
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            // #9: split invoicing — either a single seq (legacy) or an array of seqs (multi-select)
            $installmentSeqs = $_POST['installment_seqs'] ?? null;
            if (is_array($installmentSeqs)) {
                $installmentSeqs = array_values(array_filter(array_map('intval', $installmentSeqs), fn($n) => $n > 0));
            } else {
                $installmentSeqs = [];
            }
            // Fallback to single radio value if multi-select not used
            if (empty($installmentSeqs)) {
                $single = $_POST['installment_seq'] ?? '';
                if ($single !== '' && is_numeric($single)) $installmentSeqs = [(int)$single];
            }
            $installmentSeq = count($installmentSeqs) === 1 ? $installmentSeqs[0] : null;
            $installmentSeqsCsv = !empty($installmentSeqs) ? implode(',', $installmentSeqs) : null;
            $user = $this->getCurrentUser();

            // division_id is NOT NULL in ar_invoices — derive from SO or fallback to first division
            if (!$divisionId && $soId) {
                $soRow = $db->fetch("SELECT division_id FROM sales_order_headers WHERE so_id = ?", [$soId]);
                $divisionId = $soRow['division_id'] ?? null;
            }
            if (!$divisionId) {
                $divRow = $db->fetch("SELECT division_id FROM divisions ORDER BY division_id LIMIT 1");
                $divisionId = $divRow['division_id'] ?? 1;
            }

            // Compute credit_days from payment term (NOT NULL on ar_invoices)
            $creditDays = 0;
            if ($paymentTermId) {
                $ptRow = $db->fetch("SELECT credit_days FROM payment_terms WHERE term_id = ?", [$paymentTermId]);
                $creditDays = intval($ptRow['credit_days'] ?? 0);
            }
            if (!$creditDays && $invoiceDate && $dueDate) {
                $creditDays = (strtotime($dueDate) - strtotime($invoiceDate)) / 86400;
                $creditDays = max(0, (int)$creditDays);
            }

            $itemIds = $_POST['item_id'] ?? [];
            $descriptions = $_POST['item_description'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $units = $_POST['unit'] ?? [];
            $unitPrices = $_POST['unit_price'] ?? [];

            if (empty($customerId) || empty($invoiceDate) || empty($dueDate)) {
                flash('error', 'Customer, invoice date and due date are required.');
                $this->redirect('/ar/invoices/create');
                return;
            }

            $db->beginTransaction();

            $invoiceNo = $this->generateInvoiceNo($db);

            $vatRate = floatval($_POST['vat_rate'] ?? 7);

            // #9 Split invoicing: build lines from the linked quotation,
            //    scaled by the sum of selected installment percentages.
            if (!empty($installmentSeqs) && $soId) {
                $so = $db->fetch(
                    "SELECT subtotal_thb, grand_total_thb, quotation_id FROM sales_order_headers WHERE so_id = ?",
                    [$soId]
                );
                if (!$so) {
                    $db->rollback();
                    flash('error', __('installment_not_found'));
                    $this->redirect('/ar/invoices/create?so_id=' . $soId);
                    return;
                }

                // Sum selected percentages
                $placeholders = implode(',', array_fill(0, count($installmentSeqs), '?'));
                $instList = $db->fetchAll(
                    "SELECT seq_no, percentage, description_en, trigger_type
                     FROM payment_term_installments
                     WHERE term_id = ? AND seq_no IN ($placeholders)
                     ORDER BY seq_no",
                    array_merge([$paymentTermId], $installmentSeqs)
                );
                if (empty($instList)) {
                    $db->rollback();
                    flash('error', __('installment_not_found'));
                    $this->redirect('/ar/invoices/create?so_id=' . $soId);
                    return;
                }
                $totalPct = 0;
                $seqLabels = [];
                foreach ($instList as $ins) {
                    $totalPct += floatval($ins['percentage']);
                    $seqLabels[] = '#' . (int)$ins['seq_no'] . ' ' . number_format(floatval($ins['percentage']), 0) . '%';
                }
                $ratio = $totalPct / 100.0;

                // Pull line items — prefer SO's own lines, fallback to SO's quotation lines
                $sourceLines = $db->fetchAll(
                    "SELECT sol.line_no, sol.item_id, sol.item_description, sol.quantity,
                            sol.unit, sol.unit_price, sol.ext_price, i.item_code, i.item_name
                     FROM sales_order_lines sol
                     LEFT JOIN items i ON i.item_id = sol.item_id
                     WHERE sol.so_id = ?
                     ORDER BY sol.line_no",
                    [$soId]
                );
                if (empty($sourceLines) && !empty($so['quotation_id'])) {
                    $sourceLines = $db->fetchAll(
                        "SELECT ql.line_no, ql.item_id, ql.item_description, ql.quantity,
                                ql.unit, ql.unit_price, ql.ext_price, i.item_code, i.item_name
                         FROM quotation_lines ql
                         LEFT JOIN items i ON i.item_id = ql.item_id
                         WHERE ql.quotation_id = ?
                         ORDER BY ql.line_no",
                        [$so['quotation_id']]
                    );
                }

                $lineData = [];
                $subtotal = 0;
                if (!empty($sourceLines)) {
                    // Scale each line by the ratio (preserve original descriptions)
                    foreach ($sourceLines as $sl) {
                        $qty = floatval($sl['quantity'] ?? 0);
                        $origPrice = floatval($sl['unit_price'] ?? 0);
                        $scaledPrice = round($origPrice * $ratio, 2);
                        $lineAmount = round($scaledPrice * $qty, 2);
                        $lineData[] = [
                            'item_id' => $sl['item_id'] ?: null,
                            'item_description' => $sl['item_description'] ?? $sl['item_name'] ?? '',
                            'quantity' => $qty,
                            'unit' => $sl['unit'] ?? '',
                            'unit_price' => $scaledPrice,
                            'ext_price' => $lineAmount,
                        ];
                        $subtotal += $lineAmount;
                    }
                } else {
                    // No source lines — fallback to one synthetic line
                    $soSubtotal = floatval($so['subtotal_thb']);
                    $subtotal = round($soSubtotal * $ratio, 2);
                    $lineData[] = [
                        'item_id' => null,
                        'item_description' => 'Installments ' . implode(', ', $seqLabels),
                        'quantity' => 1,
                        'unit' => 'Lot',
                        'unit_price' => $subtotal,
                        'ext_price' => $subtotal,
                    ];
                }
                $vatAmount = round($subtotal * ($vatRate / 100), 2);
                $grandTotalThb = $subtotal + $vatAmount;
            } else {
                // Standard: use posted line items
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
                        'ext_price' => $extPrice,
                    ];
                }
                $vatAmount = $subtotal * ($vatRate / 100);
                $grandTotalThb = $subtotal + $vatAmount;
            }

            // Insert header (credit_days, total_after_discount are NOT NULL in schema)
            $totalAfterDiscount = $subtotal; // no discount yet
            $row = $db->fetch(
                "INSERT INTO ar_invoices
                 (invoice_no, invoice_date, due_date, credit_days, customer_id, so_id, division_id,
                  payment_term_id, installment_seq, installment_seqs,
                  currency_code, exchange_rate, subtotal_thb,
                  total_after_discount, vat_rate, vat_amount,
                  grand_total_thb, balance_thb, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'OPEN',?)
                 RETURNING invoice_id",
                [$invoiceNo, $invoiceDate, $dueDate, $creditDays, $customerId, $soId, $divisionId,
                 $paymentTermId, $installmentSeq, $installmentSeqsCsv,
                 $currencyCode, $exchangeRate, $subtotal,
                 $totalAfterDiscount, $vatRate, $vatAmount,
                 $grandTotalThb, $grandTotalThb, $user['user_id']]
            );
            $invoiceId = $row['invoice_id'];

            // Insert lines
            $lineNo = 1;
            foreach ($lineData as $line) {
                $db->query(
                    "INSERT INTO ar_invoice_lines
                     (invoice_id, line_no, item_id, item_description, quantity, unit, unit_price, ext_price)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [$invoiceId, $lineNo++, $line['item_id'], $line['item_description'],
                     $line['quantity'], $line['unit'], $line['unit_price'], $line['ext_price']]
                );
            }

            // #12 Auto-update SO status to INVOICED when an invoice is generated
            if ($soId) {
                $db->query(
                    "UPDATE sales_order_headers SET status = 'INVOICED', updated_at = NOW()
                     WHERE so_id = ? AND status = 'CONFIRMED'",
                    [$soId]
                );
            }

            $db->commit();
            flash('success', "Invoice {$invoiceNo} created.");
            $this->redirect('/ar/invoices/' . $invoiceId);
        } catch (Exception $e) {
            $db->rollback();
            error_log('ARController::storeInvoice - ' . $e->getMessage());
            flash('error', 'Failed to create invoice.');
            $this->redirect('/ar/invoices/create');
        }
    }

    public function showInvoice($id)
    {
        $this->requireAuth();
        $this->requireAccess('ar');
        $db = Database::getInstance();

        try {
            $invoice = $db->fetch(
                "SELECT ai.*, c.customer_name, c.address as customer_address,
                        c.tax_id as customer_tax_id, c.contact_person,
                        pt.term_name_en as payment_term_name
                 FROM ar_invoices ai
                 LEFT JOIN customers c ON c.customer_id = ai.customer_id
                 LEFT JOIN payment_terms pt ON pt.term_id = ai.payment_term_id
                 WHERE ai.invoice_id = ? AND ai.is_deleted = FALSE",
                [$id]
            );

            if (!$invoice) {
                flash('error', 'Invoice not found.');
                $this->redirect('/ar/invoices');
                return;
            }

            $lines = $db->fetchAll(
                "SELECT il.*, i.item_code, i.item_name
                 FROM ar_invoice_lines il
                 LEFT JOIN items i ON i.item_id = il.item_id
                 WHERE il.invoice_id = ? ORDER BY il.line_no",
                [$id]
            );

            // Payment history for this invoice
            $payments = $db->fetchAll(
                "SELECT apa.*, ap.payment_no, ap.payment_date
                 FROM ar_payment_allocations apa
                 JOIN ar_payments ap ON ap.payment_id = apa.payment_id
                 WHERE apa.invoice_id = ?
                 ORDER BY ap.payment_date",
                [$id]
            );

            $this->render('ar/invoice_detail', [
                'pageTitle' => 'Invoice ' . $invoice['invoice_no'],
                'invoice' => $invoice,
                'lines' => $lines ?: [],
                'payments' => $payments ?: []
            ]);
        } catch (Exception $e) {
            error_log('ARController::showInvoice - ' . $e->getMessage());
            flash('error', 'Failed to load invoice.');
            $this->redirect('/ar/invoices');
        }
    }

    public function updateInvoice($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $status = sanitize($_POST['status'] ?? '');
            $user = $this->getCurrentUser();

            $db->query(
                "UPDATE ar_invoices SET status = ?, updated_by = ?, updated_at = NOW()
                 WHERE invoice_id = ?",
                [$status, $user['user_id'], $id]
            );

            flash('success', 'Invoice updated.');
            $this->redirect('/ar/invoices/' . $id);
        } catch (Exception $e) {
            error_log('ARController::updateInvoice - ' . $e->getMessage());
            flash('error', 'Failed to update invoice.');
            $this->redirect('/ar/invoices/' . $id);
        }
    }

    // -- Payments --

    public function payments()
    {
        $this->requireAuth();
        $this->requireAccess('ar');
        $db = Database::getInstance();

        try {
            $payments = $db->fetchAll(
                "SELECT ap.*, c.customer_name
                 FROM ar_payments ap
                 LEFT JOIN customers c ON c.customer_id = ap.customer_id
                 WHERE ap.is_deleted = FALSE
                 ORDER BY ap.payment_date DESC, ap.payment_no DESC"
            );

            $this->render('ar/payments', [
                'pageTitle' => 'AR Payments',
                'payments' => $payments ?: []
            ]);
        } catch (Exception $e) {
            error_log('ARController::payments - ' . $e->getMessage());
            flash('error', 'Failed to load payments.');
            $this->render('ar/payments', [
                'pageTitle' => 'AR Payments',
                'payments' => []
            ]);
        }
    }

    public function storePayment()
    {
        $this->requireAuth();
        $this->requireAccess('ar');
        $this->validateCsrf();
        $db = Database::getInstance();

        try {
            $customerId = sanitize($_POST['customer_id'] ?? '');
            $paymentDate = sanitize($_POST['payment_date'] ?? '');
            $paymentMethod = sanitize($_POST['payment_method'] ?? '');
            $bankName = sanitize($_POST['bank_name'] ?? '');
            $referenceNo = sanitize($_POST['reference_no'] ?? '');
            $amountReceived = floatval($_POST['amount_received'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');
            $divisionId = sanitize($_POST['division_id'] ?? '') ?: null;
            $user = $this->getCurrentUser();

            // Allocation arrays
            $invoiceIds = $_POST['invoice_id'] ?? [];
            $allocAmounts = $_POST['alloc_amount'] ?? [];

            if (empty($customerId) || empty($paymentDate) || $amountReceived <= 0) {
                flash('error', 'Customer, payment date and amount are required.');
                $this->redirect('/ar/payments');
                return;
            }

            $db->beginTransaction();

            // Generate payment number
            $paymentNo = $this->generatePaymentNo($db, 'AR');

            // Insert payment
            $row = $db->fetch(
                "INSERT INTO ar_payments
                 (payment_no, division_id, payment_date, customer_id, payment_method, bank_name,
                  reference_no, amount_thb, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?)
                 RETURNING payment_id",
                [$paymentNo, $divisionId, $paymentDate, $customerId, $paymentMethod, $bankName,
                 $referenceNo, $amountReceived, $notes, $user['user_id']]
            );
            $paymentId = $row['payment_id'];

            // Allocate to invoices
            $totalAllocated = 0;
            for ($i = 0; $i < count($invoiceIds); $i++) {
                $invId = sanitize($invoiceIds[$i]);
                $allocAmt = floatval($allocAmounts[$i] ?? 0);

                if ($allocAmt <= 0 || empty($invId)) continue;

                $totalAllocated += $allocAmt;

                // Insert allocation
                $db->query(
                    "INSERT INTO ar_payment_allocations (payment_id, invoice_id, allocated_amount)
                     VALUES (?, ?, ?)",
                    [$paymentId, $invId, $allocAmt]
                );

                // Update invoice paid/balance
                $db->query(
                    "UPDATE ar_invoices SET
                     paid_amount_thb = paid_amount_thb + ?,
                     balance_thb = balance_thb - ?,
                     updated_at = NOW()
                     WHERE invoice_id = ?",
                    [$allocAmt, $allocAmt, $invId]
                );

                // Update invoice status
                $invoice = $db->fetch(
                    "SELECT balance_thb FROM ar_invoices WHERE invoice_id = ?",
                    [$invId]
                );

                $newBalance = floatval($invoice['balance_thb'] ?? 0);
                if ($newBalance <= 0.01) {
                    $newStatus = 'PAID';
                } else {
                    $newStatus = 'PARTIAL';
                }

                $db->query(
                    "UPDATE ar_invoices SET status = ?, updated_by = ?, updated_at = NOW()
                     WHERE invoice_id = ?",
                    [$newStatus, $user['user_id'], $invId]
                );
            }

            $db->commit();
            flash('success', "Payment {$paymentNo} recorded. Allocated: " . number_format($totalAllocated, 2));
            $this->redirect('/ar/payments');
        } catch (Exception $e) {
            $db->rollback();
            error_log('ARController::storePayment - ' . $e->getMessage());
            flash('error', 'Failed to record payment.');
            $this->redirect('/ar/payments');
        }
    }

    /**
     * Generate invoice number: IV-{YYYY}{MM}{NNNNNN}
     */
    private function generateInvoiceNo($db)
    {
        $yearMonth = date('Ym');
        $prefix = 'IV-' . $yearMonth;

        $row = $db->fetch(
            "SELECT invoice_no FROM ar_invoices
             WHERE invoice_no LIKE ?
             ORDER BY invoice_no DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($row) {
            $seq = intval(substr($row['invoice_no'], strlen($prefix))) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate payment number
     */
    private function generatePaymentNo($db, $type = 'AR')
    {
        $yearMonth = date('Ym');
        $prefix = 'RV-' . $yearMonth;
        $table = 'ar_payments';

        $row = $db->fetch(
            "SELECT payment_no FROM {$table}
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
