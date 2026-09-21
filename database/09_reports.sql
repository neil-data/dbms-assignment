-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 09: Institutional Analytical Reports
-- 8 Comprehensive SQL Reports for Academic Governance
-- ============================================================================

USE cems_db;

-- ----------------------------------------------------------------------------
-- Report 1: Students by Academic Department
-- Measures student enrollment distribution across campus departments
-- ----------------------------------------------------------------------------
SELECT 
    d.department_id,
    d.department_name,
    COUNT(s.student_id) AS total_enrolled_students,
    ROUND((COUNT(s.student_id) / (SELECT COUNT(*) FROM student)) * 100, 1) AS department_percentage
FROM department d
LEFT JOIN student s ON d.department_id = s.department_id
GROUP BY d.department_id, d.department_name
ORDER BY total_enrolled_students DESC;

-- ----------------------------------------------------------------------------
-- Report 2: Registrations by Event
-- Details student interest and registration totals across all campus events
-- ----------------------------------------------------------------------------
SELECT 
    e.event_id,
    e.event_name,
    e.status AS event_status,
    v.venue_name,
    COUNT(r.registration_id) AS total_registrations,
    COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END) AS confirmed_passes,
    COUNT(CASE WHEN r.status = 'CANCELLED' THEN 1 END) AS cancelled_passes
FROM event e
INNER JOIN venue v ON e.venue_id = v.venue_id
LEFT JOIN registration r ON e.event_id = r.event_id
GROUP BY e.event_id, e.event_name, e.status, v.venue_name
ORDER BY confirmed_passes DESC;

-- ----------------------------------------------------------------------------
-- Report 3: Registrations by Category
-- Measures campus activity engagement across different event genres
-- ----------------------------------------------------------------------------
SELECT 
    c.category_id,
    c.category_name,
    COUNT(DISTINCT e.event_id) AS events_offered,
    COUNT(r.registration_id) AS total_category_registrations
FROM category c
LEFT JOIN event_category ec ON c.category_id = ec.category_id
LEFT JOIN event e ON ec.event_id = e.event_id
LEFT JOIN registration r ON e.event_id = r.event_id AND r.status = 'CONFIRMED'
GROUP BY c.category_id, c.category_name
ORDER BY total_category_registrations DESC;

-- ----------------------------------------------------------------------------
-- Report 4: Event Capacity Utilization & Seat Scarcity
-- Highlights room utilization efficiency and remaining seats
-- ----------------------------------------------------------------------------
SELECT 
    e.event_id,
    e.event_name,
    v.venue_name,
    e.max_capacity,
    COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END) AS confirmed_attendees,
    (e.max_capacity - COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END)) AS available_seats,
    ROUND((COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END) / e.max_capacity) * 100, 1) AS utilization_rate
FROM event e
INNER JOIN venue v ON e.venue_id = v.venue_id
LEFT JOIN registration r ON e.event_id = r.event_id
GROUP BY e.event_id, e.event_name, v.venue_name, e.max_capacity
ORDER BY utilization_rate DESC;

-- ----------------------------------------------------------------------------
-- Report 5: Registration Status Distribution
-- Overall breakdown of pass states across the institutional system
-- ----------------------------------------------------------------------------
SELECT 
    status,
    COUNT(*) AS total_count,
    ROUND((COUNT(*) / (SELECT COUNT(*) FROM registration)) * 100, 1) AS status_percentage
FROM registration
GROUP BY status
ORDER BY total_count DESC;

-- ----------------------------------------------------------------------------
-- Report 6: Upcoming Events with Schedule & Location
-- Calendar report of upcoming campus programs
-- ----------------------------------------------------------------------------
SELECT 
    e.event_id,
    e.event_name,
    e.event_date,
    e.event_time,
    v.venue_name,
    v.location,
    e.max_capacity
FROM event e
INNER JOIN venue v ON e.venue_id = v.venue_id
WHERE e.status = 'UPCOMING'
ORDER BY e.event_date ASC;

-- ----------------------------------------------------------------------------
-- Report 7: Most Registered (Top-Tier) Events
-- Identifies the most popular events by enrollment
-- ----------------------------------------------------------------------------
SELECT 
    e.event_id,
    e.event_name,
    e.event_date,
    COUNT(r.registration_id) AS total_signups
FROM event e
LEFT JOIN registration r ON e.event_id = r.event_id AND r.status = 'CONFIRMED'
GROUP BY e.event_id, e.event_name, e.event_date
ORDER BY total_signups DESC
LIMIT 5;

-- ----------------------------------------------------------------------------
-- Report 8: Venue Usage & Physical Asset Allocation
-- Analyzes how many events each campus facility hosts
-- ----------------------------------------------------------------------------
SELECT 
    v.venue_id,
    v.venue_name,
    v.location,
    v.capacity,
    COUNT(e.event_id) AS events_hosted,
    COUNT(r.registration_id) AS total_attendees_accommodated
FROM venue v
LEFT JOIN event e ON v.venue_id = e.venue_id
LEFT JOIN registration r ON e.event_id = r.event_id AND r.status = 'CONFIRMED'
GROUP BY v.venue_id, v.venue_name, v.location, v.capacity
ORDER BY events_hosted DESC;
