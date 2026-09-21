-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 07: Database Views
-- Encapsulated, Virtualized Relational Queries
-- ============================================================================

USE cems_db;

DROP VIEW IF EXISTS view_student_registrations;
DROP VIEW IF EXISTS view_event_registration_summary;
DROP VIEW IF EXISTS view_department_enrollment_stats;

-- ----------------------------------------------------------------------------
-- View 1: view_student_registrations
-- Aggregates Student, Registration, Event, Venue, and Department details
-- ----------------------------------------------------------------------------
CREATE VIEW view_student_registrations AS
SELECT 
    r.registration_id,
    s.student_id,
    s.name AS student_name,
    s.email AS student_email,
    d.department_name,
    e.event_id,
    e.event_name,
    e.event_date,
    e.event_time,
    v.venue_name,
    v.location AS venue_location,
    r.registration_date,
    r.status AS registration_status
FROM registration r
INNER JOIN student s ON r.student_id = s.student_id
INNER JOIN department d ON s.department_id = d.department_id
INNER JOIN event e ON r.event_id = e.event_id
INNER JOIN venue v ON e.venue_id = v.venue_id;

-- ----------------------------------------------------------------------------
-- View 2: view_event_registration_summary
-- Summarizes Event capacity, confirmed registrations, and available seats
-- ----------------------------------------------------------------------------
CREATE VIEW view_event_registration_summary AS
SELECT 
    e.event_id,
    e.event_name,
    e.event_date,
    e.event_time,
    e.status AS event_status,
    v.venue_name,
    e.max_capacity,
    COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END) AS enrolled_count,
    (e.max_capacity - COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END)) AS available_seats,
    ROUND((COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END) / e.max_capacity) * 100, 1) AS occupancy_percentage
FROM event e
INNER JOIN venue v ON e.venue_id = v.venue_id
LEFT JOIN registration r ON e.event_id = r.event_id
GROUP BY e.event_id, e.event_name, e.event_date, e.event_time, e.status, v.venue_name, e.max_capacity;

-- ----------------------------------------------------------------------------
-- View 3: view_department_enrollment_stats
-- Summarizes Department student enrollment and total event registrations
-- ----------------------------------------------------------------------------
CREATE VIEW view_department_enrollment_stats AS
SELECT 
    d.department_id,
    d.department_name,
    COUNT(DISTINCT s.student_id) AS total_students,
    COUNT(DISTINCT r.registration_id) AS total_registrations
FROM department d
LEFT JOIN student s ON d.department_id = s.department_id
LEFT JOIN registration r ON s.student_id = r.student_id
GROUP BY d.department_id, d.department_name;
