<?php
/**
 * E2E test for 2-step Customer/Supplier approval flow.
 *
 *   STAFF creates customer/supplier → PENDING_MANAGER
 *   PM approves (manager step) → PENDING_CEO
 *   PM tries CEO step → BLOCKED
 *   CEO approves → APPROVED
 */
declare(strict_types=1);

$BASE = 'http://localhost:8090';
$PASS = 'TestPR!2026';

$DBH = new PDO('pgsql:host=localhost;port=5432;dbname=pegasus_erp', 'postgres',
    getenv('PG_PASSWORD') ?: 'postgres', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function http(string $method, string $url, string $jar, array $fields = []): array {
    global $BASE;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => str_starts_with($url,'http')?$url:$BASE.$url,
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true,
        CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_COOKIEJAR=>$jar, CURLOPT_COOKIEFILE=>$jar,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsz  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $hdrs = substr($resp,0,$hsz); $body = substr($resp,$hsz);
    $loc  = preg_match('/^Location:\s*(.+)$/mi',$hdrs,$m) ? trim($m[1]) : null;
    return ['code'=>$code,'location'=>$loc,'body'=>$body];
}
function csrf(string $body): string {
    return preg_match('/_csrf_token"\s*value="([^"]+)"/',$body,$m) ? $m[1] : '';
}
function login(string $email, string $pass): string {
    $jar = sys_get_temp_dir()."/m2step_".md5($email).".cookie"; @unlink($jar);
    $r = http('GET','/login',$jar);
    $r = http('POST','/login',$jar,['_csrf_token'=>csrf($r['body']),'username'=>$email,'password'=>$pass]);
    return $jar;
}
function dbStatus(string $table, int $id): string {
    global $DBH;
    $st=$DBH->prepare("SELECT approval_status FROM $table WHERE ".($table==='customers'?'customer_id':'supplier_id')."=?");
    $st->execute([$id]);
    return (string)$st->fetchColumn();
}

$tests = []; $pass = 0; $fail = 0;
function assertx(string $tc, bool $ok, string $msg) {
    global $tests, $pass, $fail;
    $tests[] = ['tc'=>$tc,'msg'=>$msg,'ok'=>$ok];
    if ($ok) $pass++; else $fail++;
    printf("  %-6s %s  %s\n", $ok?'PASS':'FAIL', $tc, $msg);
}

echo "\n=== Login ===\n";
$jReq = login('pr_requester@test.local', $PASS);
$jPm  = login('pr_pm@test.local', $PASS);
$jCeo = login('pr_ceo@test.local', $PASS);
echo "  3 users logged in\n";

echo "\n=== TC-A: requester creates customer (-> PENDING_MANAGER) ===\n";
$r = http('GET','/master/customers',$jReq);
$tok = csrf($r['body']);
$code = 'CUS-E2E-' . substr(bin2hex(random_bytes(2)),0,4);
$r = http('POST','/master/customers',$jReq, [
    '_csrf_token'=>$tok,
    'customer_code'=>$code,
    'customer_name'=>'E2E Test Co Ltd',
    'country'=>'TH',
    'tax_id'=>'0000000000000',
    'credit_limit'=>'100000',
    'payment_terms'=>'30',
    'currency_code'=>'THB',
]);
assertx('TC-A','POST=302' === ('POST='.$r['code']), "create customer HTTP={$r['code']}");
// Find newly created
$newCust = $DBH->query("SELECT customer_id, approval_status FROM customers WHERE customer_code='$code' AND is_deleted=false ORDER BY customer_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$cid = (int)($newCust['customer_id'] ?? 0);
assertx('TC-A.db', $newCust && $newCust['approval_status']==='PENDING_MANAGER', "customer.id=$cid status={$newCust['approval_status']}");

echo "\n=== TC-B: PM (Manager) approves customer (-> PENDING_CEO) ===\n";
$r = http('GET','/approvals/customers',$jPm);
$tok = csrf($r['body']);
$r = http('POST',"/approvals/customers/$cid/approve",$jPm,['_csrf_token'=>$tok]);
assertx('TC-B', $r['code']===302, "approve manager step HTTP={$r['code']}");
$st = dbStatus('customers',$cid);
assertx('TC-B.db', $st==='PENDING_CEO', "status after PM approve = $st");

echo "\n=== TC-C: PM tries CEO step (BLOCKED) ===\n";
$r = http('GET','/approvals/customers',$jPm);
$tok = csrf($r['body']);
$r = http('POST',"/approvals/customers/$cid/approve",$jPm,['_csrf_token'=>$tok]);
$st = dbStatus('customers',$cid);
assertx('TC-C.db', $st==='PENDING_CEO', "status still PENDING_CEO (PM blocked from CEO step) = $st");

echo "\n=== TC-D: CEO approves customer (-> APPROVED) ===\n";
$r = http('GET','/approvals/customers',$jCeo);
$tok = csrf($r['body']);
$r = http('POST',"/approvals/customers/$cid/approve",$jCeo,['_csrf_token'=>$tok]);
assertx('TC-D', $r['code']===302, "approve CEO step HTTP={$r['code']}");
$st = dbStatus('customers',$cid);
assertx('TC-D.db', $st==='APPROVED', "status after CEO approve = $st");

echo "\n=== TC-E: requester creates supplier (-> PENDING_MANAGER) ===\n";
$r = http('GET','/master/suppliers',$jReq);
$tok = csrf($r['body']);
$scode = 'SUP-E2E-' . substr(bin2hex(random_bytes(2)),0,4);
$r = http('POST','/master/suppliers',$jReq, [
    '_csrf_token'=>$tok,
    'supplier_code'=>$scode,
    'supplier_name'=>'E2E Supplier Co Ltd',
    'country'=>'TH',
    'tax_id'=>'9999999999999',
    'payment_terms'=>'30',
    'currency_code'=>'THB',
    'wht_rate'=>'3',
]);
assertx('TC-E', $r['code']===302, "create supplier HTTP={$r['code']}");
$newSup = $DBH->query("SELECT supplier_id, approval_status FROM suppliers WHERE supplier_code='$scode' AND is_deleted=false ORDER BY supplier_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$sid = (int)($newSup['supplier_id'] ?? 0);
assertx('TC-E.db', $newSup && $newSup['approval_status']==='PENDING_MANAGER', "supplier.id=$sid status={$newSup['approval_status']}");

echo "\n=== TC-F: PM approves supplier, CEO finalises ===\n";
$r = http('GET','/approvals/suppliers',$jPm);
$tok = csrf($r['body']);
$r = http('POST',"/approvals/suppliers/$sid/approve",$jPm,['_csrf_token'=>$tok]);
assertx('TC-F.pm', dbStatus('suppliers',$sid)==='PENDING_CEO', "PM approve supplier -> PENDING_CEO");
$r = http('GET','/approvals/suppliers',$jCeo);
$tok = csrf($r['body']);
$r = http('POST',"/approvals/suppliers/$sid/approve",$jCeo,['_csrf_token'=>$tok]);
assertx('TC-F.ceo', dbStatus('suppliers',$sid)==='APPROVED', "CEO approve supplier -> APPROVED");

echo "\n=== TC-G: rejection path ===\n";
// create another customer, reject at manager step
$r = http('GET','/master/customers',$jReq);
$tok = csrf($r['body']);
$code2 = 'CUS-REJ-' . substr(bin2hex(random_bytes(2)),0,4);
http('POST','/master/customers',$jReq, [
    '_csrf_token'=>$tok,
    'customer_code'=>$code2,
    'customer_name'=>'Reject Test Co',
    'country'=>'TH', 'tax_id'=>'1234567890123',
    'credit_limit'=>'0','payment_terms'=>'30','currency_code'=>'THB',
]);
$cid2 = (int)$DBH->query("SELECT customer_id FROM customers WHERE customer_code='$code2' ORDER BY customer_id DESC LIMIT 1")->fetchColumn();
$r = http('GET','/approvals/customers',$jPm);
$tok = csrf($r['body']);
$r = http('POST',"/approvals/customers/$cid2/reject",$jPm,['_csrf_token'=>$tok,'reason'=>'test rejection']);
$st = dbStatus('customers',$cid2);
assertx('TC-G.db', $st==='REJECTED', "rejected status = $st");
$row = $DBH->query("SELECT rejection_reason FROM customers WHERE customer_id=$cid2")->fetchColumn();
assertx('TC-G.reason', strpos((string)$row,'test rejection')!==false, "rejection_reason saved: $row");

echo "\n=== TC-H: requester (STAFF) blocked from /approvals/customers ===\n";
$r = http('GET','/approvals/customers',$jReq);
assertx('TC-H', $r['code']===302, "requester redirected HTTP={$r['code']}");

echo "\n=== TC-I: stepper renders on PR approval queue ===\n";
$r = http('GET','/approvals/purchase-requests',$jPm);
$has = (strpos($r['body'],'approval-stepper')!==false);
assertx('TC-I', $has, "stepper HTML on PR queue: " . ($has?'YES':'NO'));

echo "\n=== TC-J: stepper renders on customers approval queue ===\n";
// Look at APPROVED status filter (TC-A customer is there)
$r = http('GET','/approvals/customers?status=APPROVED',$jPm);
$has = (strpos($r['body'],'approval-stepper')!==false);
assertx('TC-J', $has, "stepper HTML on customers queue: " . ($has?'YES':'NO'));

echo "\n=== SUMMARY: PASS=$pass / FAIL=$fail ===\n";
$evidence = ['ts'=>date('c'),'pass'=>$pass,'fail'=>$fail,'tests'=>$tests];
file_put_contents(__DIR__.'/../backups/master_2step_e2e_evidence.json',
    json_encode($evidence, JSON_PRETTY_PRINT));
exit($fail===0 ? 0 : 1);
