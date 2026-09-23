<?php
/**
 * CEMS Complete API Flow & Method Verification Suite
 * Verifies all API endpoints for correct HTTP methods, expected 200s, and strict 405 Method Not Allowed.
 */

$baseUrl = 'http://127.0.0.1:8080/';

$cookieStudent = __DIR__ . '/cookie_student.txt';
$cookieAdmin   = __DIR__ . '/cookie_admin.txt';

if (file_exists($cookieStudent)) unlink($cookieStudent);
if (file_exists($cookieAdmin)) unlink($cookieAdmin);

$sessionCookies = [];

function apiReq(string $method, string $path, $body = null, ?string $cookieKey = null): array {
    global $baseUrl, $sessionCookies;
    $url = $baseUrl . ltrim($path, '/');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);

    $headers = ['Accept: application/json'];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    if ($cookieKey && isset($sessionCookies[$cookieKey])) {
        $headers[] = 'Cookie: ' . $sessionCookies[$cookieKey];
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headerStr = substr((string)$response, 0, $headerSize);
    $rawBody = substr((string)$response, $headerSize);

    if ($cookieKey && preg_match('/Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $headerStr, $m)) {
        $sessionCookies[$cookieKey] = $m[1];
    }

    $json = json_decode((string)$rawBody, true);
    $lastRes = ['status' => $status, 'body' => $json, 'raw' => $rawBody, 'headers' => $headerStr];
    return $lastRes;
}

$testsRun = 0;
$testsPassed = 0;

function assertFlow(string $name, bool $condition, ?array $res = null) {
    global $testsRun, $testsPassed, $lastRes;
    $testsRun++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] $name\n";
    } else {
        $r = $res ?? $lastRes ?? [];
        $status = $r['status'] ?? 'unknown';
        $raw = substr($r['raw'] ?? '', 0, 120);
        echo " [FAIL] $name: Status $status | $raw\n";
    }
}

echo "=================================================================\n";
echo " CEMS API FLOWS & METHOD CONFORMANCE AUDIT\n";
echo "=================================================================\n\n";

// 1. AUTHENTICATION FLOWS
echo "--- 1. Authentication Flows ---\n";
// Student Login with GET -> Must be 405
$res = apiReq('GET', 'backend/auth/student_login.php');
assertFlow('GET student_login.php returns 405 Method Not Allowed', $res['status'] === 405);

// Student Register with GET -> Must be 405
$res = apiReq('GET', 'backend/auth/student_register.php');
assertFlow('GET student_register.php returns 405 Method Not Allowed', $res['status'] === 405);

// Admin Login with GET -> Must be 405
$res = apiReq('GET', 'backend/auth/admin_login.php');
assertFlow('GET admin_login.php returns 405 Method Not Allowed', $res['status'] === 405);

// Student Login with POST (Valid)
$res = apiReq('POST', 'backend/auth/student_login.php', [
    'email' => 'aarav.sharma@campus.edu',
    'password' => 'Student@123'
], $cookieStudent);
assertFlow('POST student_login.php with valid credentials returns 200', $res['status'] === 200 && ($res['body']['success'] ?? false) === true);

// Check Me endpoint with GET (Authenticated Student)
$res = apiReq('GET', 'backend/auth/me.php', null, $cookieStudent);
assertFlow('GET me.php returns 200 and authenticated:true', $res['status'] === 200 && ($res['body']['data']['authenticated'] ?? false) === true);

// Check Me endpoint with POST -> Must be 405
$res = apiReq('POST', 'backend/auth/me.php', ['dummy' => 1], $cookieStudent);
assertFlow('POST me.php returns 405 Method Not Allowed', $res['status'] === 405);

// Admin Login with POST
$res = apiReq('POST', 'backend/auth/admin_login.php', [
    'username' => 'admin',
    'password' => 'admin123'
], $cookieAdmin);
assertFlow('POST admin_login.php with valid credentials returns 200', $res['status'] === 200 && ($res['body']['success'] ?? false) === true);

// Logout endpoints method check
$res = apiReq('GET', 'backend/auth/student_logout.php');
assertFlow('GET student_logout.php returns 405 Method Not Allowed', $res['status'] === 405);

$res = apiReq('GET', 'backend/auth/admin_logout.php');
assertFlow('GET admin_logout.php returns 405 Method Not Allowed', $res['status'] === 405);

// 2. CLUBS API FLOWS
echo "\n--- 2. Clubs API Flows ---\n";
// GET clubs list
$res = apiReq('GET', 'backend/clubs/list.php');
assertFlow('GET clubs/list.php returns 200', $res['status'] === 200 && is_array($res['body']['data'] ?? null));

