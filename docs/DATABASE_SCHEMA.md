# Database Schema Specification — CEMS

**Database Name:** `cems_db`  
**Character Encoding:** `utf8mb4`  
**Collation:** `utf8mb4_unicode_ci`  
**Storage Engine:** `InnoDB` (ACID Compliant)

---

## 1. Relational Table Specifications

### 1.1 `department`
Stores academic divisions offering collegiate degree programs.

| Field Name | Data Type | Constraint / Key | Nullable | Default | Description |
|---|---|---|---|---|---|
| `department_id` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT` | No | Auto | Unique department identifier |
| `department_name` | `VARCHAR(100)` | `UNIQUE` | No | None | Official institutional department title |

---

### 1.2 `student`
Represents registered students eligible to enroll in campus activities.

| Field Name | Data Type | Constraint / Key | Nullable | Default | Description |
|---|---|---|---|---|---|
| `student_id` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT` | No | Auto | Unique student registration roll number |
| `department_id` | `INT` | `FOREIGN KEY` &rarr; `department.department_id` | No | None | Academic department reference |
| `name` | `VARCHAR(100)` | `NOT NULL` | No | None | Full legal student name |
| `email` | `VARCHAR(120)` | `NOT NULL`, `UNIQUE` | No | None | Institutional email address (login identifier) |
| `phone` | `VARCHAR(20)` | `NOT NULL` | No | None | Contact telephone number |
| `semester` | `INT` | `NOT NULL` | No | None | Current enrolled semester (1 to 8) |
| `password` | `VARCHAR(255)` | `NOT NULL` | No | None | Secure bcrypt password hash (`$2y$10$...`) |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | No | `CURRENT_TIMESTAMP` | System registration timestamp |

---

### 1.3 `venue`
Represents physical campus facilities available for event hosting.

| Field Name | Data Type | Constraint / Key | Nullable | Default | Description |
|---|---|---|---|---|---|
| `venue_id` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT` | No | Auto | Unique facility identifier |
| `venue_name` | `VARCHAR(100)` | `NOT NULL`, `UNIQUE` | No | None | Name of auditorium, lab, or hall |
| `location` | `VARCHAR(150)` | `NOT NULL` | No | None | Physical campus block and floor |
| `capacity` | `INT` | `NOT NULL`, `CHECK(capacity > 0)` | No | None | Maximum physical seat capacity |

---

### 1.4 `event`
Stores symposiums, bootcamps, tournaments, and workshops scheduled on campus.

| Field Name | Data Type | Constraint / Key | Nullable | Default | Description |
|---|---|---|---|---|---|
| `event_id` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT` | No | Auto | Unique event identifier |
| `event_name` | `VARCHAR(150)` | `NOT NULL` | No | None | Official event title |
| `description` | `TEXT` | None | Yes | `NULL` | Event overview, agenda, and requirements |
| `event_date` | `DATE` | `NOT NULL` | No | None | Scheduled calendar date (YYYY-MM-DD) |
| `event_time` | `VARCHAR(50)` | `NOT NULL` | No | None | Operational hours / time slot |
| `venue_id` | `INT` | `FOREIGN KEY` &rarr; `venue.venue_id` | No | None | Assigned campus facility |
| `max_capacity` | `INT` | `NOT NULL`, `CHECK(max_capacity > 0)` | No | None | Maximum attendee registration ceiling |
| `status` | `ENUM(...)` | `NOT NULL` | No | `'UPCOMING'` | `'UPCOMING'`, `'ONGOING'`, `'COMPLETED'`, `'CANCELLED'` |

---

### 1.5 `category`
Master catalog of event classifications.

| Field Name | Data Type | Constraint / Key | Nullable | Default | Description |
|---|---|---|---|---|---|
| `category_id` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT` | No | Auto | Unique category identifier |
| `category_name` | `VARCHAR(100)` | `NOT NULL`, `UNIQUE` | No | None | Genre name (Technical, Sports, Cultural, etc.) |

---

### 1.6 `registration`
Resolves the Student $M:N$ Event relationship; tracks pass reservations and seat quotas.

| Field Name | Data Type | Constraint / Key | Nullable | Default | Description |
|---|---|---|---|---|---|
| `registration_id` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT` | No | Auto | Unique digital pass serial number |
| `student_id` | `INT` | `FOREIGN KEY` &rarr; `student.student_id` | No | None | Registered student reference |
| `event_id` | `INT` | `FOREIGN KEY` &rarr; `event.event_id` | No | None | Target event reference |
| `registration_date` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | No | `CURRENT_TIMESTAMP` | Enrollment confirmation timestamp |
| `status` | `ENUM(...)` | `NOT NULL` | No | `'CONFIRMED'` | `'PENDING'`, `'CONFIRMED'`, `'CANCELLED'`, `'COMPLETED'` |

