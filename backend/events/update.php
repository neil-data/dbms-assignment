<?php
/**
 * CEMS - Update Event Endpoint (Admin Only)
 * POST /backend/events/update.php
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
$id = $input['event_id'] ?? null;

if (!isValidId($id)) {
    sendError('Valid event ID is required.', ['event_id' => 'Parameter missing or invalid.'], 422);
}

$eventName   = sanitizeString($input['event_name'] ?? $input['title'] ?? null);
$description = sanitizeString($input['description'] ?? '');
$eventDate   = trim((string)($input['event_date'] ?? $input['date'] ?? ''));
$eventTime   = sanitizeString($input['event_time'] ?? $input['time'] ?? null);
$venueId     = $input['venue_id'] ?? null;
$maxCapacity = $input['max_capacity'] ?? $input['capacity'] ?? null;
$status      = strtoupper(trim((string)($input['status'] ?? 'UPCOMING')));
$categoryIds = $input['category_ids'] ?? null;

$errors = [];
if (empty($eventName)) {
    $errors['event_name'] = 'Event title cannot be blank.';
}
if (!empty($eventDate) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
    $errors['event_date'] = 'Invalid date format (YYYY-MM-DD).';
}
if ($venueId !== null && !isValidId($venueId)) {
    $errors['venue_id'] = 'Invalid venue ID.';
}
if ($maxCapacity !== null && !filter_var($maxCapacity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $errors['max_capacity'] = 'Maximum capacity must be greater than zero.';
}
if (!empty($status) && !isValidEventStatus($status)) {
    $errors['status'] = 'Status must be UPCOMING, ONGOING, COMPLETED, or CANCELLED.';
}

if (!empty($errors)) {
    sendError('Validation failed.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // Verify existing event
    $stmt = $pdo->prepare('SELECT * FROM event WHERE event_id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        sendError('Event record not found.', [], 404);
    }

    $upVenueId  = $venueId !== null ? (int)$venueId : (int)$existing['venue_id'];
    $upCapacity = $maxCapacity !== null ? (int)$maxCapacity : (int)$existing['max_capacity'];
    $upDate     = !empty($eventDate) ? $eventDate : $existing['event_date'];
    $upTime     = !empty($eventTime) ? $eventTime : $existing['event_time'];
    $upStatus   = !empty($status) ? $status : $existing['status'];

    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare('
        UPDATE event 
        SET event_name = ?, description = ?, event_date = ?, event_time = ?, venue_id = ?, max_capacity = ?, status = ?
        WHERE event_id = ?
    ');
    $updateStmt->execute([$eventName, $description, $upDate, $upTime, $upVenueId, $upCapacity, $upStatus, $id]);

    // Update categories if provided
    if (is_array($categoryIds)) {
        $delCats = $pdo->prepare('DELETE FROM event_category WHERE event_id = ?');
        $delCats->execute([$id]);

        $addCat = $pdo->prepare('INSERT INTO event_category (event_id, category_id) VALUES (?, ?)');
        foreach ($categoryIds as $catId) {
            if (isValidId($catId)) {
                $addCat->execute([$id, (int)$catId]);
            }
        }
    }

    $pdo->commit();

    sendSuccess('Event updated successfully.', [
        'event_id'     => (int)$id,
        'event_name'   => $eventName,
        'event_date'   => $upDate,
        'event_time'   => $upTime,
        'venue_id'     => $upVenueId,
        'max_capacity' => $upCapacity,
        'status'       => $upStatus
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Update event error: ' . $e->getMessage());
    sendError('Failed to update event record.', ['database' => 'Query execution failed.'], 500);
}
