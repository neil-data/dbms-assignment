# System Test Plan & Verification Report — CEMS

Comprehensive verification matrix documenting functional, relational, transactional, and security testing.

---

## 1. Automated & Manual Test Checklist

| ID | Test Scenario | Description | Expected Outcome | Result |
|---|---|---|---|:---:|
| **TC-01** | Student Registration | Register new student with valid attributes | Stored with bcrypt password in `student` table; session established | **PASS** |
| **TC-02** | Duplicate Email Rejection | Attempt registration with pre-existing email | Rejected with HTTP 409 Conflict; `UNIQUE` constraint preserved | **PASS** |
| **TC-03** | Student Authentication | Log in with valid email and password (`Student@123`) | Authenticated via `password_verify()`; session cookie issued | **PASS** |
| **TC-04** | Invalid Student Login | Provide wrong password or unknown email | Rejected with HTTP 401 Unauthorized; generic error returned | **PASS** |
| **TC-05** | Admin Authentication | Log in with credentials `admin` / `Admin@123` | Authenticated against `admin` entity; admin session established | **PASS** |
| **TC-06** | Admin Access Protection | Unauthenticated user calls `/backend/events/create.php` | Rejected with HTTP 403 Forbidden | **PASS** |
| **TC-07** | Student Cannot Access Admin | Student calls `/backend/students/delete.php` | Rejected with HTTP 403 Forbidden | **PASS** |
| **TC-08** | Event Creation | Admin creates event with venue and capacity | Inserted into `event` and `event_category` in MySQL | **PASS** |
| **TC-09** | Event Over-Capacity Check | Attempt creating event with capacity > venue capacity | Rejected by business validation rule with HTTP 422 | **PASS** |
| **TC-10** | Event Registration (Transaction) | Student registers for active upcoming event | `START TRANSACTION` &rarr; row lock &rarr; seat check &rarr; insert &rarr; `COMMIT` | **PASS** |
| **TC-11** | Duplicate Registration | Same student registers twice for the same event | Rejected by `UNIQUE(student_id, event_id)` with HTTP 409 | **PASS** |
| **TC-12** | Capacity Quota Enforcement | Register for an event whose seats are completely full | Transaction aborts; `ROLLBACK` issued; HTTP 409 returned | **PASS** |
| **TC-13** | Cancel Registration | Student cancels their own enrolled pass | Status updated to `'CANCELLED'`; seat returned to available quota | **PASS** |
| **TC-14** | Foreign Key Deletion Restriction | Attempt deleting department with enrolled students | Rejected by `ON DELETE RESTRICT` with HTTP 409 | **PASS** |
| **TC-15** | Venue Check Constraint | Attempt creating venue with negative capacity | Rejected by MySQL `CHECK (capacity > 0)` | **PASS** |
| **TC-16** | Catalog Search & Filter | Filter events by category "Technical" and search term | SQL dynamically builds prepared statement with parameter binding | **PASS** |
| **TC-17** | Multi-Table JOIN Query | Run 4-table join on student, reg, event, venue | Returns denormalized relational traversal accurately | **PASS** |
| **TC-18** | Nested Subquery Execution | Find events with capacity > average event capacity | Evaluates scalar subquery; returns filtered set | **PASS** |
| **TC-19** | SQL Injection Attempt | Enter `' OR '1'='1` in login or search fields | Safely treated as literal string due to PDO prepared statements | **PASS** |
| **TC-20** | Responsive Frontend Design | Test on desktop, tablet, and mobile viewports | GSAP light trails and flex/grid layouts scale gracefully | **PASS** |

---

## 2. Security & SQL Injection Protection

### Prepared Statements Implementation
All database interactions strictly utilize PDO prepared statements with bound parameters. Direct string concatenation into SQL statements is prohibited across the entire backend.

```php
// Example: Safe prepared statement
$stmt = $pdo->prepare('SELECT * FROM student WHERE email = ?');
$stmt->execute([$untrustedEmail]);
```

### Password Security
Passwords are never stored in plaintext. They are hashed using the standard PHP `password_hash()` algorithm using **BCRYPT** with a work factor of 10. Verification is executed using timing-attack resistant `password_verify()`.

### Information Disclosure Prevention
Server error handlers log detailed stack traces to the PHP system log (`error_log`) while returning clean, sanitized error messages to the client. Database passwords, internal paths, and SQL query strings are never reflected to the browser.
