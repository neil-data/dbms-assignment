<?php
declare(strict_types=1);

$baseUrl = "http://127.0.0.1:8080";

function httpRequest(string $method, string $path, array $data = [], string $cookieFile = "", bool $followRedirects = false): array {
    global $baseUrl;
    $url = $baseUrl . "/" . ltrim($path, "/");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($followRedirects) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    }
    
    $headers = [];
    if (!empty($data)) {
        $json = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = "Content-Type: application/json";
    }
    
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $json = json_decode((string)$raw, true);
    return [
        "code" => $httpCode,
        "raw"  => $raw,
        "json" => $json
    ];
}

$passed = 0;
$failed = 0;

function assertHttp(string $title, bool $cond, string $info = ""): void {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$title}" . ($info ? " ({$info})" : "") . PHP_EOL;
    } else {
        $failed++;
        echo "  [FAIL] {$title}" . ($info ? " ({$info})" : "") . PHP_EOL;
    }
}

echo PHP_EOL . "================================================================================" . PHP_EOL;
echo "  CEMS HTTP ENDPOINT & API ARCHITECTURE AUDIT" . PHP_EOL;
echo "================================================================================" . PHP_EOL . PHP_EOL;

// 1. Static HTML Pages
$pages = [
    "index.html",
    "events.html",
    "event-details.html?id=1",
    "login.html",
    "register.html",
    "student-dashboard.html",
    "admin.html"
];

echo "--- 1. Testing Public & Portal Pages ---" . PHP_EOL;
foreach ($pages as $p) {
    $res = httpRequest("GET", $p);
    assertHttp("HTTP 200 for /{$p}", $res["code"] === 200, "Length: " . strlen((string)$res["raw"]));
}

// Unauthenticated access to /admin/database-demo.php correctly redirects to /login.html
$demoUnauth = httpRequest("GET", "admin/database-demo.php");
assertHttp("Unauthenticated access to /admin/database-demo.php redirects (302)", $demoUnauth["code"] === 302);

// 2. Health Check / Status API
echo PHP_EOL . "--- 2. Testing API Health Check & Data Providers ---" . PHP_EOL;
$statusRes = httpRequest("GET", "backend/auth/me.php");
assertHttp("GET /backend/auth/me.php", $statusRes["code"] === 200 && ($statusRes["json"]["data"]["database_connected"] ?? false) === true);

$eventsRes = httpRequest("GET", "backend/events/list.php");
assertHttp("GET /backend/events/list.php", $eventsRes["code"] === 200 && count($eventsRes["json"]["data"] ?? []) >= 8, count($eventsRes["json"]["data"] ?? []) . " events returned");

$catsRes = httpRequest("GET", "backend/categories/list.php");
assertHttp("GET /backend/categories/list.php", $catsRes["code"] === 200 && count($catsRes["json"]["data"] ?? []) >= 6);

$deptsRes = httpRequest("GET", "backend/departments/list.php");
assertHttp("GET /backend/departments/list.php", $deptsRes["code"] === 200 && count($deptsRes["json"]["data"] ?? []) >= 5);

$venuesRes = httpRequest("GET", "backend/venues/list.php");
assertHttp("GET /backend/venues/list.php", $venuesRes["code"] === 200 && count($venuesRes["json"]["data"] ?? []) >= 4);

// 3. Authorization Enforcement (Unauthenticated Access Rejected)
echo PHP_EOL . "--- 3. Testing RBAC Access Control & Route Protection ---" . PHP_EOL;
$unauthReport = httpRequest("GET", "backend/reports/dashboard.php");
assertHttp("GET /backend/reports/dashboard.php blocked without auth (403)", $unauthReport["code"] === 403);

$unauthRegList = httpRequest("GET", "backend/registrations/list.php");
assertHttp("GET /backend/registrations/list.php blocked without auth (401)", $unauthRegList["code"] === 401);

// 4. Student Authentication Lifecycle
echo PHP_EOL . "--- 4. Testing Student Authentication Flow ---" . PHP_EOL;
$studentJar = tempnam(sys_get_temp_dir(), "student_sess_");

$badLogin = httpRequest("POST", "backend/auth/student_login.php", [
    "email" => "aarav.sharma@campus.edu",
    "password" => "WrongPassword"
], $studentJar);
assertHttp("Invalid password rejected (401)", $badLogin["code"] === 401);

