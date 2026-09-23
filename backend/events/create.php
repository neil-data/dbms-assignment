<?php
/**
 * CEMS - Create Event Endpoint (Admin Only)
 * POST /backend/events/create.php
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

$input = getRequestData();

$eventName   = sanitizeString($input['event_name'] ?? $input['title'] ?? null);
$description = sanitizeString($input['description'] ?? '');
$eventDate   = trim((string)($input['event_date'] ?? $input['date'] ?? ''));
$eventTime   = sanitizeString($input['event_time'] ?? $input['time'] ?? '10:00 AM - 04:00 PM');
$venueId     = $input['venue_id'] ?? null;
$maxCapacity = $input['max_capacity'] ?? $input['capacity'] ?? null;
$status      = strtoupper(trim((string)($input['status'] ?? 'UPCOMING')));
$categoryIds = $input['category_ids'] ?? [];

$errors = [];
if (empty($eventName)) {
    $errors['event_name'] = 'Event title is required.';
}
if (empty($eventDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
    $errors['event_date'] = 'Valid event date (YYYY-MM-DD) is required.';
}
if (!isValidId($venueId)) {
    $errors['venue_id'] = 'Valid venue assignment is required.';
}
if (!filter_var($maxCapacity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $errors['max_capacity'] = 'Maximum capacity must be a positive integer greater than zero (CHECK constraint).';
}
if (!isValidEventStatus($status)) {
    $errors['status'] = 'Status must be UPCOMING, ONGOING, COMPLETED, or CANCELLED.';
}

if (!empty($errors)) {
    sendError('Validation failed.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // 1. Verify Venue exists and has sufficient capacity
    $venueStmt = $pdo->prepare('SELECT venue_id, venue_name, capacity FROM venue WHERE venue_id = ?');
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch();
    if (!$venue) {
        sendError('Assigned venue does not exist.', ['venue_id' => 'Invalid venue reference.'], 404);
    }

    if ((int)$maxCapacity > (int)$venue['capacity']) {
        sendError("Event capacity ({$maxCapacity}) cannot exceed the physical venue capacity ({$venue['capacity']}).", [
            'capacity' => 'Exceeds venue limit.'
        ], 422);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO event (event_name, description, event_date, event_time, venue_id, max_capacity, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $eventName,
        $description,
        $eventDate,
        $eventTime,
        (int)$venueId,
        (int)$maxCapacity,
        $status
    ]);

    $eventId = (int)$pdo->lastInsertId();

    // Insert categories into junction table
    if (!empty($categoryIds)) {
        if (!is_array($categoryIds)) {
            $categoryIds = [(int)$categoryIds];
        }
        $catInsert = $pdo->prepare('INSERT INTO event_category (event_id, category_id) VALUES (?, ?)');
        foreach ($categoryIds as $catId) {
            if (isValidId($catId)) {
                $catInsert->execute([$eventId, (int)$catId]);
            }
        }
    } else {
        // Default to category 1 (Technical) if none specified
        $catInsert = $pdo->prepare('INSERT INTO event_category (event_id, category_id) VALUES (?, 1)');
        $catInsert->execute([$eventId]);
    }

    $pdo->commit();

    sendSuccess('Event created successfully in relational catalog.', [
        'event_id'     => $eventId,
        'event_name'   => $eventName,
        'event_date'   => $eventDate,
        'event_time'   => $eventTime,
        'venue_id'     => (int)$venueId,
        'venue_name'   => $venue['venue_name'],
        'max_capacity' => (int)$maxCapacity,
        'status'       => $status
    ], 201);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Create event error: ' . $e->getMessage());
    sendError('Failed to create event record.', ['database' => 'Query execution failed.'], 500);
}
