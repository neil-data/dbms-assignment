<?php
/**
 * CEMS - Student Login Endpoint
 * POST /backend/auth/student_login.php
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
$email    = strtolower(trim($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

$errors = [];
if (empty($email) || !isValidEmail($email)) {
    $errors['email'] = 'A valid institutional email is required.';
}
if (empty($password)) {
    $errors['password'] = 'Password is required.';
}

if (!empty($errors)) {
    sendError('Please provide both your email and password.', $errors, 422);
}

try {
    $pdo = Database::getConnection();

    // Query student and join department details
    $stmt = $pdo->prepare('
        SELECT 
            s.student_id,
            s.department_id,
            s.name,
            s.email,
            s.phone,
            s.semester,
            s.password,
            d.department_name
        FROM student s
        INNER JOIN department d ON s.department_id = d.department_id
        WHERE s.email = ?
        LIMIT 1
    ');
    $stmt->execute([$email]);
    $student = $stmt->fetch();

    if (!$student || !verifyPassword($password, $student['password'])) {
        sendError('Invalid email or password.', ['auth' => 'Credentials verification failed.'], 401);
    }

    // Initialize session
    initSession();
    $_SESSION['user_id']         = (int)$student['student_id'];
    $_SESSION['role']            = 'student';
    $_SESSION['name']            = $student['name'];
    $_SESSION['email']           = $student['email'];
    $_SESSION['department_id']   = (int)$student['department_id'];
    $_SESSION['department_name'] = $student['department_name'];
    $_SESSION['semester']        = (int)$student['semester'];

    sendSuccess('Authentication successful. Welcome back, ' . htmlspecialchars($student['name']) . '.', [
        'student_id'      => (int)$student['student_id'],
        'name'            => $student['name'],
        'email'           => $student['email'],
        'phone'           => $student['phone'],
        'department_id'   => (int)$student['department_id'],
        'department_name' => $student['department_name'],
        'semester'        => (int)$student['semester'],
        'role'            => 'student'
    ]);

} catch (PDOException $e) {
    error_log('Student login PDO Error: ' . $e->getMessage());
    sendError('Authentication server error.', ['database' => 'Query execution failed.'], 500);
}
