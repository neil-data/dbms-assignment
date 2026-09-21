<?php
/**
 * CEMS - Create Student Record Endpoint (Admin Only)
 * POST /backend/students/create.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

$input = getRequestData();
$name         = sanitizeString($input['name'] ?? null);
$email        = strtolower(trim($input['email'] ?? ''));
$phone        = sanitizeString($input['phone'] ?? null);
$departmentId = $input['department_id'] ?? null;
$semester     = $input['semester'] ?? null;
$password     = $input['password'] ?? 'Student@123'; // Default fallback

$errors = [];
if (empty($name)) {
    $errors['name'] = 'Full name is required.';
}
if (!isValidEmail($email)) {
    $errors['email'] = 'Valid institutional email is required.';
}
if (empty($phone) || !isValidPhone($phone)) {
    $errors['phone'] = 'Valid contact phone number is required.';
}
if (!isValidId($departmentId)) {
    $errors['department_id'] = 'Valid department must be selected.';
}
if (!filter_var($semester, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]])) {
    $errors['semester'] = 'Semester must be between 1 and 8.';
}

if (!empty($errors)) {
    sendError('Validation failed.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // Check duplicate email
    $check = $pdo->prepare('SELECT student_id FROM student WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        sendError('A student with this email address already exists.', ['email' => 'Must be unique.'], 409);
    }

    $hashed = password_hash((string)$password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('
        INSERT INTO student (department_id, name, email, phone, semester, password, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([(int)$departmentId, $name, $email, $phone, (int)$semester, $hashed]);

    $id = (int)$pdo->lastInsertId();

    sendSuccess('Student record created successfully.', [
        'student_id'    => $id,
        'name'          => $name,
        'email'         => $email,
        'phone'         => $phone,
        'department_id' => (int)$departmentId,
        'semester'      => (int)$semester
    ], 201);
} catch (PDOException $e) {
    error_log('Create student error: ' . $e->getMessage());
    sendError('Failed to create student record.', ['database' => 'Query execution failed.'], 500);
}
