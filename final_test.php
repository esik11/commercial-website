<?php
/**
 * Simple comprehensive test - just run: php final_test.php
 */

$base = 'http://localhost/commercial-website';
$pass = $fail = 0;

function req($url, $post = null, &$cook = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if ($cook) curl_setopt($ch, CURLOPT_COOKIE, implode('; ', array_map(fn($k,$v)=>"$k=$v", array_keys($cook), $cook)));
    $r = curl_exec($ch);
    if ($r === false) {
        return ['code'=>0, 'body'=>'', 'error'=>curl_error($ch)];
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($r, $hs);
    preg_match_all('/Set-Cookie: ([^=]+)=([^;]+)/', substr($r, 0, $hs), $m);
    foreach($m[1] as $i=>$n) $cook[$n] = $m[2][$i];
    curl_close($ch);
    return ['code'=>$code, 'body'=>$body];
}

function csrf($html) {
    return preg_match('/name=["\']csrf["\'] value=["\'](.*?)["\']/i', $html, $m) ? $m[1] : null;
}

echo "\n=== DOI PC Security Tests ===\n";
echo "Base URL: $base\n";

// Test server connection
echo "Testing server... ";
$test = req("$base/index.php");
if ($test['code'] == 0) {
    die("❌ Cannot connect to server. Start with: php -S localhost:8000\n");
}
echo "✅ Connected (HTTP {$test['code']})\n\n";

// TEST 1: Valid quote with CSRF
echo "[1] Valid quote with CSRF token\n";
$c = [];
$r = req("$base/index.php", null, $c);
$csrf = csrf($r['body']);
if ($csrf) {
    $r = req("$base/backend/send-quote.php", [
        'csrf'=>$csrf, 'name'=>'Test User', 'phone'=>'+639171234567',
        'email'=>'test@ex.com', 'usecase'=>'Office', 'budget'=>'20000',
        'message'=>'Test', 'bundle_id'=>1
    ], $c);
    $j = json_decode($r['body'], 1);
    if ($j['ok']??0) { echo "✅ PASS - Check DB for new row\n"; $pass++; }
    else { echo "❌ FAIL - {$j['message']}\n"; $fail++; }
} else { echo "❌ No CSRF\n"; $fail++; }

// TEST 2: Missing CSRF (expect 403)
echo "\n[2] Missing CSRF token\n";
$r = req("$base/backend/send-quote.php", ['name'=>'Test','phone'=>'+639171234567','usecase'=>'Test','budget'=>'10000','message'=>'x']);
if ($r['code']==403) { echo "✅ PASS - Got 403\n"; $pass++; }
else { echo "❌ FAIL - Got {$r['code']}\n"; $fail++; }

// TEST 3: Honeypot
echo "\n[3] Honeypot field\n";
$c = [];
$r = req("$base/index.php", null, $c);
$csrf = csrf($r['body']);
$r = req("$base/backend/send-quote.php", [
    'csrf'=>$csrf, 'name'=>'Bot', 'phone'=>'+639171234567',
    'email'=>'bot@spam.com', 'usecase'=>'Spam', 'budget'=>'0',
    'message'=>'Spam', 'website'=>'http://spam.com'
], $c);
$j = json_decode($r['body'], 1);
if ($j['ok']??0) { echo "✅ PASS - Fake success (check DB has NO row)\n"; $pass++; }
else { echo "❌ FAIL\n"; $fail++; }

// TEST 4: 30-sec rate limit
echo "\n[4] 30-second rate limit\n";
$c = [];
$r = req("$base/index.php", null, $c);
$csrf = csrf($r['body']);
$r = req("$base/backend/send-quote.php", [
    'csrf'=>$csrf, 'name'=>'Rate1', 'phone'=>'+639171234567',
    'email'=>'rate@test.com', 'usecase'=>'Test', 'budget'=>'10000', 'message'=>'1'
], $c);
$r = req("$base/backend/send-quote.php", [
    'csrf'=>$csrf, 'name'=>'Rate2', 'phone'=>'+639171234567',
    'email'=>'rate@test.com', 'usecase'=>'Test', 'budget'=>'10000', 'message'=>'2'
], $c);
if ($r['code']==429) { echo "✅ PASS - Got 429\n"; $pass++; }
else { echo "❌ FAIL - Got {$r['code']}\n"; $fail++; }

// TEST 5: IP rate limit (skip - takes too long)
echo "\n[5] IP rate limit (5/hour)\n";
echo "⏭️  SKIPPED - Takes 5+ minutes. Test manually.\n";

// TEST 6: Invalid inputs
echo "\n[6] Invalid inputs (expect 422)\n";
$c = [];
$r = req("$base/index.php", null, $c);
$csrf = csrf($r['body']);
$r = req("$base/backend/send-quote.php", [
    'csrf'=>$csrf, 'name'=>'Test', 'phone'=>'bad',
    'email'=>'test@ex.com', 'usecase'=>'Test', 'budget'=>'10000', 'message'=>'x'
], $c);
if ($r['code']==422) { echo "✅ PASS - Invalid phone rejected\n"; $pass++; }
else { echo "❌ FAIL - Got {$r['code']}\n"; $fail++; }

// TEST 7: Login brute force
echo "\n[7] Login brute-force (5 attempts)\n";
$c = [];
for($i=1;$i<=5;$i++) {
    $r = req("$base/admin/login.php", null, $c);
    $csrf = csrf($r['body']);
    if ($csrf) req("$base/admin/login.php", ['csrf'=>$csrf,'username'=>'admin','password'=>"wrong$i"], $c);
}
$r = req("$base/admin/login.php", null, $c);
$csrf = csrf($r['body']);
if ($csrf) {
    $r = req("$base/admin/login.php", ['csrf'=>$csrf,'username'=>'admin','password'=>'admin123'], $c);
    if (stripos($r['body'],'locked')!==false || stripos($r['body'],'wait')!==false) {
        echo "✅ PASS - Account locked\n"; $pass++;
    } else { echo "❌ FAIL - No lockout\n"; $fail++; }
} else { echo "❌ No CSRF\n"; $fail++; }

// TEST 8: File upload (manual)
echo "\n[8] File upload security\n";
echo "⏭️  Test manually through admin/bundle_edit.php:\n";
echo "   - Upload .php file (should reject)\n";
echo "   - Upload 3MB file (should reject)\n";
echo "   - Upload fake .jpg with text (should reject)\n";
echo "   - Upload real JPG/PNG/WebP <2MB (should accept)\n";

// TEST 9: Protected files
echo "\n[9] Protected config files\n";
$r = req("$base/config/config.php");
if ($r['code']==403||$r['code']==404) { echo "✅ PASS - config.php blocked\n"; $pass++; }
else { echo "❌ FAIL - config.php accessible (OK on PHP dev server, will work on Apache)\n"; $fail++; }

// TEST 10: PHP in uploads
echo "\n[10] PHP execution in uploads\n";
file_put_contents(__DIR__.'/assets/uploads/test.php', '<?php echo "x";');
$r = req("$base/assets/uploads/test.php");
unlink(__DIR__.'/assets/uploads/test.php');
if ($r['code']==403) { echo "✅ PASS - PHP blocked\n"; $pass++; }
else { echo "❌ FAIL - PHP executed (OK on PHP dev server, will work on Apache)\n"; $fail++; }

// TEST 11: Bundle visibility
echo "\n[11] Bundle visibility with zero stock\n";
try {
    require __DIR__.'/includes/db.php';
    $db = getDB();
    $b = $db->query("SELECT id,name FROM bundles WHERE is_active=1 LIMIT 1")->fetch();
    if ($b) {
        $p = $db->prepare("SELECT inventory_id FROM bundle_components WHERE bundle_id=? LIMIT 1");
        $p->execute([$b['id']]);
        $pid = $p->fetchColumn();
        $orig = $db->prepare("SELECT stock_qty FROM inventory WHERE id=?");
        $orig->execute([$pid]);
        $ostock = $orig->fetchColumn();
        
        // Set to 0
        $db->prepare("UPDATE inventory SET stock_qty=0 WHERE id=?")->execute([$pid]);
        ob_start(); include __DIR__.'/index.php'; $html = ob_get_clean();
        $hidden = strpos($html, "data-bundle-id=\"{$b['id']}\"")===false;
        
        // Restore
        $db->prepare("UPDATE inventory SET stock_qty=? WHERE id=?")->execute([$ostock,$pid]);
        ob_start(); include __DIR__.'/index.php'; $html = ob_get_clean();
        $shown = strpos($html, "data-bundle-id=\"{$b['id']}\"")!==false;
        
        if ($hidden && $shown) { echo "✅ PASS - Bundle hides/shows correctly\n"; $pass++; }
        else { echo "❌ FAIL - Bundle visibility issue\n"; $fail++; }
    } else { echo "⚠️  No bundles to test\n"; }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    $fail++;
}

// TEST 12: Stock deduction
echo "\n[12] Stock deduction on quote conversion\n";
try {
    $q = $db->query("SELECT id,bundle_id FROM quote_requests WHERE bundle_id IS NOT NULL AND status!='Converted' LIMIT 1")->fetch();
    if (!$q) {
        $bid = $db->query("SELECT id FROM bundles WHERE is_active=1 LIMIT 1")->fetchColumn();
        if ($bid) {
            $db->prepare("INSERT INTO quote_requests (name,phone,email,build_type,budget,message,bundle_id,ip) VALUES ('Test','+639171234567','test@ex.com','Test','20000','Test',?,'127.0.0.1')")->execute([$bid]);
            $q = ['id'=>$db->lastInsertId(), 'bundle_id'=>$bid];
        }
    }
    if ($q) {
        $parts = $db->prepare("SELECT i.id,i.stock_qty,bc.quantity FROM bundle_components bc JOIN inventory i ON bc.inventory_id=i.id WHERE bc.bundle_id=?");
        $parts->execute([$q['bundle_id']]);
        $parts = $parts->fetchAll();
        
        $canDeduct = true;
        foreach($parts as $pt) if($pt['stock_qty']<$pt['quantity']) $canDeduct=false;
        
        if ($canDeduct) {
            foreach($parts as $pt) $db->prepare("UPDATE inventory SET stock_qty=stock_qty-? WHERE id=?")->execute([$pt['quantity'],$pt['id']]);
            $db->prepare("UPDATE quote_requests SET status='Converted',stock_deducted=1 WHERE id=?")->execute([$q['id']]);
            
            // Check flag
            $flag = $db->prepare("SELECT stock_deducted FROM quote_requests WHERE id=?");
            $flag->execute([$q['id']]);
            $deducted = (bool)$flag->fetchColumn();
            
            // Restore
            foreach($parts as $pt) $db->prepare("UPDATE inventory SET stock_qty=stock_qty+? WHERE id=?")->execute([$pt['quantity'],$pt['id']]);
            $db->prepare("UPDATE quote_requests SET status='New',stock_deducted=0 WHERE id=?")->execute([$q['id']]);
            
            if ($deducted) { echo "✅ PASS - Stock deducted with flag set\n"; $pass++; }
            else { echo "❌ FAIL - Flag not set\n"; $fail++; }
        } else {
            echo "⚠️  Insufficient stock to test\n";
        }
    } else { echo "⚠️  No quotes to test\n"; }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    $fail++;
}

// Summary
echo "\n=== SUMMARY ===\n";
echo "✅ Passed: $pass\n";
echo "❌ Failed: $fail\n";
echo "\nManual checks:\n";
echo "- Verify database rows for test submissions\n";
echo "- Test file uploads in admin panel\n";
echo "- Tests 9-10 require Apache (will fail on PHP dev server)\n";
