-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 05: Academic Demonstration Sample Data
-- Controlled, Realistic Institutional Dataset for DBMS Viva
-- Default Passwords:
--   Students : Student@123
--   Admins   : Admin@123
-- (Both hashed using standard PHP password_hash PASSWORD_BCRYPT)
-- ============================================================================

USE cems_db;

-- Clear any existing records in proper child-to-parent foreign key sequence
DELETE FROM event_category;
DELETE FROM registration;
DELETE FROM event;
DELETE FROM venue;
DELETE FROM student;
DELETE FROM department;
DELETE FROM category;
DELETE FROM admin;

-- Reset Auto-Increment counters
ALTER TABLE department AUTO_INCREMENT = 1;
ALTER TABLE student AUTO_INCREMENT = 1;
ALTER TABLE venue AUTO_INCREMENT = 1;
ALTER TABLE event AUTO_INCREMENT = 1;
ALTER TABLE category AUTO_INCREMENT = 1;
ALTER TABLE registration AUTO_INCREMENT = 1;
ALTER TABLE admin AUTO_INCREMENT = 1;

-- ----------------------------------------------------------------------------
-- 1. DEPARTMENT (5 Records)
-- ----------------------------------------------------------------------------
INSERT INTO department (department_id, department_name) VALUES
(1, 'Computer Science & Engineering'),
(2, 'Information Technology'),
(3, 'Electronics & Communication Engineering'),
(4, 'Mechanical Engineering'),
(5, 'Civil Engineering');

-- ----------------------------------------------------------------------------
-- 2. VENUE (4 Records)
-- ----------------------------------------------------------------------------
INSERT INTO venue (venue_id, venue_name, location, capacity) VALUES
(1, 'Main University Auditorium', 'Central Campus Block A', 500),
(2, 'Dr. APJ Abdul Kalam Seminar Hall', 'Science & Innovation Complex Level 2', 150),
(3, 'Alan Turing Computing Lab Complex', 'IT Wing Ground Floor', 80),
(4, 'Ramanujan Lecture Theater', 'Academic Block 3 Level 1', 200);

-- ----------------------------------------------------------------------------
-- 3. CATEGORY (6 Records)
-- ----------------------------------------------------------------------------
INSERT INTO category (category_id, category_name) VALUES
(1, 'Technical'),
(2, 'Cultural'),
(3, 'Sports'),
(4, 'Workshops'),
(5, 'Seminars'),
(6, 'Competitions');

-- ----------------------------------------------------------------------------
-- 4. EVENT (8 Records)
-- ----------------------------------------------------------------------------
INSERT INTO event (event_id, event_name, description, event_date, event_time, venue_id, max_capacity, status) VALUES
(1, 'National Collegiate Hackathon 2026', 'A premier 36-hour coding marathon focused on autonomous software, agentic AI, and distributed systems architecture.', '2026-10-15', '09:00 AM - 09:00 PM', 3, 80, 'UPCOMING'),
(2, 'Annual Cultural Symphony Gala', 'Grand institutional celebration presenting classical music orchestrations, contemporary dance revues, and theatrical drama.', '2026-10-22', '05:30 PM - 10:00 PM', 1, 450, 'UPCOMING'),
(3, 'Autonomous AI & Robotics Workshop', 'Hands-on practical laboratory session on computer vision, sensor fusion, and micro-controller embedded firmware engineering.', '2026-10-28', '10:00 AM - 04:30 PM', 3, 75, 'UPCOMING'),
(4, 'Inter-University Chess Championship', 'FIDE-rated swiss league rapid and blitz chess tournament with student grandmasters from 24 participating institutions.', '2026-09-10', '09:30 AM - 06:00 PM', 2, 120, 'COMPLETED'),
(5, 'Cloud DevOps & Microservices Bootcamp', 'Intensive training on Docker containers, Kubernetes orchestration, CI/CD automated pipelines, and MySQL clustering.', '2026-09-25', '01:00 PM - 05:00 PM', 3, 60, 'ONGOING'),
(6, 'Green Building & Sustainable Tech Seminar', 'Expert symposium on carbon-neutral structural engineering, solar envelope architecture, and recycled concrete aggregates.', '2026-11-05', '11:00 AM - 02:00 PM', 4, 180, 'UPCOMING'),
(7, 'Inter-College Badminton Tournament', 'Annual indoor badminton championship featuring men and women singles, doubles, and mixed doubles fixtures.', '2026-11-12', '08:30 AM - 06:00 PM', 1, 100, 'UPCOMING'),
(8, 'Cyber Threat Intelligence Summit', 'Advanced defense lecture and red-team demonstration on zero-day mitigation and cryptographic protocols.', '2026-11-20', '10:00 AM - 03:00 PM', 2, 140, 'CANCELLED');

