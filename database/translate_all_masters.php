<?php
/**
 * PEGASUS ERP - Batch auto-translate ALL master data (except personal names)
 * Translates:
 *   - accounts (account_name / _jp / _th)
 *   - activity_categories (category_name / _jp / _th)
 *   - deal_statuses (status_name / _jp / _th)
 *   - items (item_name / _jp / _th)
 *   - solution_categories (category_name / _jp / _th)
 *   - payment_terms (term_name_en / _jp / _th)
 *   - departments (department_name / _jp)
 *   - divisions (division_name / _jp)
 *   - public_holidays (holiday_name / _jp)
 *
 * Skips:
 *   - employees (personal names shouldn't be auto-translated)
 *   - banks (only has _th)
 *
 * Run: php database/translate_all_masters.php
 */

define('BASE_PATH', __DIR__ . '/..');
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Helpers.php';

$config = require BASE_PATH . '/config/database.php';
$db = Database::getInstance();

function detectLang(string $s): string {
    if (preg_match('/\p{Thai}/u', $s))     return 'th';
    if (preg_match('/\p{Hiragana}|\p{Katakana}/u', $s)) return 'ja';
    if (preg_match('/\p{Han}/u', $s)) return 'ja'; // Assume Kanji = Japanese (could be Chinese)
    return 'en';
}

function tr(string $text, string $source, string $target): ?string {
    if ($source === $target) return $text;
    $out = googleTranslate($text, $source, $target);
    if ($out !== null) usleep(200000);
    return $out;
}

/**
 * Generic master translator
 * @param string $table
 * @param string $idCol
 * @param string $enCol  Source / English column
 * @param string|null $jpCol
 * @param string|null $thCol
 * @param string $where
 */
function translateTable($db, $table, $idCol, $enCol, $jpCol = null, $thCol = null, $where = 'is_deleted=FALSE') {
    $cols = [$idCol, $enCol];
    if ($jpCol) $cols[] = $jpCol;
    if ($thCol) $cols[] = $thCol;
    $colList = implode(',', $cols);

    $rows = $db->fetchAll("SELECT $colList FROM $table WHERE $where ORDER BY $idCol");
    $total = count($rows);
    echo "=== $table ($total rows) ===\n";
    $done = 0;

    foreach ($rows as $r) {
        $id = $r[$idCol];
        $name = trim($r[$enCol] ?? '');
        $jp = $jpCol ? trim($r[$jpCol] ?? '') : '';
        $th = $thCol ? trim($r[$thCol] ?? '') : '';

        if (empty($name) && empty($jp) && empty($th)) { $done++; continue; }

        $src = ''; $srcLang = 'en';
        if (!empty($name)) { $src = $name; $srcLang = detectLang($name); }
        elseif (!empty($th)) { $src = $th; $srcLang = 'th'; }
        elseif (!empty($jp)) { $src = $jp; $srcLang = 'ja'; }

        $updates = []; $params = [];

        // EN
        if (detectLang($name) !== 'en' && !empty($name)) {
            $enText = tr($src, $srcLang, 'en');
            if ($enText !== null && $enText !== $src) {
                $updates[] = "$enCol = ?"; $params[] = $enText;
            }
        }
        // JP
        if ($jpCol && empty($jp)) {
            $jpText = tr($src, $srcLang, 'ja');
            if ($jpText !== null) { $updates[] = "$jpCol = ?"; $params[] = $jpText; }
        }
        // TH
        if ($thCol && empty($th)) {
            $thText = tr($src, $srcLang, 'th');
            if ($thText !== null) { $updates[] = "$thCol = ?"; $params[] = $thText; }
        }

        if (!empty($updates)) {
            $params[] = $id;
            // updated_at may not exist for some tables - try with, fallback without
            try {
                $db->query("UPDATE $table SET " . implode(', ', $updates) . ", updated_at=NOW() WHERE $idCol = ?", $params);
            } catch (Exception $e) {
                $db->query("UPDATE $table SET " . implode(', ', $updates) . " WHERE $idCol = ?", $params);
            }
        }

        $done++;
        if ($done % 10 === 0 || $done === $total) {
            echo sprintf("  [%d/%d] %s\n", $done, $total, substr($src, 0, 60));
        }
    }
    echo "\n";
}

// Small masters first
translateTable($db, 'accounts', 'account_id', 'account_name', 'account_name_jp', 'account_name_th', 'effective_to IS NULL');
translateTable($db, 'activity_categories', 'category_id', 'category_name', 'category_name_jp', 'category_name_th', '1=1');
translateTable($db, 'deal_statuses', 'status_id', 'status_name', 'status_name_jp', 'status_name_th', '1=1');
translateTable($db, 'solution_categories', 'category_id', 'category_name', 'category_name_jp', 'category_name_th', '1=1');
translateTable($db, 'departments', 'department_id', 'department_name', 'department_name_jp', null, 'is_deleted=FALSE');
translateTable($db, 'divisions', 'division_id', 'division_name', 'division_name_jp', null, 'is_deleted=FALSE');
translateTable($db, 'public_holidays', 'holiday_id', 'holiday_name', 'holiday_name_jp', null, '1=1');

// Payment terms has _en suffix instead of bare name
translateTable($db, 'payment_terms', 'term_id', 'term_name_en', 'term_name_jp', 'term_name_th', 'is_deleted=FALSE');

// Items - big table
translateTable($db, 'items', 'item_id', 'item_name', 'item_name_jp', 'item_name_th', 'effective_to IS NULL');

echo "=== ALL DONE ===\n";
