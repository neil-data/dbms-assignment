# DBMS Viva Preparation & Question Bank — CEMS

Exhaustive academic question and answer guide specifically tailored to the CEMS project for university viva examinations.

---

### Q1: What is a DBMS and how does an RDBMS differ?
**Answer:** A Database Management System (DBMS) is software that manages, stores, and retrieves data. A Relational Database Management System (RDBMS) organizes data into two-dimensional tables (relations) consisting of rows (tuples) and columns (attributes), enforcing mathematical relational algebra principles and referential integrity via primary and foreign keys. CEMS uses **MySQL**, an ACID-compliant RDBMS.

---

### Q2: What is the difference between Primary Key, Candidate Key, and Composite Key?
**Answer:**
* **Candidate Key:** A minimal set of attributes that can uniquely identify every tuple in a relation. In CEMS `student`, both `student_id` and `email` are candidate keys.
* **Primary Key:** The candidate key specifically chosen by the database designer as the primary tuple identifier. In CEMS, `student_id` is the primary key.
* **Composite Key:** A primary key composed of two or more attributes. In CEMS `event_category`, the primary key is `(event_id, category_id)`.

---

### Q3: What is a Foreign Key and how does CEMS enforce Referential Integrity?
**Answer:** A Foreign Key is an attribute (or collection of attributes) in one table that references the Primary Key of another table. It establishes a link between entities and ensures referential integrity.
* In CEMS, `student.department_id` references `department.department_id` with `ON DELETE RESTRICT`. If someone tries to delete a department that has active students, MySQL rejects the operation.
* In `registration`, `student_id` references `student.student_id` with `ON DELETE CASCADE`, ensuring that if a student profile is deleted, all their event passes are automatically removed.

---

### Q4: Explain the constraints used in CEMS: UNIQUE, NOT NULL, CHECK.
**Answer:**
* **UNIQUE:** Guarantees all values in a column or set of columns are distinct. In CEMS, `student.email` is UNIQUE, and `UNIQUE(student_id, event_id)` in `registration` prevents a student from registering twice for the same event.
* **NOT NULL:** Prohibits missing or undefined values for vital attributes (e.g., `event_name`, `venue_name`, `email`).
* **CHECK:** Enforces domain-level value restrictions. CEMS enforces `CHECK (capacity > 0)` on `venue` and `CHECK (max_capacity > 0)` on `event` to guarantee seating limits are strictly positive integers.

---

### Q5: What is Database Normalization and why is CEMS in 3NF?
**Answer:** Normalization decomposes complex tables into well-structured, smaller tables to eliminate insertion, update, and deletion anomalies.
* **1NF:** All attributes contain atomic scalar values (no comma-separated lists); each row has a primary key. CEMS avoids storing multiple category IDs inside the event record.
* **2NF:** It is in 1NF and contains no partial dependencies (every non-key attribute depends on the whole primary key). In `event_category`, no descriptive attributes exist except the keys.
* **3NF:** It is in 2NF and contains no transitive dependencies ($X \to Y \to Z$). Departments are separated from Students (`student_id` &rarr; `department_id` &rarr; `department_name`), Venues are separated from Events, and Categories are separated from Events.

---

### Q6: What is a Junction Table (Associative Entity)?
**Answer:** A junction table resolves a Many-to-Many ($M:N$) relationship into two One-to-Many ($1:M$) relationships.
* In CEMS, `event_category` resolves the $M:N$ relationship between `event` and `category`. Its composite primary key is `(event_id, category_id)`.
* Similarly, `registration` resolves the $M:N$ relationship between `student` and `event`.

---

### Q7: Explain the difference between INNER JOIN and LEFT JOIN with examples from CEMS.
**Answer:**
* **INNER JOIN:** Returns only tuples with matching values in both tables. Example: Querying active passes where `registration` matches both `student` and `event`.
* **LEFT JOIN (or LEFT OUTER JOIN):** Returns all tuples from the left table and matched tuples from the right table (filling with `NULL` if no match exists). Example: Querying all venues to find facilities with zero scheduled events:
  ```sql
  SELECT v.venue_name, e.event_name
  FROM venue v
  LEFT JOIN event e ON v.venue_id = e.venue_id;
  ```