-- ----------------------------------------------------------------------------
-- 5. EVENT_CATEGORY (Many-to-Many Mappings)
-- ----------------------------------------------------------------------------
INSERT INTO event_category (event_id, category_id) VALUES
(1, 1), -- Hackathon: Technical
(1, 6), -- Hackathon: Competitions
(2, 2), -- Gala: Cultural
(3, 1), -- Robotics: Technical
(3, 4), -- Robotics: Workshops
(4, 3), -- Chess: Sports
(4, 6), -- Chess: Competitions
(5, 1), -- Cloud: Technical
(5, 4), -- Cloud: Workshops
(6, 5), -- Green Building: Seminars
(7, 3), -- Badminton: Sports
(7, 6), -- Badminton: Competitions
(8, 1), -- Cyber: Technical
(8, 5); -- Cyber: Seminars

-- ----------------------------------------------------------------------------
-- 6. STUDENT (12 Records)
-- Password: 'Student@123'
-- Hash: $2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq
-- ----------------------------------------------------------------------------
INSERT INTO student (student_id, department_id, name, email, phone, semester, password, created_at) VALUES
(1, 1, 'Aarav Sharma', 'aarav.sharma@campus.edu', '+91 98765 43210', 6, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-01 10:00:00'),
(2, 1, 'Diya Patel', 'diya.patel@campus.edu', '+91 98765 43211', 4, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-02 11:15:00'),
(3, 2, 'Rohan Verma', 'rohan.verma@campus.edu', '+91 98765 43212', 6, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-05 09:30:00'),
(4, 2, 'Ananya Iyer', 'ananya.iyer@campus.edu', '+91 98765 43213', 2, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-06 14:20:00'),
(5, 3, 'Kavya Nair', 'kavya.nair@campus.edu', '+91 98765 43214', 8, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-10 16:45:00'),
(6, 3, 'Siddharth Rao', 'siddharth.rao@campus.edu', '+91 98765 43215', 4, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-12 12:00:00'),
(7, 4, 'Vikram Singh', 'vikram.singh@campus.edu', '+91 98765 43216', 6, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-15 15:10:00'),
(8, 4, 'Meera Deshmukh', 'meera.deshmukh@campus.edu', '+91 98765 43217', 2, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-18 10:40:00'),
(9, 5, 'Arjun Reddy', 'arjun.reddy@campus.edu', '+91 98765 43218', 6, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-20 13:25:00'),
(10, 5, 'Pooja Joshi', 'pooja.joshi@campus.edu', '+91 98765 43219', 8, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-22 17:00:00'),
(11, 1, 'Ishaan Gupta', 'ishaan.gupta@campus.edu', '+91 98765 43220', 4, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-25 09:10:00'),
(12, 2, 'Sneha Kulkarni', 'sneha.kulkarni@campus.edu', '+91 98765 43221', 6, '$2y$10$p/bb3ZTFtFMryl2nV7n2z.8OXJTG53AVnCEfVi6iAZt7OnVO6Gxgq', '2026-08-28 11:35:00');

-- ----------------------------------------------------------------------------
-- 7. REGISTRATION (15 Records)
-- Enforces UNIQUE(student_id, event_id)
-- ----------------------------------------------------------------------------
INSERT INTO registration (registration_id, student_id, event_id, registration_date, status) VALUES
(1, 1, 1, '2026-09-01 10:12:00', 'CONFIRMED'),
(2, 2, 1, '2026-09-01 11:45:00', 'CONFIRMED'),
(3, 3, 1, '2026-09-02 09:30:00', 'CONFIRMED'),
(4, 11, 1, '2026-09-02 14:15:00', 'CONFIRMED'),
(5, 4, 2, '2026-09-03 16:20:00', 'CONFIRMED'),
(6, 5, 2, '2026-09-03 17:05:00', 'CONFIRMED'),
(7, 8, 2, '2026-09-04 10:00:00', 'CONFIRMED'),
(8, 1, 3, '2026-09-04 11:30:00', 'CONFIRMED'),
(9, 6, 3, '2026-09-05 13:40:00', 'CONFIRMED'),
(10, 7, 4, '2026-09-05 15:50:00', 'COMPLETED'),
(11, 9, 4, '2026-09-06 09:10:00', 'COMPLETED'),
(12, 12, 5, '2026-09-07 10:25:00', 'CONFIRMED'),
(13, 10, 6, '2026-09-08 14:00:00', 'CONFIRMED'),
(14, 2, 7, '2026-09-09 16:45:00', 'CONFIRMED'),
(15, 3, 8, '2026-09-10 11:20:00', 'CANCELLED');

-- ----------------------------------------------------------------------------
-- 8. ADMIN (2 Records)
-- Password: 'Admin@123'
-- Hash: $2y$10$x35s4D4jo07eTAyPlqv7G.LKP9e/QflocBYifMGIP4kSBjU4wUQqm
-- ----------------------------------------------------------------------------
INSERT INTO admin (admin_id, username, password, created_at) VALUES
(1, 'admin', '$2y$10$x35s4D4jo07eTAyPlqv7G.LKP9e/QflocBYifMGIP4kSBjU4wUQqm', '2026-08-01 00:00:00'),
(2, 'head_coordinator', '$2y$10$x35s4D4jo07eTAyPlqv7G.LKP9e/QflocBYifMGIP4kSBjU4wUQqm', '2026-08-01 00:00:00');
