# DBMS Demonstration Module Guide — CEMS

**Target Audience:** Academic Examiner, Faculty Evaluator, Project Viva Panel  
**Interface Route:** `/admin/database-demo.php` (also linked inside Admin Console)

---

## 1. Purpose of the Demonstration Module

The **DBMS Demonstration Module** was designed specifically to elevate CEMS from a traditional website submission into a rigorous **Database Management Systems portfolio piece**. Rather than asking examiners to inspect code files, this interactive screen allows examiners to trigger, benchmark, and observe real MySQL relational database operations live in real time.

---

## 2. Module Walkthrough & Viva Talking Points

### Module A: Database Overview
* **What it demonstrates:** Live metadata retrieval from `cems_db`.
* **Examiner Focus:** Shows that frontend is not querying static JSON or fake mock variables, but reading genuine MySQL InnoDB table row counts and table structures.
* **Under the Hood:** Executes `SELECT COUNT(*) FROM {table}` and `DESCRIBE {table}` across all 8 tables.

---

### Module B: Relational Foreign Key JOINs
* **What it demonstrates:** Traversal across 4 normalized relational entities.
* **Examiner Focus:** Visually demonstrates referential integrity across `student` &rarr; `registration` &rarr; `event` &rarr; `venue`.
* **Under the Hood:**
  ```sql
  SELECT r.registration_id, s.name, s.email, d.department_name, e.event_name, v.venue_name
  FROM registration r
  INNER JOIN student s ON r.student_id = s.student_id
  INNER JOIN department d ON s.department_id = d.department_id
  INNER JOIN event e ON r.event_id = e.event_id
  INNER JOIN venue v ON e.venue_id = v.venue_id;
  ```

---

### Module C: Aggregate Functions
* **What it demonstrates:** Single-query multi-column aggregation.
* **Examiner Focus:** Explains `COUNT()`, `SUM()`, `AVG()`, `MIN()`, `MAX()`.
* **Key viva answer:** Aggregates compute over sets of values rather than individual rows, producing scalar summaries without transferring entire datasets to the application memory.

---

### Module D: GROUP BY with Arithmetic Projections
* **What it demonstrates:** Relational aggregation grouped by entity primary keys.
* **Examiner Focus:** Explains how `COUNT(CASE WHEN r.status = 'CONFIRMED' THEN 1 END)` is combined with mathematical expressions (`max_capacity - enrolled`) to calculate remaining seats dynamically without storing redundant counter fields.

---

### Module E: Nested Subqueries
* **What it demonstrates:** Scalar subqueries and Correlated subqueries.
* **Examiner Focus:**
  1. *Scalar Subquery:* Evaluates `SELECT AVG(max_capacity) FROM event` once in the inner query, filtering events whose capacity exceeds that campus average.
  2. *Correlated Subquery:* Inner query references the outer query's `s.student_id` to evaluate multi-event registered students.

---

### Module F: Relational Constraints & Integrity Proof
* **What it demonstrates:** Engine-level constraint enforcement.
* **Examiner Focus:** Proves that the database itself (not just JavaScript) rejects corrupt or invalid tuples.
  1. **Duplicate Registration:** Attempts duplicate insert for student 1 and event 1. MySQL throws error **1062** (Duplicate entry for key `uq_student_event_registration`).
  2. **Foreign Key Violation:** Attempts inserting a student with non-existent `department_id = 99999`. MySQL throws error **1452** (Cannot add or update child row).
  3. **Check Constraint:** Attempts creating a venue with capacity $-50$. MySQL throws error **3819** (`chk_venue_capacity` is violated).
  4. **NOT NULL:** Attempts inserting NULL into `department_name`. MySQL throws error **1048** (Column cannot be null).

---

### Module G: ACID Transactions (COMMIT & ROLLBACK)
* **What it demonstrates:** Atomic isolation boundaries under the InnoDB storage engine.
* **Examiner Focus:** 
  - *COMMIT Demonstration:* Traces `START TRANSACTION` &rarr; row lock (`FOR UPDATE`) &rarr; capacity check &rarr; insert &rarr; `COMMIT`. Confirms tuple was written to disk.
  - *ROLLBACK Demonstration:* Traces `START TRANSACTION` &rarr; temporary insert &rarr; simulated failure &rarr; `ROLLBACK`. Proves via query that the temporary record was cleanly reverted and no data corruption occurred.

---

### Module H: Database Views
* **What it demonstrates:** Relational virtualization and query encapsulation.
* **Examiner Focus:** Shows `view_event_registration_summary` and `view_student_registrations`. Explains that views provide security (restricting sensitive password columns), simplify client queries, and compile execution plans inside the database engine.