$goodLogin = httpRequest("POST", "backend/auth/student_login.php", [
    "email" => "aarav.sharma@campus.edu",
    "password" => "Student@123"
], $studentJar);
assertHttp("Valid student login (200)", $goodLogin["code"] === 200 && ($goodLogin["json"]["success"] ?? false) === true);

$studentMe = httpRequest("GET", "backend/auth/me.php", [], $studentJar);
assertHttp("Session recognized as student role", ($studentMe["json"]["data"]["role"] ?? "") === "student");

$studentRegs = httpRequest("GET", "backend/registrations/list.php", [], $studentJar);
assertHttp("Student can view registered passes", $studentRegs["code"] === 200 && is_array($studentRegs["json"]["data"]));

// Student attempting admin endpoint must be blocked with 403 Forbidden
$studentAdminAttempt = httpRequest("GET", "backend/reports/dashboard.php", [], $studentJar);
assertHttp("Student attempting Admin Dashboard blocked (403)", $studentAdminAttempt["code"] === 403);

// 5. Admin Authentication & Dashboard Reports
echo PHP_EOL . "--- 5. Testing Admin Authentication & Reports ---" . PHP_EOL;
$adminJar = tempnam(sys_get_temp_dir(), "admin_sess_");

$adminLogin = httpRequest("POST", "backend/auth/admin_login.php", [
    "username" => "admin",
    "password" => "Admin@123"
], $adminJar);
assertHttp("Admin login successful (200)", $adminLogin["code"] === 200 && ($adminLogin["json"]["success"] ?? false) === true);

$adminMe = httpRequest("GET", "backend/auth/me.php", [], $adminJar);
assertHttp("Session recognized as admin role", ($adminMe["json"]["data"]["role"] ?? "") === "admin");

$adminDash = httpRequest("GET", "backend/reports/dashboard.php", [], $adminJar);
assertHttp("Admin can access reports/dashboard.php", $adminDash["code"] === 200 && isset($adminDash["json"]["data"]["totalStudents"]));

// Authenticated admin accessing /admin/database-demo.php
$demoAuth = httpRequest("GET", "admin/database-demo.php", [], $adminJar);
assertHttp("Authenticated admin access to /admin/database-demo.php (200)", $demoAuth["code"] === 200, "Length: " . strlen((string)$demoAuth["raw"]));

// 6. DBMS Demonstration Endpoints (Protected by Admin Auth)
echo PHP_EOL . "--- 6. Testing DBMS Demonstration Endpoints ---" . PHP_EOL;
$demoOverview = httpRequest("GET", "backend/database-demo/overview.php", [], $adminJar);
assertHttp("GET /backend/database-demo/overview.php (200)", $demoOverview["code"] === 200 && count($demoOverview["json"]["data"]["tables"] ?? []) === 8);

$demoQueries = httpRequest("GET", "backend/database-demo/queries.php", [], $adminJar);
assertHttp("GET /backend/database-demo/queries.php (200)", $demoQueries["code"] === 200 && count($demoQueries["json"]["data"] ?? []) >= 4);

$demoCommit = httpRequest("POST", "backend/database-demo/test_transaction.php", ["action" => "commit"], $adminJar);
assertHttp("POST /backend/database-demo/test_transaction.php [commit]", $demoCommit["code"] === 200 && ($demoCommit["json"]["success"] ?? false) === true);

$demoRollback = httpRequest("POST", "backend/database-demo/test_transaction.php", ["action" => "rollback"], $adminJar);
assertHttp("POST /backend/database-demo/test_transaction.php [rollback]", $demoRollback["code"] === 200 && ($demoRollback["json"]["success"] ?? false) === true);

$demoConstraint = httpRequest("POST", "backend/database-demo/test_constraint.php", [], $adminJar);
assertHttp("POST /backend/database-demo/test_constraint.php triggers 23000 violation", $demoConstraint["code"] === 200 && ($demoConstraint["json"]["data"]["mysql_error_code"] ?? "") === "23000");

// Clean up cookie files
@unlink($studentJar);
@unlink($adminJar);

echo PHP_EOL . "================================================================================" . PHP_EOL;
echo "  HTTP AUDIT RESULTS: {$passed} PASSED, {$failed} FAILED (TOTAL: " . ($passed + $failed) . ")" . PHP_EOL;
echo "================================================================================" . PHP_EOL . PHP_EOL;

if ($failed > 0) exit(1);
exit(0);