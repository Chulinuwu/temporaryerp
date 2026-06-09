<?php
/**
 * Reset all user passwords to random 12-char strings.
 * Outputs CSV: user_id,username,email,new_password
 */
define('BASE_PATH', __DIR__ . '/..');
require BASE_PATH . '/core/Database.php';
$config = require BASE_PATH . '/config/database.php';
$db = Database::getInstance();

function randPass(int $len = 12): string {
    // Avoid ambiguous chars (0/O/1/l/I)
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&';
    $out = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, $max)];
    }
    // Guarantee at least one digit + one symbol
    if (!preg_match('/\d/', $out))  $out[0] = (string)random_int(2,9);
    if (!preg_match('/[!@#$%&]/',$out)) $out[1] = '!@#$%&'[random_int(0,5)];
    return $out;
}

$users = $db->fetchAll("SELECT user_id, username, email FROM users ORDER BY user_id");
$csvPath = BASE_PATH . '/backups/password_reset_' . date('Ymd_His') . '.csv';
$fp = fopen($csvPath, 'w');
fputcsv($fp, ['user_id', 'username', 'email', 'new_password']);

$done = 0;
foreach ($users as $u) {
    $pw = randPass(12);
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password_hash = ? WHERE user_id = ?", [$hash, $u['user_id']]);
    fputcsv($fp, [$u['user_id'], $u['username'], $u['email'], $pw]);
    $done++;
}
fclose($fp);
echo "Reset {$done} user passwords.\n";
echo "CSV saved: {$csvPath}\n";
