# CEMS — Full Deployment & Demonstration Guide
**College Event & Registration Management System**
*Academic DBMS Coursework — Complete Deployment Manual*

Repository: [https://github.com/neil-data/dbms-assignment](https://github.com/neil-data/dbms-assignment)

---

## 1. Quick Overview of Deployment Targets

Depending on your academic requirements and evaluation format, this project supports three deployment methods:

| Target | Best For | Database Engine | Difficulty |
| :--- | :--- | :--- | :---: |
| **Option A: Local XAMPP / MySQL Workbench** | **Teacher Viva & Lab Evaluation (Recommended)** | Local MySQL 8.0 on `localhost:3306` | Simple |
| **Option B: Vercel + Free Cloud MySQL** | **Online URL Sharing & Submission Links** | Cloud MySQL (Aiven / Railway / TiDB) | Moderate |
| **Option C: Single-Container (Railway / Render)** | **Full-Stack All-in-One Cloud Hosting** | Managed MySQL + Apache Container | Simple |

---

## OPTION A: Local Deployment (Standard College Evaluation)

This is the standard approach expected by college examiners because they want to watch queries execute directly in **MySQL Workbench** or **phpMyAdmin**.

### Prerequisites
* **XAMPP** (with Apache and MySQL) OR standalone **PHP 8.0+** and **MySQL 8.0+**
* **MySQL Workbench** (already installed on your PC)

### Step 1: Database Setup
1. Start MySQL (in XAMPP Control Panel or Windows Services).
2. Open MySQL Workbench and connect to **`Local instance MySQL80`** (`localhost:3306`, user `root`, empty password).
3. Open the master reset script in Workbench:
   * File $\to$ Open SQL Script $\to$ select `database/10_reset_database.sql`.
4. Click the **Yellow Lightning Bolt** to execute.
   * This drops old databases, creates `cems_db`, creates all 8 3NF tables, adds constraints, builds indexes, and seeds academic records.

### Step 2: Web Server Launch

#### Method 1: Using Standard XAMPP
1. Copy the project folder into `C:\xampp\htdocs\cems`.
2. Start **Apache** in XAMPP Control Panel.
3. Access in your browser: **`http://localhost/cems/`**

#### Method 2: Using PHP Built-In Server (Current Working Setup)
In the project directory, run in PowerShell:
```powershell
php -S 127.0.0.1:8080
```
Open in browser: **`http://127.0.0.1:8080/`**

---

## OPTION B: Deploying on Vercel with Cloud MySQL

Vercel is a serverless platform. The frontend (HTML/CSS/GSAP) and PHP serverless functions run on Vercel's edge network, while database data is queried from a free cloud MySQL database.

### Step 1: Create a Free Cloud MySQL Database (2 Minutes)

Choose any free cloud MySQL provider (we recommend **Aiven** or **Railway**):

#### Recommended: Aiven Free MySQL (Free Tier)
1. Go to **[https://aiven.io](https://aiven.io)** and sign up (free).
2. Click **Create Service** $\to$ select **MySQL**.
3. Select the **Free Tier** plan $\to$ click **Create Service**.
4. Once created, Aiven displays your connection parameters:
   * **Host:** `mysql-xxxxx.aivencloud.com`
   * **Port:** `12345` (e.g. 15432)
   * **User:** `avnadmin`
   * **Password:** *(your generated password)*
   * **Database:** `defaultdb` (or create `cems_db`)

### Step 2: Import Schema into the Cloud Database
1. In MySQL Workbench, click the **`+`** icon next to **MySQL Connections** to add a new connection.
2. Enter:
   * **Connection Name:** `Aiven Cloud MySQL`
   * **Hostname:** Your cloud host (e.g. `mysql-xxxxx.aivencloud.com`)
   * **Port:** Your cloud port (e.g. `15432`)
   * **Username:** `avnadmin`
   * **Password:** Click *Store in Vault* and enter password.
3. Click **Test Connection** $\to$ Click **OK**.
4. Open the connection $\to$ Open `database/10_reset_database.sql` $\to$ Click **Execute**.
   * Your cloud MySQL database now has all 8 tables and records!

### Step 3: Connect Vercel to Your GitHub Repository
Your repository already contains the configured [`vercel.json`](file:///c:/Users/Neil/Downloads/neural-%E2%80%94-world-class-digital-products/vercel.json).

1. Go to **[https://vercel.com](https://vercel.com)** and sign in with GitHub.
2. Click **"Add New..."** $\to$ **"Project"**.
3. Find **`dbms-assignment`** and click **"Import"**.
4. In the **Environment Variables** section, add your cloud database credentials:

| Key | Example Value |
| :--- | :--- |
| `DB_HOST` | `mysql-xxxxx.aivencloud.com` |
| `DB_PORT` | `15432` |
| `DB_NAME` | `cems_db` (or `defaultdb`) |
| `DB_USER` | `avnadmin` |
| `DB_PASS` | `your-cloud-password` |

5. Click **"Deploy"**.
6. In ~45 seconds, your app will be live at:
   `https://dbms-assignment-xxxxx.vercel.app`

---

## OPTION C: 1-Click Full-Stack on Railway (Alternative)

If you want PHP + MySQL hosted together on one dashboard without separating cloud services:
1. Go to **[https://railway.app](https://railway.app)**.
2. Click **"New Project"** $\to$ **"Provision MySQL"**.
3. In the same project, click **"New"** $\to$ **"GitHub Repo"** $\to$ select `neil-data/dbms-assignment`.
4. Connect the MySQL environment variables inside Railway.
5. Railway provides a live `.up.railway.app` public link with database persistence.

---

## 3. Teacher Evaluation Day Checklist (Getting Full Marks)

Before your teacher comes to your screen:

- [ ] **Step 1:** Verify MySQL is running on port 3306.
- [ ] **Step 2:** Open **MySQL Workbench** $\to$ connect to `Local instance MySQL80` $\to$ double-click `cems_db`.
- [ ] **Step 3:** Open the browser to **`http://127.0.0.1:8080/admin/database-demo.php`** (or `http://localhost/cems/admin/database-demo.php`).
- [ ] **Step 4:** Have credentials ready:
  * Student: `aarav.sharma@college.edu` / `Student@123`
  * Admin: `admin@college.edu` / `Admin@123`
- [ ] **Step 5 (The Reverse Engineer Trick):** In MySQL Workbench, press `Ctrl + R` (Database $\to$ Reverse Engineer) to show the visual EER diagram.
- [ ] **Step 6 (Live Proof):** Book an event on the website and immediately query `SELECT * FROM registration ORDER BY registration_id DESC LIMIT 1;` in MySQL Workbench.