**Relational Rule:** `UNIQUE(student_id, event_id)` forbids duplicate registrations by the same student for the same event.

---

### 1.7 `event_category`
Associative junction table resolving the Event $M:N$ Category relationship.

| Field Name | Data Type | Constraint / Key | Nullable | Default | Description |
|---|---|---|---|---|---|
| `event_id` | `INT` | `PRIMARY KEY`, `FOREIGN KEY` &rarr; `event.event_id` | No | None | Referenced event |
| `category_id` | `INT` | `PRIMARY KEY`, `FOREIGN KEY` &rarr; `category.category_id` | No | None | Referenced category |

---

### 1.8 `admin`
Stores authorized administrative accounts for institutional governance.

| Field Name | Data Type | Constraint / Key | Nullable | Default | Description |
|---|---|---|---|---|---|
| `admin_id` | `INT` | `PRIMARY KEY`, `AUTO_INCREMENT` | No | Auto | Unique admin identifier |
| `username` | `VARCHAR(50)` | `NOT NULL`, `UNIQUE` | No | None | Administrative sign-in handle |
| `password` | `VARCHAR(255)` | `NOT NULL` | No | None | Secure bcrypt password hash (`$2y$10$...`) |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | No | `CURRENT_TIMESTAMP` | Account creation timestamp |

---

## 2. Integrity Constraints & Referential Rules

1. **Entity Integrity:** Every entity possesses an unambiguous `PRIMARY KEY`.
2. **Domain Integrity:** 
   - `CHECK(capacity > 0)` guarantees that venue capacity cannot be zero or negative.
   - `CHECK(max_capacity > 0)` guarantees that event seat quotas cannot be zero or negative.
   - `ENUM` constraints on `event.status` and `registration.status` restrict values to verified life-cycle states.
3. **Referential Integrity:**
   - `student(department_id)` &rarr; `department(department_id)` with `ON DELETE RESTRICT`. Prevents deletion of active departments.
   - `event(venue_id)` &rarr; `venue(venue_id)` with `ON DELETE RESTRICT`. Prevents deletion of assigned venues.
   - `registration(student_id)` &rarr; `student(student_id)` with `ON DELETE CASCADE`. If a student record is removed, its registrations are safely purged.
   - `registration(event_id)` &rarr; `event(event_id)` with `ON DELETE CASCADE`. If an event is removed, its registrations are safely purged.
   - `event_category` references both `event` and `category` with `ON DELETE CASCADE`.

---

## 3. Physical Indexes

| Index Name | Table | Indexed Columns | Index Type | Purpose / Query Optimization |
|---|---|---|---|---|
| `idx_student_email` | `student` | `email` | B-Tree | High-speed single-row lookup during authentication without full-table scan |
| `idx_student_department` | `student` | `department_id` | B-Tree | Accelerates joins with `department` and departmental grouping |
| `idx_event_date` | `event` | `event_date` | B-Tree | Optimizes chronological queries (`WHERE event_date >= CURRENT_DATE` and `ORDER BY event_date`) |
| `idx_event_venue` | `event` | `venue_id` | B-Tree | Speeds up facility joins and venue usage analytics |
| `idx_event_status` | `event` | `status` | B-Tree | Fast filtering of active public events |
| `idx_registration_student` | `registration` | `student_id` | B-Tree | Fast retrieval of student passes on the student portal |
| `idx_registration_event` | `registration` | `event_id` | B-Tree | Optimizes capacity checking (`COUNT(*) WHERE event_id = ?`) during transactions |
| `idx_registration_status` | `registration` | `status` | B-Tree | Accelerates confirmed pass counts and status distribution reports |
