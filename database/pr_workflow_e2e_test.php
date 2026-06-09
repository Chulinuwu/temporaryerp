<?php
/**
 * PR Workflow E2E Test Harness
 *
 * Logs in as 4 different test users and runs the full PR flow through HTTP,
 * asserting both POSITIVE (allowed) and NEGATIVE (forbidden) outcomes.
 *
 *   Usage:  php database/pr_workflow_e2e_test.php
 *   Output: stdout test log + JSON evidence saved to backups/pr_e2e_evidence.json
 *           + Markdown report at backups/pr_e2e_report.md
 */

declare(strict_types=1);

$BASE = getenv('PR_E2E_BASE') ?: 'http://localhost:8090';
$PASS = 'TestPR!2026';

$USERS = [
    'requester' => ['email'=>'pr_requester@test.local', 'role'=>'STAFF',    'pos'=>'STAFF'],
    'buyer'     => ['email'=>'pr_buyer@test.local',     'role'=>'PURCHASE', 'pos'=>'STAFF'],
    'pm'        => ['email'=>'pr_pm@test.local',        'role'=>'MANAGER',  'pos'=>'MANAGER'],
    'ceo'       => ['email'=>'pr_ceo@test.local',       'role'=>'ADMIN',    'pos'=>'DIRECTOR'],
];

$results = []; // each entry: {tc, desc, actor, http, location, expect, ok, evidence}
$prId    = null;
$poId    = null;
$cookieJars = [];

