<?php
/**
 * Test Suite for Redesigned CEMS Routes and Club APIs
 */

$baseUrl = 'http://127.0.0.1:8080/';

$routes = [
    'index.html',
    'clubs.html',
    'club-details.html?id=1',
    'events.html',
    'event-details.html?id=1',
    'login.html',
    'register.html',
    'student-dashboard.html',
    'profile.html',
    'admin.html',
    'backend/clubs/list.php',
    'backend/clubs/get.php?id=1',
    'backend/events/list.php',
    'backend/events/get.php?id=1',
    'backend/departments/list.php',
    'backend/categories/list.php'
];

echo "=== CEMS REDESIGN ROUTE VERIFICATION ===\n";
$allPassed = true;

foreach ($routes as $route) {
    $url = $baseUrl . $route;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "[PASS] HTTP 200 : {$route}\n";
    } else {
        echo "[FAIL] HTTP {$httpCode} : {$route}\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\n>>> ALL 16 ROUTES & APIS RESPONDED WITH HTTP 200 SUCCESS!\n";
} else {
    echo "\n>>> SOME ROUTES FAILED!\n";
    exit(1);
}
