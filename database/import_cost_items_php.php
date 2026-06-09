<?php
/**
 * PEGASUS ERP - Import cost items from Excel via openpyxl → JSON → PHP insert
 * Usage: php import_cost_items_php.php <excel_file> <project_id>
 */

if ($argc < 3) {
    echo "Usage: php import_cost_items_php.php <excel_file> <project_id>\n";
    exit(1);
}

$excelFile = $argv[1];
$projectId = intval($argv[2]);

// Step 1: Use Python to extract data as JSON
$tmpJson = tempnam(sys_get_temp_dir(), 'cost_') . '.json';
$pyScript = <<<'PYTHON'
import sys, json, openpyxl
excel_path = sys.argv[1]
out_path = sys.argv[2]
wb = openpyxl.load_workbook(excel_path, data_only=True)
ws = None
# Priority: find sheet with 'breakdown' first, then any with 'cost'
for name in wb.sheetnames:
    if 'breakdown' in name.lower():
        ws = wb[name]
        break
if not ws:
    for name in wb.sheetnames:
        if 'cost' in name.lower() and 'summary' not in name.lower():
            ws = wb[name]
            break
if not ws:
    ws = wb[wb.sheetnames[0]]

rows = []
for r in range(9, 105):
    d = ws.cell(r, 4).value
    e = ws.cell(r, 5).value
    f = ws.cell(r, 6).value
    g = ws.cell(r, 7).value
    h = ws.cell(r, 8).value
    j = ws.cell(r, 10).value
    k = ws.cell(r, 11).value
    l = ws.cell(r, 12).value
    m = ws.cell(r, 13).value
    n = ws.cell(r, 14).value
    if not any([d, e, j, k, l]):
        continue
    def clean(v):
        if v is None: return None
        s = str(v).strip()
        if s in ['-', 'None', '']: return None
        return s
    def num(v):
        try:
            if v is not None and isinstance(v, (int, float)): return float(v)
        except: pass
        return 0
    rows.append({
        'row': r,
        'D': clean(d), 'E': clean(e), 'F': clean(f), 'G': clean(g), 'H': clean(h),
        'J': num(j), 'K': num(k), 'L': num(l), 'M': clean(m), 'N': clean(n)
    })

with open(out_path, 'w', encoding='utf-8') as fp:
    json.dump(rows, fp, ensure_ascii=False)
print(f"Extracted {len(rows)} rows")
PYTHON;

$pyTmp = tempnam(sys_get_temp_dir(), 'py_') . '.py';
file_put_contents($pyTmp, $pyScript);
$cmd = sprintf('python3 %s %s %s 2>&1', escapeshellarg($pyTmp), escapeshellarg($excelFile), escapeshellarg($tmpJson));
echo "Extracting Excel data...\n";
$output = shell_exec($cmd);
echo $output;

if (!file_exists($tmpJson)) {
    echo "ERROR: JSON extraction failed\n";
    exit(1);
}

$rows = json_decode(file_get_contents($tmpJson), true);
unlink($tmpJson);
unlink($pyTmp);

if (empty($rows)) {
    echo "ERROR: No data extracted\n";
    exit(1);
}

// Step 2: Insert into database
require __DIR__ . '/../core/Database.php';
$db = Database::getInstance();

// Clear existing IMPORT items
$db->query("DELETE FROM project_cost_items WHERE project_id = ? AND source = 'IMPORT'", [$projectId]);
echo "Cleared existing IMPORT items for project_id=$projectId\n\n";

$currentCategory = null;
$lineNo = 0;
$totalCost = 0;
$inserted = 0;

foreach ($rows as $r) {
    $d = $r['D'];
    $e = $r['E'];
    $isCategory = false;

    if ($d !== null) {
        $currentCategory = $d;
        if ($r['J'] == 0 && $r['K'] == 0 && $r['L'] == 0) {
            $isCategory = true;
        }
    }

    $lineNo++;
    $total = round($r['L'], 2);

    $db->query(
        "INSERT INTO project_cost_items
            (project_id, line_no, category, description, supplier, brand, lead_time,
             unit_price, quantity, total_amount, unit, remark, is_category_row, source, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'IMPORT', 1)",
        [
            $projectId,
            $lineNo,
            $isCategory ? $d : $currentCategory,
            $isCategory ? $d : $e,
            $r['F'],
            $r['G'],
            $r['H'],
            round($r['J'], 4),
            round($r['K'], 4),
            $total,
            $r['M'],
            $r['N'],
            $isCategory ? 'TRUE' : 'FALSE',
        ]
    );

    $inserted++;
    $totalCost += $total;
    $status = $isCategory ? 'CAT ' : '    ';
    $desc = substr($isCategory ? ($d ?? '') : ($e ?? $d ?? ''), 0, 50);
    printf("  %s Line %3d: %-50s | %14s\n", $status, $lineNo, $desc, number_format($total, 2));
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Imported $inserted items, Total cost: " . number_format($totalCost, 2) . " Baht\n";
echo "Project ID: $projectId\n";
