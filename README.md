# CEMS — College Event & Registration Management System

> **Academic DBMS Full-Stack Assignment**  
> Engineered with Vanilla HTML5, CSS3, JavaScript, GSAP animations, PHP 8+ PDO backend, and MySQL 8+ relational database.

---

## 1. Project Overview & Problem Statement

Colleges and universities host dozens of technical symposiums, hackathons, guest lectures, cultural galas, and sports championships throughout the academic year. Traditional event management through spreadsheets or disjointed portals introduces critical challenges:
* **Capacity Overbooking:** Venues exceed fire and physical seating limits.
* **Duplicate Registrations:** Students repeatedly enroll in the same program, wasting allocated resources.
* **Physical Asset Double-Booking:** Multiple departments schedule distinct events in the same auditorium simultaneously.
* **Lack of Relational Auditability:** Un-normalized records cause insertion, update, and deletion anomalies.

**CEMS (College Event & Registration Management System)** resolves these institutional problems through a fully normalized **3NF relational database** coupled with a high-performance **PHP PDO backend** ensuring strict **ACID transactional consistency**, and a dark cinematic frontend with GSAP motion design.

---

## 2. Technology Stack

* **Frontend:**
  - Semantic HTML5, Modular CSS3 (Custom Properties & Design Tokens)
  - Vanilla JavaScript (ES6+ Modules, Async/Await Fetch API)
  - GSAP (GreenSock Animation Platform) 3.12+ & ScrollTrigger
  - Google Material Symbols & Modern Cinematic Typography
* **Backend:**
  - PHP 8.1+ / 8.3+
  - PHP Data Objects (PDO) with strict Prepared Statements
  - Session-based Role-Based Access Control (Student vs Admin)
  - Bcrypt Password Hashing (`password_hash` & `password_verify`)
* **Database:**
  - MySQL 8.0+ / MariaDB 10.4+ (InnoDB Engine)
  - Fully Normalized 3NF Relational Model
  - B-Tree Composite & Scalar Indexes
  - Relational Foreign Key Integrity (`ON UPDATE CASCADE`, `ON DELETE RESTRICT/CASCADE`)
  - Domain Constraints (`CHECK (capacity > 0)`)
* **Local Development Environment:**
  - XAMPP / Apache / MySQL / phpMyAdmin

---

## 3. Relational Database Architecture

### Entities & Cardinalities
```
DEPARTMENT (1) ──── (M) STUDENT (1) ──── (M) REGISTRATION (M) ──── (1) EVENT (M) ──── (1) VENUE
                                                                      │ (M)
                                                                      │
                                                             EVENT_CATEGORY (M:M Junction)
                                                                      │
                                                                 CATEGORY (1)
```

### 3NF Schema Summary
1. `department`: `(department_id PK, department_name UNIQUE)`
2. `student`: `(student_id PK, department_id FK, name, email UNIQUE, phone, semester, password, created_at)`
3. `venue`: `(venue_id PK, venue_name UNIQUE, location, capacity CHECK(capacity > 0))`
4. `event`: `(event_id PK, event_name, description, event_date, event_time, venue_id FK, max_capacity CHECK(max_capacity > 0), status)`
5. `category`: `(category_id PK, category_name UNIQUE)`
6. `registration`: `(registration_id PK, student_id FK, event_id FK, registration_date, status, UNIQUE(student_id, event_id))`
7. `event_category`: `(event_id PK,FK, category_id PK,FK)`
8. `admin`: `(admin_id PK, username UNIQUE, password, created_at)`

---

## 4. Project Directory Structure

