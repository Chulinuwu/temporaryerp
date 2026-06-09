<?php
/**
 * PEGASUS ERP — Deep Business Audit
 *  - Master data quality (duplicates, NULL, orphans, FK integrity)
 *  - Business workflow integrity (deal → quotation → SO → invoice → payment)
 *  - Security checks
 *  - Performance smoke test
 */
define('BASE_PATH', __DIR__ . '/..');
require_once BASE_PATH . '/core/Database.php';
$config = require BASE_PATH . '/config/database.php';
$db = Database::getInstance();

$pass = 0; $warn = 0; $fail = 0; $info = [];
function head($s) { echo "\n\033[1;36m═══ {$s} ═══\033[0m\n"; }
function ok($s)   { global $pass; $pass++; echo "  \033[32m✓\033[0m {$s}\n"; }
function wn($s)   { global $warn; $warn++; echo "  \033[33m⚠\033[0m {$s}\n"; }
function ng($s)   { global $fail; $fail++; echo "  \033[31m✗\033[0m {$s}\n"; }
function info($k,$v) { global $info; $info[$k] = $v; printf("  \033[34mℹ\033[0m %-40s : %s\n", $k, $v); }

// ────────────────────────────────────────────────────────────
head('A. MASTER DATA QUALITY');

// A1. Customer data
$row = $db->fetch("SELECT COUNT(*) AS total,
    COUNT(*) FILTER (WHERE customer_name IS NULL OR customer_name = '') AS no_name,
    COUNT(*) FILTER (WHERE tax_id IS NULL OR tax_id = '') AS no_tax,
    COUNT(*) FILTER (WHERE email IS NULL OR email = '') AS no_email,
    COUNT(*) FILTER (WHERE address IS NULL OR address = '') AS no_addr
   FROM customers WHERE is_deleted = FALSE AND is_current = TRUE");
info('Customers (active)', $row['total']);
($row['no_name'] == 0) ? ok('all customers have a name') : ng("$row[no_name] customers have empty name");
($row['no_tax']  < 50)  ? ok("Tax ID present (only $row[no_tax] missing)") : wn("$row[no_tax] customers missing Tax ID");
wn("$row[no_email] customers without email (informational)");
wn("$row[no_addr] customers without address (informational)");

// A2. Supplier data
$row = $db->fetch("SELECT COUNT(*) AS total,
    COUNT(*) FILTER (WHERE supplier_name IS NULL OR supplier_name = '') AS no_name
   FROM suppliers WHERE is_deleted = FALSE AND is_current = TRUE");
info('Suppliers (active)', $row['total']);
($row['no_name'] == 0) ? ok('all suppliers have a name') : ng("$row[no_name] suppliers without name");

// A3. Code uniqueness
foreach (['customers' => 'customer_code', 'suppliers' => 'supplier_code'] as $tbl => $col) {
    $dups = $db->fetchAll("SELECT $col FROM $tbl WHERE is_deleted=FALSE AND is_current=TRUE
                           GROUP BY $col HAVING COUNT(*) > 1");
    empty($dups) ? ok("$tbl.$col — no duplicates") : ng(count($dups) . " duplicate $col");
}

// A4. Code format consistency
$badCust = $db->fetch("SELECT COUNT(*) AS n FROM customers WHERE is_deleted=FALSE AND customer_code !~ '^CUS-'");
($badCust['n'] == 0) ? ok('all customers use CUS- prefix') : wn("$badCust[n] customers with non-CUS prefix");
$badSup = $db->fetch("SELECT COUNT(*) AS n FROM suppliers WHERE is_deleted=FALSE AND supplier_code !~ '^SUP-'");
($badSup['n'] == 0) ? ok('all suppliers use SUP- prefix') : wn("$badSup[n] suppliers with non-SUP prefix");

// A5. Solution categories
$row = $db->fetch("SELECT COUNT(*) AS total,
    SUM(CASE WHEN evaluation_profit_pct IS NULL THEN 1 ELSE 0 END) AS no_pct
   FROM solution_categories WHERE is_deleted = FALSE");
info('Solution categories', $row['total']);
($row['no_pct'] == 0) ? ok('all solution categories have evaluation_profit_pct') : wn("$row[no_pct] without pct");

// A6. Exchange rates
$row = $db->fetch("SELECT COUNT(*) AS n FROM exchange_rates WHERE is_deleted=FALSE
                   AND effective_from <= CURRENT_DATE AND (effective_to IS NULL OR effective_to >= CURRENT_DATE)");
($row['n'] >= 16) ? ok("active FX rates: $row[n]") : wn("only $row[n] active FX rates");

// ────────────────────────────────────────────────────────────
head('B. REFERENTIAL INTEGRITY (Orphans)');

$checks = [
    'customers'        => "SELECT COUNT(*) AS n FROM deals WHERE customer_id IS NOT NULL AND customer_id NOT IN (SELECT customer_id FROM customers)",
    'quotation→deal'   => "SELECT COUNT(*) AS n FROM quotation_headers WHERE deal_id IS NOT NULL AND deal_id NOT IN (SELECT deal_id FROM deals)",
    'so→customer'      => "SELECT COUNT(*) AS n FROM sales_order_headers WHERE customer_id NOT IN (SELECT customer_id FROM customers)",
    'so→quotation'     => "SELECT COUNT(*) AS n FROM sales_order_headers WHERE quotation_id IS NOT NULL AND quotation_id NOT IN (SELECT quotation_id FROM quotation_headers)",
    'po→supplier'      => "SELECT COUNT(*) AS n FROM purchase_order_headers WHERE supplier_id NOT IN (SELECT supplier_id FROM suppliers)",
    'ar_invoice→cust'  => "SELECT COUNT(*) AS n FROM ar_invoices WHERE customer_id NOT IN (SELECT customer_id FROM customers)",
    'ar_invoice→so'    => "SELECT COUNT(*) AS n FROM ar_invoices WHERE so_id IS NOT NULL AND so_id NOT IN (SELECT so_id FROM sales_order_headers)",
    'ar_payment→cust'  => "SELECT COUNT(*) AS n FROM ar_payments WHERE customer_id NOT IN (SELECT customer_id FROM customers)",
    'inspection→qt'    => "SELECT COUNT(*) AS n FROM quotation_inspection_schedule WHERE quotation_id NOT IN (SELECT quotation_id FROM quotation_headers)",
    'kpi_target→emp'   => "SELECT COUNT(*) AS n FROM sales_kpi_targets WHERE employee_id NOT IN (SELECT employee_id FROM employees)",
    'role_perm→role'   => "SELECT COUNT(*) AS n FROM role_permissions WHERE role_code NOT IN (SELECT role_code FROM roles)",
];
foreach ($checks as $name => $sql) {
    $r = $db->fetch($sql);
    ($r['n'] == 0) ? ok("$name — clean") : ng("$name — $r[n] orphans");
}

// ────────────────────────────────────────────────────────────
head('C. BUSINESS WORKFLOW INTEGRITY');

// C1. Deal counts by status
$rows = $db->fetchAll("SELECT ds.status_name, ds.win_pct, COUNT(d.deal_id) AS n,
                              COALESCE(SUM(d.expected_amount),0) AS pipeline
                       FROM deal_statuses ds
                       LEFT JOIN deals d ON d.status_id = ds.status_id AND d.is_deleted = FALSE
                       GROUP BY ds.status_id, ds.status_name, ds.win_pct, ds.sort_order
                       ORDER BY ds.sort_order");
$totalDeals = array_sum(array_column($rows, 'n'));
$totalPipe  = array_sum(array_column($rows, 'pipeline'));
info('Total deals', $totalDeals);
info('Total pipeline (THB)', number_format($totalPipe, 0));

// C2. Quotation counts by status
$rows = $db->fetchAll("SELECT status, COUNT(*) AS n FROM quotation_headers
                       WHERE is_deleted = FALSE GROUP BY status");
$qtTotal = array_sum(array_column($rows, 'n'));
info('Total quotations', $qtTotal);
foreach ($rows as $r) info("  status: $r[status]", $r['n']);

// C3. Pending approvals
$pendingC = (int)$db->fetch("SELECT COUNT(*) AS n FROM customers WHERE approval_status='PENDING' AND is_deleted=FALSE")['n'];
$pendingS = (int)$db->fetch("SELECT COUNT(*) AS n FROM suppliers WHERE approval_status='PENDING' AND is_deleted=FALSE")['n'];
$pendingQ = (int)$db->fetch("SELECT COUNT(*) AS n FROM quotation_headers WHERE status='PENDING_APPROVAL' AND is_deleted=FALSE")['n'];
$pendingP = (int)$db->fetch("SELECT COUNT(*) AS n FROM purchase_order_headers WHERE status='PENDING_APPROVAL' AND is_deleted=FALSE")['n'];
info('Pending approvals', "Customers=$pendingC, Suppliers=$pendingS, Quotations=$pendingQ, POs=$pendingP");

// C4. AR/AP balance
$arOpen = $db->fetch("SELECT COUNT(*) AS n, COALESCE(SUM(balance_thb),0) AS bal FROM ar_invoices
                      WHERE is_deleted=FALSE AND status IN ('OPEN','PARTIAL','OVERDUE')");
$apOpen = $db->fetch("SELECT COUNT(*) AS n, COALESCE(SUM(balance_thb),0) AS bal FROM ap_invoices
                      WHERE is_deleted=FALSE AND status IN ('OPEN','PARTIAL','OVERDUE')");
info('AR open', "$arOpen[n] invoices, balance ฿" . number_format($arOpen['bal'], 0));
info('AP open', "$apOpen[n] invoices, balance ฿" . number_format($apOpen['bal'], 0));

// C5. Quotation totals correctness (subtotal × (1+vat) ≈ grand_total)
$badTotal = $db->fetchAll(
    "SELECT quotation_no, subtotal_thb, vat_rate, vat_amount, grand_total_thb,
            ROUND(subtotal_thb * (1 + vat_rate/100), 2) AS expected
     FROM quotation_headers
     WHERE is_deleted = FALSE
       AND ABS(grand_total_thb - ROUND(subtotal_thb * (1 + vat_rate/100), 2)) > 1
     LIMIT 5"
);
empty($badTotal) ? ok('quotation totals are mathematically consistent (subtotal × (1+VAT))')
                 : wn(count($badTotal) . ' quotations have minor rounding diff (>1 THB)');

// ────────────────────────────────────────────────────────────
head('D. SECURITY AUDIT');

// D1. Admin count
$adminCount = (int)$db->fetch("SELECT COUNT(*) AS n FROM users WHERE role='ADMIN' AND is_active=TRUE")['n'];
($adminCount >= 1 && $adminCount <= 3) ? ok("admin users: $adminCount (good)")
                                       : wn("admin users: $adminCount (consider reducing)");

// D2. Default password detection
$db->query("SET LOCAL statement_timeout = '5s'");
$defaultPwUsers = 0;
$users = $db->fetchAll("SELECT user_id, email, password_hash FROM users WHERE is_active=TRUE LIMIT 100");
foreach ($users as $u) {
    if (password_verify('admin123', $u['password_hash'])) $defaultPwUsers++;
}
($defaultPwUsers == 0) ? ok('no users using default password "admin123"')
                       : ng("$defaultPwUsers users still using default password 'admin123' — SECURITY RISK");

// D3. Permissions seeded
$permCount = (int)$db->fetch("SELECT COUNT(*) AS n FROM role_permissions")['n'];
($permCount >= 20) ? ok("permission grants: $permCount") : wn("only $permCount permission grants");

// D4. Audit log activity
$auditCount = (int)$db->fetch("SELECT COUNT(*) AS n FROM audit_logs WHERE changed_at > NOW() - INTERVAL '30 days'")['n'];
info('Audit log activity (30 days)', "$auditCount entries");

// ────────────────────────────────────────────────────────────
head('E. SCHEMA COMPLETENESS');

$tables = (int)$db->fetch("SELECT COUNT(*) AS n FROM pg_tables WHERE schemaname='public'")['n'];
$indexes = (int)$db->fetch("SELECT COUNT(*) AS n FROM pg_indexes WHERE schemaname='public'")['n'];
$fks = (int)$db->fetch("SELECT COUNT(*) AS n FROM pg_constraint WHERE contype='f'")['n'];
$triggers = (int)$db->fetch("SELECT COUNT(*) AS n FROM pg_trigger WHERE NOT tgisinternal")['n'];
info('DB tables', $tables);
info('DB indexes', $indexes);
info('DB foreign keys', $fks);
info('DB triggers (audit)', $triggers);

// ────────────────────────────────────────────────────────────
head('F. PERFORMANCE SMOKE TEST');

$queries = [
    'customers list' => "SELECT * FROM customers WHERE is_deleted=FALSE AND is_current=TRUE ORDER BY customer_name LIMIT 100",
    'deals list'     => "SELECT d.*, c.customer_name FROM deals d LEFT JOIN customers c ON c.customer_id=d.customer_id WHERE d.is_deleted=FALSE ORDER BY d.deal_id DESC LIMIT 100",
    'quotations'     => "SELECT * FROM quotation_headers WHERE is_deleted=FALSE ORDER BY quotation_id DESC LIMIT 100",
    'cf forecast agg'=> "SELECT TO_CHAR(expected_income_date, 'YYYY-MM') AS m, SUM(grand_total_thb) FROM quotation_headers WHERE expected_income_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '12 months' GROUP BY 1",
    'kpi rollup'     => "SELECT sales_person_id, COUNT(*) FROM deal_activities WHERE activity_date > CURRENT_DATE - INTERVAL '90 days' GROUP BY sales_person_id",
];
foreach ($queries as $name => $sql) {
    $start = microtime(true);
    $db->fetchAll($sql);
    $ms = round((microtime(true) - $start) * 1000, 1);
    if ($ms < 50)       ok(sprintf("%-25s %sms (excellent)", $name, $ms));
    elseif ($ms < 200)  ok(sprintf("%-25s %sms (good)", $name, $ms));
    elseif ($ms < 500)  wn(sprintf("%-25s %sms (acceptable)", $name, $ms));
    else                ng(sprintf("%-25s %sms (slow)", $name, $ms));
}

// ────────────────────────────────────────────────────────────
head('SUMMARY');
echo "  \033[32mPassed: $pass\033[0m\n";
echo "  \033[33mWarnings: $warn\033[0m\n";
echo "  \033[31mFailed: $fail\033[0m\n";
exit($fail > 0 ? 1 : 0);
