# Entity-Relationship (ER) Diagram — CEMS

## 1. Relational ER Model Overview

The College Event & Registration Management System (**CEMS**) is engineered around a normalized relational model satisfying **Third Normal Form (3NF)**.

### Entities & Cardinalities

```
+---------------------+              +---------------------+
|     DEPARTMENT      |              |        VENUE        |
+---------------------+              +---------------------+
| PK  department_id   |              | PK  venue_id        |
|     department_name |              |     venue_name      |
+----------+----------+              |     location        |
           | 1                       |     capacity        |
           |                         +----------+----------+
           | M                                  | 1
+----------v----------+                         |
|       STUDENT       |                         |
+---------------------+                         |
| PK  student_id      |                         |
| FK  department_id   |                         |
|     name            |                         |
|     email           |                         |
|     phone           |                         |
|     semester        |                         |
|     password        |                         |
|     created_at      |                         |
+----------+----------+                         |
           | 1                                  |
           |                                    |
           | M                                  | M
+----------v----------+              +----------v----------+
|    REGISTRATION     |              |        EVENT        |
+---------------------+              +---------------------+
| PK  registration_id |              | PK  event_id        |
| FK  student_id      |              |     event_name      |
| FK  event_id        |<-------------|     description     |
|     registration_date              |     event_date      |
|     status          |              |     event_time      |
+---------------------+              | FK  venue_id        |
                                     |     max_capacity    |
                                     |     status          |
                                     +----------+----------+
                                                | M
                                                |
                                                | M (Resolved by Junction)
                                     +----------v----------+
                                     |   EVENT_CATEGORY    |
                                     +---------------------+
                                     | PK,FK event_id      |
                                     | PK,FK category_id   |
                                     +----------+----------+
                                                | M
                                                |
                                                | 1
                                     +----------v----------+
                                     |      CATEGORY       |
                                     +---------------------+
                                     | PK  category_id     |
                                     |     category_name   |
                                     +---------------------+

                                     +---------------------+
                                     |        ADMIN        |
                                     +---------------------+
                                     | PK  admin_id        |
                                     |     username        |
                                     |     password        |
                                     |     created_at      |
                                     +---------------------+
```

---

## 2. Mermaid Relational Diagram

```mermaid
erDiagram
    DEPARTMENT ||--o{ STUDENT : "enrolls (1:M)"
    VENUE ||--o{ EVENT : "hosts (1:M)"
    STUDENT ||--o{ REGISTRATION : "submits (1:M)"
    EVENT ||--o{ REGISTRATION : "receives (1:M)"
    EVENT ||--o{ EVENT_CATEGORY : "classified_under (1:M)"
    CATEGORY ||--o{ EVENT_CATEGORY : "applies_to (1:M)"

    DEPARTMENT {
        int department_id PK
        varchar department_name UK
    }

    STUDENT {
        int student_id PK
        int department_id FK
        varchar name
        varchar email UK
        varchar phone
        int semester
        varchar password
        timestamp created_at
    }

    VENUE {
        int venue_id PK
        varchar venue_name UK
        varchar location
        int capacity
    }

    EVENT {
        int event_id PK
        varchar event_name
        text description
        date event_date
        varchar event_time
        int venue_id FK
        int max_capacity
        enum status
    }

    CATEGORY {
        int category_id PK
        varchar category_name UK
    }

    EVENT_CATEGORY {
        int event_id PK,FK
        int category_id PK,FK
    }

    REGISTRATION {
        int registration_id PK
        int student_id FK
        int event_id FK
        timestamp registration_date
        enum status
    }

    ADMIN {
        int admin_id PK
        varchar username UK
        varchar password
        timestamp created_at
    }
```

---

## 3. Detailed Relationship Semantics

| Relationship | Type | Parent Entity | Child Entity | Foreign Key | Enforcement Rule | Rationale |
|---|---|---|---|---|---|---|
| **DEPARTMENT &rarr; STUDENT** | One-to-Many ($1:M$) | `DEPARTMENT` | `STUDENT` | `department_id` | `ON UPDATE CASCADE ON DELETE RESTRICT` | A department contains many enrolled students. A student must belong to exactly one academic department. Deleting a department with enrolled students is restricted to prevent orphaned student records. |
| **VENUE &rarr; EVENT** | One-to-Many ($1:M$) | `VENUE` | `EVENT` | `venue_id` | `ON UPDATE CASCADE ON DELETE RESTRICT` | A campus facility can host multiple sequential events. An event is scheduled at exactly one physical venue. Venues cannot be removed if events are assigned to them. |
| **STUDENT &rarr; REGISTRATION** | One-to-Many ($1:M$) | `STUDENT` | `REGISTRATION` | `student_id` | `ON UPDATE CASCADE ON DELETE CASCADE` | A student may register for multiple campus programs. Each registration belongs to one verified student. |
| **EVENT &rarr; REGISTRATION** | One-to-Many ($1:M$) | `EVENT` | `REGISTRATION` | `event_id` | `ON UPDATE CASCADE ON DELETE CASCADE` | An event receives multiple student registrations up to its capacity limit. |
| **EVENT &harr; CATEGORY** | Many-to-Many ($M:M$) | `EVENT` / `CATEGORY` | `EVENT_CATEGORY` | `(event_id, category_id)` | Composite PK; `ON UPDATE CASCADE ON DELETE CASCADE` | An event can belong to multiple categories (e.g., both "Technical" and "Competitions"). A category spans multiple events. Resolved cleanly via associative table `EVENT_CATEGORY`. |
| **STUDENT &harr; EVENT** | Many-to-Many ($M:M$) | `STUDENT` / `EVENT` | `REGISTRATION` | `(student_id, event_id)` | `UNIQUE(student_id, event_id)` | A student registers for many events; an event holds many students. Resolved by `REGISTRATION` with an explicit composite unique constraint to forbid duplicate registrations. |
| **ADMIN** | Independent System Entity | — | — | — | Standalone | Governs system management, entity CRUD, and institutional reporting. |
