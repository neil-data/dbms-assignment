<?php
/**
 * CEMS - Update Student Endpoint
 * POST /backend/students/update.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

$session = getCurrentSession();
if (!$session) {
    sendError('Authentication required.', [], 401);
}

$input = getRequestData();
$id = $input['student_id'] ?? null;

// Only admin can update another student's profile
if ($session['role'] === 'student') {
    $targetId = (int)$session['user_id'];
} else {
    $targetId = isValidId($id) ? (int)$id : (int)$session['user_id'];
}

$name         = sanitizeString($input['name'] ?? null);
$phone        = sanitizeString($input['phone'] ?? null);
$semester     = $input['semester'] ?? null;
$departmentId = $input['department_id'] ?? null;
$password     = $input['password'] ?? null;

$errors = [];
if (empty($name)) {
    $errors['name'] = 'Full name cannot be blank.';
}
if (!empty($phone) && !isValidPhone($phone)) {
    $errors['phone'] = 'Invalid phone number format.';
}
if ($semester !== null && !filter_var($semester, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]])) {
    $errors['semester'] = 'Semester must be between 1 and 8.';
}

if (!empty($errors)) {
    sendError('Validation failed.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // Fetch existing student
    $existingStmt = $pdo->prepare('SELECT * FROM student WHERE student_id = ?');
    $existingStmt->execute([$targetId]);
    $existing = $existingStmt->fetch();
    if (!$existing) {
        sendError('Student record not found.', [], 404);
    }

    $updateDept = ($departmentId && isValidId($departmentId) && $session['role'] === 'admin') ? (int)$departmentId : (int)$existing['department_id'];
    $updateSem  = $semester !== null ? (int)$semester : (int)$existing['semester'];
    $updatePhone = !empty($phone) ? $phone : $existing['phone'];

    if (!empty($password) && strlen((string)$password) >= 6) {
        $updatePass = password_hash((string)$password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('
            UPDATE student 
            SET name = ?, phone = ?, department_id = ?, semester = ?, password = ?
            WHERE student_id = ?
        ');
        $stmt->execute([$name, $updatePhone, $updateDept, $updateSem, $updatePass, $targetId]);
    } else {
        $stmt = $pdo->prepare('
            UPDATE student 
            SET name = ?, phone = ?, department_id = ?, semester = ?
            WHERE student_id = ?
        ');
        $stmt->execute([$name, $updatePhone, $updateDept, $updateSem, $targetId]);
    }

    sendSuccess('Student profile updated successfully.', [
        'student_id'    => $targetId,
        'name'          => $name,
        'phone'         => $updatePhone,
        'department_id' => $updateDept,
        'semester'      => $updateSem
    ]);
} catch (PDOException $e) {
    error_log('Update student error: ' . $e->getMessage());
    sendError('Failed to update student profile.', ['database' => 'Query execution failed.'], 500);
}
