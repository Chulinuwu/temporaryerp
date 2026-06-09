<?php
/**
 * PEGASUS ERP - Batch auto-translate master data (suppliers, customers)
 * Populates supplier_name / supplier_name_jp / supplier_name_th etc.
 *
 * Run: php database/translate_masters.php
 */

define('BASE_PATH', __DIR__ . '/..');
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Helpers.php';

$config = require BASE_PATH . '/config/database.php';
$_ENV = array_merge($_ENV, [
    'DB_HOST' => $config['host'],
    'DB_PORT' => $config['port'],
    'DB_NAME' => $config['database'],
    'DB_USER' => $config['username'],
    'DB_PASS' => $config['password'],
]);

$db = Database::getInstance();

// ─ Language detection (simple: Thai / Japanese / Latin) ─
function detectLang(string $s): string {
    if (preg_match('/\p{Thai}/u', $s))     return 'th';
    if (preg_match('/\p{Hiragana}|\p{Katakana}|\p{Han}/u', $s)) return 'ja';
    return 'en';
}

function translate(string $text, string $source, string $target): ?string {
    if ($source === $target) return $text;
    $out = googleTranslate($text, $source, $target);
    if ($out === null) return null;
    // Rate-limit: small sleep to avoid being blocked
    usleep(200000); // 0.2s
    return $out;
}

// ─ Process suppliers ─
echo "=== Suppliers ===\n";
$rows = $db->fetchAll(
    "SELECT supplier_id, supplier_code, supplier_name, supplier_name_jp, supplier_name_th
     FROM suppliers
     WHERE is_deleted=FALSE
     ORDER BY supplier_id"
);
$total = count($rows);
$done = 0;
foreach ($rows as $r) {
    $id = $r['supplier_id'];
    $name = trim($r['supplier_name'] ?? '');
    $jp = trim($r['supplier_name_jp'] ?? '');
    $th = trim($r['supplier_name_th'] ?? '');

    if (empty($name) && empty($jp) && empty($th)) {
        $done++;
        continue;
    }

    // Pick source text and language
    $src = '';
    $srcLang = 'en';
    if (!empty($name)) { $src = $name; $srcLang = detectLang($name); }
    elseif (!empty($th)) { $src = $th; $srcLang = 'th'; }
    elseif (!empty($jp)) { $src = $jp; $srcLang = 'ja'; }

    $updates = [];
    $params  = [];

    // Fill EN (supplier_name should be English)
    if ($srcLang !== 'en' || empty($name) || detectLang($name) !== 'en') {
        $enText = translate($src, $srcLang, 'en');
        if ($enText !== null && $enText !== $src) {
            $updates[] = 'supplier_name = ?';
            $params[] = $enText;
        }
    }
    // Fill JP
    if (empty($jp)) {
        $jpText = translate($src, $srcLang, 'ja');
        if ($jpText !== null) {
            $updates[] = 'supplier_name_jp = ?';
            $params[] = $jpText;
        }
    }
    // Fill TH
    if (empty($th)) {
        $thText = translate($src, $srcLang, 'th');
        if ($thText !== null) {
            $updates[] = 'supplier_name_th = ?';
            $params[] = $thText;
        }
    }

    if (!empty($updates)) {
        $params[] = $id;
        $db->query("UPDATE suppliers SET " . implode(', ', $updates) . ", updated_at=NOW() WHERE supplier_id = ?", $params);
    }

    $done++;
    echo sprintf("  [%d/%d] %s: %s\n", $done, $total, $r['supplier_code'], substr($src, 0, 60));
}

// ─ Process customers ─
echo "\n=== Customers ===\n";
$rows = $db->fetchAll(
    "SELECT customer_id, customer_code, customer_name, customer_name_jp, customer_name_th
     FROM customers
     WHERE is_deleted=FALSE
     ORDER BY customer_id"
);
$total = count($rows);
$done = 0;
foreach ($rows as $r) {
    $id = $r['customer_id'];
    $name = trim($r['customer_name'] ?? '');
    $jp = trim($r['customer_name_jp'] ?? '');
    $th = trim($r['customer_name_th'] ?? '');

    if (empty($name) && empty($jp) && empty($th)) {
        $done++;
        continue;
    }

    $src = '';
    $srcLang = 'en';
    if (!empty($name)) { $src = $name; $srcLang = detectLang($name); }
    elseif (!empty($th)) { $src = $th; $srcLang = 'th'; }
    elseif (!empty($jp)) { $src = $jp; $srcLang = 'ja'; }

    $updates = [];
    $params  = [];

    if ($srcLang !== 'en' || empty($name) || detectLang($name) !== 'en') {
        $enText = translate($src, $srcLang, 'en');
        if ($enText !== null && $enText !== $src) {
            $updates[] = 'customer_name = ?';
            $params[] = $enText;
        }
    }
    if (empty($jp)) {
        $jpText = translate($src, $srcLang, 'ja');
        if ($jpText !== null) {
            $updates[] = 'customer_name_jp = ?';
            $params[] = $jpText;
        }
    }
    if (empty($th)) {
        $thText = translate($src, $srcLang, 'th');
        if ($thText !== null) {
            $updates[] = 'customer_name_th = ?';
            $params[] = $thText;
        }
    }

    if (!empty($updates)) {
        $params[] = $id;
        $db->query("UPDATE customers SET " . implode(', ', $updates) . ", updated_at=NOW() WHERE customer_id = ?", $params);
    }

    $done++;
    if ($done % 10 === 0 || $done === $total) {
        echo sprintf("  [%d/%d] %s: %s\n", $done, $total, $r['customer_code'], substr($src, 0, 60));
    }
}

echo "\n=== DONE ===\n";
