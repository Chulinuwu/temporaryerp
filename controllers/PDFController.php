<?php
/**
 * PEGASUS ERP - PDF Controller
 * Generates print-ready HTML pages for quotations, sales orders, and invoices.
 * Uses browser print-to-PDF (window.print) instead of a PHP PDF library.
 */

class PDFController extends Controller
{
    /**
     * Render a print-ready quotation document.
     */
    public function quotationPdf($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            // Try full query with JOINs; fall back to simple query if columns are missing
            try {
                $header = $db->fetch(
                    "SELECT qh.*, c.customer_name, c.customer_name_th AS customer_name_local, c.address AS customer_address,
                            c.tax_id AS customer_tax_id, c.contact_person,
                            c.email AS customer_email, c.phone AS customer_phone,
                            pt.term_name_en AS payment_term_name, pt.credit_days,
                            COALESCE(emp_quoted.full_name, emp_created.full_name,
                                     (SELECT e2.full_name FROM employees e2
                                      WHERE e2.is_deleted = FALSE
                                        AND (LOWER(REPLACE(e2.full_name,' ','.')) = LOWER(u.username)
                                          OR LOWER(REPLACE(e2.full_name,' ','.')) = LOWER(REPLACE(u.username,'.',' '))
                                          OR LOWER(e2.email) = LOWER(u.email))
                                      LIMIT 1)) AS prepared_by_name,
                            emp_quoted.full_name AS in_charge_name,
                            COALESCE(
                                emp_approved.full_name,
                                emp_approved_via_user.full_name,
                                (SELECT e3.full_name FROM employees e3
                                 WHERE e3.is_deleted = FALSE AND u_appr.username IS NOT NULL
                                   AND (LOWER(REPLACE(e3.full_name,' ','.')) = LOWER(u_appr.username)
                                     OR LOWER(e3.full_name) ILIKE '%' || LOWER(split_part(u_appr.username,'.',1)) || '%'
                                        AND LOWER(e3.full_name) ILIKE '%' || LOWER(split_part(u_appr.username,'.',2)) || '%'
                                     OR LOWER(e3.email) = LOWER(u_appr.email))
                                 LIMIT 1)
                            ) AS approved_by_name
                     FROM quotation_headers qh
                     LEFT JOIN customers c ON c.customer_id = qh.customer_id
                     LEFT JOIN payment_terms pt ON pt.term_id = qh.payment_term_id
                     LEFT JOIN users u ON u.user_id = qh.created_by
                     LEFT JOIN employees emp_created ON emp_created.employee_id = u.employee_id
                     LEFT JOIN employees emp_quoted  ON emp_quoted.employee_id  = qh.quoted_by
                     LEFT JOIN employees emp_approved ON emp_approved.employee_id = qh.approved_by
                     LEFT JOIN users u_appr ON u_appr.user_id = qh.approved_by
                     LEFT JOIN employees emp_approved_via_user ON emp_approved_via_user.employee_id = u_appr.employee_id
                     WHERE qh.quotation_id = ?",
                    [$id]
                );
            } catch (Exception $queryEx) {
                // Fallback: simpler query without potentially missing columns
                $header = $db->fetch(
                    "SELECT qh.*, c.customer_name, c.address AS customer_address,
                            c.tax_id AS customer_tax_id, c.contact_person,
                            c.email AS customer_email, c.phone AS customer_phone
                     FROM quotation_headers qh
                     LEFT JOIN customers c ON c.customer_id = qh.customer_id
                     WHERE qh.quotation_id = ?",
                    [$id]
                );
            }

            if (!$header) {
                http_response_code(404);
                echo '<h1>Quotation not found</h1>';
                exit;
            }

            try {
                $lines = $db->fetchAll(
                    "SELECT ql.*
                     FROM quotation_lines ql
                     WHERE ql.quotation_id = ? AND (ql.is_deleted = FALSE OR ql.is_deleted IS NULL)
                     ORDER BY ql.sort_order ASC, ql.line_no ASC",
                    [$id]
                );
            } catch (Exception $linesEx) {
                // Fallback without sort_order/is_deleted if columns missing
                $lines = $db->fetchAll(
                    "SELECT ql.*
                     FROM quotation_lines ql
                     WHERE ql.quotation_id = ?
                     ORDER BY ql.line_no ASC",
                    [$id]
                );
            }

            // Count pages (estimate: ~15 items per page)
            $itemCount = count(array_filter($lines ?: [], fn($l) => empty($l['is_category_row'])));
            $totalPages = max(1, ceil($itemCount / 15));

            // Payment term installments (percentage + EN description)
            $paymentInstallments = [];
            if (!empty($header['payment_term_id'])) {
                try {
                    $paymentInstallments = $db->fetchAll(
                        "SELECT seq_no, percentage, description_en, trigger_type, credit_days
                         FROM payment_term_installments
                         WHERE term_id = ?
                         ORDER BY seq_no ASC",
                        [(int)$header['payment_term_id']]
                    ) ?: [];
                } catch (Exception $piEx) {
                    $paymentInstallments = [];
                }
            }

            $this->renderPdf('pdf/quotation', [
                'header'              => $header,
                'lines'               => $lines ?: [],
                'totalPages'          => $totalPages,
                'paymentInstallments' => $paymentInstallments,
                'pageTitle'           => e($header['quotation_no']),
            ]);
        } catch (Exception $e) {
            error_log('PDFController::quotationPdf - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            http_response_code(500);
            echo '<h1>Error generating quotation PDF</h1>';
            echo '<pre style="margin:20px;color:#c00;">' . htmlspecialchars($e->getMessage()) . '</pre>';
            exit;
        }
    }

