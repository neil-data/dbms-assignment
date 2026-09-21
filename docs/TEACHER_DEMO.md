# Teacher Demonstration Flow (33-Step Evaluation Guide) — CEMS

**Purpose:** Recommended sequential demonstration script for presenting CEMS to examiners and professors for maximum marks during DBMS viva and project defense.

---

## Part 1: Conceptual & Relational Design (Steps 1 – 4)

1. **Explain Problem Statement:**
   - Present the institutional problem: Colleges struggle to coordinate symposiums across academic departments, prevent double-bookings of physical campus venues, enforce capacity quotas, and prevent duplicate student registrations.
2. **Show ER Diagram:**
   - Open [`docs/ER_DIAGRAM.md`](file:///c:/Users/Neil/Downloads/neural-%E2%80%94-world-class-digital-products/docs/ER_DIAGRAM.md) or display the Mermaid diagram.
3. **Explain Entities:**
   - Walk through the 8 entities: `DEPARTMENT`, `STUDENT`, `VENUE`, `EVENT`, `CATEGORY`, `REGISTRATION`, `EVENT_CATEGORY`, `ADMIN`.
4. **Explain Relationships & Normalization:**
   - Explain $1:M$ between Department and Student.
   - Explain $1:M$ between Venue and Event.
   - Explain how `REGISTRATION` resolves the Student $M:N$ Event relationship.
   - Explain how `EVENT_CATEGORY` resolves the Event $M:N$ Category relationship.
   - Clarify 3NF compliance: no repeating groups (1NF), no partial dependencies (2NF), no transitive dependencies (3NF).

---

## Part 2: Database Schema & MySQL Verification (Steps 5 – 10)

5. **Open phpMyAdmin or MySQL CLI:**
   - Navigate to `http://localhost/phpmyadmin/` or open MySQL terminal.
6. **Show `cems_db`:**
   - Click on `cems_db` in the left database tree.
7. **Show All Tables:**
   - Point out the 8 clean, normalized relational tables.
8. **Open Important Tables:**
   - Open `event`, `student`, and `registration`. Show the controlled academic sample dataset.
9. **Show Primary Keys and Foreign Keys:**
   - Click **Structure** on `registration`. Show composite candidate key and foreign key links to `student.student_id` and `event.event_id`.
10. **Show Integrity Constraints:**
    - Highlight `CHECK (capacity > 0)` on `venue` and `CHECK (max_capacity > 0)` on `event`.
    - Highlight `UNIQUE(student_id, event_id)` on `registration`.

---

## Part 3: Live SQL Benchmarking & Queries (Steps 11 – 26)

*Tip: You can execute these directly in phpMyAdmin SQL tab OR trigger them instantly using the interactive `/admin/database-demo.php` console!*

11. **Run INSERT query:**
    - Insert a new venue or category.
12. **Show the new record:**
    - Verify the newly inserted row appears in the table browse tab.
13. **Run SELECT query:**
    - Retrieve active events with `SELECT * FROM event WHERE status = 'UPCOMING';`.
14. **Demonstrate UPDATE:**
    - Increase venue capacity or modify an event schedule.
15. **Demonstrate DELETE:**
    - Delete the test record and show row count decrease.
16. **Demonstrate Relational JOIN:**
    - Run the 4-table `INNER JOIN` linking Student &rarr; Department &rarr; Registration &rarr; Event &rarr; Venue.
17. **Demonstrate GROUP BY:**
    - Run registrations grouped by event with available seat calculations.
18. **Demonstrate Aggregate Functions:**
    - Run `COUNT()`, `SUM()`, `AVG()`, `MIN()`, `MAX()` across events and venues.
19. **Demonstrate Subquery:**
    - Run `WHERE max_capacity > (SELECT AVG(max_capacity) FROM event)`.
20. **Demonstrate Many-to-Many Relationship:**
    - Run query linking `event` with `category` through `event_category` with `GROUP_CONCAT()`.
21. **Demonstrate Duplicate Registration Prevention:**
    - Attempt to insert duplicate registration for student 1 and event 1. Show MySQL throwing Error 1062.
22. **Demonstrate Transaction:**
    - Open `admin/database-demo.php` and click **Execute COMMIT Scenario**.
23. **Demonstrate COMMIT:**
    - Show `START TRANSACTION`, row-lock, seat check, insert, and `COMMIT`.
24. **Demonstrate ROLLBACK:**
    - Click **Execute ROLLBACK Scenario**. Show how simulated failure triggers `ROLLBACK` and leaves the table unmodified.
25. **Show Database Views:**
    - Run `SELECT * FROM view_event_registration_summary;` and `SELECT * FROM view_student_registrations;`.
26. **Show Indexes:**
    - Show indexes on `student.email`, `event.event_date`, `registration.student_id`. Explain query optimization.

---

## Part 4: Working Full-Stack Web Application (Steps 27 – 33)

27. **Show the Working Website:**
    - Open `index.html` in browser. Point out the dark cinematic design, GSAP light trails, and **Database: Connected (MySQL)** status indicator.
28. **Login as Student:**
    - Click **Sign In**. Use `aarav.sharma@campus.edu` / `Student@123`.
29. **Register for an Event:**
    - Navigate to `events.html`. Select an upcoming event, click **View Details**, and click **Register for Event**.
30. **Show the New Registration in MySQL:**
    - Switch to phpMyAdmin, refresh `registration` table, and point out the newly inserted row!
31. **Login as Admin:**
    - Sign out, switch to **Admin Console** tab on `login.html`. Log in as `admin` / `Admin@123`.
32. **Show the Registration in Admin Panel:**
    - Navigate to the **Registrations** tab in `admin.html`. Show the student pass record, ticket token, and digital pass.
33. **Show Analytical Reports:**
    - Click the **Reports** tab. Show real-time students per department, event capacity utilization, and attendance percentages.
