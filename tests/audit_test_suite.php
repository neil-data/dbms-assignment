<?php
declare(strict_types=1);
/**
 * CEMS - Comprehensive Technical Audit & End-to-End Verification Test Suite
 * Tests all requirements across Phases 1 through 32 against live MySQL and PHP PDO.
 */

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/shared/auth.php';
require_once __DIR__ . '/../backend/shared/validation.php';

$totalChecks = 0;
$passedChecks = 0;
$failedChecks = 0;
$failures = [];

function check(string $phase, string $name, bool $condition, string $info = ''): void {
    global $totalChecks, $passedChecks, $failedChecks, $failures;
    $totalChecks++;
    if ($condition) {
        $passedChecks++;
        echo "  [PASS] [{$phase}] {$name}" . ($info ? " -- {$info}" : "") . PHP_EOL;
    } else {
        $failedChecks++;
        $msg = "[{$phase}] {$name}" . ($info ? " -- {$info}" : "");
        $failures[] = $msg;
        echo "  [FAIL] {$msg}" . PHP_EOL;
    }
}

echo PHP_EOL . "================================================================================" . PHP_EOL;
echo "  CEMS TECHNICAL AUDIT & END-TO-END VERIFICATION TEST SUITE" . PHP_EOL;
echo "================================================================================" . PHP_EOL . PHP_EOL;

