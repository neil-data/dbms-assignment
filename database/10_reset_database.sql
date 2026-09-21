-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 10: Complete Database Reset & Setup
-- Single executable script for complete teardown, rebuild, and reseed in phpMyAdmin
-- ============================================================================

CREATE DATABASE IF NOT EXISTS cems_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE cems_db;

-- 1. Drop existing Views
DROP VIEW IF EXISTS view_student_registrations;
DROP VIEW IF EXISTS view_event_registration_summary;
DROP VIEW IF EXISTS view_department_enrollment_stats;

-- 2. Drop existing Tables (in reverse foreign key dependency order)
DROP TABLE IF EXISTS event_category;
DROP TABLE IF EXISTS registration;
DROP TABLE IF EXISTS event;
DROP TABLE IF EXISTS venue;
DROP TABLE IF EXISTS student;
DROP TABLE IF EXISTS department;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS admin;

-- 3. Create Tables
CREATE TABLE department (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    semester INT NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venue (
    venue_id INT AUTO_INCREMENT PRIMARY KEY,
    venue_name VARCHAR(100) NOT NULL UNIQUE,
    location VARCHAR(150) NOT NULL,
    capacity INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    event_date DATE NOT NULL,
    event_time VARCHAR(50) NOT NULL,
    venue_id INT NOT NULL,
    max_capacity INT NOT NULL,
    status ENUM('UPCOMING', 'ONGOING', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'UPCOMING'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registration (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED') NOT NULL DEFAULT 'CONFIRMED'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_category (
    event_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (event_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Add Constraints
ALTER TABLE student
    ADD CONSTRAINT fk_student_department
    FOREIGN KEY (department_id) REFERENCES department(department_id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE venue
    ADD CONSTRAINT chk_venue_capacity
    CHECK (capacity > 0);

ALTER TABLE event
    ADD CONSTRAINT fk_event_venue
    FOREIGN KEY (venue_id) REFERENCES venue(venue_id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE event
    ADD CONSTRAINT chk_event_max_capacity
    CHECK (max_capacity > 0);

ALTER TABLE registration
    ADD CONSTRAINT fk_registration_student
    FOREIGN KEY (student_id) REFERENCES student(student_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE registration
    ADD CONSTRAINT fk_registration_event
    FOREIGN KEY (event_id) REFERENCES event(event_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE registration
    ADD CONSTRAINT uq_student_event_registration
    UNIQUE (student_id, event_id);

ALTER TABLE event_category
    ADD CONSTRAINT fk_event_category_event
    FOREIGN KEY (event_id) REFERENCES event(event_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE event_category
    ADD CONSTRAINT fk_event_category_category
    FOREIGN KEY (category_id) REFERENCES category(category_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- 5. Add Indexes
CREATE INDEX idx_student_email ON student(email);
CREATE INDEX idx_student_department ON student(department_id);
CREATE INDEX idx_event_date ON event(event_date);
CREATE INDEX idx_event_venue ON event(venue_id);
CREATE INDEX idx_event_status ON event(status);
CREATE INDEX idx_registration_student ON registration(student_id);
CREATE INDEX idx_registration_event ON registration(event_id);
CREATE INDEX idx_registration_status ON registration(status);

-- 6. Insert Academic Sample Seed Data
INSERT INTO department (department_id, department_name) VALUES
(1, 'Computer Science & Engineering'),
(2, 'Information Technology'),
(3, 'Electronics & Communication Engineering'),
(4, 'Mechanical Engineering'),
(5, 'Civil Engineering');

INSERT INTO venue (venue_id, venue_name, location, capacity) VALUES
(1, 'Main University Auditorium', 'Central Campus Block A', 500),
(2, 'Dr. APJ Abdul Kalam Seminar Hall', 'Science & Innovation Complex Level 2', 150),
(3, 'Alan Turing Computing Lab Complex', 'IT Wing Ground Floor', 80),
(4, 'Ramanujan Lecture Theater', 'Academic Block 3 Level 1', 200);

INSERT INTO category (category_id, category_name) VALUES
(1, 'Technical'),
(2, 'Cultural'),
(3, 'Sports'),
(4, 'Workshops'),
(5, 'Seminars'),
(6, 'Competitions');

INSERT INTO event (event_id, event_name, description, event_date, event_time, venue_id, max_capacity, status) VALUES
(1, 'National Collegiate Hackathon 2026', 'A premier 36-hour coding marathon focused on autonomous software, agentic AI, and distributed systems architecture.', '2026-10-15', '09:00 AM - 09:00 PM', 3, 80, 'UPCOMING'),
(2, 'Annual Cultural Symphony Gala', 'Grand institutional celebration presenting classical music orchestrations, contemporary dance revues, and theatrical drama.', '2026-10-22', '05:30 PM - 10:00 PM', 1, 450, 'UPCOMING'),
(3, 'Autonomous AI & Robotics Workshop', 'Hands-on practical laboratory session on computer vision, sensor fusion, and micro-controller embedded firmware engineering.', '2026-10-28', '10:00 AM - 04:30 PM', 3, 75, 'UPCOMING'),
(4, 'Inter-University Chess Championship', 'FIDE-rated swiss league rapid and blitz chess tournament with student grandmasters from 24 participating institutions.', '2026-09-10', '09:30 AM - 06:00 PM', 2, 120, 'COMPLETED'),
(5, 'Cloud DevOps & Microservices Bootcamp', 'Intensive training on Docker containers, Kubernetes orchestration, CI/CD automated pipelines, and MySQL clustering.', '2026-09-25', '01:00 PM - 05:00 PM', 3, 60, 'ONGOING'),
(6, 'Green Building & Sustainable Tech Seminar', 'Expert symposium on carbon-neutral structural engineering, solar envelope architecture, and recycled concrete aggregates.', '2026-11-05', '11:00 AM - 02:00 PM', 4, 180, 'UPCOMING'),
(7, 'Inter-College Badminton Tournament', 'Annual indoor badminton championship featuring men and women singles, doubles, and mixed doubles fixtures.', '2026-11-12', '08:30 AM - 06:00 PM', 1, 100, 'UPCOMING'),
(8, 'Cyber Threat Intelligence Summit', 'Advanced defense lecture and red-team demonstration on zero-day mitigation and cryptographic protocols.', '2026-11-20', '10:00 AM - 03:00 PM', 2, 140, 'CANCELLED');

INSERT INTO event_category (event_id, category_id) VALUES
(1, 1), (1, 6),
(2, 2),
(3, 1), (3, 4),
(4, 3), (4, 6),
(5, 1), (5, 4),
(6, 5),
(7, 3), (7, 6),
(8, 1), (8, 5);

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

INSERT INTO admin (admin_id, username, password, created_at) VALUES
(1, 'admin', '$2y$10$x35s4D4jo07eTAyPlqv7G.LKP9e/QflocBYifMGIP4kSBjU4wUQqm', '2026-08-01 00:00:00'),
(2, 'head_coordinator', '$2y$10$x35s4D4jo07eTAyPlqv7G.LKP9e/QflocBYifMGIP4kSBjU4wUQqm', '2026-08-01 00:00:00');

-- 7. Create Views
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
