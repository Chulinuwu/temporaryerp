<?php
/**
 * PEGASUS ERP — Cashflow Forecast / Actual
 * Layout mirrors the "profit" sheet in quotation_list.xlsx:
 *   Rows:    deal statuses (with win_pct)     ← forecast
 *            AR/AP payment buckets             ← actual
 *   Columns: months (relative to today)
 *   Cells:   sum of quotation.grand_total_thb scheduled in that (status × month)
 */
class CashflowController extends Controller
{
    /** Forecast CF: quotation_headers × deal.expected_close × deal_status */
    public function forecast()
    {
        $this->requireAuth();
        $this->requireAccess('accounting');

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-t', strtotime('+12 months'));

        $months = $this->monthRange($from, $to);
        $statuses = $this->db->fetchAll(
            "SELECT status_id, status_name, win_pct, color
             FROM deal_statuses WHERE is_deleted = FALSE ORDER BY sort_order, status_id"
        ) ?: [];

        // #8/#13/#14: Aggregate by (status, YYYY-MM) using:
        //   1. Installment schedule (grand_total × % distributed from income_date forward)
        //   2. If no installments: full amount on expected_income_date
        //   3. If neither: fall back to expected_close / issue_date
        $quotations = $this->db->fetchAll(
            "SELECT qh.quotation_id, qh.grand_total_thb, qh.payment_term_id,
                    COALESCE(qh.expected_income_date, qh.expected_invoice_date,
                             d.expected_close, qh.issue_date) AS base_date,
                    d.status_id,
                    pt.installment_count
             FROM quotation_headers qh
             LEFT JOIN deals d ON d.deal_id = qh.deal_id
             LEFT JOIN payment_terms pt ON pt.term_id = qh.payment_term_id
             WHERE qh.is_deleted = FALSE
               AND (d.is_deleted = FALSE OR d.deal_id IS NULL)
               AND COALESCE(qh.expected_income_date, qh.expected_invoice_date,
                            d.expected_close, qh.issue_date) BETWEEN ? AND ?",
            [$from, $to]
        );

        // Preload all installments
        $installments = $this->db->fetchAll(
            "SELECT term_id, seq_no, percentage, credit_days
             FROM payment_term_installments ORDER BY term_id, seq_no"
        ) ?: [];
        $instByTerm = [];
        foreach ($installments as $ins) {
            $tid = $ins['term_id'] ?? null;
            if ($tid === null) continue;
            $instByTerm[(int)$tid][] = $ins;
        }

        // Build matrix: matrix[status_id][YYYY-MM]
        $matrix = [];
        foreach ($quotations as $qt) {
            $sid = (int)($qt['status_id'] ?? 0);
            $baseDate = $qt['base_date'] ?? null;
            $amount = floatval($qt['grand_total_thb']);
            if ($amount <= 0 || !$baseDate) continue;

            $termId = $qt['payment_term_id'] ?? null;
            $termInst = ($termId !== null && isset($instByTerm[(int)$termId])) ? $instByTerm[(int)$termId] : [];

            if (!empty($termInst)) {
                // Split by installments; each installment falls on base_date + credit_days offset
                foreach ($termInst as $ins) {
                    $offset = intval($ins['credit_days'] ?? 0);
                    $cellDate = date('Y-m', strtotime($baseDate . " +{$offset} days"));
                    $cellAmt = $amount * floatval($ins['percentage']) / 100;
                    if (!isset($matrix[$sid][$cellDate])) {
                        $matrix[$sid][$cellDate] = ['amount' => 0, 'qt_count' => 0];
                    }
                    $matrix[$sid][$cellDate]['amount'] += $cellAmt;
                }
                // count once per quotation in the first month
                $firstMonth = date('Y-m', strtotime($baseDate));
                if (isset($matrix[$sid][$firstMonth])) {
                    $matrix[$sid][$firstMonth]['qt_count']++;
                }
            } else {
                // Lump-sum on base_date
                $cellDate = date('Y-m', strtotime($baseDate));
                if (!isset($matrix[$sid][$cellDate])) {
                    $matrix[$sid][$cellDate] = ['amount' => 0, 'qt_count' => 0];
                }
                $matrix[$sid][$cellDate]['amount'] += $amount;
                $matrix[$sid][$cellDate]['qt_count']++;
            }
        }

        // Column totals (per month)
        $colTotals = array_fill_keys($months, 0);
        $colWeighted = array_fill_keys($months, 0);
        foreach ($statuses as $s) {
            $sid = $s['status_id'];
            $pct = floatval($s['win_pct']) / 100;
            foreach ($months as $m) {
                $amt = $matrix[$sid][$m]['amount'] ?? 0;
                $colTotals[$m]   += $amt;
                $colWeighted[$m] += $amt * $pct;
            }
        }

        $this->render('cashflow/forecast', [
            'pageTitle'    => __('cashflow_forecast'),
            'months'       => $months,
            'statuses'     => $statuses,
            'matrix'       => $matrix,
            'colTotals'    => $colTotals,
            'colWeighted'  => $colWeighted,
            'filters'      => ['from' => $from, 'to' => $to],
        ]);
    }