    /**
     * Render a print-ready sales order document.
     */
    public function salesOrderPdf($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $header = $db->fetch(
                "SELECT so.*, c.customer_name, c.address AS customer_address,
                        c.tax_id AS customer_tax_id, c.contact_person,
                        c.email AS customer_email, c.phone AS customer_phone
                 FROM sales_order_headers so
                 LEFT JOIN customers c ON c.customer_id = so.customer_id
                 WHERE so.so_id = ?",
                [$id]
            );

            if (!$header) {
                http_response_code(404);
                echo '<h1>Sales Order not found</h1>';
                exit;
            }

            $lines = $db->fetchAll(
                "SELECT sl.*
                 FROM sales_order_lines sl
                 WHERE sl.so_id = ?
                 ORDER BY sl.line_no ASC",
                [$id]
            );

            $this->renderPdf('pdf/sales_order', [
                'header'    => $header,
                'lines'     => $lines ?: [],
                'pageTitle' => e($header['so_no']),
            ]);
        } catch (Exception $e) {
            error_log('PDFController::salesOrderPdf - ' . $e->getMessage());
            http_response_code(500);
            echo '<h1>Error generating sales order PDF</h1>';
            exit;
        }
    }

    /**
     * Render a print-ready purchase order document.
     */
    public function purchaseOrderPdf($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $header = $db->fetch(
                "SELECT po.*, s.supplier_name, s.address AS supplier_address,
                        s.tax_id AS supplier_tax_id, s.contact_person,
                        s.email AS supplier_email, s.phone AS supplier_phone
                 FROM purchase_order_headers po
                 LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
                 WHERE po.po_id = ?",
                [$id]
            );

            if (!$header) {
                http_response_code(404);
                echo '<h1>Purchase Order not found</h1>';
                exit;
            }

            $lines = $db->fetchAll(
                "SELECT pl.*
                 FROM purchase_order_lines pl
                 WHERE pl.po_id = ?
                 ORDER BY pl.line_no ASC",
                [$id]
            );

            $this->renderPdf('pdf/purchase_order', [
                'header'    => $header,
                'lines'     => $lines ?: [],
                'pageTitle' => e($header['po_no']),
            ]);
        } catch (Exception $e) {
            error_log('PDFController::purchaseOrderPdf - ' . $e->getMessage());
            http_response_code(500);
            echo '<h1>Error generating purchase order PDF</h1>';
            echo '<pre style="margin:20px;color:#c00;">' . htmlspecialchars($e->getMessage()) . '</pre>';
            exit;
        }
    }

    /**
     * Render a print-ready invoice document.
     */
    public function invoicePdf($id)
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $id = (int) $id;

            $header = $db->fetch(
                "SELECT ai.*, c.customer_name, c.customer_name_jp, c.customer_name_th,
                        c.address AS customer_address, c.tax_id AS customer_tax_id,
                        c.contact_person, c.email AS customer_email, c.phone AS customer_phone,
                        pt.term_name_en AS payment_term_name,
                        so.so_no, ai.po_reference AS so_po_no,
                        ins.description_en AS installment_desc,
                        ins.description_jp AS installment_desc_jp,
                        ins.description_th AS installment_desc_th,
                        ins.percentage   AS installment_pct,
                        ins.trigger_type AS installment_trigger,
                        e.full_name AS salesman_name
                 FROM ar_invoices ai
                 LEFT JOIN customers c ON c.customer_id = ai.customer_id
                 LEFT JOIN payment_terms pt ON pt.term_id = ai.payment_term_id
                 LEFT JOIN sales_order_headers so ON so.so_id = ai.so_id
                 LEFT JOIN payment_term_installments ins
                        ON ins.term_id = ai.payment_term_id
                       AND ins.seq_no = ai.installment_seq
                 LEFT JOIN employees e ON e.employee_id = ai.salesperson_id
                 WHERE ai.invoice_id = ?",
                [$id]
            );

            if (!$header) {
                http_response_code(404);
                echo '<h1>Invoice not found</h1>';
                exit;
            }

            $lines = $db->fetchAll(
                "SELECT il.*, i.item_code, i.item_name
                 FROM ar_invoice_lines il
                 LEFT JOIN items i ON i.item_id = il.item_id
                 WHERE il.invoice_id = ?
                 ORDER BY il.line_no ASC",
                [$id]
            );

            $this->renderPdf('pdf/invoice', [
                'header'    => $header,
                'lines'     => $lines ?: [],
                'pageTitle' => e($header['invoice_no']),
            ]);
        } catch (Exception $e) {
            error_log('PDFController::invoicePdf - ' . $e->getMessage());
            http_response_code(500);
            echo '<h1>Error generating invoice PDF</h1>';
            exit;
        }
    }

    /**
     * Handle bulk PDF generation.
     * Expects POST with 'type' (quotation|salesorder|invoice) and 'ids[]' array.
     * Renders all selected documents in a single printable page with page breaks.
     */
    public function bulkPdf()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        try {
            $type = sanitize($_POST['type'] ?? '');
            $ids  = $_POST['ids'] ?? [];

            if (!is_array($ids) || empty($ids)) {
                http_response_code(400);
                echo '<h1>No documents selected</h1>';
                exit;
            }

            if (!in_array($type, ['quotation', 'salesorder', 'invoice'], true)) {
                http_response_code(400);
                echo '<h1>Invalid document type</h1>';
                exit;
            }

            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, fn($id) => $id > 0);

            if (empty($ids)) {
                http_response_code(400);
                echo '<h1>No valid document IDs provided</h1>';
                exit;
            }

            $documents = [];

            foreach ($ids as $docId) {
                $doc = $this->fetchDocument($db, $type, $docId);
                if ($doc !== null) {
                    $documents[] = $doc;
                }
            }

            if (empty($documents)) {
                http_response_code(404);
                echo '<h1>No documents found</h1>';
                exit;
            }

            $this->renderPdf('pdf/bulk', [
                'documents' => $documents,
                'type'      => $type,
                'pageTitle' => __('bulk_print') . ' - ' . ucfirst($type),
            ]);
        } catch (Exception $e) {
            error_log('PDFController::bulkPdf - ' . $e->getMessage());
            http_response_code(500);
            echo '<h1>Error generating bulk PDF</h1>';
            exit;
        }
    }

    /**
     * Fetch a single document (header + lines) by type and ID.
     *
     * @return array|null ['header' => ..., 'lines' => ...] or null if not found
     */
    private function fetchDocument(Database $db, string $type, int $id): ?array
    {
        switch ($type) {
            case 'quotation':
                $header = $db->fetch(
                    "SELECT qh.*, c.customer_name, c.address AS customer_address,
                            c.tax_id AS customer_tax_id, c.contact_person,
                            c.email AS customer_email, c.phone AS customer_phone
                     FROM quotation_headers qh
                     LEFT JOIN customers c ON c.customer_id = qh.customer_id
                     WHERE qh.quotation_id = ?",
                    [$id]
                );
                if (!$header) {
                    return null;
                }
                $lines = $db->fetchAll(
                    "SELECT ql.*
                     FROM quotation_lines ql
                     WHERE ql.quotation_id = ?
                     ORDER BY ql.line_no ASC",
                    [$id]
                );
                return ['header' => $header, 'lines' => $lines ?: []];

            case 'salesorder':
                $header = $db->fetch(
                    "SELECT so.*, c.customer_name, c.address AS customer_address,
                            c.tax_id AS customer_tax_id, c.contact_person,
                            c.email AS customer_email, c.phone AS customer_phone
                     FROM sales_order_headers so
                     LEFT JOIN customers c ON c.customer_id = so.customer_id
                     WHERE so.so_id = ?",
                    [$id]
                );
                if (!$header) {
                    return null;
                }
                $lines = $db->fetchAll(
                    "SELECT sl.*
                     FROM sales_order_lines sl
                     WHERE sl.so_id = ?
                     ORDER BY sl.line_no ASC",
                    [$id]
                );
                return ['header' => $header, 'lines' => $lines ?: []];

            case 'invoice':
                $header = $db->fetch(
                    "SELECT ai.*, c.customer_name, c.address AS customer_address,
                            c.tax_id AS customer_tax_id, c.contact_person,
                            c.email AS customer_email, c.phone AS customer_phone
                     FROM ar_invoices ai
                     LEFT JOIN customers c ON c.customer_id = ai.customer_id
                     WHERE ai.invoice_id = ?",
                    [$id]
                );
                if (!$header) {
                    return null;
                }
                $lines = $db->fetchAll(
                    "SELECT il.*
                     FROM ar_invoice_lines il
                     WHERE il.invoice_id = ?
                     ORDER BY il.line_no ASC",
                    [$id]
                );
                return ['header' => $header, 'lines' => $lines ?: []];

            default:
                return null;
        }
    }

    /**
     * Render a view without the main application layout.
     * Outputs the view content directly and terminates execution.
     * Used for print-ready PDF pages that bypass the sidebar/nav chrome.
     */
    private function renderPdf(string $view, array $data): void
    {
        extract($data);

        $viewFile = BASE_PATH . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("PDF view not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        echo $content;
        exit;
    }
}
