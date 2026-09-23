<?php
/**
 * CEMS - Create Venue Endpoint (Admin Only)
 * POST /backend/venues/create.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

requireAdminAuth();

$input    = getRequestData();
$name     = sanitizeString($input['venue_name'] ?? null);
$location = sanitizeString($input['location'] ?? null);
$capacity = $input['capacity'] ?? null;

$errors = [];
if (empty($name)) {
    $errors['venue_name'] = 'Venue facility name is required.';
}
if (empty($location)) {
    $errors['location'] = 'Campus location is required.';
}
if (!filter_var($capacity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $errors['capacity'] = 'Capacity must be a positive integer greater than zero (CHECK constraint).';
}

if (!empty($errors)) {
    sendError('Validation failed.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // Check unique venue name
    $check = $pdo->prepare('SELECT venue_id FROM venue WHERE venue_name = ?');
    $check->execute([$name]);
    if ($check->fetch()) {
        sendError('A venue with this name already exists.', ['venue_name' => 'Must be unique.'], 409);
    }

    $stmt = $pdo->prepare('INSERT INTO venue (venue_name, location, capacity) VALUES (?, ?, ?)');
    $stmt->execute([$name, $location, (int)$capacity]);

    $id = (int)$pdo->lastInsertId();

    sendSuccess('Venue created successfully.', [
        'venue_id'   => $id,
        'venue_name' => $name,
        'location'   => $location,
        'capacity'   => (int)$capacity
    ], 201);
} catch (PDOException $e) {
    error_log('Create venue error: ' . $e->getMessage());
    sendError('Failed to create venue record.', ['database' => 'Query execution failed.'], 500);
}