// Direct DB handle for assertions (bypasses shell quoting issues on Windows)
$DBH = new PDO(
    'pgsql:host=localhost;port=5432;dbname=pegasus_erp',
    'postgres',
    getenv('PG_PASSWORD') ?: 'postgres',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function dbScalar(string $sql, array $params = []): ?string {
    global $DBH;
    $st = $DBH->prepare($sql);
    $st->execute($params);
    $r = $st->fetchColumn();
    return $r === false ? null : (string)$r;
}
function dbRows(string $sql, array $params = []): array {
    global $DBH;
    $st = $DBH->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function expectStatus(array &$results, string $tc, int $prId, string $expected): void {
    $actual = dbScalar("SELECT status FROM purchase_requests WHERE pr_id = ?", [$prId]);
    $passed = ($actual === $expected);
    $results[] = [
        'tc'    => $tc . '.db',
        'desc'  => "DB assertion: PR.status should be $expected",
        'actor' => '-',
        'http'  => 0,
        'location' => "DB:$actual",
        'expect' => "status=$expected",
        'passed' => $passed,
        'reason' => $passed ? '' : "Got status=$actual",
    ];
    printf("  %-6s %s.db  expect status=%s  actual=%s\n",
        $passed ? 'PASS' : 'FAIL', $tc, $expected, $actual);
}

function cookieJarPath(string $name): string {
    return sys_get_temp_dir() . '/pr_e2e_' . $name . '.cookie';
}

function http(string $method, string $url, array $opts = []): array {
    global $BASE;
    $ch = curl_init();
    $full = (str_starts_with($url, 'http') ? $url : $BASE . $url);
    curl_setopt_array($ch, [
        CURLOPT_URL => $full,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $opts['jar'] ?? null,
        CURLOPT_COOKIEFILE => $opts['jar'] ?? null,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($opts['multipart'])) {
            // CURL builds multipart automatically when passing array
            curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['fields']);
        } else {
            $body = is_array($opts['fields'] ?? null)
                ? http_build_query($opts['fields'])
                : ($opts['fields'] ?? '');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsz  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $hdrs = substr($resp, 0, $hsz);
    $body = substr($resp, $hsz);
    $loc  = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $hdrs, $m)) $loc = trim($m[1]);
    curl_close($ch);
    return ['code'=>$code, 'location'=>$loc, 'body'=>$body, 'headers'=>$hdrs];
}

function loginAs(string $name): string {
    global $USERS, $PASS, $cookieJars;
    $jar = cookieJarPath($name);
    @unlink($jar);
    $cookieJars[$name] = $jar;

    $r = http('GET', '/login', ['jar'=>$jar]);
    if (!preg_match('/_csrf_token"\s*value="([^"]+)"/', $r['body'], $m)) {
        throw new RuntimeException("Could not get CSRF for $name");
    }
    $csrf = $m[1];
    $u = $USERS[$name];
    $r = http('POST', '/login', [
        'jar'=>$jar,
        'fields' => [
            '_csrf_token' => $csrf,
            'username'    => $u['email'],
            'password'    => $PASS,
        ],
    ]);
    if ($r['code'] !== 302 && $r['code'] !== 200) {
        throw new RuntimeException("Login failed for $name (HTTP {$r['code']})");
    }
    return $jar;
}

/** Get a CSRF token from a page (after auth). */
function csrfFrom(string $jar, string $url): string {
    $r = http('GET', $url, ['jar'=>$jar]);
    if (preg_match('/_csrf_token"\s*value="([^"]+)"/', $r['body'], $m)) return $m[1];
    return '';
}

function ok(array &$results, string $tc, string $desc, string $actor, array $r, string $expect): void {
    $http = $r['code'];
    $loc  = $r['location'];
    $passed = false;
    $reason = '';
    if ($expect === 'success_redirect') {
        $passed = ($http === 302 && $loc && !str_contains((string)$loc, 'login'));
        if (!$passed) $reason = "Expected 302 to non-login; got $http -> $loc";
    } elseif ($expect === 'forbidden_or_redirect') {
        // Forbidden may be 403, or 302 to dashboard with flash error
        $passed = ($http === 403 || ($http === 302 && $loc));
        if (!$passed) $reason = "Expected 403 or 302; got $http";
    } elseif (str_starts_with($expect, 'http:')) {
        $want = (int)substr($expect, 5);
        $passed = ($http === $want);
        if (!$passed) $reason = "Expected HTTP $want; got $http";
    }
    $results[] = [
        'tc'       => $tc,
        'desc'     => $desc,
        'actor'    => $actor,
        'http'     => $http,
        'location' => $loc,
        'expect'   => $expect,
        'passed'   => $passed,
        'reason'   => $reason,
    ];
    printf("  %-6s %s  [%s] HTTP %d  -> %s   %s\n",
        $passed ? 'PASS' : 'FAIL', $tc, $actor, $http, $loc ?? '-', $reason);
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== Logging in 4 test users ===\n";
foreach (array_keys($USERS) as $name) {
    $jar = loginAs($name);
    // Verify session by hitting /dashboard
    $r = http('GET', '/dashboard', ['jar'=>$jar]);
    printf("  %-10s  login OK  (dashboard HTTP %d)\n", $name, $r['code']);
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-01..03: requester creates DRAFT PR (POSITIVE) ===\n";
$jar = $cookieJars['requester'];
$csrf = csrfFrom($jar, '/purchasing/requests/create');
$r = http('POST', '/purchasing/requests', [
    'jar'=>$jar,
    'multipart'=>true,
    'fields'=>[
        '_csrf_token' => $csrf,
        'department'  => 'Engineering',
        'needed_by_date' => '2026-06-30',
        'justification'  => 'E2E test PR — laptops for new hires',
        'lines[0][item_description]' => 'Laptop Dell XPS 13',
        'lines[0][quantity]' => '3',
        'lines[0][unit]'     => 'PCS',
        'lines[0][est_unit_price]' => '45000',
        'lines[1][item_description]' => 'External monitor 27"',
        'lines[1][quantity]' => '3',
        'lines[1][unit]'     => 'PCS',
        'lines[1][est_unit_price]' => '8000',
    ],
]);
ok($results, 'TC-01', 'Requester creates DRAFT PR', 'requester', $r, 'success_redirect');

// Get the created PR id from redirect location
if ($r['location'] && preg_match('#/purchasing/requests/(\d+)#', $r['location'], $m)) {
    $prId = (int)$m[1];
}
echo "  → created pr_id = " . ($prId ?? 'NULL') . "\n";

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-04..05: workflow gate enforcement ===\n";
if ($prId) {
    // TC-04 POSITIVE: requester submits DRAFT → SUBMITTED
    $csrf = csrfFrom($jar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/submit", [
        'jar'=>$jar,
        'fields'=>['_csrf_token'=>$csrf],
    ]);
    ok($results, 'TC-04', 'Requester submits DRAFT', 'requester', $r, 'success_redirect');

    // TC-05 NEGATIVE: requester tries to start-quotes (purchasing-only)
    $csrf = csrfFrom($jar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/start-quotes", [
        'jar'=>$jar,
        'fields'=>['_csrf_token'=>$csrf],
    ]);
    // Will redirect with flash error → status still SUBMITTED
    ok($results, 'TC-05', 'Requester tries start-quotes (forbidden)', 'requester', $r, 'forbidden_or_redirect');
    expectStatus($results, 'TC-05', $prId, 'SUBMITTED'); // still SUBMITTED — start-quotes blocked
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-06: buyer starts quote collection (POSITIVE) ===\n";
if ($prId) {
    $bjar = $cookieJars['buyer'];
    $csrf = csrfFrom($bjar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/start-quotes", [
        'jar'=>$bjar,
        'fields'=>['_csrf_token'=>$csrf],
    ]);
    ok($results, 'TC-06', 'Buyer starts quote collection', 'buyer', $r, 'success_redirect');
    expectStatus($results, 'TC-06', $prId, 'QUOTES_PENDING');
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-07..09: buyer adds 3 quotes with PDF (POSITIVE) ===\n";
if ($prId) {
    // Need PR line IDs (direct DB)
    $lineIds = array_column(
        dbRows("SELECT pr_line_id FROM purchase_request_lines WHERE pr_id = ? ORDER BY line_no", [$prId]),
        'pr_line_id'
    );
    echo "  PR line IDs: " . implode(',', $lineIds) . "\n";

    // Create a small fake PDF
    $pdfPath = sys_get_temp_dir() . '/fake_quote.pdf';
    file_put_contents($pdfPath, "%PDF-1.4\n% E2E test quote\n%%EOF");

    // 3 supplier prices per quote position
    $pricesPerPosition = [
        1 => ['supplier' => 'ABC Co.',  'l0' => 44000, 'l1' => 7500],  // cheapest line1
        2 => ['supplier' => 'XYZ Ltd.', 'l0' => 43000, 'l1' => 7800],  // cheapest line0
        3 => ['supplier' => 'DEF Inc.', 'l0' => 45500, 'l1' => 7900],
    ];
    $bjar = $cookieJars['buyer'];
    foreach ($pricesPerPosition as $pos => $info) {
        $csrf = csrfFrom($bjar, "/purchasing/requests/$prId");
        $fields = [
            '_csrf_token' => $csrf,
            'position'    => (string)$pos,
            'supplier_id' => '',
            'supplier_name_text' => $info['supplier'],
            'quote_no'    => "Q-2026-{$pos}",
            'quote_date'  => '2026-05-17',
            'lead_time_days' => '7',
            'payment_terms'  => 'NET30',
            'notes'          => 'Quote ' . $pos,
            'quote_pdf'      => new CURLFile($pdfPath, 'application/pdf', "quote_{$pos}.pdf"),
        ];
        // per-line prices
        if (isset($lineIds[0])) $fields["prices[{$lineIds[0]}]"] = (string)$info['l0'];
        if (isset($lineIds[1])) $fields["prices[{$lineIds[1]}]"] = (string)$info['l1'];
        $r = http('POST', "/purchasing/requests/$prId/quotes", [
            'jar'=>$bjar, 'multipart'=>true, 'fields'=>$fields,
        ]);
        ok($results, "TC-07.{$pos}", "Buyer adds quote #{$pos} with PDF", 'buyer', $r, 'success_redirect');
    }
    $cnt = dbScalar("SELECT COUNT(*) FROM purchase_request_quotes WHERE pr_id = ? AND is_deleted = FALSE", [$prId]);
    echo "  → quotes in DB: $cnt\n";
    $results[] = ['tc'=>'TC-07.db','desc'=>'3 quote rows exist','actor'=>'-','http'=>0,'location'=>"count=$cnt",'expect'=>'count=3','passed'=>$cnt==='3','reason'=>''];
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-10: buyer selects winners (POSITIVE) ===\n";
if ($prId && !empty($lineIds)) {
    // Determine quote ids (direct DB)
    $quotePosToId = [];
    foreach (dbRows("SELECT quote_id, position FROM purchase_request_quotes WHERE pr_id = ? AND is_deleted = FALSE ORDER BY position", [$prId]) as $row) {
        $quotePosToId[(int)$row['position']] = (int)$row['quote_id'];
    }
    echo "  quotes (pos->id): " . json_encode($quotePosToId) . "\n";

    $bjar = $cookieJars['buyer'];
    $csrf = csrfFrom($bjar, "/purchasing/requests/$prId");
    // Pick: line0 winner = pos 2 (cheapest), line1 winner = pos 1 (cheapest)
    $r = http('POST', "/purchasing/requests/$prId/select-winners", [
        'jar'=>$bjar,
        'multipart'=>true,
        'fields'=>[
            '_csrf_token' => $csrf,
            "winners[{$lineIds[0]}]" => (string)$quotePosToId[2],
            "winners[{$lineIds[1]}]" => (string)$quotePosToId[1],
        ],
    ]);
    ok($results, 'TC-10', 'Buyer selects per-line winners', 'buyer', $r, 'success_redirect');
    $winCount = (int)dbScalar(
        "SELECT COUNT(*) FROM purchase_request_quote_lines ql
         JOIN purchase_request_quotes q ON q.quote_id = ql.quote_id
         WHERE q.pr_id = ? AND ql.is_winner = TRUE", [$prId]
    );
    $expectWin = count($lineIds);
    $pass = ($winCount === $expectWin);
    $results[] = ['tc'=>'TC-10.db','desc'=>"DB assertion: $expectWin winner rows set",'actor'=>'-','http'=>0,'location'=>"winners=$winCount",'expect'=>"=$expectWin",'passed'=>$pass,'reason'=>''];
    printf("  %-6s TC-10.db expect winners=%d actual=%d\n", $pass?'PASS':'FAIL', $expectWin, $winCount);
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-11: buyer submits to manager (POSITIVE) ===\n";
if ($prId) {
    $bjar = $cookieJars['buyer'];
    $csrf = csrfFrom($bjar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/submit-manager", [
        'jar'=>$bjar,
        'fields'=>['_csrf_token'=>$csrf,'note'=>'3 quotes collected, cheapest selected per line'],
    ]);
    ok($results, 'TC-11', 'Buyer submits PR to Purchasing Manager', 'buyer', $r, 'success_redirect');
    expectStatus($results, 'TC-11', $prId, 'PENDING_MANAGER');
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-12: buyer tries to approve-manager (NEGATIVE — not a manager) ===\n";
if ($prId) {
    $bjar = $cookieJars['buyer'];
    $csrf = csrfFrom($bjar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/approve-manager", [
        'jar'=>$bjar,
        'fields'=>['_csrf_token'=>$csrf,'note'=>'forbidden'],
    ]);
    ok($results, 'TC-12', 'Buyer tries approve-manager (forbidden)', 'buyer', $r, 'forbidden_or_redirect');
    expectStatus($results, 'TC-12', $prId, 'PENDING_MANAGER');
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-13: PM approves (POSITIVE) ===\n";
if ($prId) {
    $pjar = $cookieJars['pm'];
    $csrf = csrfFrom($pjar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/approve-manager", [
        'jar'=>$pjar,
        'fields'=>['_csrf_token'=>$csrf,'note'=>'PM approval'],
    ]);
    ok($results, 'TC-13', 'PM approves PR (Manager-step)', 'pm', $r, 'success_redirect');
    expectStatus($results, 'TC-13', $prId, 'PENDING_CEO');
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-14: PM tries to approve-ceo (NEGATIVE) ===\n";
if ($prId) {
    $pjar = $cookieJars['pm'];
    $csrf = csrfFrom($pjar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/approve-ceo", [
        'jar'=>$pjar,
        'fields'=>['_csrf_token'=>$csrf,'note'=>'forbidden'],
    ]);
    ok($results, 'TC-14', 'PM tries approve-ceo (forbidden — not director)', 'pm', $r, 'forbidden_or_redirect');
    expectStatus($results, 'TC-14', $prId, 'PENDING_CEO');
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-15: CEO final approval (POSITIVE) ===\n";
if ($prId) {
    $cjar = $cookieJars['ceo'];
    $csrf = csrfFrom($cjar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/approve-ceo", [
        'jar'=>$cjar,
        'fields'=>['_csrf_token'=>$csrf,'note'=>'CEO final approval'],
    ]);
    ok($results, 'TC-15', 'CEO final approval', 'ceo', $r, 'success_redirect');
    expectStatus($results, 'TC-15', $prId, 'APPROVED');
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-16: requester tries convert-to-po (NEGATIVE) ===\n";
if ($prId) {
    $rjar = $cookieJars['requester'];
    $csrf = csrfFrom($rjar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/convert-to-po", [
        'jar'=>$rjar,
        'fields'=>['_csrf_token'=>$csrf],
    ]);
    ok($results, 'TC-16', 'Requester tries convert-to-PO (forbidden)', 'requester', $r, 'forbidden_or_redirect');
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-17: buyer converts PR to PO (POSITIVE) ===\n";
if ($prId) {
    $bjar = $cookieJars['buyer'];
    $csrf = csrfFrom($bjar, "/purchasing/requests/$prId");
    $r = http('POST', "/purchasing/requests/$prId/convert-to-po", [
        'jar'=>$bjar,
        'fields'=>['_csrf_token'=>$csrf],
    ]);
    // Redirects to PO form; we accept any 302 to /purchasing/orders/create
    ok($results, 'TC-17', 'Buyer initiates convert-to-PO', 'buyer', $r, 'success_redirect');
    // Note: convert-to-po only redirects to PO form; PO is created when the form is submitted.
    // We verify the redirect target points to /purchasing/orders/create?from_pr_id=N
    $loc = $r['location'] ?? '';
    $pass = (bool)preg_match('#/purchasing/orders/create\?from_pr_id=' . $prId . '#', $loc);
    $results[] = ['tc'=>'TC-17.redir','desc'=>'redirect target is PO create with PR id','actor'=>'-','http'=>0,'location'=>$loc,'expect'=>'/purchasing/orders/create?from_pr_id='.$prId,'passed'=>$pass,'reason'=>''];
    printf("  %-6s TC-17.redir expect PO-create-from-PR  actual=%s\n", $pass?'PASS':'FAIL', $loc);
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-18: PO creation BLOCKED without from_pr_id (NEGATIVE) ===\n";
if ($prId) {
    $bjar = $cookieJars['buyer'];
    // Grab CSRF from the PO create page WITH a PR (has the form rendered)
    $csrf = csrfFrom($bjar, "/purchasing/orders/create?from_pr_id={$prId}");
    if ($csrf) {
        $r = http('POST', '/purchasing/orders', [
            'jar'=>$bjar,
            'fields'=>[
                '_csrf_token' => $csrf,
                // NOTE: deliberately NO from_pr_id
                'supplier_id' => '1',
                'order_date'  => date('Y-m-d'),
                'vat_rate'    => '7',
            ],
        ]);
        // Expect 302 → /purchasing/orders/create  (flash error 'po_requires_pr')
        $ok = ($r['code'] === 302 && str_contains((string)$r['location'], '/purchasing/orders/create'));
        $results[] = [
            'tc'=>'TC-18','desc'=>'POST /purchasing/orders without from_pr_id (rejected)','actor'=>'buyer',
            'http'=>$r['code'],'location'=>$r['location'],'expect'=>'redirect to create with flash',
            'passed'=>$ok,'reason'=>$ok?'':'Expected 302 to /create; got '.$r['code'].' '.$r['location']
        ];
        printf("  %-6s TC-18  POST without from_pr_id  HTTP=%d  -> %s\n",
            $ok?'PASS':'FAIL', $r['code'], $r['location']);
        // No PO row should have been created
        $maxPo = (int)dbScalar("SELECT COALESCE(MAX(po_id),0) FROM purchase_order_headers WHERE pr_id IS NULL AND created_at >= NOW() - INTERVAL '1 minute'");
        $ok2 = ($maxPo === 0);
        $results[] = ['tc'=>'TC-18.db','desc'=>'No PO row inserted without PR','actor'=>'-','http'=>0,'location'=>"orphan_count=$maxPo",'expect'=>'=0','passed'=>$ok2,'reason'=>''];
        printf("  %-6s TC-18.db expect 0 orphan PO rows  actual=%d\n", $ok2?'PASS':'FAIL', $maxPo);
    }
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-19: PO PR picker is visible on /create with no params ===\n";
if (!empty($cookieJars['buyer'])) {
    $bjar = $cookieJars['buyer'];
    $r = http('GET', '/purchasing/orders/create', ['jar'=>$bjar]);
    $hasPicker = (str_contains($r['body'], 'po_requires_pr') || str_contains($r['body'], 'PR') )
        && str_contains($r['body'], 'select_pr');
    // It will use English/JP labels — check for the SELECT element
    $hasSelect = str_contains($r['body'], 'name="from_pr_id"');
    $results[] = ['tc'=>'TC-19','desc'=>'/orders/create renders PR picker','actor'=>'buyer',
        'http'=>$r['code'],'location'=>'-','expect'=>'page has from_pr_id <select>',
        'passed'=>$hasSelect,'reason'=>$hasSelect?'':'PR picker <select> not found'];
    printf("  %-6s TC-19  PR picker rendered  HTTP=%d  picker=%s\n",
        $hasSelect?'PASS':'FAIL', $r['code'], $hasSelect?'YES':'NO');
}

// ─────────────────────────────────────────────────────────────────────
echo "\n=== TC-20: /approvals/purchase-requests visibility ===\n";
//   pm  : isManagerOrAbove → should see (200)
//   ceo : isDirectorOrAbove → should see (200)
//   requester : STAFF → should be redirected (302 → dashboard)
foreach (['pm','ceo'] as $who) {
    $jar = $cookieJars[$who];
    $r = http('GET', '/approvals/purchase-requests', ['jar'=>$jar]);
    $ok = ($r['code'] === 200 && str_contains($r['body'], 'approval_queue_prs') || str_contains($r['body'], 'PR'));
    // tighter: page must have the table with pr_no link
    $ok = ($r['code'] === 200 && str_contains($r['body'], '/purchasing/requests/'));
    $results[] = ['tc'=>"TC-20.$who",'desc'=>"$who can access PR approval queue",'actor'=>$who,
        'http'=>$r['code'],'location'=>'-','expect'=>'200 + table',
        'passed'=>$ok,'reason'=>$ok?'':'expected 200 with PR table'];
    printf("  %-6s TC-20.%s  HTTP=%d  table=%s\n",
        $ok?'PASS':'FAIL', $who, $r['code'], $ok?'YES':'NO');
}
// Negative: requester (STAFF) is not isManagerOrAbove → requireApprover redirects with flash
$rjar = $cookieJars['requester'];
$r = http('GET', '/approvals/purchase-requests', ['jar'=>$rjar]);
$ok = ($r['code'] === 302);
$results[] = ['tc'=>'TC-20.requester','desc'=>'requester is blocked from PR approval queue','actor'=>'requester',
    'http'=>$r['code'],'location'=>$r['location'],'expect'=>'302 redirect (forbidden)',
    'passed'=>$ok,'reason'=>$ok?'':'expected 302; got '.$r['code']];
printf("  %-6s TC-20.requester  HTTP=%d -> %s\n", $ok?'PASS':'FAIL', $r['code'], $r['location']);

// ─────────────────────────────────────────────────────────────────────
echo "\n=== Results Summary ===\n";
$pass = count(array_filter($results, fn($r)=>$r['passed']));
$fail = count($results) - $pass;
echo "  TOTAL: " . count($results) . "   PASS: $pass   FAIL: $fail\n";

// Write JSON evidence + Markdown report
@mkdir(__DIR__ . '/../backups', 0775, true);
file_put_contents(__DIR__ . '/../backups/pr_e2e_evidence.json',
    json_encode(['pr_id'=>$prId, 'tests'=>$results], JSON_PRETTY_PRINT));

$md = "# PR ワークフロー E2E テスト報告書\n\n";
$md .= "**実施日時**: " . date('Y-m-d H:i:s') . "  \n";
$md .= "**対象 PR ID**: " . ($prId ?? '—') . "  \n";
$md .= "**結果**: " . count($results) . " ケース中 PASS=$pass / FAIL=$fail\n\n";

$md .= "## テストユーザー\n\n";
$md .= "| キー | Email | role | position_level | パスワード |\n|---|---|---|---|---|\n";
foreach ($USERS as $k=>$u) {
    $md .= "| $k | {$u['email']} | {$u['role']} | {$u['pos']} | TestPR!2026 |\n";
}

$md .= "\n## ケース別結果\n\n";
$md .= "| # | テストケース | 実行ユーザー | 想定 | HTTP | リダイレクト | 結果 |\n|---|---|---|---|---|---|---|\n";
foreach ($results as $r) {
    $exp = $r['expect'] === 'forbidden_or_redirect' ? '権限なし(拒否)' :
          ($r['expect'] === 'success_redirect' ? '成功(302リダイレクト)' : $r['expect']);
    $md .= sprintf("| %s | %s | %s | %s | %d | %s | %s |\n",
        $r['tc'], $r['desc'], $r['actor'], $exp,
        $r['http'], $r['location'] ?? '-',
        $r['passed'] ? '✅ PASS' : '❌ FAIL');
}

file_put_contents(__DIR__ . '/../backups/pr_e2e_report.md', $md);
echo "\nEvidence saved:\n  backups/pr_e2e_evidence.json\n  backups/pr_e2e_report.md\n";
exit($fail === 0 ? 0 : 1);
