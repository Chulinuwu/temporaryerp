<?php
/**
 * PEGASUS ERP — Sales KPI Dashboard + Master
 *
 * Mirrors "SUMMARY" sheet in Sales_Management_Tomas Tech_20260404_R1.xlsx
 *  - Per employee matrix of monthly Call / Meeting / etc counts
 *  - Vs targets from sales_kpi_targets × sales_kpi_monthly_pct
 *  - Profit progress vs annual_profit_target
 */
class SalesKpiController extends Controller
{
    /** /sales/kpi — Dashboard (Admin only) */
    public function dashboard()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/dashboard');
            return;
        }

        // Fiscal year default = current Japanese-style (Apr–Mar)
        $today  = new DateTime();
        $fyNow  = (int)$today->format('n') >= 4 ? (int)$today->format('Y') : (int)$today->format('Y') - 1;
        $fy     = intval($_GET['fy'] ?? $fyNow);

        // Months in the FY (Apr = 1, Mar = 12)
        $monthList = [];
        for ($i = 0; $i < 12; $i++) {
            $d = (new DateTime(sprintf('%d-04-01', $fy)))->modify("+{$i} months");
            $monthList[] = [
                'no'    => $i + 1,                 // 1..12
                'key'   => $d->format('Y-m'),      // "2026-04"
                'label' => $d->format('M'),        // "Apr"
            ];
        }
        $fyStart = sprintf('%d-04-01', $fy);
        $fyEnd   = sprintf('%d-03-31', $fy + 1);

        // All active sales persons with a KPI target (or with any activity)
        $employees = $this->db->fetchAll(
            "SELECT DISTINCT e.employee_id, e.full_name
             FROM employees e
             LEFT JOIN sales_kpi_targets k ON k.employee_id = e.employee_id AND k.fiscal_year = ?
             LEFT JOIN deal_activities da ON da.sales_person_id = e.employee_id
                    AND da.activity_date BETWEEN ? AND ?
             WHERE e.is_deleted = FALSE
               AND (k.kpi_id IS NOT NULL OR da.activity_id IS NOT NULL)
             ORDER BY e.full_name",
            [$fy, $fyStart, $fyEnd]
        ) ?: [];

        // Activity categories (rows)
        $categories = $this->db->fetchAll(
            "SELECT category_id, category_name, icon, sort_order
             FROM activity_categories
             WHERE is_deleted = FALSE
             ORDER BY sort_order, category_id"
        ) ?: [];

        // Count activities per (employee, category, YYYY-MM)
        $rows = $this->db->fetchAll(
            "SELECT da.sales_person_id,
                    da.activity_category_id,
                    TO_CHAR(da.activity_date, 'YYYY-MM') AS ym,
                    COUNT(*) AS n
             FROM deal_activities da
             WHERE da.activity_date BETWEEN ? AND ?
               AND da.sales_person_id IS NOT NULL
             GROUP BY da.sales_person_id, da.activity_category_id, TO_CHAR(da.activity_date, 'YYYY-MM')",
            [$fyStart, $fyEnd]
        );
        $counts = [];
        foreach ($rows as $r) {
            $counts[(int)$r['sales_person_id']][(int)$r['activity_category_id']][$r['ym']] = (int)$r['n'];
        }

        // Pull targets + monthly pct for each employee
        $targets = [];
        foreach ($employees as $emp) {
            $k = $this->db->fetch(
                "SELECT * FROM sales_kpi_targets WHERE employee_id = ? AND fiscal_year = ?",
                [$emp['employee_id'], $fy]
            );
            if (!$k) continue;
            $pctRows = $this->db->fetchAll(
                "SELECT month_no, pct FROM sales_kpi_monthly_pct WHERE kpi_id = ? ORDER BY month_no",
                [$k['kpi_id']]
            ) ?: [];
            $pctMap = [];
            foreach ($pctRows as $p) $pctMap[(int)$p['month_no']] = floatval($p['pct']);
            $targets[(int)$emp['employee_id']] = [
                'kpi_id'                => $k['kpi_id'],
                'annual_contact_target' => (int)$k['annual_contact_target'],
                'annual_meeting_target' => (int)$k['annual_meeting_target'],
                'annual_profit_target'  => floatval($k['annual_profit_target']),
                'monthly_pct'           => $pctMap,
            ];
        }

        // Profit actuals per employee × month (from won deals / booked SO)
        $profitRows = $this->db->fetchAll(
            "SELECT d.sales_person_id,
                    TO_CHAR(COALESCE(d.expected_close, d.updated_at::date), 'YYYY-MM') AS ym,
                    COALESCE(SUM(d.est_profit), 0) AS profit
             FROM deals d
             LEFT JOIN deal_statuses ds ON ds.status_id = d.status_id
             WHERE d.is_deleted = FALSE
               AND ds.win_pct = 100  -- closed-won only
               AND COALESCE(d.expected_close, d.updated_at::date) BETWEEN ? AND ?
             GROUP BY d.sales_person_id, TO_CHAR(COALESCE(d.expected_close, d.updated_at::date), 'YYYY-MM')",
            [$fyStart, $fyEnd]
        );
        $profit = [];
        foreach ($profitRows as $p) {
            if (!$p['sales_person_id']) continue;
            $profit[(int)$p['sales_person_id']][$p['ym']] = floatval($p['profit']);
        }

        $this->render('sales/kpi_dashboard', [
            'pageTitle'   => __('sales_kpi_dashboard'),
            'fy'          => $fy,
            'monthList'   => $monthList,
            'employees'   => $employees,
            'categories'  => $categories,
            'counts'      => $counts,
            'targets'     => $targets,
            'profit'      => $profit,
            'fyStart'     => $fyStart,
            'fyEnd'       => $fyEnd,
        ]);
    }

    /** /sales/kpi/master — edit targets (Admin only) */
    public function master()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/dashboard');
            return;
        }

        $fyNow = (int)date('n') >= 4 ? (int)date('Y') : (int)date('Y') - 1;
        $fy    = intval($_GET['fy'] ?? $fyNow);

        $targets = $this->db->fetchAll(
            "SELECT k.*, e.full_name
             FROM sales_kpi_targets k
             JOIN employees e ON e.employee_id = k.employee_id
             WHERE k.fiscal_year = ?
             ORDER BY e.full_name",
            [$fy]
        ) ?: [];

        // Load monthly pct per target
        $pctByKpi = [];
        foreach ($targets as $t) {
            $rows = $this->db->fetchAll(
                "SELECT month_no, pct FROM sales_kpi_monthly_pct WHERE kpi_id = ? ORDER BY month_no",
                [$t['kpi_id']]
            ) ?: [];
            foreach ($rows as $r) $pctByKpi[$t['kpi_id']][(int)$r['month_no']] = floatval($r['pct']);
        }

        $employees = $this->db->fetchAll(
            "SELECT employee_id, full_name FROM employees WHERE is_deleted = FALSE ORDER BY full_name"
        ) ?: [];

        $this->render('sales/kpi_master', [
            'pageTitle' => __('kpi_target_master'),
            'fy'        => $fy,
            'targets'   => $targets,
            'pctByKpi'  => $pctByKpi,
            'employees' => $employees,
        ]);
    }

    /** POST /sales/kpi/master/save — upsert one KPI row (Admin only) */
    public function saveTarget()
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            flash('error', __('admin_only_action'));
            $this->redirect('/dashboard');
            return;
        }
        $this->validateCsrf();

        $empId  = intval($_POST['employee_id'] ?? 0);
        $fy     = intval($_POST['fiscal_year'] ?? 0);
        if (!$empId || !$fy) {
            flash('error', __('msg_invalid_input'));
            $this->redirect('/sales/kpi/master');
            return;
        }

        $profit         = floatval($_POST['annual_profit_target'] ?? 0);
        $profitPerOrder = max(1, floatval($_POST['profit_per_order'] ?? 100000));
        $closeRate      = max(0.01, floatval($_POST['close_rate_pct'] ?? 5));
        $apptRate       = max(0.01, floatval($_POST['appt_rate_pct'] ?? 10));

        // Auto-compute cascade: profit → orders → meetings → contacts
        $orders   = (int) round($profit / $profitPerOrder);
        $meetings = (int) round($orders   / ($closeRate / 100));
        $contacts = (int) round($meetings / ($apptRate  / 100));

        $kpi = $this->db->fetch(
            "INSERT INTO sales_kpi_targets (employee_id, fiscal_year,
                 annual_profit_target, profit_per_order, annual_order_target,
                 close_rate_pct, annual_meeting_target,
                 appt_rate_pct, annual_contact_target)
             VALUES (?,?,?,?,?,?,?,?,?)
             ON CONFLICT (employee_id, fiscal_year) DO UPDATE SET
                annual_profit_target  = EXCLUDED.annual_profit_target,
                profit_per_order      = EXCLUDED.profit_per_order,
                annual_order_target   = EXCLUDED.annual_order_target,
                close_rate_pct        = EXCLUDED.close_rate_pct,
                annual_meeting_target = EXCLUDED.annual_meeting_target,
                appt_rate_pct         = EXCLUDED.appt_rate_pct,
                annual_contact_target = EXCLUDED.annual_contact_target,
                updated_at = NOW()
             RETURNING kpi_id",
            [$empId, $fy, $profit, $profitPerOrder, $orders,
             $closeRate, $meetings, $apptRate, $contacts]
        );
        $kpiId = $kpi['kpi_id'];

        // Monthly percentages
        $pcts = $_POST['pct'] ?? [];
        if (is_array($pcts)) {
            $this->db->query("DELETE FROM sales_kpi_monthly_pct WHERE kpi_id = ?", [$kpiId]);
            for ($m = 1; $m <= 12; $m++) {
                $v = floatval($pcts[$m] ?? (100 / 12));
                $this->db->query(
                    "INSERT INTO sales_kpi_monthly_pct (kpi_id, month_no, pct) VALUES (?,?,?)",
                    [$kpiId, $m, $v]
                );
            }
        }

        flash('success', __('kpi_saved'));
        $this->redirect('/sales/kpi/master?fy=' . $fy);
    }
}