```
cems/
│
├── index.html                  # Cinematic homepage with featured events & categories
├── events.html                 # Public campus events catalog with live search & filters
├── event-details.html          # Detailed event specification & atomic registration pass
├── login.html                  # Dual-portal authentication (Student & Admin)
├── register.html               # New student account enrollment form
├── student-dashboard.html      # Student portal: enrolled passes & digital QR access
├── admin.html                  # Administrative console: full relational CRUD & reports
│
├── admin/
│   └── database-demo.php       # Standalone interactive DBMS demonstration console for viva
│
├── css/
│   ├── style.css               # Core cinematic dark design tokens and component styles
│   └── responsive.css          # Viewport breakpoints for mobile, tablet, and widescreen
│
├── js/
│   ├── api.js                  # Centralized fetch wrapper resolving relative base paths
│   ├── main.js                 # Header session UI, status beacon, pass modal, featured cards
│   ├── auth.js                 # Student/Admin login and student registration controllers
│   ├── events.js               # Events catalog filters, details view, and booking handler
│   ├── dashboard.js            # Student passes manager and Admin CRUD console
│   └── animations.js           # GSAP light trails and ScrollTrigger animation engine
│
├── backend/
│   ├── config/
│   │   └── database.php        # Centralized PDO connection provider (XAMPP / Env)
│   ├── shared/
│   │   ├── response.php        # Standardized JSON response helper
│   │   ├── auth.php            # Session guards & role-based access controllers
│   │   └── validation.php      # Server-side input sanitizers & regex validators
│   ├── auth/
│   │   ├── student_register.php# Student enrollment endpoint
│   │   ├── student_login.php   # Student login endpoint
│   │   ├── student_logout.php  # Student logout endpoint
│   │   ├── admin_login.php     # Admin login endpoint
│   │   ├── admin_logout.php    # Admin logout endpoint
│   │   └── me.php              # Active session & database health check
│   ├── events/                 # CRUD: list, get, create, update, delete
│   ├── students/               # CRUD: list, get, create, update, delete
│   ├── departments/            # CRUD: list, create, update, delete
│   ├── venues/                 # CRUD: list, create, update, delete
│   ├── categories/             # CRUD: list, create, update, delete
│   ├── registrations/          # ACID Transaction: create, list, cancel, delete
│   ├── reports/                # Analytical reports: dashboard, event, dept, cat, status
│   └── database-demo/          # Live viva queries, constraint tests, and transaction demo
│
├── database/
│   ├── 01_create_database.sql  # Database creation script (cems_db)
│   ├── 02_create_tables.sql    # 3NF table DDL definitions
│   ├── 03_constraints.sql      # FKs, CHECK rules, UNIQUE rules
│   ├── 04_indexes.sql          # Performance optimization B-Tree indexes
│   ├── 05_sample_data.sql      # Academic demo seed data (Bcrypt hashed passwords)
│   ├── 06_queries.sql          # CRUD, multi-joins, aggregates, group by, subqueries
│   ├── 07_views.sql            # Compiled views (event summary, student passes)
│   ├── 08_transactions.sql     # Documented ACID transaction examples (COMMIT/ROLLBACK)
│   ├── 09_reports.sql          # 8 institutional analytical queries
│   └── 10_reset_database.sql   # Complete one-click teardown and reseed script
│
├── docs/
│   ├── ER_DIAGRAM.md           # ER diagram with Mermaid & cardinality analysis
│   ├── DATABASE_SCHEMA.md      # Field-level dictionary and data types
│   ├── NORMALIZATION.md        # Mathematical 1NF, 2NF, 3NF proof and FD tables
│   ├── SQL_QUERIES.md          # Comprehensive catalog of all SQL statements
│   ├── DBMS_DEMONSTRATION.md   # Teacher presentation guide for `/admin/database-demo.php`
│   ├── API_DOCUMENTATION.md    # REST API endpoints, payloads, and response schemas
│   ├── TESTING.md              # 20-point verification checklist and test report
│   ├── TEACHER_DEMO.md         # 33-step recommended evaluation walkthrough
│   └── VIVA_QUESTIONS.md       # Comprehensive DBMS viva questions and answers
│
└── README.md                   # Complete system documentation
```

---

## 5. Installation & Setup (XAMPP)

