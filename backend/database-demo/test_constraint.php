<?php
/**
 * CEMS - DBMS Demonstration: Live Constraint Testing Endpoint
 * POST /backend/database-demo/test_constraint.php
 * Conforms to Requirement 16-J & 16-K
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../shared/response.php';
require_once __DIR__ . '/../shared/auth.php';

requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', [], 405);
}

$input = getRequestData();
$test  = trim((string)($input['test'] ?? 'duplicate_registration'));

try {
    $pdo = Database::getConnection();

    switch ($test) {
        // 1. UNIQUE(student_id, event_id) Constraint Demonstration (Requirement 16-K)
        case 'duplicate_registration':
            // Ensure student 1 and event 1 exist
            $chk = $pdo->query('SELECT COUNT(*) FROM registration WHERE student_id = 1 AND event_id = 1')->fetchColumn();
            if ((int)$chk === 0) {
                $pdo->query('INSERT INTO registration (student_id, event_id, status) VALUES (1, 1, "CONFIRMED")');
            }

            try {
                // Attempt intentional duplicate insertion
                $pdo->query('INSERT INTO registration (student_id, event_id, status) VALUES (1, 1, "CONFIRMED")');
                
                // If it reached here, constraint failed
                sendError('Unexpected: MySQL allowed duplicate registration.', [], 500);
            } catch (PDOException $ex) {
                sendSuccess('Duplicate Registration Rejected by Relational Constraint.', [
                    'constraint_tested' => 'UNIQUE(student_id, event_id)',
                    'attempted_query'   => 'INSERT INTO registration (student_id, event_id, status) VALUES (1, 1, "CONFIRMED");',
                    'mysql_error_code'  => $ex->getCode(),
                    'mysql_message'     => $ex->getMessage(),
                    'academic_explanation' => 'The database engine rejected the duplicate insert because the composite constraint `UNIQUE(student_id, event_id)` forbids multiple registration tuples for the same student in the same event. Referential entity integrity is preserved.'
                ]);
            }
            break;

        // 2. FOREIGN KEY Referential Integrity (Requirement 16-J)
        case 'foreign_key':
            try {
                // Attempt inserting a student with non-existent department_id 99999
                $pdo->query('
                    INSERT INTO student (department_id, name, email, phone, semester, password) 
                    VALUES (99999, "Invalid Department Student", "fk_test@campus.edu", "+91 00000 00000", 1, "test")
                ');
                sendError('Unexpected: MySQL allowed invalid foreign key.', [], 500);
            } catch (PDOException $ex) {
                sendSuccess('Invalid Foreign Key Rejected by MySQL.', [
                    'constraint_tested' => 'FOREIGN KEY (department_id) REFERENCES department(department_id)',
                    'attempted_query'   => 'INSERT INTO student (department_id, ...) VALUES (99999, ...);',
                    'mysql_error_code'  => $ex->getCode(),
                    'mysql_message'     => $ex->getMessage(),
                    'academic_explanation' => 'Foreign Key constraint `fk_student_department` requires any department_id referenced in `student` to pre-exist in the parent `department` table. MySQL prevented orphaned child records.'
                ]);
            }
            break;

        // 3. CHECK Constraint Validation (Requirement 16-J)
        case 'check_constraint':
            try {
                // Attempt inserting venue with capacity <= 0
                $pdo->query('INSERT INTO venue (venue_name, location, capacity) VALUES ("Negative Capacity Room", "Basement", -50)');
                sendError('Unexpected: MySQL allowed negative venue capacity.', [], 500);
            } catch (PDOException $ex) {
                sendSuccess('Domain Violation Rejected by CHECK Constraint.', [
                    'constraint_tested' => 'CHECK (capacity > 0)',
                    'attempted_query'   => 'INSERT INTO venue (venue_name, location, capacity) VALUES ("...", "...", -50);',
                    'mysql_error_code'  => $ex->getCode(),
                    'mysql_message'     => $ex->getMessage(),
                    'academic_explanation' => 'Domain integrity rule `CHECK (capacity > 0)` requires seat capacities to be strictly positive integers. MySQL rejected negative values.'
                ]);
            }
            break;

        // 4. NOT NULL Constraint Validation (Requirement 16-J)
        case 'not_null':
            try {
                // Attempt inserting department with NULL department_name
                $pdo->query('INSERT INTO department (department_name) VALUES (NULL)');
                sendError('Unexpected: MySQL allowed NULL value in NOT NULL column.', [], 500);
            } catch (PDOException $ex) {
                sendSuccess('NULL Violation Rejected by NOT NULL Constraint.', [
                    'constraint_tested' => 'department_name VARCHAR(100) NOT NULL',
                    'attempted_query'   => 'INSERT INTO department (department_name) VALUES (NULL);',
                    'mysql_error_code'  => $ex->getCode(),
                    'mysql_message'     => $ex->getMessage(),
                    'academic_explanation' => 'The attribute `department_name` is defined with NOT NULL. MySQL enforces completeness and rejects missing entity values.'
                ]);
            }
            break;

        default:
            sendError('Unknown constraint test requested.', [
                'options' => ['duplicate_registration', 'foreign_key', 'check_constraint', 'not_null']
            ], 400);
            break;
    }

} catch (PDOException $e) {
    error_log('DBMS Demo Constraint Error: ' . $e->getMessage());
    sendError('Constraint demonstration error: ' . $e->getMessage(), [], 500);
}
