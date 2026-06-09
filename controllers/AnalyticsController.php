<?php
/**
 * PEGASUS ERP — Analytics Dashboards (#19/#20)
 *  - /analytics/quotations  : by probability / customer / solution / period
 *  - /analytics/purchasing  : by supplier / period
 */

class AnalyticsController extends Controller
{
    /** Quotation analytics dashboard */
    public function quotations()
    {
        $this->requireAuth();
        $this->requireAccess('sales');

        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-01-01'),
            'date_to'   => $_GET['date_to']   ?? date('Y-12-31'),
        ];

        // 1) By probability (deal_statuses.win_pct buckets)
        $byProbability = $this->db->fetchAll(
            "SELECT ds.status_name, ds.win_pct, ds.color,
                    COUNT(qh.quotation_id) AS cnt,
                    COALESCE(SUM(qh.grand_total_thb),0) AS amount
             FROM quotation_headers qh
             LEFT JOIN deals d ON d.deal_id = qh.deal_id
             LEFT JOIN deal_statuses ds ON ds.status_id = d.status_id
             WHERE qh.is_deleted = FALSE
               AND qh.issue_date BETWEEN ? AND ?
             GROUP BY ds.status_name, ds.win_pct, ds.color, ds.sort_order
             ORDER BY ds.sort_order",
            [$filters['date_from'], $filters['date_to']]
        );

        // 2) By customer (top 15)
        $byCustomer = $this->db->fetchAll(
            "SELECT c.customer_name, c.customer_name_jp, c.customer_name_th,
                    COUNT(qh.quotation_id) AS cnt,
                    COALESCE(SUM(qh.grand_total_thb),0) AS amount
             FROM quotation_headers qh
             LEFT JOIN customers c ON c.customer_id = qh.customer_id
             WHERE qh.is_deleted = FALSE
               AND qh.issue_date BETWEEN ? AND ?
             GROUP BY c.customer_name, c.customer_name_jp, c.customer_name_th
             ORDER BY amount DESC
             LIMIT 15",
            [$filters['date_from'], $filters['date_to']]
        );

        // 3) By solution category
        $bySolution = $this->db->fetchAll(
            "SELECT sc.category_name, sc.category_name_jp, sc.category_group,
                    COUNT(qh.quotation_id) AS cnt,
                    COALESCE(SUM(qh.grand_total_thb),0) AS amount
             FROM quotation_headers qh
             LEFT JOIN solution_categories sc ON sc.category_id = qh.solution_category_id
             WHERE qh.is_deleted = FALSE
               AND qh.issue_date BETWEEN ? AND ?
             GROUP BY sc.category_name, sc.category_name_jp, sc.category_group, sc.sort_order
             ORDER BY amount DESC",
            [$filters['date_from'], $filters['date_to']]
        );

        // 4) By month (period)
        $byMonth = $this->db->fetchAll(
            "SELECT TO_CHAR(qh.issue_date, 'YYYY-MM') AS month,
                    COUNT(qh.quotation_id) AS cnt,
                    COALESCE(SUM(qh.grand_total_thb),0) AS amount
             FROM quotation_headers qh
             WHERE qh.is_deleted = FALSE
               AND qh.issue_date BETWEEN ? AND ?
             GROUP BY TO_CHAR(qh.issue_date, 'YYYY-MM')
             ORDER BY month",
            [$filters['date_from'], $filters['date_to']]
        );

        // KPIs
        $kpi = $this->db->fetch(
            "SELECT COUNT(*) AS total_qt,
                    COALESCE(SUM(grand_total_thb),0) AS total_amount,
                    COALESCE(SUM(CASE WHEN status='WON' THEN grand_total_thb ELSE 0 END),0) AS won_amount,
                    COUNT(*) FILTER (WHERE status='WON') AS won_cnt
             FROM quotation_headers
             WHERE is_deleted = FALSE AND issue_date BETWEEN ? AND ?",
            [$filters['date_from'], $filters['date_to']]
        );

        $this->render('analytics/quotations', [
            'pageTitle'     => __('analytics_quotations'),
            'filters'       => $filters,
            'byProbability' => $byProbability ?: [],
            'byCustomer'    => $byCustomer ?: [],
            'bySolution'    => $bySolution ?: [],
            'byMonth'       => $byMonth ?: [],
            'kpi'           => $kpi ?: ['total_qt'=>0,'total_amount'=>0,'won_amount'=>0,'won_cnt'=>0],
        ]);
    }

    /** Purchasing analytics dashboard */
    public function purchasing()
    {
        $this->requireAuth();
        $this->requireAccess('purchasing');

        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-01-01'),
            'date_to'   => $_GET['date_to']   ?? date('Y-12-31'),
        ];

        // 1) By supplier (top 15)
        $bySupplier = $this->db->fetchAll(
            "SELECT s.supplier_name, s.supplier_name_jp, s.supplier_name_th,
                    COUNT(po.po_id) AS cnt,
                    COALESCE(SUM(po.payment_amount),0) AS amount
             FROM purchase_order_headers po
             LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id
             WHERE po.is_deleted = FALSE
               AND po.order_date BETWEEN ? AND ?
             GROUP BY s.supplier_name, s.supplier_name_jp, s.supplier_name_th
             ORDER BY amount DESC
             LIMIT 15",
            [$filters['date_from'], $filters['date_to']]
        );

        // 2) By month
        $byMonth = $this->db->fetchAll(
            "SELECT TO_CHAR(po.order_date, 'YYYY-MM') AS month,
                    COUNT(po.po_id) AS cnt,
                    COALESCE(SUM(po.payment_amount),0) AS amount
             FROM purchase_order_headers po
             WHERE po.is_deleted = FALSE
               AND po.order_date BETWEEN ? AND ?
             GROUP BY TO_CHAR(po.order_date, 'YYYY-MM')
             ORDER BY month",
            [$filters['date_from'], $filters['date_to']]
        );

        // 3) By status
        $byStatus = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS cnt, COALESCE(SUM(payment_amount),0) AS amount
             FROM purchase_order_headers
             WHERE is_deleted = FALSE AND order_date BETWEEN ? AND ?
             GROUP BY status
             ORDER BY status",
            [$filters['date_from'], $filters['date_to']]
        );

        // KPIs
        $kpi = $this->db->fetch(
            "SELECT COUNT(*) AS total_po,
                    COALESCE(SUM(payment_amount),0) AS total_amount,
                    COUNT(DISTINCT supplier_id) AS supplier_cnt
             FROM purchase_order_headers
             WHERE is_deleted = FALSE AND order_date BETWEEN ? AND ?",
            [$filters['date_from'], $filters['date_to']]
        );

        $this->render('analytics/purchasing', [
            'pageTitle'  => __('analytics_purchasing'),
            'filters'    => $filters,
            'bySupplier' => $bySupplier ?: [],
            'byMonth'    => $byMonth ?: [],
            'byStatus'   => $byStatus ?: [],
            'kpi'        => $kpi ?: ['total_po'=>0,'total_amount'=>0,'supplier_cnt'=>0],
        ]);
    }
}
