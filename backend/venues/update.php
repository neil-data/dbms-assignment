<?php
/**
 * CEMS - Update Venue Endpoint (Admin Only)
 * POST /backend/venues/update.php
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
$id       = $input['venue_id'] ?? null;
$name     = sanitizeString($input['venue_name'] ?? null);
$location = sanitizeString($input['location'] ?? null);
$capacity = $input['capacity'] ?? null;

$errors = [];
if (!isValidId($id)) {
    $errors['venue_id'] = 'Valid venue ID is required.';
}
if (empty($name)) {
    $errors['venue_name'] = 'Venue facility name is required.';
}
if (empty($location)) {
    $errors['location'] = 'Location cannot be blank.';
}
if (!filter_var($capacity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $errors['capacity'] = 'Capacity must be a positive integer greater than zero.';
}

if (!empty($errors)) {
    sendError('Validation failed.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // Check duplicate name for another venue
    $check = $pdo->prepare('SELECT venue_id FROM venue WHERE venue_name = ? AND venue_id != ?');
    $check->execute([$name, $id]);
    if ($check->fetch()) {
        sendError('Another venue with this name already exists.', ['venue_name' => 'Must be unique.'], 409);
    }

    $stmt = $pdo->prepare('UPDATE venue SET venue_name = ?, location = ?, capacity = ? WHERE venue_id = ?');
    $stmt->execute([$name, $location, (int)$capacity, $id]);

    sendSuccess('Venue updated successfully.', [
        'venue_id'   => (int)$id,
        'venue_name' => $name,
        'location'   => $location,
        'capacity'   => (int)$capacity
    ]);
} catch (PDOException $e) {
    error_log('Update venue error: ' . $e->getMessage());
    sendError('Failed to update venue record.', ['database' => 'Query execution failed.'], 500);
}
