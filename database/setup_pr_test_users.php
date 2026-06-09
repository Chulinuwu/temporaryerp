<?php
/**
 * Create / reset 4 test users for PR workflow E2E tests.
 *
 *   pr_requester@test.local   STAFF      / position STAFF       — requester
 *   pr_buyer@test.local       PURCHASE   / position STAFF       — purchasing officer
 *   pr_pm@test.local          MANAGER    / position MANAGER     — purchasing manager
 *   pr_ceo@test.local         ADMIN      / position DIRECTOR    — CEO
 *
 * Password (all): TestPR!2026
 */

require_once __DIR__ . '/../config/database.php';

$pdo = new PDO(
    'pgsql:host=' . (getenv('PG_HOST') ?: 'localhost') .
    ';port=' . (getenv('PG_PORT') ?: '5432') .
    ';dbname=' . (getenv('PG_DATABASE') ?: 'pegasus_erp'),
    getenv('PG_USER') ?: 'postgres',
    getenv('PG_PASSWORD') ?: 'postgres'
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pass = 'TestPR!2026';
$hash = password_hash($pass, PASSWORD_DEFAULT);

$plan = [
    ['email'=>'pr_requester@test.local','name'=>'PR Tester Requester','role'=>'STAFF',   'pos'=>'STAFF'],
    ['email'=>'pr_buyer@test.local',    'name'=>'PR Tester Buyer',    'role'=>'PURCHASE','pos'=>'STAFF'],
    ['email'=>'pr_pm@test.local',       'name'=>'PR Tester PMgr',     'role'=>'MANAGER', 'pos'=>'MANAGER'],
    ['email'=>'pr_ceo@test.local',      'name'=>'PR Tester CEO',      'role'=>'ADMIN',   'pos'=>'DIRECTOR'],
];

$divId = 1;
$row = $pdo->query("SELECT division_id FROM divisions WHERE is_deleted=false ORDER BY division_id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($row) $divId = (int)$row['division_id'];

foreach ($plan as $p) {
    // employee
    $st = $pdo->prepare(
        "SELECT employee_id FROM employees WHERE email = ? LIMIT 1"
    );
    $st->execute([$p['email']]);
    $emp = $st->fetch(PDO::FETCH_ASSOC);

    if ($emp) {
        $empId = (int)$emp['employee_id'];
        $pdo->prepare("UPDATE employees SET position_level=?, full_name_jp=?, updated_at=NOW() WHERE employee_id=?")
            ->execute([$p['pos'], $p['name'], $empId]);
    } else {
        $st = $pdo->prepare(
            "INSERT INTO employees (emp_code, division_id, full_name, full_name_jp, full_name_th,
                                    email, position_level, hire_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_DATE) RETURNING employee_id"
        );
        $code = 'TST-' . strtoupper(substr(md5($p['email']), 0, 5));
        $st->execute([$code, $divId, $p['name'], $p['name'], $p['name'], $p['email'], $p['pos']]);
        $empId = (int)$st->fetch(PDO::FETCH_ASSOC)['employee_id'];
    }

    // user
    $st = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $st->execute([$p['email']]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    if ($u) {
        $pdo->prepare("UPDATE users SET role=?, password_hash=?, employee_id=?, is_active=true WHERE user_id=?")
            ->execute([$p['role'], $hash, $empId, $u['user_id']]);
        $uid = (int)$u['user_id'];
    } else {
        $st = $pdo->prepare(
            "INSERT INTO users (username, email, password_hash, role, employee_id, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, true, NOW()) RETURNING user_id"
        );
        $st->execute([$p['email'], $p['email'], $hash, $p['role'], $empId]);
        $uid = (int)$st->fetch(PDO::FETCH_ASSOC)['user_id'];
    }
    echo sprintf("  user_id=%d  email=%-32s  role=%-10s  pos=%-10s  emp_id=%d\n",
        $uid, $p['email'], $p['role'], $p['pos'], $empId);
}
echo "\nAll test users created. Password: {$pass}\n";
