<?php
/**
 * PEGASUS ERP — System Test (ST)
 *
 * Runs health checks covering:
 *   1. All expected DB tables exist
 *   2. Required columns are present
 *   3. All HTTP routes respond (2xx/3xx, not 5xx)
 *   4. Critical data integrity (active users, KPI, etc.)
 *
 * Usage:  php database/system_test.php  [base_url] [email] [password]
 */

$baseUrl = $argv[1] ?? 'http://localhost:8080';
$email   = $argv[2] ?? 'nozaki.ryo@tomastc.com';
$passwd  = $argv[3] ?? 'admin123';

define('BASE_PATH', __DIR__ . '/..');
require_once BASE_PATH . '/core/Database.php';
$config = require BASE_PATH . '/config/database.php';
$db = Database::getInstance();

$pass = 0; $fail = 0; $warn = 0; $issues = [];

function banner($s) { echo "\n\033[1;34m═══ {$s} ═══\033[0m\n"; }
function ok($s)     { global $pass; $pass++; echo "  \033[32m[OK]\033[0m  {$s}\n"; }
function ng($s)     { global $fail, $issues; $fail++; $issues[] = $s; echo "  \033[31m[FAIL]\033[0m {$s}\n"; }
function wn($s)     { global $warn; $warn++; echo "  \033[33m[WARN]\033[0m {$s}\n"; }

// ─────────────────────────────────────────────────────────────
banner('1. Database Tables');
$expected = [
    'core' => ['users','employees','divisions','departments','customers','suppliers','items','accounts',
               'payment_terms','payment_term_installments','banks','solution_categories','deal_statuses',
               'activity_categories'],
    'sales' => ['deals','deal_activities','quotation_headers','quotation_lines',
                'sales_order_headers','sales_order_lines','customer_contacts','business_cards'],
    'purchasing' => ['purchase_order_headers','purchase_order_lines'],
    'inventory' => ['inventory_transactions','stock_balances','warehouses','shipment_headers','shipment_lines'],
    'accounting' => ['journal_entries','journal_lines','ar_invoices','ar_invoice_lines','ar_payments','ar_payment_allocations',
                     'ap_invoices','ap_invoice_lines','ap_payments','ap_payment_allocations'],
    'hr' => ['attendance_records','leave_requests','work_schedules','public_holidays'],
    'expense' => ['expense_claims','expense_claim_lines','expense_account_mapping'],
    'production' => ['mo_headers','mo_lines','bom_headers','bom_lines','mrp_snapshots','mrp_items','mrp_daily_quantities','mrp_purchase_recommendations'],
    'projects' => ['projects','project_invoices','project_purchases','project_progress','project_cost_items','project_payment_schedules','cost_sheets'],
    'admin' => ['audit_logs','roles','permissions','role_permissions'],
    'kpi' => ['sales_kpi_targets','sales_kpi_monthly_pct'],
    'fx' => ['currencies','exchange_rates'],
];
$existing = $db->fetchAll("SELECT tablename FROM pg_tables WHERE schemaname='public'");
$existing = array_column($existing, 'tablename');

foreach ($expected as $group => $tables) {
    foreach ($tables as $t) {
        if (in_array($t, $existing, true)) ok("table $t ($group)");
        else ng("MISSING table $t ($group)");
    }
}

// ─────────────────────────────────────────────────────────────
banner('2. Critical columns added by recent migrations');
$colChecks = [
    'customers' => ['customer_name_th','contact_person','address','approval_status'],
    'suppliers' => ['supplier_name_th'],
    'quotation_headers' => ['expected_invoice_date','expected_income_date','won_so_id'],
    'sales_order_headers' => ['deal_id','payment_term_id'],
    'purchase_order_headers' => ['project_id'],
    'ar_invoices' => ['installment_seq','installment_seqs','po_reference','salesperson_id'],
    'deals' => ['evaluation_profit_pct','customer_staff'],
    'deal_activities' => ['parent_activity_id','contact_id','start_time','end_time'],
    'business_cards' => ['thumbnail_path'],
    'sales_kpi_targets' => ['profit_per_order','close_rate_pct','appt_rate_pct','annual_order_target'],
    'divisions' => ['division_name_th'],
    'departments' => ['department_name_th'],
    'solution_categories' => ['category_group','evaluation_profit_pct'],
];
foreach ($colChecks as $tbl => $cols) {
    foreach ($cols as $c) {
        $r = $db->fetch(
            "SELECT 1 FROM information_schema.columns WHERE table_name=? AND column_name=?",
            [$tbl, $c]
        );
        if ($r) ok("$tbl.$c");
        else   ng("MISSING $tbl.$c");
    }
}