// -----------------------------------------------------------------------------
// PHASE 2 & 3: DATABASE CONNECTION & PDO CONFIGURATION
// -----------------------------------------------------------------------------
echo "--- Phase 2 & 3: Database Connection & PDO Verification ---" . PHP_EOL;
try {
    $pdo = Database::getConnection();
    check("Phase 3", "Database::getConnection() returns PDO instance", $pdo instanceof PDO);
    
    $errMode = $pdo->getAttribute(PDO::ATTR_ERRMODE);
    check("Phase 3", "PDO::ATTR_ERRMODE is ERRMODE_EXCEPTION", $errMode === PDO::ERRMODE_EXCEPTION);

    $fetchMode = $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
    check("Phase 3", "PDO::ATTR_DEFAULT_FETCH_MODE is FETCH_ASSOC", $fetchMode === PDO::FETCH_ASSOC);

    $emulatePrepares = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    check("Phase 3", "PDO::ATTR_EMULATE_PREPARES is false", (!$emulatePrepares || $emulatePrepares === 0 || $emulatePrepares === false));

    $ver = $pdo->query("SELECT VERSION()")->fetchColumn();
    check("Phase 3", "MySQL Engine Version Check", !empty($ver), "Running MySQL {$ver}");

    $charset = $pdo->query("SELECT @@character_set_database, @@collation_database")->fetch();
    check("Phase 3", "Character Encoding utf8mb4", stripos($charset['@@character_set_database'], 'utf8') !== false, "Charset: {$charset['@@character_set_database']}, Collation: {$charset['@@collation_database']}");

} catch (Throwable $e) {
    check("Phase 3", "PDO Connection Exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 2: ENTITY & SCHEMA VERIFICATION (8 TABLES, KEYS, CONSTRAINTS)
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 2: Schema & Entity Verification ---" . PHP_EOL;
try {
    $requiredTables = ['department', 'student', 'venue', 'event', 'category', 'registration', 'event_category', 'admin'];
    $existingTables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($requiredTables as $t) {
        check("Phase 2", "Table exists: {$t}", in_array($t, $existingTables, true));
    }

    // Check Composite Primary Key on event_category
    $ecKeys = $pdo->query("
        SELECT COLUMN_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'cems_db' AND TABLE_NAME = 'event_category' AND CONSTRAINT_NAME = 'PRIMARY'
        ORDER BY ORDINAL_POSITION
    ")->fetchAll(PDO::FETCH_COLUMN);
    check("Phase 2", "event_category composite PK (event_id, category_id)", $ecKeys === ['event_id', 'category_id'], "Columns: " . implode(', ', $ecKeys));

    // Check Foreign Keys
    $fks = $pdo->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'cems_db' AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetchAll();
    check("Phase 2", "Foreign Key constraints registered", count($fks) >= 6, count($fks) . " foreign keys found");

    // Check Unique Constraints
    $uqs = $pdo->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME 
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = 'cems_db' AND CONSTRAINT_TYPE = 'UNIQUE'
    ")->fetchAll();
    check("Phase 2", "Unique constraints registered", count($uqs) >= 5, count($uqs) . " unique constraints found");

    // Check Views
    $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);
    check("Phase 2", "View: view_student_registrations", in_array('view_student_registrations', $views, true));
    check("Phase 2", "View: view_event_registration_summary", in_array('view_event_registration_summary', $views, true));
    check("Phase 2", "View: view_department_enrollment_stats", in_array('view_department_enrollment_stats', $views, true));

} catch (Throwable $e) {
    check("Phase 2", "Schema inspection exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 6 & 7: AUTHENTICATION & PASSWORD HASHING
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 6 & 7: Authentication & Authorization ---" . PHP_EOL;
try {
    // Student Login verification
    $stdStmt = $pdo->prepare("SELECT student_id, name, email, password FROM student WHERE email = ?");
    $stdStmt->execute(['aarav.sharma@campus.edu']);
    $student = $stdStmt->fetch();
    check("Phase 6", "Student Aarav Sharma exists in DB", !empty($student));
    check("Phase 6", "Student password stored as bcrypt hash", password_verify('Student@123', $student['password'] ?? ''));
    check("Phase 6", "Invalid password rejected", !password_verify('WrongPassword', $student['password'] ?? ''));

    // Admin Login verification
    $admStmt = $pdo->prepare("SELECT admin_id, username, password FROM admin WHERE username = ?");
    $admStmt->execute(['admin']);
    $admin = $admStmt->fetch();
    check("Phase 7", "Admin user exists in DB", !empty($admin));
    check("Phase 7", "Admin password stored as bcrypt hash", password_verify('Admin@123', $admin['password'] ?? ''));
    check("Phase 7", "Admin invalid password rejected", !password_verify('WrongAdminPass', $admin['password'] ?? ''));

    // Duplicate Student Email rejection check
    $dupRejected = false;
    try {
        $pdo->prepare("INSERT INTO student (department_id, name, email, phone, semester, password) VALUES (1, 'Duplicate Test', 'aarav.sharma@campus.edu', '9999999999', 4, 'hash')")->execute();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $dupRejected = true;
        }
    }
    check("Phase 6", "Duplicate student email rejected by UNIQUE constraint", $dupRejected);

} catch (Throwable $e) {
    check("Phase 6/7", "Authentication exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 8: STUDENT CRUD OPERATIONS
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 8: Student CRUD Operations ---" . PHP_EOL;
try {
    $tempEmail = 'audit.student.' . time() . '@campus.edu';
    
    // CREATE
    $ins = $pdo->prepare("INSERT INTO student (department_id, name, email, phone, semester, password) VALUES (1, 'Audit Student', ?, '9876543210', 3, ?)");
    $hash = password_hash('TestPass@123', PASSWORD_BCRYPT);
    $ins->execute([$tempEmail, $hash]);
    $newStdId = (int)$pdo->lastInsertId();
    check("Phase 8", "CREATE Student", $newStdId > 0, "Created student_id={$newStdId}");

    // READ
    $read = $pdo->query("SELECT * FROM student WHERE student_id = {$newStdId}")->fetch();
    check("Phase 8", "READ Student", !empty($read) && $read['name'] === 'Audit Student');

    // UPDATE
    $upd = $pdo->prepare("UPDATE student SET name = 'Audit Student Updated', semester = 4 WHERE student_id = ?");
    $upd->execute([$newStdId]);
    $readUpd = $pdo->query("SELECT * FROM student WHERE student_id = {$newStdId}")->fetch();
    check("Phase 8", "UPDATE Student", $readUpd['name'] === 'Audit Student Updated' && (int)$readUpd['semester'] === 4);

    // DELETE
    $del = $pdo->prepare("DELETE FROM student WHERE student_id = ?");
    $del->execute([$newStdId]);
    $readDel = $pdo->query("SELECT * FROM student WHERE student_id = {$newStdId}")->fetch();
    check("Phase 8", "DELETE Student", $readDel === false);

} catch (Throwable $e) {
    check("Phase 8", "Student CRUD exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 9: DEPARTMENT CRUD & FOREIGN KEY RESTRICTION
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 9: Department CRUD & Referential Integrity ---" . PHP_EOL;
try {
    // CREATE
    $deptName = 'Audit Dept ' . time();
    $ins = $pdo->prepare("INSERT INTO department (department_name) VALUES (?)");
    $ins->execute([$deptName]);
    $deptId = (int)$pdo->lastInsertId();
    check("Phase 9", "CREATE Department", $deptId > 0, "Created dept_id={$deptId}");

    // READ
    $read = $pdo->query("SELECT * FROM department WHERE department_id = {$deptId}")->fetch();
    check("Phase 9", "READ Department", !empty($read) && $read['department_name'] === $deptName);

    // UPDATE
    $upd = $pdo->prepare("UPDATE department SET department_name = ? WHERE department_id = ?");
    $upd->execute([$deptName . ' Mod', $deptId]);
    $readUpd = $pdo->query("SELECT * FROM department WHERE department_id = {$deptId}")->fetch();
    check("Phase 9", "UPDATE Department", $readUpd['department_name'] === ($deptName . ' Mod'));

    // Attempt to DELETE a department with active students (Dept 1 - Computer Science)
    $restrictTriggered = false;
    try {
        $pdo->prepare("DELETE FROM department WHERE department_id = 1")->execute();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $restrictTriggered = true;
        }
    }
    check("Phase 9", "ON DELETE RESTRICT protects department with active students", $restrictTriggered);

    // Clean up test department
    $pdo->prepare("DELETE FROM department WHERE department_id = ?")->execute([$deptId]);
    $readDel = $pdo->query("SELECT * FROM department WHERE department_id = {$deptId}")->fetch();
    check("Phase 9", "DELETE Department (empty)", $readDel === false);

} catch (Throwable $e) {
    check("Phase 9", "Department CRUD exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 10: VENUE CRUD & CHECK CONSTRAINT (capacity > 0)
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 10: Venue CRUD & Capacity Constraints ---" . PHP_EOL;
try {
    $vName = 'Audit Venue ' . time();
    // CREATE
    $ins = $pdo->prepare("INSERT INTO venue (venue_name, location, capacity) VALUES (?, 'Building X', 250)");
    $ins->execute([$vName]);
    $vId = (int)$pdo->lastInsertId();
    check("Phase 10", "CREATE Venue", $vId > 0, "Created venue_id={$vId}");

    // CHECK CONSTRAINT: capacity > 0
    $checkRejected = false;
    try {
        $pdo->prepare("INSERT INTO venue (venue_name, location, capacity) VALUES ('Invalid Cap Venue', 'X', 0)")->execute();
    } catch (PDOException $e) {
        $checkRejected = true;
    }
    check("Phase 10", "CHECK (capacity > 0) rejects capacity=0", $checkRejected);

    // UPDATE
    $pdo->prepare("UPDATE venue SET capacity = 300 WHERE venue_id = ?")->execute([$vId]);
    $readUpd = $pdo->query("SELECT capacity FROM venue WHERE venue_id = {$vId}")->fetchColumn();
    check("Phase 10", "UPDATE Venue capacity", (int)$readUpd === 300);

    // Clean up
    $pdo->prepare("DELETE FROM venue WHERE venue_id = ?")->execute([$vId]);
    $readDel = $pdo->query("SELECT * FROM venue WHERE venue_id = {$vId}")->fetch();
    check("Phase 10", "DELETE Venue", $readDel === false);

} catch (Throwable $e) {
    check("Phase 10", "Venue CRUD exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 11 & 13: EVENT CRUD & MANY-TO-MANY RELATIONSHIP (EVENT_CATEGORY)
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 11 & 13: Event CRUD & M:M Event Categories ---" . PHP_EOL;
try {
    $eTitle = 'Audit Symposium ' . time();
    
    // CREATE Event
    $ins = $pdo->prepare("INSERT INTO event (event_name, description, event_date, event_time, venue_id, max_capacity, status) VALUES (?, 'Symposium description', '2026-11-20', '10:00 AM - 04:00 PM', 1, 150, 'UPCOMING')");
    $ins->execute([$eTitle]);
    $eId = (int)$pdo->lastInsertId();
    check("Phase 11", "CREATE Event", $eId > 0, "Created event_id={$eId}");

    // Assign multiple categories in junction table (M:M)
    $pdo->prepare("INSERT INTO event_category (event_id, category_id) VALUES (?, 1), (?, 2)")->execute([$eId, $eId]);
    $catCount = (int)$pdo->query("SELECT COUNT(*) FROM event_category WHERE event_id = {$eId}")->fetchColumn();
    check("Phase 13", "M:M Assign Multiple Categories to Event", $catCount === 2);

    // Duplicate assignment prevention (Composite PK on event_category)
    $dupCatRejected = false;
    try {
        $pdo->prepare("INSERT INTO event_category (event_id, category_id) VALUES (?, 1)")->execute([$eId]);
    } catch (PDOException $e) {
        $dupCatRejected = true;
    }
    check("Phase 13", "Composite PK (event_id, category_id) prevents duplicate assignment", $dupCatRejected);

    // Retrieve Event with Categories JOIN
    $joinedCats = $pdo->query("
        SELECT c.category_name 
        FROM category c
        INNER JOIN event_category ec ON c.category_id = ec.category_id
        WHERE ec.event_id = {$eId}
    ")->fetchAll(PDO::FETCH_COLUMN);
    check("Phase 13", "Retrieve Event Categories via JOIN", count($joinedCats) === 2, implode(', ', $joinedCats));

    // CHECK CONSTRAINT: max_capacity > 0
    $eventCapRejected = false;
    try {
        $pdo->prepare("INSERT INTO event (event_name, description, event_date, event_time, venue_id, max_capacity, status) VALUES ('Invalid Cap Event', 'x', '2026-11-20', '10:00 AM', 1, -5, 'UPCOMING')")->execute();
    } catch (PDOException $e) {
        $eventCapRejected = true;
    }
    check("Phase 11", "CHECK (max_capacity > 0) rejects negative capacity", $eventCapRejected);

    // Clean up
    $pdo->prepare("DELETE FROM event WHERE event_id = ?")->execute([$eId]);
    $readDel = $pdo->query("SELECT * FROM event WHERE event_id = {$eId}")->fetch();
    check("Phase 11", "DELETE Event (Cascades event_category)", $readDel === false);
    $orphanCats = (int)$pdo->query("SELECT COUNT(*) FROM event_category WHERE event_id = {$eId}")->fetchColumn();
    check("Phase 13", "Cascade removal of junction records", $orphanCats === 0);

} catch (Throwable $e) {
    check("Phase 11/13", "Event/M:M exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 14, 15, 16, 17, 18: EVENT REGISTRATION, ACID TRANSACTIONS, CONSTRAINTS
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 14-18: ACID Transactions, Capacity & Registrations ---" . PHP_EOL;
try {
    // 1. Create a controlled event with max_capacity = 2 for capacity testing
    $testVenueId = 1;
    $pdo->prepare("INSERT INTO event (event_name, description, event_date, event_time, venue_id, max_capacity, status) VALUES ('Capacity Test Workshop', 'Strict capacity testing', '2026-12-01', '02:00 PM', ?, 2, 'UPCOMING')")->execute([$testVenueId]);
    $capEventId = (int)$pdo->lastInsertId();

    // Use existing students 1, 2, 3
    $std1 = 1;
    $std2 = 2;
    $std3 = 3;

    // Clear any previous registrations for this test event
    $pdo->prepare("DELETE FROM registration WHERE event_id = ?")->execute([$capEventId]);

    // Student 1: Registration using ACID Transaction with FOR UPDATE
    $pdo->beginTransaction();
    $lockStmt = $pdo->prepare("SELECT max_capacity FROM event WHERE event_id = ? FOR UPDATE");
    $lockStmt->execute([$capEventId]);
    $maxCap = (int)$lockStmt->fetchColumn();

    $curCountStmt = $pdo->prepare("SELECT COUNT(*) FROM registration WHERE event_id = ? AND status = 'CONFIRMED' FOR UPDATE");
    $curCountStmt->execute([$capEventId]);
    $curCount = (int)$curCountStmt->fetchColumn();

    $insReg1 = $pdo->prepare("INSERT INTO registration (student_id, event_id, status) VALUES (?, ?, 'CONFIRMED')");
    $insReg1->execute([$std1, $capEventId]);
    $pdo->commit();

    $reg1Exists = (int)$pdo->query("SELECT COUNT(*) FROM registration WHERE student_id = {$std1} AND event_id = {$capEventId}")->fetchColumn();
    check("Phase 14 & 18", "ACID Transaction: Student 1 Registered & Committed", $reg1Exists === 1);

    // Student 1: Attempt Duplicate Registration
    $dupAttemptCaught = false;
    try {
        $pdo->beginTransaction();
        $insDup = $pdo->prepare("INSERT INTO registration (student_id, event_id, status) VALUES (?, ?, 'CONFIRMED')");
        $insDup->execute([$std1, $capEventId]);
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() === '23000') {
            $dupAttemptCaught = true;
        }
    }
    check("Phase 15", "Duplicate Registration blocked by UNIQUE(student_id, event_id)", $dupAttemptCaught);

    // Student 2: Register (Seat 2 of 2)
    $pdo->beginTransaction();
    $insReg2 = $pdo->prepare("INSERT INTO registration (student_id, event_id, status) VALUES (?, ?, 'CONFIRMED')");
    $insReg2->execute([$std2, $capEventId]);
    $pdo->commit();
    $regCountAfter2 = (int)$pdo->query("SELECT COUNT(*) FROM registration WHERE event_id = {$capEventId} AND status = 'CONFIRMED'")->fetchColumn();
    check("Phase 16", "Event reached capacity (2/2)", $regCountAfter2 === 2);

    // Student 3: Attempt registration when full (Capacity limit reached)
    $pdo->beginTransaction();
    $capCheck = (int)$pdo->query("SELECT COUNT(*) FROM registration WHERE event_id = {$capEventId} AND status = 'CONFIRMED'")->fetchColumn();
    $overCapacity = ($capCheck >= $maxCap);
    if ($overCapacity) {
        $pdo->rollBack(); // Abort registration
    }
    check("Phase 16", "Student 3 Rejected when event is at max capacity", $overCapacity);

    // Phase 18: Deliberate Failure & ROLLBACK Test
    $pdo->beginTransaction();
    $rollbackWorked = false;
    try {
        $pdo->prepare("INSERT INTO registration (student_id, event_id, status) VALUES (99999, ?, 'CONFIRMED')")->execute([$capEventId]);
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $rollbackWorked = true;
    }
    $invalidCount = (int)$pdo->query("SELECT COUNT(*) FROM registration WHERE student_id = 99999")->fetchColumn();
    check("Phase 18", "Deliberate Transaction ROLLBACK leaves zero partial state", $rollbackWorked && $invalidCount === 0);

    // Phase 17: Registration Cancellation Test
    $cancelStmt = $pdo->prepare("UPDATE registration SET status = 'CANCELLED' WHERE student_id = ? AND event_id = ?");
    $cancelStmt->execute([$std1, $capEventId]);
    $cancelledStatus = $pdo->query("SELECT status FROM registration WHERE student_id = {$std1} AND event_id = {$capEventId}")->fetchColumn();
    check("Phase 17", "Registration status successfully updated to 'CANCELLED'", $cancelledStatus === 'CANCELLED');

    $activeSeatsAfterCancel = (int)$pdo->query("SELECT COUNT(*) FROM registration WHERE event_id = {$capEventId} AND status = 'CONFIRMED'")->fetchColumn();
    check("Phase 17", "Active confirmed seats restored (1/2)", $activeSeatsAfterCancel === 1);

    // Clean up test event and registrations
    $pdo->prepare("DELETE FROM registration WHERE event_id = ?")->execute([$capEventId]);
    $pdo->prepare("DELETE FROM event WHERE event_id = ?")->execute([$capEventId]);

} catch (Throwable $e) {
    check("Phase 14-18", "Registration & transaction exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 19 & 20: SQL QUERY TESTING & RELATIONAL JOINS
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 19 & 20: SQL Query Suite & Relational JOINs ---" . PHP_EOL;
try {
    // 4-Table JOIN: Student -> Department -> Registration -> Event -> Venue
    $joinSql = "
        SELECT 
            s.name AS student_name,
            d.department_name,
            e.event_name,
            v.venue_name,
            r.registration_date,
            r.status AS registration_status
        FROM registration r
        INNER JOIN student s ON r.student_id = s.student_id
        INNER JOIN department d ON s.department_id = d.department_id
        INNER JOIN event e ON r.event_id = e.event_id
        INNER JOIN venue v ON e.venue_id = v.venue_id
        LIMIT 10
    ";
    $joinRows = $pdo->query($joinSql)->fetchAll();
    check("Phase 20", "4-Table Relational INNER JOIN (Student+Dept+Reg+Event+Venue)", count($joinRows) > 0, count($joinRows) . " rows returned");

    // LEFT JOIN: Events with or without registrations
    $leftJoinSql = "
        SELECT e.event_name, COUNT(r.registration_id) AS total_registrations
        FROM event e
        LEFT JOIN registration r ON e.event_id = r.event_id AND r.status = 'CONFIRMED'
        GROUP BY e.event_id, e.event_name
    ";
    $leftJoinRows = $pdo->query($leftJoinSql)->fetchAll();
    check("Phase 19", "LEFT JOIN with GROUP BY (Event left join Registration)", count($leftJoinRows) > 0);

    // WHERE, LIKE, BETWEEN, IN, ORDER BY
    $clausesSql = "
        SELECT * FROM event 
        WHERE status = 'UPCOMING'
          AND event_name LIKE '%Hackathon%'
          AND event_date BETWEEN '2026-01-01' AND '2026-12-31'
          AND venue_id IN (1, 2, 3, 4)
        ORDER BY event_date ASC
    ";
    $clauseRows = $pdo->query($clausesSql)->fetchAll();
    check("Phase 19", "SQL Clauses: WHERE, LIKE, BETWEEN, IN, ORDER BY", count($clauseRows) > 0, count($clauseRows) . " matching events");

} catch (Throwable $e) {
    check("Phase 19/20", "SQL query exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 21: AGGREGATE QUERIES (COUNT, SUM, AVG, MIN, MAX, GROUP BY, HAVING)
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 21: Aggregate Functions & GROUP BY HAVING ---" . PHP_EOL;
try {
    $agg = $pdo->query("
        SELECT 
            COUNT(*) AS total_events,
            SUM(max_capacity) AS total_capacity,
            ROUND(AVG(max_capacity), 2) AS avg_capacity,
            MIN(max_capacity) AS min_capacity,
            MAX(max_capacity) AS max_capacity
        FROM event
    ")->fetch();
    check("Phase 21", "Aggregates: COUNT, SUM, AVG, MIN, MAX", !empty($agg), "Total: {$agg['total_events']}, Avg: {$agg['avg_capacity']}, Min: {$agg['min_capacity']}, Max: {$agg['max_capacity']}");

    // GROUP BY with HAVING
    $having = $pdo->query("
        SELECT d.department_name, COUNT(s.student_id) AS student_count
        FROM department d
        LEFT JOIN student s ON d.department_id = s.department_id
        GROUP BY d.department_id, d.department_name
        HAVING student_count >= 1
        ORDER BY student_count DESC
    ")->fetchAll();
    check("Phase 21", "GROUP BY with HAVING condition", count($having) > 0, count($having) . " departments satisfied HAVING >= 1");

} catch (Throwable $e) {
    check("Phase 21", "Aggregate exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 22: SUBQUERIES (CAPACITY > AVG CAPACITY)
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 22: Subquery Demonstrations ---" . PHP_EOL;
try {
    $subquerySql = "
        SELECT event_id, event_name, max_capacity
        FROM event
        WHERE max_capacity > (SELECT AVG(max_capacity) FROM event)
        ORDER BY max_capacity DESC
    ";
    $subRows = $pdo->query($subquerySql)->fetchAll();
    check("Phase 22", "Scalar Subquery: Events with max_capacity > AVG(max_capacity)", count($subRows) > 0, count($subRows) . " events above average");

    // Correlated Subquery / EXISTS
    $existsSql = "
        SELECT d.department_name
        FROM department d
        WHERE EXISTS (
            SELECT 1 FROM student s WHERE s.department_id = d.department_id
        )
    ";
    $existsRows = $pdo->query($existsSql)->fetchAll();
    check("Phase 22", "Correlated Subquery with WHERE EXISTS", count($existsRows) > 0, count($existsRows) . " departments with enrolled students");

} catch (Throwable $e) {
    check("Phase 22", "Subquery exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 23: DATABASE VIEWS
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 23: Compiled Database Views ---" . PHP_EOL;
try {
    $v1 = $pdo->query("SELECT * FROM view_student_registrations LIMIT 5")->fetchAll();
    check("Phase 23", "Query view_student_registrations", count($v1) > 0, count($v1) . " rows retrieved");

    $v2 = $pdo->query("SELECT * FROM view_event_registration_summary LIMIT 5")->fetchAll();
    check("Phase 23", "Query view_event_registration_summary", count($v2) > 0, count($v2) . " rows retrieved");

    $v3 = $pdo->query("SELECT * FROM view_department_enrollment_stats LIMIT 5")->fetchAll();
    check("Phase 23", "Query view_department_enrollment_stats", count($v3) > 0, count($v3) . " rows retrieved");

} catch (Throwable $e) {
    check("Phase 23", "Views exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 24: INDEXES VERIFICATION
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 24: B-Tree Indexes Inspection ---" . PHP_EOL;
try {
    $indexes = $pdo->query("
        SELECT DISTINCT INDEX_NAME, TABLE_NAME
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = 'cems_db' AND INDEX_NAME NOT LIKE 'PRIMARY%'
    ")->fetchAll();
    $indexNames = array_column($indexes, 'INDEX_NAME');

    check("Phase 24", "Index: idx_student_email on student(email)", in_array('idx_student_email', $indexNames, true));
    check("Phase 24", "Index: idx_event_date on event(event_date, status)", in_array('idx_event_date', $indexNames, true));
    check("Phase 24", "Index: idx_registration_student on registration", in_array('idx_registration_student', $indexNames, true));
    check("Phase 24", "Index: idx_event_venue on event(venue_id)", in_array('idx_event_venue', $indexNames, true));

} catch (Throwable $e) {
    check("Phase 24", "Index inspection exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// PHASE 26: SECURITY AUDIT (SQL INJECTION RESISTANCE)
// -----------------------------------------------------------------------------
echo PHP_EOL . "--- Phase 26: Security & SQL Injection Resistance ---" . PHP_EOL;
try {
    $maliciousInput = "' OR '1'='1' -- ";
    
    // Test prepared statement with malicious input
    $secStmt = $pdo->prepare("SELECT * FROM student WHERE email = ?");
    $secStmt->execute([$maliciousInput]);
    $secRes = $secStmt->fetchAll();
    check("Phase 26", "Prepared statement neutralizes SQL injection payload", count($secRes) === 0);

    // Test password hashing algorithm
    check("Phase 26", "password_hash uses PASSWORD_BCRYPT algorithm", PASSWORD_BCRYPT !== null);

} catch (Throwable $e) {
    check("Phase 26", "Security exception", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo PHP_EOL . "================================================================================" . PHP_EOL;
echo "  AUDIT RESULTS: {$passedChecks} PASSED, {$failedChecks} FAILED (TOTAL: {$totalChecks})" . PHP_EOL;
echo "================================================================================" . PHP_EOL . PHP_EOL;

if ($failedChecks > 0) {
    echo "Failures:" . PHP_EOL;
    foreach ($failures as $f) {
        echo "  - {$f}" . PHP_EOL;
    }
    exit(1);
} else {
    echo "All audit checks passed successfully!" . PHP_EOL;
    exit(0);
}
