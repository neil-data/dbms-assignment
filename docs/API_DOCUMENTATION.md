# REST API Specification — CEMS

All endpoints communicate via JSON over HTTP. Standard response schema adheres strictly to Requirement 21.

### Response Contracts

**Success (200 / 201):**
```json
{
  "success": true,
  "message": "Human-readable status description.",
  "data": {}
}
```

**Error (400 / 401 / 403 / 404 / 409 / 422 / 500):**
```json
{
  "success": false,
  "message": "Human-readable error explanation.",
  "errors": {
    "field_name": "Specific validation or database reason."
  }
}
```

---

## 1. Authentication Endpoints (`backend/auth/`)

### `GET /backend/auth/me.php`
* **Purpose:** Inspects active session, user identity, and live MySQL database connectivity.
* **Access:** Public
* **Response `data`:**
  ```json
  {
    "database_connected": true,
    "authenticated": true,
    "role": "student",
    "user": {
      "user_id": 1,
      "name": "Aarav Sharma",
      "email": "aarav.sharma@campus.edu",
      "role": "student"
    }
  }
  ```

### `POST /backend/auth/student_register.php`
* **Purpose:** Enrolls a new student tuple in `student` table.
* **Access:** Public
* **Payload:**
  ```json
  {
    "name": "Ananya Roy",
    "email": "ananya.roy@campus.edu",
    "phone": "+91 98765 11111",
    "department_id": 1,
    "semester": 4,
    "password": "SecretPassword@123"
  }
  ```
* **Status Codes:** `201 Created`, `409 Conflict` (Email duplicate), `422 Unprocessable Entity`

### `POST /backend/auth/student_login.php`
* **Purpose:** Authenticates student via bcrypt `password_verify()` and establishes PHP session.
* **Access:** Public
* **Payload:** `{"email": "aarav.sharma@campus.edu", "password": "Student@123"}`
* **Status Codes:** `200 OK`, `401 Unauthorized`

### `POST /backend/auth/admin_login.php`
* **Purpose:** Authenticates system administrator against `admin` table.
* **Access:** Public
* **Payload:** `{"username": "admin", "password": "Admin@123"}`
* **Status Codes:** `200 OK`, `401 Unauthorized`

### `POST /backend/auth/student_logout.php` & `POST /backend/auth/admin_logout.php`
* **Purpose:** Destroys server-side session and invalidates cookie.

---

## 2. Event Endpoints (`backend/events/`)

### `GET /backend/events/list.php`
* **Purpose:** Queries events joined with venues and categories.
* **Query Parameters:**
  - `search`: Keyword string (matches event name, description, venue)
  - `category`: Category slug or ID
  - `status`: `UPCOMING`, `ONGOING`, `COMPLETED`, `CANCELLED`
  - `sort`: `date_asc`, `date_desc`, `seats_left`, `title_asc`
  - `featured`: `1` (limits to top 3 for homepage)

### `GET /backend/events/get.php?id={event_id}`
* **Purpose:** Retrieves complete event specification, capacity, and current student enrollment state.

### `POST /backend/events/create.php`
* **Purpose:** Creates event and attaches categories in `event_category`.
* **Access:** Admin only (`403 Forbidden` if unauthorized)
* **Payload:**
  ```json
  {
    "event_name": "Artificial Intelligence Hackathon",
    "description": "36-hour challenge.",
    "event_date": "2026-11-15",
    "event_time": "09:00 AM - 05:00 PM",
    "venue_id": 3,
    "max_capacity": 75,
    "status": "UPCOMING",
    "category_ids": [1, 6]
  }
  ```

### `POST /backend/events/update.php` & `POST /backend/events/delete.php`
* **Purpose:** Updates or deletes event tuple in MySQL.

---

## 3. Registration Endpoints (`backend/registrations/`)

### `POST /backend/registrations/create.php`
* **Purpose:** Executes ACID transaction to reserve an event seat.
* **Access:** Student only
* **Payload:** `{"event_id": 1}`
* **Process:**
  1. `START TRANSACTION;`
  2. `SELECT ... FOR UPDATE` (Row lock)
  3. Verify `status != 'CANCELLED' / 'COMPLETED'`
  4. Verify `UNIQUE(student_id, event_id)`
  5. Check `COUNT(confirmed) < max_capacity`
  6. `INSERT INTO registration ...`
  7. `COMMIT;` (or `ROLLBACK;` on any failure)
* **Status Codes:** `201 Created`, `409 Conflict` (Duplicate or Quota full), `400 Bad Request`

### `GET /backend/registrations/list.php`
* **Access:** Authenticated. (Students receive their own passes; Admin receives all passes across campus).

### `POST /backend/registrations/cancel.php`
* **Purpose:** Marks registration status as `'CANCELLED'`, returning seat to quota.

---

## 4. Entity CRUD Endpoints

| Resource | Endpoints | Access |
|---|---|---|
| **Students** | `list.php`, `get.php`, `create.php`, `update.php`, `delete.php` | Admin (or self for profile) |
| **Departments** | `list.php`, `create.php`, `update.php`, `delete.php` | Public List / Admin CRUD |
| **Venues** | `list.php`, `create.php`, `update.php`, `delete.php` | Public List / Admin CRUD |
| **Categories** | `list.php`, `create.php`, `update.php`, `delete.php` | Public List / Admin CRUD |

---

## 5. Analytical Reports Endpoints (`backend/reports/`)

* `GET /backend/reports/dashboard.php`: Top statistics (counts of students, events, registrations, venues).
* `GET /backend/reports/event_statistics.php`: Capacity utilization rates and seat scarcity.
* `GET /backend/reports/department_statistics.php`: Student distribution and total passes per department.
* `GET /backend/reports/category_statistics.php`: Registrations grouped by event genre.
* `GET /backend/reports/registration_statistics.php`: Pass distribution by status (`CONFIRMED`, `PENDING`, `CANCELLED`, `COMPLETED`).