---

### Q8: What is the difference between WHERE and HAVING?
**Answer:**
* **WHERE:** Filters individual rows *before* any grouping or aggregation takes place.
* **HAVING:** Filters aggregated groups *after* the `GROUP BY` clause is evaluated.
* In CEMS, finding events with more than 3 confirmed registrations:
  ```sql
  SELECT event_id, COUNT(*) AS confirmed_count
  FROM registration
  WHERE status = 'CONFIRMED'
  GROUP BY event_id
  HAVING confirmed_count > 3;
  ```

---

### Q9: What are ACID properties in Database Transactions?
**Answer:**
* **Atomicity:** All operations succeed (`COMMIT`) or all are rolled back (`ROLLBACK`). All-or-nothing.
* **Consistency:** The database transitions from one valid state to another, satisfying all constraints.
* **Isolation:** Concurrent transactions execute without interfering with one another (achieved via row locking: `FOR UPDATE`).
* **Durability:** Once committed, data persists permanently on disk even during power failures.

---

### Q10: How does CEMS demonstrate a Transaction during Event Registration?
**Answer:** In `backend/registrations/create.php`:
1. `START TRANSACTION;`
2. Row lock acquired on event: `SELECT * FROM event WHERE event_id = ? FOR UPDATE;`
3. Check current confirmed registrations count against `max_capacity`.
4. Check if student already registered (`UNIQUE(student_id, event_id)`).
5. If valid: `INSERT INTO registration ...; COMMIT;`
6. If full or duplicate: `ROLLBACK;` leaving the database untouched.

---

### Q11: What is the difference between DELETE, TRUNCATE, and DROP?
**Answer:**
* **DELETE:** DML command. Deletes specific rows matching a `WHERE` condition. Fires triggers, logs each row removal, and can be rolled back.
* **TRUNCATE:** DDL command. Quickly removes all rows from a table by deallocating data pages. Resets auto-increment counters and cannot be selectively filtered.
* **DROP:** DDL command. Completely removes table structure, indexes, and constraints from the database catalog.

---

### Q12: What is a Database View and what are its advantages?
**Answer:** A view is a virtual table defined by a stored SQL query. It does not store physical data itself (unless materialized); it queries the underlying base tables when accessed.
* **Advantages:** Simplifies complex multi-table joins for frontend queries, provides a security abstraction layer (hiding sensitive password hashes), and encapsulates business query logic.
* **CEMS Views:** `view_student_registrations`, `view_event_registration_summary`.

---

### Q13: What is a Database Index and why did you add indexes in CEMS?
**Answer:** An index is an auxiliary B-Tree data structure that enables the database engine to locate tuples quickly without performing costly sequential full-table scans.
* In CEMS, indexes were added on:
  - `student(email)` for instantaneous login lookups.
  - `event(event_date)` to accelerate chronological calendar ordering and range filtering.
  - `registration(student_id)` and `registration(event_id)` to speed up foreign key traversals and capacity quota aggregations.

---

### Q14: What is SQL Injection and how is it prevented in CEMS?
**Answer:** SQL Injection is an attack technique where malicious SQL commands are injected into input fields to alter execution flow.
* In CEMS, SQL Injection is **100% prevented** because all queries use **PHP PDO prepared statements with parameter binding**. User inputs are transmitted separately from the compiled SQL command structure, ensuring the database engine treats input strictly as literal scalar values, never executable SQL.

---

### Q15: Why is PHP PDO preferred over old `mysql_*` or procedural `mysqli`?
**Answer:**
1. **Security:** PDO provides native prepared statements with disabled client-side emulation (`ATTR_EMULATE_PREPARES => false`).
2. **Object-Oriented:** Consistent object model with robust exception handling (`ATTR_ERRMODE => ERRMODE_EXCEPTION`).
3. **Database Portability:** PDO supports multiple database engines through a unified interface.
