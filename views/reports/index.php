<?php
/**
 * PEGASUS ERP - Report Center
 * Variables: (none required)
 */
extract($viewData ?? []);

// Each "url" points to an existing operational page where possible;
// otherwise to a "coming soon" stub. Report Center is the navigation hub.
$reportCategories = [
    [
        'title'  => 'Sales Reports',
        'icon'   => '&#128200;',
        'color'  => 'var(--color-primary)',
        'bg'     => 'var(--color-primary-light)',
        'reports' => [
            ['name' => 'Sales by Customer',  'url' => '/analytics/quotations',  'desc' => 'Revenue breakdown by customer / solution / period'],
            ['name' => 'Sales by Product',   'url' => '/analytics/quotations',  'desc' => 'Product-level sales analysis and ranking'],
            ['name' => 'Pipeline Analysis',  'url' => '/sales/pipeline',         'desc' => 'Quotation conversion rates and pipeline value'],
            ['name' => 'Cashflow Forecast',  'url' => '/cashflow/forecast',      'desc' => 'Expected income by status × month'],
        ],
    ],
    [
        'title'  => 'Financial Reports',
        'icon'   => '&#128176;',
        'color'  => 'var(--color-success)',
        'bg'     => 'var(--color-success-bg)',
        'reports' => [
            ['name' => 'P&L Statement',  'url' => '/accounting/pl',             'desc' => 'Profit and loss by period and cost center'],
            ['name' => 'Balance Sheet',  'url' => '/accounting/bs',             'desc' => 'Assets, liabilities, and equity snapshot'],
            ['name' => 'General Ledger', 'url' => '/accounting/ledger',         'desc' => 'Account balances and transactions'],
            ['name' => 'Cash Flow',      'url' => '/cashflow/actual',           'desc' => 'AR receipts and AP disbursements by month'],
            ['name' => 'Trial Balance',  'url' => '/reports/stub?name=Trial+Balance', 'desc' => 'Account balances for selected period (coming soon)'],
        ],
    ],
    [
        'title'  => 'HR Reports',
        'icon'   => '&#128101;',
        'color'  => '#8E24AA',
        'bg'     => '#F3E5F5',
        'reports' => [
            ['name' => 'Attendance Summary', 'url' => '/hr/attendance',          'desc' => 'Monthly attendance, overtime, and late summary'],
            ['name' => 'Leave Balance',      'url' => '/hr/leave',               'desc' => 'Remaining leave entitlements per employee'],
            ['name' => 'Headcount',          'url' => '/hr/employees',           'desc' => 'Employee count by department, type, and nationality'],
        ],
    ],
    [
        'title'  => 'Inventory Reports',
        'icon'   => '&#128230;',
        'color'  => 'var(--color-warning)',
        'bg'     => 'var(--color-warning-bg)',
        'reports' => [
            ['name' => 'Stock Valuation',   'url' => '/inventory/stock',  'desc' => 'Current stock value by warehouse and category'],
            ['name' => 'Movement History',  'url' => '/inventory/stock',  'desc' => 'Stock in/out transactions'],
            ['name' => 'Aging Analysis',    'url' => '/reports/stub?name=Aging+Analysis',  'desc' => 'Inventory age brackets (coming soon)'],
        ],
    ],
    [
        'title'  => 'Purchasing Reports',
        'icon'   => '&#128722;',
        'color'  => '#26A69A',
        'bg'     => '#E0F2F1',
        'reports' => [
            ['name' => 'Purchasing Analytics', 'url' => '/analytics/purchasing', 'desc' => 'PO trends by supplier / period / status'],
            ['name' => 'PO List',              'url' => '/purchasing/orders',    'desc' => 'Purchase order register'],
        ],
    ],
    [
        'title'  => 'Tax Reports (Thailand)',
        'icon'   => '&#128220;',
        'color'  => 'var(--color-danger)',
        'bg'     => 'var(--color-danger-bg)',
        'reports' => [
            ['name' => 'PND3',  'url' => '/reports/stub?name=PND3',  'desc' => 'Withholding tax on individual service payments'],
            ['name' => 'PND53', 'url' => '/reports/stub?name=PND53', 'desc' => 'Withholding tax on corporate service payments'],
            ['name' => 'PP30',  'url' => '/reports/stub?name=PP30',  'desc' => 'Monthly VAT return summary'],
        ],
    ],
];
?>

<div class="page-header" style="margin-bottom:24px;">
    <h1 style="font-size:20px;font-weight:600;"><?= _e('report_list') ?></h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(380px, 1fr));gap:20px;">
    <?php foreach ($reportCategories as $cat): ?>
        <div class="card">
            <div class="card-header" style="border-bottom:2px solid <?= $cat['color'] ?>;">
                <h3 class="card-title" style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:20px;"><?= $cat['icon'] ?></span>
                    <?= e($cat['title']) ?>
                </h3>
            </div>
            <div class="card-body" style="padding:0;">
                <?php foreach ($cat['reports'] as $i => $report): ?>
                    <a href="<?= e($report['url']) ?>" style="display:block;padding:12px 20px;border-bottom:1px solid var(--color-border-light);text-decoration:none;transition:background 0.15s;">
                        <div style="font-weight:500;color:var(--color-text-primary);font-size:13px;">
                            <?= e($report['name']) ?>
                        </div>
                        <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px;">
                            <?= e($report['desc']) ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .card-body a:hover {
        background: #FAFAFA !important;
    }
    .card-body a:last-child {
        border-bottom: none !important;
    }
    @media (max-width: 768px) {
        div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>
