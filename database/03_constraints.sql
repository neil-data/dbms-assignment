-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 03: Constraints & Foreign Keys
-- Referential Integrity, Domain & Entity Integrity Rules
-- ============================================================================

USE cems_db;

-- ----------------------------------------------------------------------------
-- Foreign Key: STUDENT -> DEPARTMENT (DEPARTMENT 1 : M STUDENT)
-- ----------------------------------------------------------------------------
ALTER TABLE student
    ADD CONSTRAINT fk_student_department
    FOREIGN KEY (department_id) REFERENCES department(department_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;

-- ----------------------------------------------------------------------------
-- Check Constraint: VENUE Capacity (Must be strictly greater than 0)
-- ----------------------------------------------------------------------------
ALTER TABLE venue
    ADD CONSTRAINT chk_venue_capacity
    CHECK (capacity > 0);

-- ----------------------------------------------------------------------------
-- Foreign Key: EVENT -> VENUE (VENUE 1 : M EVENT)
-- ----------------------------------------------------------------------------
ALTER TABLE event
    ADD CONSTRAINT fk_event_venue
    FOREIGN KEY (venue_id) REFERENCES venue(venue_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;

-- ----------------------------------------------------------------------------
-- Check Constraint: EVENT Capacity (Must be strictly greater than 0)
-- ----------------------------------------------------------------------------
ALTER TABLE event
    ADD CONSTRAINT chk_event_max_capacity
    CHECK (max_capacity > 0);

-- ----------------------------------------------------------------------------
-- Foreign Keys & Unique Constraint: REGISTRATION
-- STUDENT 1 : M REGISTRATION, EVENT 1 : M REGISTRATION
-- UNIQUE(student_id, event_id) Prevents duplicate registration
-- ----------------------------------------------------------------------------
ALTER TABLE registration
    ADD CONSTRAINT fk_registration_student
    FOREIGN KEY (student_id) REFERENCES student(student_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE;

ALTER TABLE registration
    ADD CONSTRAINT fk_registration_event
    FOREIGN KEY (event_id) REFERENCES event(event_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE;

ALTER TABLE registration
    ADD CONSTRAINT uq_student_event_registration
    UNIQUE (student_id, event_id);

-- ----------------------------------------------------------------------------
-- Foreign Keys: EVENT_CATEGORY (Junction table resolving M : M)
-- ----------------------------------------------------------------------------
ALTER TABLE event_category
    ADD CONSTRAINT fk_event_category_event
    FOREIGN KEY (event_id) REFERENCES event(event_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE;

ALTER TABLE event_category
    ADD CONSTRAINT fk_event_category_category
    FOREIGN KEY (category_id) REFERENCES category(category_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE;
