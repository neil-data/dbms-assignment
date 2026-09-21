# SQL Queries Catalog — CEMS

Comprehensive reference of all SQL statements implemented in the CEMS project, classified by DBMS operation.

---

## 1. Data Definition Language (DDL)

### Table Creation with Constraints
```sql
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_department FOREIGN KEY (department_id) 
        REFERENCES department(department_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venue (
    venue_id INT AUTO_INCREMENT PRIMARY KEY,
    venue_name VARCHAR(100) NOT NULL UNIQUE,
    location VARCHAR(150) NOT NULL,
    capacity INT NOT NULL,
    CONSTRAINT chk_venue_capacity CHECK (capacity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    event_date DATE NOT NULL,
    event_time VARCHAR(50) NOT NULL,
    venue_id INT NOT NULL,
    max_capacity INT NOT NULL,
    status ENUM('UPCOMING', 'ONGOING', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'UPCOMING',
    CONSTRAINT fk_event_venue FOREIGN KEY (venue_id) 
        REFERENCES venue(venue_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_event_max_capacity CHECK (max_capacity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registration (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED') NOT NULL DEFAULT 'CONFIRMED',
    CONSTRAINT fk_registration_student FOREIGN KEY (student_id) 
        REFERENCES student(student_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_registration_event FOREIGN KEY (event_id) 
        REFERENCES event(event_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT uq_student_event_registration UNIQUE (student_id, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 2. Relational JOINs

### 4-Table INNER JOIN (Traversing Referential Hierarchy)
```sql
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
```

### LEFT OUTER JOIN (Including Venues Without Events)
```sql
SELECT 
    v.venue_id,
    v.venue_name,
    v.capacity,
    e.event_id,
    COALESCE(e.event_name, '(No Event Scheduled)') AS event_name
FROM venue v
LEFT JOIN event e ON v.venue_id = e.venue_id
ORDER BY v.venue_name ASC;
```

### Many-to-Many Relational Resolution (`EVENT` &harr; `CATEGORY`)
```sql
SELECT 
    e.event_id,
    e.event_name,
    GROUP_CONCAT(c.category_name ORDER BY c.category_name SEPARATOR ', ') AS categories
FROM event e
INNER JOIN event_category ec ON e.event_id = ec.event_id
INNER JOIN category c ON ec.category_id = c.category_id
GROUP BY e.event_id, e.event_name;
```

---

## 3. Aggregate & GROUP BY Queries

### Statistical Aggregations (COUNT, SUM, AVG, MIN, MAX)
```sql
SELECT 
    (SELECT COUNT(*) FROM student) AS total_students,
    (SELECT COUNT(*) FROM event) AS total_events,
    (SELECT COUNT(*) FROM registration WHERE status = 'CONFIRMED') AS confirmed_registrations,
    (SELECT ROUND(AVG(max_capacity), 1) FROM event) AS average_event_capacity,
    (SELECT MAX(capacity) FROM venue) AS maximum_venue_capacity,
    (SELECT MIN(capacity) FROM venue) AS minimum_venue_capacity,
    (SELECT SUM(capacity) FROM venue) AS total_campus_seating_capacity;
```

### GROUP BY with Arithmetic Seat Projection
```sql
SELECT 
    e.event_id,
    e.event_name,
    e.max_capacity,
    COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END) AS enrolled_count,
    (e.max_capacity - COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END)) AS available_seats
FROM event e
LEFT JOIN registration r ON e.event_id = r.event_id
GROUP BY e.event_id, e.event_name, e.max_capacity
ORDER BY enrolled_count DESC;
```

---

## 4. Subqueries

### Scalar Subquery: Events Above Average Capacity
```sql
SELECT event_id, event_name, max_capacity, event_date
FROM event
WHERE max_capacity > (
    SELECT AVG(max_capacity) FROM event
)
ORDER BY max_capacity DESC;
```

### Correlated Subquery: Active Multi-Event Enrollees
```sql
SELECT 
    s.student_id,
    s.name,
    s.email,
    (
        SELECT COUNT(*) 
        FROM registration r 
        WHERE r.student_id = s.student_id AND r.status = 'CONFIRMED'
    ) AS active_passes
FROM student s
WHERE (
    SELECT COUNT(*) 
    FROM registration r 
    WHERE r.student_id = s.student_id AND r.status = 'CONFIRMED'
) > 1;
```

---

## 5. Database Views

### View 1: Student Pass Traversal
```sql
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
```

### View 2: Event Registration Summary
```sql
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
```

---

## 6. ACID Transactions (Row-Locking & Rollback)

```sql
START TRANSACTION;

-- Lock the target event tuple
SELECT event_id, event_name, max_capacity, status
FROM event
WHERE event_id = 1 AND status = 'UPCOMING'
FOR UPDATE;

-- Verify capacity
SELECT COUNT(*) FROM registration WHERE event_id = 1 AND status = 'CONFIRMED';

-- Verify student has not already registered
SELECT registration_id FROM registration WHERE student_id = 5 AND event_id = 1;

-- Perform insert
INSERT INTO registration (student_id, event_id, status) VALUES (5, 1, 'CONFIRMED');

-- Commit permanently
COMMIT;

-- In case of failure or capacity breach:
-- ROLLBACK;
```
