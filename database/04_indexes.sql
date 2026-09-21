-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 04: Indexes
-- Performance Optimization for Search, Join, and Range Queries
-- ============================================================================

USE cems_db;

-- ----------------------------------------------------------------------------
-- 1. Index on student(email)
-- Rationale: Fast lookup during student portal login and registration duplicate
-- checks without full table scans. (Note: UNIQUE constraint in MySQL automatically
-- establishes an index; creating an explicit index guarantees optimized access).
-- ----------------------------------------------------------------------------
CREATE INDEX idx_student_email ON student(email);

-- ----------------------------------------------------------------------------
-- 2. Index on student(department_id)
-- Rationale: Optimizes foreign key joins between DEPARTMENT and STUDENT, and
-- speeds up analytical reports grouping students by department.
-- ----------------------------------------------------------------------------
CREATE INDEX idx_student_department ON student(department_id);

-- ----------------------------------------------------------------------------
-- 3. Index on event(event_date)
-- Rationale: Supports chronological range filtering, calendar sorting (ORDER BY event_date),
-- and upcoming event queries (WHERE event_date >= CURRENT_DATE).
-- ----------------------------------------------------------------------------
CREATE INDEX idx_event_date ON event(event_date);

-- ----------------------------------------------------------------------------
-- 4. Index on event(venue_id)
-- Rationale: Accelerates parent-child relational joins between EVENT and VENUE
-- to display facility names and calculate venue occupancy.
-- ----------------------------------------------------------------------------
CREATE INDEX idx_event_venue ON event(venue_id);

-- ----------------------------------------------------------------------------
-- 5. Index on event(status)
-- Rationale: Speeds up public event catalog queries filtering for active events
-- (WHERE status = 'UPCOMING' OR status = 'ONGOING').
-- ----------------------------------------------------------------------------
CREATE INDEX idx_event_status ON event(status);

-- ----------------------------------------------------------------------------
-- 6. Index on registration(student_id)
-- Rationale: Optimizes student dashboard queries retrieving all tickets/passes
-- for the currently logged-in student.
-- ----------------------------------------------------------------------------
CREATE INDEX idx_registration_student ON registration(student_id);

-- ----------------------------------------------------------------------------
-- 7. Index on registration(event_id)
-- Rationale: Speeds up event aggregate capacity queries (COUNT(*) WHERE event_id = ?)
-- during high-concurrency registration transactions.
-- ----------------------------------------------------------------------------
CREATE INDEX idx_registration_event ON registration(event_id);

-- ----------------------------------------------------------------------------
-- 8. Index on registration(status)
-- Rationale: Enables fast filtering for confirmed vs cancelled registrations
-- when computing effective seat allocations.
-- ----------------------------------------------------------------------------
CREATE INDEX idx_registration_status ON registration(status);
