# Database Normalization Analysis (1NF, 2NF, 3NF) — CEMS

## 1. Overview of Normalization

Normalization is the systematic process of organizing data in a relational database to minimize redundancy and prevent insertion, update, and deletion anomalies while maintaining data integrity.

The **CEMS** relational schema has been rigorously designed to satisfy **Third Normal Form (3NF)**.

---

## 2. Step-by-Step Normalization Breakdown

### 2.1 First Normal Form (1NF)

**Rule:**
1. Every attribute value must be atomic (indivisible single values).
2. There must be no repeating groups or multivalued attributes.
3. Every row must be uniquely identifiable via a Primary Key.

**How CEMS Achieves 1NF:**
* In un-normalized student spreadsheets, fields often contain composite strings like `"Computer Science & Engineering, Semester 6"` or multiple phone numbers separated by commas.
* In CEMS:
  - Each attribute holds exactly one scalar value (`name`, `email`, `phone`, `semester`).
  - An event can belong to multiple categories. Instead of storing a comma-separated string such as `'Technical, Workshops, Competitions'` in the `event` table (which violates 1NF and prevents indexing and joins), each classification tuple is isolated into the junction table `event_category`.
  - Every table possesses an explicit primary key (`student_id`, `event_id`, `venue_id`, etc.).

---

### 2.2 Second Normal Form (2NF)

**Rule:**
1. The relation must be in 1NF.
2. Every non-prime attribute must be **fully functionally dependent** on the entire primary key (no partial functional dependencies on composite keys).

**How CEMS Achieves 2NF:**
* In the composite table `event_category(event_id, category_id)`, the primary key is composite: `(event_id, category_id)`.
  - If we had included `category_name` or `venue_name` inside `event_category`, those attributes would only depend on *part* of the primary key (`category_name` depends on `category_id` alone). That would be a partial dependency.
  - To achieve 2NF, `category_name` is isolated into `category(category_id, category_name)`. In `event_category`, only the keys exist.
* In `registration(registration_id, student_id, event_id, registration_date, status)`:
  - `registration_id` serves as a single-column surrogate primary key, and the natural candidate key is `(student_id, event_id)`.
  - All non-key attributes (`registration_date`, `status`) depend on the complete registration event, not on student or event in isolation.

---

### 2.3 Third Normal Form (3NF)

**Rule:**
1. The relation must be in 2NF.
2. There must be **no transitive functional dependencies** (no non-prime attribute determines another non-prime attribute: $X \to Y \to Z$).

**How CEMS Achieves 3NF:**
Let us examine the specific architectural separations in CEMS:

#### A. Separation of `department` from `student`
* **Violating Design:** If `student` table were:
  `student(student_id, name, email, department_name, department_head, department_office)`
* **Transitive Dependency:** 
  $$\text{student\_id} \to \text{department\_name} \to \text{department\_office}$$
* **Anomalies Caused:**
  - *Redundancy:* Repeating `"Computer Science & Engineering"` and its office for thousands of students.
  - *Update Anomaly:* Changing an office location requires updating thousands of student records.
  - *Deletion Anomaly:* If all CSE students graduate, the fact that the CSE department exists is accidentally deleted.
* **CEMS 3NF Resolution:** Decomposed into:
  - `department(department_id, department_name)`
  - `student(student_id, department_id, name, email, phone, semester, password)`
  Here, non-key attributes depend *only* directly on `student_id`.

#### B. Separation of `venue` from `event`
* **Violating Design:** If `event` table were:
  `event(event_id, event_name, date, venue_name, venue_location, venue_capacity)`
* **Transitive Dependency:**
  $$\text{event\_id} \to \text{venue\_name} \to \text{venue\_location}, \text{venue\_capacity}$$
* **Anomalies Caused:**
  - Repeating the capacity and location of `"Main Auditorium"` for every event hosted there.
  - Updating venue capacity could produce inconsistencies if some events are missed.
* **CEMS 3NF Resolution:** Decomposed into:
  - `venue(venue_id, venue_name, location, capacity)`
  - `event(event_id, event_name, description, event_date, event_time, venue_id, max_capacity, status)`

#### C. Separation of `category` from `event`
* Events and Categories have an inherent Many-to-Many relationship.
* One event can span multiple categories; one category spans multiple events.
* Directly embedding category columns in `event` (e.g., `category_1`, `category_2`) or event columns in `category` violates 1NF and 3NF.
* **CEMS 3NF Resolution:** Resolved via `event_category(event_id, category_id)` junction table with foreign keys referencing both independent entities.

#### D. Separation of `registration` from `student` and `event`
* Students register for many events; events accept many students.
* Storing event IDs inside the `student` table would require multi-valued fields or repeating columns.
* Storing student IDs inside the `event` table would require array structures.
* **CEMS 3NF Resolution:** Resolved via the associative entity `registration(registration_id, student_id, event_id, registration_date, status)` where:
  - Each tuple links one student to one event.
  - Extra transaction metadata (`status`, `registration_date`) depends directly on the relationship itself.
  - `UNIQUE(student_id, event_id)` eliminates duplicate registrations.

---

## 3. Summary of Functional Dependencies (FDs)

| Relation | Primary Key | Functional Dependencies | Normal Form |
|---|---|---|---|
| `department` | `department_id` | `department_id` &rarr; `department_name` | **3NF** |
| `student` | `student_id` | `student_id` &rarr; `department_id, name, email, phone, semester, password, created_at` | **3NF** |
| `venue` | `venue_id` | `venue_id` &rarr; `venue_name, location, capacity` | **3NF** |
| `event` | `event_id` | `event_id` &rarr; `event_name, description, event_date, event_time, venue_id, max_capacity, status` | **3NF** |
| `category` | `category_id` | `category_id` &rarr; `category_name` | **3NF** |
| `registration` | `registration_id` | `registration_id` &rarr; `student_id, event_id, registration_date, status`<br>`(student_id, event_id)` &rarr; `registration_id, registration_date, status` | **3NF** |
| `event_category` | `(event_id, category_id)` | `(event_id, category_id)` (All attributes prime) | **3NF** |
| `admin` | `admin_id` | `admin_id` &rarr; `username, password, created_at` | **3NF** |

**Conclusion:** Every determinant in the schema is a candidate key. Therefore, the schema is in 3NF and also satisfies Boyce-Codd Normal Form (**BCNF**).
