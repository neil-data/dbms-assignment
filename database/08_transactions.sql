-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 08: ACID Transaction Demonstrations
-- Demonstrating START TRANSACTION, Capacity Checks, Row Locking, COMMIT & ROLLBACK
-- ============================================================================

USE cems_db;

-- ----------------------------------------------------------------------------
-- SCENARIO A: Successful Event Registration Transaction (COMMIT)
-- Student 5 ('Kavya Nair') registers for Event 1 ('National Collegiate Hackathon 2026')
-- ----------------------------------------------------------------------------

START TRANSACTION;

-- Step 1: Lock the event record to inspect current max_capacity and status safely
SELECT event_id, event_name, max_capacity, status
FROM event
WHERE event_id = 1 AND status = 'UPCOMING'
FOR UPDATE;

-- Step 2: Check current confirmed enrollment for Event 1
SELECT COUNT(*) AS current_confirmed
FROM registration
WHERE event_id = 1 AND status = 'CONFIRMED';

-- Step 3: Verify the student is not already registered (Duplicate check)
SELECT registration_id
FROM registration
WHERE student_id = 5 AND event_id = 1;

-- Step 4: Insert the new registration record
INSERT INTO registration (student_id, event_id, status)
VALUES (5, 1, 'CONFIRMED');

-- Step 5: Commit the transaction, making the changes permanent in MySQL
COMMIT;


-- ----------------------------------------------------------------------------
-- SCENARIO B: Capacity Exceeded / Aborted Registration Transaction (ROLLBACK)
-- Student attempts registration, condition fails (e.g. overcapacity or duplicate),
-- so the transaction is rolled back, leaving the database unmodified.
-- ----------------------------------------------------------------------------

START TRANSACTION;

-- Step 1: Select event with row lock
SELECT event_id, max_capacity
FROM event
WHERE event_id = 1
FOR UPDATE;

-- Step 2: Simulate duplicate attempt or business rule violation
-- Attempt to insert a registration that violates business logic
INSERT INTO registration (student_id, event_id, status)
VALUES (5, 1, 'CONFIRMED');

-- Step 3: Application logic detects violation / error -> Issue explicit ROLLBACK
ROLLBACK;

-- Verify that the duplicate or invalid state was NOT saved:
SELECT * FROM registration WHERE student_id = 5 AND event_id = 1;
