-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 06: Comprehensive Academic Queries & Benchmarks
-- Covers: CRUD, JOINs, Aggregates, GROUP BY, HAVING, Subqueries, Pattern Matching
-- ============================================================================

USE cems_db;

-- ----------------------------------------------------------------------------
-- A. CRUD DEMONSTRATIONS
-- ----------------------------------------------------------------------------

-- 1. INSERT: Create a new venue record
INSERT INTO venue (venue_name, location, capacity)
VALUES ('Dr. Homi Bhabha Nuclear Research Hall', 'Science Quadrangle West', 220);

-- 2. SELECT: Retrieve the newly created venue
SELECT venue_id, venue_name, location, capacity
FROM venue
WHERE venue_name LIKE '%Homi Bhabha%';

-- 3. UPDATE: Increase capacity of the venue
UPDATE venue
SET capacity = 250
WHERE venue_name = 'Dr. Homi Bhabha Nuclear Research Hall';

-- 4. DELETE: Remove the demo venue record
DELETE FROM venue
WHERE venue_name = 'Dr. Homi Bhabha Nuclear Research Hall';


-- ----------------------------------------------------------------------------
-- B. RELATIONAL JOIN QUERIES
-- ----------------------------------------------------------------------------

-- 1. Multi-Table INNER JOIN: Student -> Registration -> Event -> Venue
-- Visually demonstrates referential integrity across 4 normalized entities
SELECT 
    r.registration_id,
    s.name AS student_name,
    s.email AS student_email,
    d.department_name,
    e.event_name,
    e.event_date,
    v.venue_name,
    v.location AS venue_location,
    r.status AS registration_status
FROM registration r
INNER JOIN student s ON r.student_id = s.student_id
INNER JOIN department d ON s.department_id = d.department_id
INNER JOIN event e ON r.event_id = e.event_id
INNER JOIN venue v ON e.venue_id = v.venue_id
ORDER BY e.event_date ASC, s.name ASC;

-- 2. LEFT JOIN: All Venues and the Events hosted in them (including empty venues)
SELECT 
    v.venue_id,
    v.venue_name,
    v.capacity AS venue_capacity,
    e.event_id,
    e.event_name,
    e.event_date
FROM venue v
LEFT JOIN event e ON v.venue_id = e.venue_id
ORDER BY v.venue_name ASC;

-- 3. Many-to-Many JOIN: Events with their assigned Categories via EVENT_CATEGORY
SELECT 
    e.event_id,
    e.event_name,
    GROUP_CONCAT(c.category_name ORDER BY c.category_name SEPARATOR ', ') AS categories
FROM event e
INNER JOIN event_category ec ON e.event_id = ec.event_id
INNER JOIN category c ON ec.category_id = c.category_id
GROUP BY e.event_id, e.event_name;


-- ----------------------------------------------------------------------------
-- C. AGGREGATE QUERIES (COUNT, SUM, AVG, MIN, MAX)
-- ----------------------------------------------------------------------------

SELECT 
    COUNT(DISTINCT s.student_id) AS total_students,
    COUNT(DISTINCT e.event_id) AS total_events,
    COUNT(DISTINCT r.registration_id) AS total_registrations,
    ROUND(AVG(e.max_capacity), 1) AS average_event_capacity,
    MAX(v.capacity) AS maximum_venue_capacity,
    MIN(v.capacity) AS minimum_venue_capacity,
    SUM(v.capacity) AS total_campus_seating_capacity
FROM student s
CROSS JOIN event e
CROSS JOIN venue v
CROSS JOIN registration r;


-- ----------------------------------------------------------------------------
-- D. GROUP BY & HAVING QUERIES
-- ----------------------------------------------------------------------------

-- 1. Registrations count by event, showing only events with at least 1 registration
SELECT 
    e.event_id,
    e.event_name,
    e.max_capacity,
    COUNT(r.registration_id) AS total_enrolled,
    (e.max_capacity - COUNT(r.registration_id)) AS available_seats
FROM event e
LEFT JOIN registration r ON e.event_id = r.event_id AND r.status = 'CONFIRMED'
GROUP BY e.event_id, e.event_name, e.max_capacity
HAVING total_enrolled > 0
ORDER BY total_enrolled DESC;

-- 2. Student distribution by Academic Department
SELECT 
    d.department_id,
    d.department_name,
    COUNT(s.student_id) AS student_count
FROM department d
LEFT JOIN student s ON d.department_id = s.department_id
GROUP BY d.department_id, d.department_name
ORDER BY student_count DESC;

-- 3. Registrations distribution by Status
SELECT 
    status,
    COUNT(*) AS total_count
FROM registration
GROUP BY status;


-- ----------------------------------------------------------------------------
-- E. NESTED SUBQUERIES
-- ----------------------------------------------------------------------------

-- 1. Find all events whose max_capacity is greater than the average event capacity
SELECT 
    event_id,
    event_name,
    max_capacity,
    event_date
FROM event
WHERE max_capacity > (
    SELECT AVG(max_capacity) FROM event
)
ORDER BY max_capacity DESC;

-- 2. Correlated Subquery: Find students who have registered for more than 1 event
SELECT 
    s.student_id,
    s.name,
    s.email,
    (
        SELECT COUNT(*) 
        FROM registration r 
        WHERE r.student_id = s.student_id AND r.status != 'CANCELLED'
    ) AS active_registrations
FROM student s
WHERE (
    SELECT COUNT(*) 
    FROM registration r 
    WHERE r.student_id = s.student_id AND r.status != 'CANCELLED'
) > 1
ORDER BY active_registrations DESC;


-- ----------------------------------------------------------------------------
-- F. FILTERING OPERATORS: WHERE, LIKE, BETWEEN, IN
-- ----------------------------------------------------------------------------

-- 1. Pattern Matching with LIKE
SELECT student_id, name, email 
FROM student 
WHERE email LIKE '%.edu' AND name LIKE 'A%';

-- 2. Range Filtering with BETWEEN
SELECT event_id, event_name, event_date, max_capacity 
FROM event 
WHERE event_date BETWEEN '2026-10-01' AND '2026-10-31';

-- 3. Membership Testing with IN
SELECT event_id, event_name, status 
FROM event 
WHERE status IN ('UPCOMING', 'ONGOING');
