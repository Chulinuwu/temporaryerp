<?php
class DashboardController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $db = Database::getInstance();

        // KPI Data
        $kpi = $this->getKPIData($db);
        $cashflow = $this->getCashflowData($db);
        $pipeline = $this->getPipelineData($db);
        $topCustomers = $this->getTopCustomers($db);

        $this->render('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'kpi' => $kpi,
            'cashflow' => $cashflow,
            'pipeline' => $pipeline,
            'topCustomers' => $topCustomers
        ]);
    }

    private function getKPIData($db)
    {
        $currentMonth = date('Y-m');

        // Cash balance
        $cashBalance = $db->fetch(
            "SELECT COALESCE(SUM(CASE WHEN jl.debit_amount > 0 THEN jl.debit_amount ELSE -jl.credit_amount END), 0) as balance
             FROM journal_lines jl
             JOIN journal_entries je ON je.je_id = jl.je_id
             WHERE jl.account_code LIKE '11%' AND je.status = 'POSTED' AND je.is_deleted = FALSE"
        );

        // Monthly income (AR payments)
        $monthlyIncome = $db->fetch(
            "SELECT COALESCE(SUM(amount_thb), 0) as total
             FROM ar_payments WHERE is_deleted = FALSE
             AND TO_CHAR(payment_date, 'YYYY-MM') = ?",
            [$currentMonth]
        );

        // Monthly expense (AP payments)
        $monthlyExpense = $db->fetch(
            "SELECT COALESCE(SUM(amount_thb), 0) as total
             FROM ap_payments WHERE is_deleted = FALSE
             AND TO_CHAR(payment_date, 'YYYY-MM') = ?",
            [$currentMonth]
        );

        // Pipeline weighted
        $pipelineTotal = $db->fetch(
            "SELECT COALESCE(SUM(grand_total_thb), 0) as total
             FROM quotation_headers
             WHERE status IN ('SUBMITTED','NEGOTIATING') AND is_deleted = FALSE"
        );

        return [
            'cash_balance' => $cashBalance['balance'] ?? 0,
            'monthly_income' => $monthlyIncome['total'] ?? 0,
            'monthly_expense' => $monthlyExpense['total'] ?? 0,
            'pipeline_total' => $pipelineTotal['total'] ?? 0,
            'current_month' => date('M-Y')
        ];
    }

    private function getCashflowData($db)
    {
        $data = [];
        for ($i = 4; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $label = date('y-m', strtotime("-{$i} months"));

            $income = $db->fetch(
                "SELECT COALESCE(SUM(amount_thb), 0) as total
                 FROM ar_payments WHERE is_deleted = FALSE
                 AND TO_CHAR(payment_date, 'YYYY-MM') = ?",
                [$month]
            );
            $expense = $db->fetch(
                "SELECT COALESCE(SUM(amount_thb), 0) as total
                 FROM ap_payments WHERE is_deleted = FALSE
                 AND TO_CHAR(payment_date, 'YYYY-MM') = ?",
                [$month]
            );

            $data[] = [
                'month' => $label,
                'income' => (float)($income['total'] ?? 0),
                'expense' => (float)($expense['total'] ?? 0),
                'net' => (float)($income['total'] ?? 0) - (float)($expense['total'] ?? 0)
            ];
        }
        return $data;
    }

    private function getPipelineData($db)
    {
        $statuses = $db->fetchAll(
            "SELECT status, COUNT(*) as count, COALESCE(SUM(grand_total_thb), 0) as total
             FROM quotation_headers
             WHERE is_deleted = FALSE AND status NOT IN ('CANCELLED','LOST','EXPIRED')
             GROUP BY status ORDER BY total DESC"
        );
        return $statuses ?: [];
    }

    private function getTopCustomers($db)
    {
        $customers = $db->fetchAll(
            "SELECT c.customer_name, c.customer_name_jp, c.customer_name_th, COALESCE(SUM(qh.grand_total_thb), 0) as pipeline_total
             FROM quotation_headers qh
             JOIN customers c ON c.customer_id = qh.customer_id
             WHERE qh.is_deleted = FALSE AND qh.status IN ('SUBMITTED','NEGOTIATING','WON')
             GROUP BY c.customer_name, c.customer_name_jp, c.customer_name_th
             ORDER BY pipeline_total DESC LIMIT 8"
        );
        return $customers ?: [];
    }

    // API endpoints
    public function kpiData()
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $this->json($this->getKPIData($db));
    }

    public function cashflowData()
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $this->json($this->getCashflowData($db));
    }

    public function pipelineData()
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $this->json($this->getPipelineData($db));
    }
}
