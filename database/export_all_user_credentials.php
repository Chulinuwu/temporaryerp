<?php
/**
 * Export ALL active users + the original randomized passwords (from 4/22 issue file)
 * + the 4 test users (TestPR!2026) into a single comprehensive CSV.
 *
 * Output: backups/all_user_credentials_<timestamp>.csv
 */
declare(strict_types=1);

$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=pegasus_erp', 'postgres',
    getenv('PG_PASSWORD') ?: 'postgres', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 1. Load the original randomized-password file (user_id → password)
$origCsv = __DIR__ . '/../backups/user_random_passwords_20260422_003318.csv';
$origByUserId = [];
if (is_file($origCsv)) {
    $fh = fopen($origCsv, 'r');
    fgetcsv($fh); // header
    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) < 4) continue;
        $origByUserId[(int)$row[0]] = $row[3]; // user_id => password
    }
    fclose($fh);
}

// 2. Test users (known fixed password)
$testPwd = 'TestPR!2026';
$testEmails = [
    'pr_requester@test.local',
    'pr_buyer@test.local',
    'pr_pm@test.local',
    'pr_ceo@test.local',
];

// 3. Pull all active users
$users = $pdo->query(
    "SELECT u.user_id, u.username, u.email, u.role, u.is_active,
            e.full_name_jp, e.full_name_th, e.full_name, e.emp_code,
            e.position_level
     FROM users u
     LEFT JOIN employees e ON e.employee_id = u.employee_id
     WHERE u.is_active = TRUE
     ORDER BY u.user_id"
)->fetchAll(PDO::FETCH_ASSOC);

$out = __DIR__ . '/../backups/all_user_credentials_' . date('Ymd_His') . '.csv';
$fh  = fopen($out, 'w');
// BOM for Excel compatibility (UTF-8)
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, ['user_id','emp_code','email','full_name','role','position_level','password','source','note']);

$nFound = 0;
$nMissing = 0;
foreach ($users as $u) {
    $uid    = (int)$u['user_id'];
    $email  = $u['email'] ?? '';
    $name   = $u['full_name_jp'] ?: $u['full_name_th'] ?: $u['full_name'] ?: '';
    $code   = $u['emp_code'] ?? '';
    $role   = $u['role'] ?? '';
    $pos    = $u['position_level'] ?? '';

    $pwd = null; $src = ''; $note = '';
    if (in_array($email, $testEmails, true)) {
        $pwd = $testPwd;
        $src = 'TEST_USER_FIXED';
        $note = 'created by setup_pr_test_users.php — fixed password';
    } elseif (isset($origByUserId[$uid])) {
        $pwd = $origByUserId[$uid];
        $src = 'RANDOMIZED_20260422';
        $note = 'from user_random_passwords_20260422_003318.csv';
    } else {
        $pwd = '(UNKNOWN — created after 2026-04-22 randomization)';
        $src = 'UNKNOWN';
        $note = 'password hash exists but plain-text not on record. Use admin reset.';
        $nMissing++;
    }
    if ($pwd && $src !== 'UNKNOWN') $nFound++;

    fputcsv($fh, [$uid, $code, $email, $name, $role, $pos, $pwd, $src, $note]);
}
fclose($fh);

echo "Output: $out\n";
echo "  total users: " . count($users) . "\n";
echo "  passwords found: $nFound\n";
echo "  passwords unknown: $nMissing\n";