### Step 1: Install & Launch XAMPP
1. Download and install [XAMPP for Windows](https://www.apachefriends.org/).
2. Open the **XAMPP Control Panel**.
3. Start **Apache** and **MySQL** services. Both indicators should turn green.

### Step 2: Place Project in `htdocs`
Copy the project folder into your XAMPP web root directory:
```
C:\xampp\htdocs\cems\
```

### Step 3: Import Database via phpMyAdmin
1. Open your browser and navigate to: `http://localhost/phpmyadmin/`.
2. Click on the **Import** tab at the top.
3. Choose the master setup file:
   ```
   C:\xampp\htdocs\cems\database\10_reset_database.sql
   ```
4. Click **Import** (or **Go**).
5. The `cems_db` database will be created with all 8 tables, constraints, indexes, views, and academic sample seed data!

*(Alternatively, you may execute `01_create_database.sql` through `07_views.sql` sequentially).*

### Step 4: Configure Database Credentials (If Needed)
Open `backend/config/database.php`. Standard XAMPP settings work immediately out-of-the-box:
* Host: `localhost`
* Port: `3306`
* Database: `cems_db`
* User: `root`
* Password: `""` (empty)

### Step 5: Launch the Application
Open your web browser and navigate to:
```
http://localhost/cems/
```
The status beacon at the bottom of the page will immediately display:  
`Database: Connected (MySQL)`.

---

## 6. Default Demonstration Credentials

| Role | Username / Email | Password | Access Scope |
|---|---|---|---|
| **Student** | `aarav.sharma@campus.edu` | `Student@123` | Student Portal, browse events, register passes, print QR passes |
| **Student** | `diya.patel@campus.edu` | `Student@123` | Student Portal |
| **Admin** | `admin` (or `admin@campus.edu`) | `Admin@123` | Full relational CRUD, analytical reports, DBMS demo console |
| **Admin** | `head_coordinator` | `Admin@123` | Full administrative console access |

*(Any new student account created via `register.html` is instantly inserted into MySQL).*

---

## 7. Faculty & Examiner Demonstration Flow

To present this project for examination, open the **DBMS Demonstration Console**:
```
http://localhost/cems/admin/database-demo.php
```
*(Or sign in as Admin and click "DBMS Demonstration" in the navigation bar).*

Follow the 33-step sequential guide in [`docs/TEACHER_DEMO.md`](file:///c:/Users/Neil/Downloads/neural-%E2%80%94-world-class-digital-products/docs/TEACHER_DEMO.md) to demonstrate:
1. Live table row counts retrieved from MySQL.
2. 4-Table relational `INNER JOIN` (Student &rarr; Department &rarr; Registration &rarr; Event &rarr; Venue).
3. `GROUP BY` and capacity arithmetic (`max_capacity - enrolled = available_seats`).
4. SQL Aggregates (`COUNT`, `SUM`, `AVG`, `MIN`, `MAX`).
5. Nested subqueries.
6. Engine-level `UNIQUE(student_id, event_id)` duplicate rejection.
7. Atomic ACID Transaction execution (`START TRANSACTION` &rarr; `COMMIT` &rarr; `ROLLBACK`).
8. Compiled Database Views (`view_event_registration_summary`).

---

## 8. Academic Viva Preparation

Review [`docs/VIVA_QUESTIONS.md`](file:///c:/Users/Neil/Downloads/neural-%E2%80%94-world-class-digital-products/docs/VIVA_QUESTIONS.md) for concise, technically precise answers covering:
* RDBMS vs DBMS
* Primary, Candidate, and Foreign Keys
* Referential Integrity and Cascading Rules
* 1NF, 2NF, 3NF Normalization proofs
* ACID Transaction isolation levels and Row Locking (`FOR UPDATE`)
* B-Tree Indexing rationale
* SQL Injection prevention via PHP PDO prepared statements

---

## 9. License & Academic Attribution
Developed as an academic DBMS assignment adhering to institutional engineering standards.