// ─────────────────────────────────────────────────────────────
banner('3. Master data sanity');
$checks = [
    ['SELECT COUNT(*) AS n FROM users WHERE is_active = TRUE', 'active users', 1],
    ['SELECT COUNT(*) AS n FROM customers WHERE is_deleted=FALSE', 'active customers', 100],
    ['SELECT COUNT(*) AS n FROM suppliers WHERE is_deleted=FALSE', 'active suppliers', 50],
    ['SELECT COUNT(*) AS n FROM items WHERE is_deleted=FALSE', 'active items', 0],
    ['SELECT COUNT(*) AS n FROM deal_statuses', 'deal statuses', 10],
    ['SELECT COUNT(*) AS n FROM solution_categories WHERE is_deleted=FALSE', 'solution categories', 20],
    ['SELECT COUNT(*) AS n FROM payment_terms WHERE is_deleted=FALSE', 'payment terms', 1],
    ['SELECT COUNT(*) AS n FROM exchange_rates WHERE is_deleted=FALSE', 'FX rates', 10],
    ['SELECT COUNT(*) AS n FROM roles', 'roles', 4],
    ['SELECT COUNT(*) AS n FROM permissions', 'permissions', 15],
    ['SELECT COUNT(*) AS n FROM role_permissions', 'role_permissions grants', 20],
    ['SELECT COUNT(*) AS n FROM sales_kpi_targets', 'KPI targets', 1],
    ['SELECT COUNT(*) AS n FROM activity_categories WHERE is_deleted=FALSE', 'activity categories', 3],
];
foreach ($checks as [$sql, $label, $min]) {
    $r = $db->fetch($sql);
    $n = (int)($r['n'] ?? 0);
    if ($n >= $min) ok("$label: $n rows");
    elseif ($n > 0) wn("$label: $n rows (expected ≥ $min)");
    else            ng("$label: 0 rows");
}

// ─────────────────────────────────────────────────────────────
banner('4. Duplicate code check');
foreach (['customers' => 'customer_code', 'suppliers' => 'supplier_code'] as $tbl => $col) {
    $dups = $db->fetchAll(
        "SELECT $col, COUNT(*) n FROM $tbl
         WHERE is_deleted = FALSE AND is_current = TRUE
         GROUP BY $col HAVING COUNT(*) > 1"
    );
    if (empty($dups)) ok("$tbl.$col — no duplicates");
    else              ng(sprintf("$tbl.$col — %d duplicate codes", count($dups)));
}

// ─────────────────────────────────────────────────────────────
banner('5. HTTP routes (authenticated)');
$ch = curl_init();
$cookieJar = sys_get_temp_dir() . '/pegasus_test_' . getmypid() . '.txt';
curl_setopt_array($ch, [
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT => 15,
]);

// Fetch CSRF
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
$loginPage = curl_exec($ch);
preg_match('/name="_csrf_token" value="([^"]+)"/', $loginPage, $m);
$csrf = $m[1] ?? '';
if (!$csrf) { ng('could not fetch CSRF token'); goto END_TESTS; }
ok('CSRF token fetched');

// POST login
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'username' => $email, 'password' => $passwd, '_csrf_token' => $csrf,
    ]),
]);
curl_exec($ch);
$loginHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($loginHttp >= 300 && $loginHttp < 400) ok("login → HTTP $loginHttp (redirect)");
else ng("login failed → HTTP $loginHttp");

// Reset to GET
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, null);
curl_setopt($ch, CURLOPT_HTTPGET, true);

$routes = [
    '/dashboard', '/master/customers', '/master/suppliers', '/master/items',
    '/master/accounts', '/master/payment-terms', '/master/banks',
    '/master/divisions', '/master/deal-statuses', '/master/solution-categories',
    '/master/exchange-rates',
    '/sales/customers', '/sales/deals', '/sales/deals/kanban',
    '/sales/quotations', '/sales/orders', '/sales/pipeline',
    '/sales/activities', '/sales/kpi', '/sales/kpi/master',
    '/purchasing/orders',
    '/inventory/stock', '/inventory/warehouses',
    '/accounting/journal', '/accounting/ledger', '/accounting/pl', '/accounting/bs',
    '/ar/invoices', '/ar/payments',
    '/ap/invoices', '/ap/payments',
    '/hr/employees', '/hr/attendance', '/hr/leave',
    '/expense/claims',
    '/production/orders', '/production/bom', '/production/mrp',
    '/projects', '/cost-sheets',
    '/cashflow/actual', '/cashflow/forecast',
    '/analytics/quotations', '/analytics/purchasing',
    '/reports',
    '/admin/audit-logs', '/admin/permissions',
    '/bi/dashboards',
];

foreach ($routes as $route) {
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $route);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($code >= 200 && $code < 400)      ok("$route → HTTP $code");
    elseif ($code >= 400 && $code < 500)  wn("$route → HTTP $code (auth/not-found)");
    else                                   ng("$route → HTTP $code (server error)");
}

@unlink($cookieJar);
curl_close($ch);

END_TESTS:

// ─────────────────────────────────────────────────────────────
echo "\n\033[1m═══ SUMMARY ═══\033[0m\n";
echo "  \033[32mPassed: $pass\033[0m\n";
echo "  \033[33mWarnings: $warn\033[0m\n";
echo "  \033[31mFailed: $fail\033[0m\n";
if ($fail > 0) {
    echo "\n\033[1;31mISSUES FOUND:\033[0m\n";
    foreach ($issues as $i) echo "  • $i\n";
}
exit($fail > 0 ? 1 : 0);
