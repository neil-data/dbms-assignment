<?php
/**
 * CEMS - Student Registration Endpoint
 * POST /backend/auth/student_register.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';
require_once __DIR__ . '/../shared/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

$input = getRequestData();

$name          = sanitizeString($input['name'] ?? null);
$email         = strtolower(trim($input['email'] ?? ''));
$phone         = sanitizeString($input['phone'] ?? null);
$departmentId  = $input['department_id'] ?? null;
$semester      = $input['semester'] ?? null;
$password      = $input['password'] ?? '';

$errors = [];

if (empty($name)) {
    $errors['name'] = 'Full name is required.';
}
if (!isValidEmail($email)) {
    $errors['email'] = 'A valid institutional email address is required.';
}
if (empty($phone) || !isValidPhone($phone)) {
    $errors['phone'] = 'A valid contact phone number is required.';
}
if (!isValidId($departmentId)) {
    $errors['department_id'] = 'Valid department selection is required.';
}
if (!filter_var($semester, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]])) {
    $errors['semester'] = 'Semester must be an integer between 1 and 8.';
}
if (strlen((string)$password) < 6) {
    $errors['password'] = 'Password must be at least 6 characters long.';
}

if (!empty($errors)) {
    sendError('Validation failed. Please correct the highlighted errors.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // 1. Verify Department exists
    $deptStmt = $pdo->prepare('SELECT department_id, department_name FROM department WHERE department_id = ?');
    $deptStmt->execute([$departmentId]);
    $dept = $deptStmt->fetch();
    if (!$dept) {
        sendError('Invalid academic department selected.', ['department_id' => 'Department not found.'], 404);
    }

    // 2. Check for duplicate email (enforcing UNIQUE constraint gracefully)
    $dupStmt = $pdo->prepare('SELECT student_id FROM student WHERE email = ?');
    $dupStmt->execute([$email]);
    if ($dupStmt->fetch()) {
        sendError('An account with this email address already exists. Please sign in.', ['email' => 'Email address already registered.'], 409);
    }

    // 3. Hash password using standard BCRYPT
    $hashedPassword = password_hash((string)$password, PASSWORD_BCRYPT);

    // 4. Insert into STUDENT table
    $insertStmt = $pdo->prepare('
        INSERT INTO student (department_id, name, email, phone, semester, password, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ');
    $insertStmt->execute([
        (int)$departmentId,
        $name,
        $email,
        $phone,
        (int)$semester,
        $hashedPassword
    ]);

    $studentId = (int)$pdo->lastInsertId();

    // 5. Initialize authenticated session for the newly registered student
    initSession();
    $_SESSION['user_id']         = $studentId;
    $_SESSION['role']            = 'student';
    $_SESSION['name']            = $name;
    $_SESSION['email']           = $email;
    $_SESSION['department_id']   = (int)$departmentId;
    $_SESSION['department_name'] = $dept['department_name'];
    $_SESSION['semester']        = (int)$semester;

    sendSuccess('Student account registered successfully.', [
        'student_id'      => $studentId,
        'name'            => $name,
        'email'           => $email,
        'department_id'   => (int)$departmentId,
        'department_name' => $dept['department_name'],
        'semester'        => (int)$semester,
        'role'            => 'student'
    ], 201);

} catch (PDOException $e) {
    error_log('Student registration PDO Error: ' . $e->getMessage());
    sendError('Database error occurred while creating student record.', ['database' => 'Query execution failed.'], 500);
}
