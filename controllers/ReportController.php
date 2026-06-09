<?php
/**
 * PEGASUS ERP - Report Controller
 * Report list/menu with categories
 */

class ReportController extends Controller
{
    /**
     * Show report list/menu with categories
     */
    public function index()
    {
        $this->requireAuth();

        try {
            $reports = [
                'Financial Reports' => [
                    [
                        'name' => 'Trial Balance',
                        'description' => 'Current period trial balance with account balances',
                        'url' => '/reports/trial-balance',
                        'icon' => 'fas fa-balance-scale',
                        'roles' => ['ADMIN', 'FINANCE', 'ACCOUNTING']
                    ],
                    [
                        'name' => 'Profit & Loss Statement',
                        'description' => 'Income statement for selected period',
                        'url' => '/reports/profit-loss',
                        'icon' => 'fas fa-chart-line',
                        'roles' => ['ADMIN', 'FINANCE', 'ACCOUNTING']
                    ],
                    [
                        'name' => 'Balance Sheet',
                        'description' => 'Statement of financial position as of date',
                        'url' => '/reports/balance-sheet',
                        'icon' => 'fas fa-file-invoice-dollar',
                        'roles' => ['ADMIN', 'FINANCE', 'ACCOUNTING']
                    ],
                    [
                        'name' => 'Cash Flow Statement',
                        'description' => 'Cash inflows and outflows by activity',
                        'url' => '/reports/cash-flow',
                        'icon' => 'fas fa-money-bill-wave',
                        'roles' => ['ADMIN', 'FINANCE', 'ACCOUNTING']
                    ],
                    [
                        'name' => 'General Ledger',
                        'description' => 'Detailed journal entries by account',
                        'url' => '/reports/general-ledger',
                        'icon' => 'fas fa-book',
                        'roles' => ['ADMIN', 'FINANCE', 'ACCOUNTING']
                    ],
                ],
                'Sales Reports' => [
                    [
                        'name' => 'Sales Summary',
                        'description' => 'Sales totals by period, customer, or product',
                        'url' => '/reports/sales-summary',
                        'icon' => 'fas fa-chart-bar',
                        'roles' => ['ADMIN', 'SALES', 'MANAGER']
                    ],
                    [
                        'name' => 'Accounts Receivable Aging',
                        'description' => 'Outstanding customer invoices by aging bucket',
                        'url' => '/reports/ar-aging',
                        'icon' => 'fas fa-clock',
                        'roles' => ['ADMIN', 'FINANCE', 'SALES']
                    ],
                    [
                        'name' => 'Quotation Pipeline',
                        'description' => 'Active quotations with conversion analysis',
                        'url' => '/reports/quotation-pipeline',
                        'icon' => 'fas fa-funnel-dollar',
                        'roles' => ['ADMIN', 'SALES', 'MANAGER']
                    ],
                ],
                'Purchasing Reports' => [
                    [
                        'name' => 'Purchase Summary',
                        'description' => 'Purchase totals by period, supplier, or item',
                        'url' => '/reports/purchase-summary',
                        'icon' => 'fas fa-shopping-cart',
                        'roles' => ['ADMIN', 'PURCHASING', 'FINANCE']
                    ],
                    [
                        'name' => 'Accounts Payable Aging',
                        'description' => 'Outstanding supplier invoices by aging bucket',
                        'url' => '/reports/ap-aging',
                        'icon' => 'fas fa-file-invoice',
                        'roles' => ['ADMIN', 'FINANCE', 'PURCHASING']
                    ],
                    [
                        'name' => 'Supplier Performance',
                        'description' => 'Delivery times, quality, and pricing analysis',
                        'url' => '/reports/supplier-performance',
                        'icon' => 'fas fa-star',
                        'roles' => ['ADMIN', 'PURCHASING', 'MANAGER']
                    ],
                ],
                'Inventory Reports' => [
                    [
                        'name' => 'Stock Valuation',
                        'description' => 'Current inventory value by item and warehouse',
                        'url' => '/reports/stock-valuation',
                        'icon' => 'fas fa-warehouse',
                        'roles' => ['ADMIN', 'INVENTORY', 'FINANCE']
                    ],
                    [
                        'name' => 'Stock Movement',
                        'description' => 'Inventory transactions for selected period',
                        'url' => '/reports/stock-movement',
                        'icon' => 'fas fa-exchange-alt',
                        'roles' => ['ADMIN', 'INVENTORY']
                    ],
                    [
                        'name' => 'Reorder Report',
                        'description' => 'Items below minimum stock level',
                        'url' => '/reports/reorder',
                        'icon' => 'fas fa-exclamation-triangle',
                        'roles' => ['ADMIN', 'INVENTORY', 'PURCHASING']
                    ],
                ],
                'HR & Payroll Reports' => [
                    [
                        'name' => 'Payroll Summary',
                        'description' => 'Monthly payroll totals by department',
                        'url' => '/reports/payroll-summary',
                        'icon' => 'fas fa-money-check-alt',
                        'roles' => ['ADMIN', 'HR_MANAGER', 'PAYROLL']
                    ],
                    [
                        'name' => 'Attendance Summary',
                        'description' => 'Employee attendance and overtime analysis',
                        'url' => '/reports/attendance-summary',
                        'icon' => 'fas fa-user-clock',
                        'roles' => ['ADMIN', 'HR_MANAGER']
                    ],
                    [
                        'name' => 'Leave Balance Report',
                        'description' => 'Current leave balances for all employees',
                        'url' => '/reports/leave-balance',
                        'icon' => 'fas fa-calendar-minus',
                        'roles' => ['ADMIN', 'HR_MANAGER']
                    ],
                    [
                        'name' => 'SSO Report (SPS 1-10)',
                        'description' => 'Social Security Office monthly filing report',
                        'url' => '/reports/sso',
                        'icon' => 'fas fa-file-alt',
                        'roles' => ['ADMIN', 'HR_MANAGER', 'PAYROLL']
                    ],
                    [
                        'name' => 'PND1 Withholding Tax',
                        'description' => 'Monthly withholding tax summary for Revenue Dept.',
                        'url' => '/reports/pnd1',
                        'icon' => 'fas fa-receipt',
                        'roles' => ['ADMIN', 'PAYROLL', 'FINANCE']
                    ],
                ],
                'Production Reports' => [
                    [
                        'name' => 'Production Summary',
                        'description' => 'Manufacturing order completion and efficiency',
                        'url' => '/reports/production-summary',
                        'icon' => 'fas fa-industry',
                        'roles' => ['ADMIN', 'PRODUCTION', 'MANAGER']
                    ],
                    [
                        'name' => 'Cost Variance Analysis',
                        'description' => 'Standard vs actual cost comparison',
                        'url' => '/reports/cost-variance',
                        'icon' => 'fas fa-calculator',
                        'roles' => ['ADMIN', 'PRODUCTION', 'FINANCE']
                    ],
                    [
                        'name' => 'MRP Recommendations',
                        'description' => 'Open material purchase recommendations',
                        'url' => '/reports/mrp-recommendations',
                        'icon' => 'fas fa-tasks',
                        'roles' => ['ADMIN', 'PRODUCTION', 'PURCHASING']
                    ],
                ],
                'Tax & Compliance Reports' => [
                    [
                        'name' => 'VAT Report (PP30)',
                        'description' => 'Monthly VAT input/output summary for filing',
                        'url' => '/reports/vat-pp30',
                        'icon' => 'fas fa-percentage',
                        'roles' => ['ADMIN', 'FINANCE', 'ACCOUNTING']
                    ],
                    [
                        'name' => 'Withholding Tax Certificates',
                        'description' => 'WHT certificate listing for the period',
                        'url' => '/reports/wht-certificates',
                        'icon' => 'fas fa-certificate',
                        'roles' => ['ADMIN', 'FINANCE', 'ACCOUNTING']
                    ],
                ],
            ];

            // Filter reports based on user roles
            $currentUser = $this->getCurrentUser();
            $filteredReports = [];

            foreach ($reports as $category => $categoryReports) {
                $accessibleReports = [];
                foreach ($categoryReports as $report) {
                    // Admin can see all reports
                    if (Auth::hasRole('ADMIN')) {
                        $accessibleReports[] = $report;
                        continue;
                    }
                    // Check if user has any of the required roles
                    foreach ($report['roles'] as $role) {
                        if (Auth::hasRole($role)) {
                            $accessibleReports[] = $report;
                            break;
                        }
                    }
                }
                if (!empty($accessibleReports)) {
                    $filteredReports[$category] = $accessibleReports;
                }
            }

            $this->render('reports/index', [
                'pageTitle' => 'Reports',
                'reports' => $filteredReports
            ]);
        } catch (Exception $e) {
            error_log('ReportController::index error: ' . $e->getMessage());
            flash('error', 'Failed to load reports menu.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Placeholder for reports not yet built.
     * URL:  /reports/stub?name=<Report Name>
     */
    public function stub()
    {
        $this->requireAuth();
        $name = sanitize($_GET['name'] ?? 'Report');
        $this->render('reports/stub', [
            'pageTitle' => $name,
            'reportName' => $name,
        ]);
    }
}