    /** Actual CF: AR received / AP paid, by month */
    public function actual()
    {
        $this->requireAuth();
        $this->requireAccess('accounting');

        $from = $_GET['from'] ?? date('Y-m-01', strtotime('-11 months'));
        $to   = $_GET['to']   ?? date('Y-m-t');

        $months = $this->monthRange($from, $to);

        $arByMonth = $this->db->fetchAll(
            "SELECT TO_CHAR(payment_date, 'YYYY-MM') AS month,
                    COALESCE(SUM(amount_thb), 0) AS total
             FROM ar_payments
             WHERE is_deleted = FALSE AND payment_date BETWEEN ? AND ?
             GROUP BY TO_CHAR(payment_date, 'YYYY-MM')",
            [$from, $to]
        );
        $apByMonth = $this->db->fetchAll(
            "SELECT TO_CHAR(payment_date, 'YYYY-MM') AS month,
                    COALESCE(SUM(amount_thb), 0) AS total
             FROM ap_payments
             WHERE is_deleted = FALSE AND payment_date BETWEEN ? AND ?
             GROUP BY TO_CHAR(payment_date, 'YYYY-MM')",
            [$from, $to]
        );

        // Also include invoices issued but unpaid in each month (for comparison)
        $arIssued = $this->db->fetchAll(
            "SELECT TO_CHAR(invoice_date, 'YYYY-MM') AS month,
                    COALESCE(SUM(grand_total_thb), 0) AS total
             FROM ar_invoices
             WHERE is_deleted = FALSE AND invoice_date BETWEEN ? AND ?
             GROUP BY TO_CHAR(invoice_date, 'YYYY-MM')",
            [$from, $to]
        );
        $apIssued = $this->db->fetchAll(
            "SELECT TO_CHAR(invoice_date, 'YYYY-MM') AS month,
                    COALESCE(SUM(grand_total_thb), 0) AS total
             FROM ap_invoices
             WHERE is_deleted = FALSE AND invoice_date BETWEEN ? AND ?
             GROUP BY TO_CHAR(invoice_date, 'YYYY-MM')",
            [$from, $to]
        );

        $flat = fn($rows) => array_column($rows, 'total', 'month');

        $this->render('cashflow/actual', [
            'pageTitle' => __('cashflow_actual'),
            'months'    => $months,
            'arPaid'    => $flat($arByMonth),
            'apPaid'    => $flat($apByMonth),
            'arIssued'  => $flat($arIssued),
            'apIssued'  => $flat($apIssued),
            'filters'   => ['from' => $from, 'to' => $to],
        ]);
    }

    /** Returns ['YYYY-MM', ...] inclusive range */
    private function monthRange(string $from, string $to): array
    {
        $start = new DateTime(date('Y-m-01', strtotime($from)));
        $end   = new DateTime(date('Y-m-01', strtotime($to)));
        $months = [];
        while ($start <= $end) {
            $months[] = $start->format('Y-m');
            $start->modify('+1 month');
        }
        return $months;
    }
}