// POST clubs list -> 405
$res = apiReq('POST', 'backend/clubs/list.php', ['search' => 'tech']);
assertFlow('POST clubs/list.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET club details
$res = apiReq('GET', 'backend/clubs/get.php?id=1');
assertFlow('GET clubs/get.php?id=1 returns 200', $res['status'] === 200 && ($res['body']['data']['club_id'] ?? null) == 1);

// POST club details -> 405
$res = apiReq('POST', 'backend/clubs/get.php?id=1', []);
assertFlow('POST clubs/get.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET clubs/join.php -> 405
$res = apiReq('GET', 'backend/clubs/join.php', null, $cookieStudent);
assertFlow('GET clubs/join.php returns 405 Method Not Allowed', $res['status'] === 405);

// POST clubs/join.php (Join)
$res = apiReq('POST', 'backend/clubs/join.php', ['club_id' => 2, 'action' => 'join'], $cookieStudent);
assertFlow('POST clubs/join.php (join) returns 200', $res['status'] === 200 && ($res['body']['success'] ?? false) === true);

// GET clubs/leave.php -> 405
$res = apiReq('GET', 'backend/clubs/leave.php', null, $cookieStudent);
assertFlow('GET clubs/leave.php returns 405 Method Not Allowed', $res['status'] === 405);

// POST clubs/leave.php (Leave)
$res = apiReq('POST', 'backend/clubs/leave.php', ['club_id' => 2], $cookieStudent);
assertFlow('POST clubs/leave.php returns 200 and is_member:false', $res['status'] === 200 && ($res['body']['data']['is_member'] ?? null) === false);

// GET clubs/my.php
$res = apiReq('GET', 'backend/clubs/my.php', null, $cookieStudent);
assertFlow('GET clubs/my.php returns 200 for student', $res['status'] === 200 && is_array($res['body']['data'] ?? null));

// POST clubs/my.php -> 405
$res = apiReq('POST', 'backend/clubs/my.php', [], $cookieStudent);
assertFlow('POST clubs/my.php returns 405 Method Not Allowed', $res['status'] === 405);

// 3. EVENTS & REGISTRATIONS FLOWS
echo "\n--- 3. Events & Registrations Flows ---\n";
// GET events list
$res = apiReq('GET', 'backend/events/list.php');
assertFlow('GET events/list.php returns 200', $res['status'] === 200 && is_array($res['body']['data'] ?? null));

// POST events list -> 405
$res = apiReq('POST', 'backend/events/list.php', ['cat' => 'all']);
assertFlow('POST events/list.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET event details
$res = apiReq('GET', 'backend/events/get.php?id=1');
assertFlow('GET events/get.php?id=1 returns 200', $res['status'] === 200 && ($res['body']['data']['event_id'] ?? null) == 1);

// POST event details -> 405
$res = apiReq('POST', 'backend/events/get.php?id=1', []);
assertFlow('POST events/get.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET registrations/create.php -> 405
$res = apiReq('GET', 'backend/registrations/create.php', null, $cookieStudent);
assertFlow('GET registrations/create.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET registrations/cancel.php -> 405
$res = apiReq('GET', 'backend/registrations/cancel.php', null, $cookieStudent);
assertFlow('GET registrations/cancel.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET registrations/list.php (Student authenticated)
$res = apiReq('GET', 'backend/registrations/list.php?my=1', null, $cookieStudent);
assertFlow('GET registrations/list.php?my=1 returns 200', $res['status'] === 200 && is_array($res['body']['data'] ?? null));

// POST registrations/list.php -> 405
$res = apiReq('POST', 'backend/registrations/list.php', [], $cookieStudent);
assertFlow('POST registrations/list.php returns 405 Method Not Allowed', $res['status'] === 405);

// 4. STUDENT PROFILE & DASHBOARD FLOWS
echo "\n--- 4. Student Profile & Campus Hub Flows ---\n";
// GET students/get.php
$res = apiReq('GET', 'backend/students/get.php', null, $cookieStudent);
assertFlow('GET students/get.php returns 200 for logged-in student', $res['status'] === 200 && ($res['body']['data']['student_id'] ?? null) == 1);

// POST students/get.php -> 405
$res = apiReq('POST', 'backend/students/get.php', [], $cookieStudent);
assertFlow('POST students/get.php returns 405 Method Not Allowed', $res['status'] === 405);

// 5. ADMIN CONSOLE FLOWS
echo "\n--- 5. Admin Console Flows ---\n";
// GET reports/dashboard.php
$res = apiReq('GET', 'backend/reports/dashboard.php', null, $cookieAdmin);
assertFlow('GET reports/dashboard.php returns 200 for admin', $res['status'] === 200 && isset($res['body']['data']['totalStudents']));

// POST reports/dashboard.php -> 405
$res = apiReq('POST', 'backend/reports/dashboard.php', [], $cookieAdmin);
assertFlow('POST reports/dashboard.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET events/create.php -> 405
$res = apiReq('GET', 'backend/events/create.php', null, $cookieAdmin);
assertFlow('GET events/create.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET events/delete.php -> 405
$res = apiReq('GET', 'backend/events/delete.php', null, $cookieAdmin);
assertFlow('GET events/delete.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET students/create.php -> 405
$res = apiReq('GET', 'backend/students/create.php', null, $cookieAdmin);
assertFlow('GET students/create.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET venues/create.php -> 405
$res = apiReq('GET', 'backend/venues/create.php', null, $cookieAdmin);
assertFlow('GET venues/create.php returns 405 Method Not Allowed', $res['status'] === 405);

// GET departments/create.php -> 405
$res = apiReq('GET', 'backend/departments/create.php', null, $cookieAdmin);
assertFlow('GET departments/create.php returns 405 Method Not Allowed', $res['status'] === 405);

echo "\n=================================================================\n";
echo " AUDIT SUMMARY: $testsPassed / $testsRun TESTS PASSED\n";
echo "=================================================================\n";

if (file_exists($cookieStudent)) unlink($cookieStudent);
if (file_exists($cookieAdmin)) unlink($cookieAdmin);

if ($testsPassed === $testsRun) {
    exit(0);
} else {
    exit(1);
}
