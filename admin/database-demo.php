<?php
/**
 * CEMS - Academic DBMS Demonstration Console
 * File: admin/database-demo.php
 * Conforms to Requirement 16 & 28 (Teacher Demonstration Sequence)
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/shared/auth.php';

initSession();
$session = getCurrentSession();

// Require admin or redirect to login
if (!$session || $session['role'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}

// Explicitly send HTML Content-Type so browsers render the UI instead of raw text
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DBMS Demonstration Console — CEMS</title>
  <meta name="description" content="Academic DBMS live demonstration console for teacher evaluation, relational integrity testing, ACID transactions, and query benchmarks." />
  
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/responsive.css" />

  <!-- GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

  <style>
    .demo-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 2rem;
      align-items: start;
    }
    @media (max-width: 900px) {
      .demo-grid { grid-template-columns: 1fr; }
      .demo-nav-card { position: static; margin-bottom: 1.5rem; }
      .demo-section-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
    }
    .demo-nav-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-md);
      padding: 1.25rem;
      position: sticky;
      top: calc(var(--header-height) + 1.5rem);
      backdrop-filter: blur(12px);
    }
    .demo-nav-link {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0.6rem 0.85rem;
      color: var(--text-secondary);
      text-decoration: none;
      font-size: 0.84rem;
      border-radius: var(--radius-sm);
      transition: all var(--transition-fast);
      margin-bottom: 0.25rem;
    }
    .demo-nav-link:hover, .demo-nav-link.active {
      color: var(--gold-primary);
      background: rgba(245, 207, 104, 0.08);
      border-left: 3px solid var(--gold-primary);
    }
    .demo-section-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-md);
      padding: 2rem;
      margin-bottom: 2.25rem;
      backdrop-filter: blur(12px);
    }
    .demo-section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border-subtle);
      flex-wrap: wrap;
      gap: 1rem;
    }
    .demo-code-box {
      background: rgba(2, 4, 9, 0.85);
      border: 1px solid var(--border-subtle);
      border-radius: 6px;
      padding: 1.25rem;
      font-family: monospace;
      font-size: 0.82rem;
      color: var(--blue-primary);
      overflow-x: auto;
      line-height: 1.6;
      margin-bottom: 1.25rem;
    }
    .demo-output-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.82rem;
      margin-top: 1rem;
    }
    .demo-output-table th {
      background: rgba(16, 28, 58, 0.8);
      color: var(--gold-primary);
      padding: 0.65rem 0.85rem;
      text-align: left;
      font-weight: 600;
      border-bottom: 1px solid var(--border-gold);
    }
    .demo-output-table td {
      padding: 0.6rem 0.85rem;
      border-bottom: 1px solid var(--border-subtle);
      color: var(--text-primary);
    }
    .demo-output-table tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }
    .status-badge-ok {
      background: rgba(16, 185, 129, 0.15);
      color: var(--status-open);
      border: 1px solid rgba(16, 185, 129, 0.3);
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .status-badge-err {
      background: rgba(239, 68, 68, 0.15);
      color: var(--status-closing);
      border: 1px solid rgba(239, 68, 68, 0.3);
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <!-- Cinematic Background Elements -->
  <div class="cinematic-background" style="height: 550px; min-height: 550px;" aria-hidden="true">
    <div class="glow-nebula-gold" style="top: 15%; width: 850px; height: 350px;"></div>
    <div class="glow-nebula-blue" style="top: 30%; width: 750px; height: 350px;"></div>
    <div class="dust-particles"></div>
    <div class="vignette-overlay"></div>
  </div>

  <!-- Header -->
  <header class="cems-header scrolled">
    <div class="nav-container">
      <a href="../index.html" class="brand-logo">
        <span class="brand-mark">C</span>
        <span class="brand-name">CEMS</span>
        <span class="brand-tag">DBMS Viva Console</span>
      </a>

      <nav class="nav-links">
        <a href="../index.html" class="nav-link">Home</a>
        <a href="../events.html" class="nav-link">Public Events</a>
        <a href="../admin.html" class="nav-link">Admin Console</a>
        <a href="database-demo.php" class="nav-link active">DBMS Demonstration</a>
      </nav>

      <div class="nav-actions">
        <span style="font-size:0.82rem;color:var(--gold-primary);display:flex;align-items:center;gap:0.4rem;">
          <span class="material-symbols-outlined" style="font-size:18px;">database</span>
          <span>cems_db (MySQL)</span>
        </span>
        <a href="../admin.html" class="btn btn-secondary" style="font-size:0.82rem;">Return to Console</a>
      </div>
    </div>
  </header>

  <main style="position: relative; z-index: 10; padding-top: calc(var(--header-height) + 2rem); min-height: 90vh; padding-bottom: 5rem;">
    <div class="section-wrapper" style="padding-top: 1rem;">
      
      <!-- Banner -->
      <div style="margin-bottom: 2.5rem;">
        <span class="section-eyebrow">ACADEMIC EVALUATION INTERFACE</span>
        <h1 class="section-title">DBMS Relational Demonstration</h1>
        <p class="section-subtitle">
          Interactive evaluation module designed specifically for academic faculty and external examiners. Demonstrates ER modeling, 3NF normalization, relational constraints, ACID transactions, and live SQL benchmarks.
        </p>
      </div>

      <div class="demo-grid">
        
        <!-- Navigation Sidebar -->
        <aside class="demo-nav-card">
          <div style="font-size:0.75rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;letter-spacing:0.08em;margin-bottom:0.75rem;">
            Demonstration Modules
          </div>
          <nav>
            <a href="#overview" class="demo-nav-link active">
              <span class="material-symbols-outlined" style="font-size:17px;">table_chart</span>
              <span>A. Database Overview</span>
            </a>
            <a href="#relationships" class="demo-nav-link">
              <span class="material-symbols-outlined" style="font-size:17px;">join</span>
              <span>B. Relational JOINs</span>
            </a>
            <a href="#aggregates" class="demo-nav-link">
              <span class="material-symbols-outlined" style="font-size:17px;">calculate</span>
              <span>C. Aggregate Functions</span>
            </a>
            <a href="#groupby" class="demo-nav-link">
              <span class="material-symbols-outlined" style="font-size:17px;">pie_chart</span>
              <span>D. GROUP BY &amp; HAVING</span>
            </a>
            <a href="#subqueries" class="demo-nav-link">
              <span class="material-symbols-outlined" style="font-size:17px;">nest_cam_wired_stand</span>
              <span>E. Nested Subqueries</span>
            </a>
            <a href="#constraints" class="demo-nav-link">
              <span class="material-symbols-outlined" style="font-size:17px;">verified_user</span>
              <span>F. Constraints &amp; Integrity</span>
            </a>
            <a href="#transactions" class="demo-nav-link">
              <span class="material-symbols-outlined" style="font-size:17px;">sync_alt</span>
              <span>G. ACID Transactions</span>
            </a>
            <a href="#views" class="demo-nav-link">
              <span class="material-symbols-outlined" style="font-size:17px;">visibility</span>
              <span>H. Database Views</span>
            </a>
          </nav>
        </aside>

        <!-- Main Content Area -->
        <div>
          
          <!-- SECTION A: Database Overview -->
          <section id="overview" class="demo-section-card">
            <div class="demo-section-header">
              <div>
                <h3 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                  A. Relational Overview (cems_db)
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.2rem;">
                  Active entity tables and live tuple counts queried directly from MySQL InnoDB.
                </p>
              </div>
              <button id="refresh-overview-btn" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.45rem 0.85rem;">
                <span class="material-symbols-outlined" style="font-size: 16px;">refresh</span>
                <span>Refresh Live Counts</span>
              </button>
            </div>

            <div id="overview-tables-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
              <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                Querying database metadata...
              </div>
            </div>
          </section>

          <!-- SECTION B: Relational Relationships (JOIN Queries) -->
          <section id="relationships" class="demo-section-card">
            <div class="demo-section-header">
              <div>
                <h3 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                  B. Relational Foreign Key JOINs
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.2rem;">
                  Visually proves 4-entity traversal: Student &rarr; Registration &rarr; Event &rarr; Venue.
                </p>
              </div>
              <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary demo-query-btn" data-type="multi_join" style="font-size: 0.8rem;">
                  <span class="material-symbols-outlined" style="font-size: 16px;">play_arrow</span>
                  <span>Execute 4-Table INNER JOIN</span>
                </button>
                <button class="btn btn-secondary demo-query-btn" data-type="left_join" style="font-size: 0.8rem;">
                  <span>LEFT OUTER JOIN</span>
                </button>
                <button class="btn btn-secondary demo-query-btn" data-type="many_to_many" style="font-size: 0.8rem;">
                  <span>M:M Junction JOIN</span>
                </button>
              </div>
            </div>

            <div id="join-results-container">
              <div class="demo-code-box">
                -- Select a JOIN operation above to execute against MySQL.
              </div>
            </div>
          </section>

          <!-- SECTION C: Aggregate Queries -->
          <section id="aggregates" class="demo-section-card">
            <div class="demo-section-header">
              <div>
                <h3 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                  C. SQL Aggregate Functions
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.2rem;">
                  Demonstrates <code>COUNT()</code>, <code>SUM()</code>, <code>AVG()</code>, <code>MIN()</code>, <code>MAX()</code>.
                </p>
              </div>
              <button class="btn btn-primary demo-query-btn" data-type="aggregate" style="font-size: 0.8rem;">
                <span class="material-symbols-outlined" style="font-size: 16px;">play_arrow</span>
                <span>Run Aggregates</span>
              </button>
            </div>

            <div id="aggregate-results-container">
              <div class="demo-code-box">
                SELECT COUNT(*), SUM(capacity), AVG(max_capacity), MIN(capacity), MAX(capacity) FROM ...
              </div>
            </div>
          </section>

          <!-- SECTION D: GROUP BY & HAVING -->
          <section id="groupby" class="demo-section-card">
            <div class="demo-section-header">
              <div>
                <h3 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                  D. GROUP BY &amp; Arithmetic Projections
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.2rem;">
                  Groups registrations by event, calculates capacity arithmetic (Enrolled vs Seats Left).
                </p>
              </div>
              <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary demo-query-btn" data-type="group_by_event" style="font-size: 0.8rem;">
                  <span>Seats by Event</span>
                </button>
                <button class="btn btn-secondary demo-query-btn" data-type="group_by_dept" style="font-size: 0.8rem;">
                  <span>Students by Dept</span>
                </button>
                <button class="btn btn-secondary demo-query-btn" data-type="group_by_status" style="font-size: 0.8rem;">
                  <span>Status Breakdown</span>
                </button>
              </div>
            </div>

            <div id="groupby-results-container">
              <div class="demo-code-box">
                -- Grouping benchmarks will display here.
              </div>
            </div>
          </section>

          <!-- SECTION E: Subqueries -->
          <section id="subqueries" class="demo-section-card">
            <div class="demo-section-header">
              <div>
                <h3 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                  E. Nested Subqueries
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.2rem;">
                  Finds events with <code>max_capacity &gt; (SELECT AVG(max_capacity) FROM event)</code>.
                </p>
              </div>
              <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary demo-query-btn" data-type="subquery_capacity" style="font-size: 0.8rem;">
                  <span>Capacity &gt; Average</span>
                </button>
                <button class="btn btn-secondary demo-query-btn" data-type="subquery_students" style="font-size: 0.8rem;">
                  <span>Multi-Event Students</span>
                </button>
              </div>
            </div>

            <div id="subquery-results-container">
              <div class="demo-code-box">
                -- Subquery results will render here.
              </div>
            </div>
          </section>

          <!-- SECTION F: Constraint & Integrity Demonstration -->
          <section id="constraints" class="demo-section-card">
            <div class="demo-section-header">
              <div>
                <h3 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                  F. Constraint Enforcement (Viva Key Topic)
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.2rem;">
                  Demonstrates how MySQL rejects invalid tuples: UNIQUE, FOREIGN KEY, CHECK, NOT NULL.
                </p>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
              <button class="btn btn-secondary constraint-test-btn" data-test="duplicate_registration" style="font-size: 0.82rem; justify-content: flex-start; padding: 0.75rem 1rem;">
                <span class="material-symbols-outlined" style="color:var(--gold-primary);font-size:18px;">block</span>
                <span>Test Duplicate UNIQUE Registration</span>
              </button>
              <button class="btn btn-secondary constraint-test-btn" data-test="foreign_key" style="font-size: 0.82rem; justify-content: flex-start; padding: 0.75rem 1rem;">
                <span class="material-symbols-outlined" style="color:var(--blue-primary);font-size:18px;">link_off</span>
                <span>Test Invalid FOREIGN KEY</span>
              </button>
              <button class="btn btn-secondary constraint-test-btn" data-test="check_constraint" style="font-size: 0.82rem; justify-content: flex-start; padding: 0.75rem 1rem;">
                <span class="material-symbols-outlined" style="color:var(--status-closing);font-size:18px;">rule</span>
                <span>Test Negative CHECK Capacity</span>
              </button>
              <button class="btn btn-secondary constraint-test-btn" data-test="not_null" style="font-size: 0.82rem; justify-content: flex-start; padding: 0.75rem 1rem;">
                <span class="material-symbols-outlined" style="color:var(--text-muted);font-size:18px;">cancel</span>
                <span>Test NULL Column Rejection</span>
              </button>
            </div>

            <div id="constraint-output-area">
              <div style="background: rgba(2, 4, 9, 0.6); border: 1px dashed var(--border-subtle); border-radius: 6px; padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                Click any constraint test button above to observe live engine rejection.
              </div>
            </div>
          </section>

          <!-- SECTION G: ACID Transactions Demonstration -->
          <section id="transactions" class="demo-section-card">
            <div class="demo-section-header">
              <div>
                <h3 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                  G. ACID Transactions (COMMIT &amp; ROLLBACK)
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.2rem;">
                  Step-by-step execution demonstrating <code>START TRANSACTION</code>, row locking, and rollback.
                </p>
              </div>
              <div style="display: flex; gap: 0.75rem;">
                <button id="demo-tx-commit-btn" class="btn btn-primary" style="font-size: 0.82rem;">
                  <span class="material-symbols-outlined" style="font-size: 16px;">check_circle</span>
                  <span>Execute COMMIT Scenario</span>
                </button>
                <button id="demo-tx-rollback-btn" class="btn btn-secondary" style="font-size: 0.82rem; color: var(--status-closing); border-color: rgba(239, 68, 68, 0.4);">
                  <span class="material-symbols-outlined" style="font-size: 16px;">undo</span>
                  <span>Execute ROLLBACK Scenario</span>
                </button>
              </div>
            </div>

            <div id="transaction-output-area">
              <div style="background: rgba(2, 4, 9, 0.6); border: 1px dashed var(--border-subtle); border-radius: 6px; padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                Trigger COMMIT or ROLLBACK above to trace atomic isolation boundaries.
              </div>
            </div>
          </section>

          <!-- SECTION H: Database Views -->
          <section id="views" class="demo-section-card">
            <div class="demo-section-header">
              <div>
                <h3 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                  H. Compiled Database Views
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.2rem;">
                  Virtual table structures compiled in MySQL: <code>view_event_registration_summary</code> &amp; <code>view_student_registrations</code>.
                </p>
              </div>
              <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary demo-query-btn" data-type="view_summary" style="font-size: 0.8rem;">
                  <span>Query Event Summary View</span>
                </button>
                <button class="btn btn-secondary demo-query-btn" data-type="view_student" style="font-size: 0.8rem;">
                  <span>Query Student Passes View</span>
                </button>
              </div>
            </div>

            <div id="views-results-container">
              <div class="demo-code-box">
                -- View query output will render here.
              </div>
            </div>
          </section>

        </div>
      </div>

    </div>
  </main>

  <footer class="cems-footer">
    <div class="footer-inner">
      <div class="footer-bottom" style="border: none; padding-top: 0;">
        <div>&copy; 2026 College Event &amp; Registration Management System. Academic DBMS viva console.</div>
        <div class="system-status-indicator">
          <span class="status-beacon"></span>
          <span>Database: Connected (MySQL)</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Interactive JavaScript Controller for Demonstration Page -->
  <script type="module">
    import { api } from '../js/api.js';

    async function loadOverview() {
      const container = document.getElementById('overview-tables-container');
      try {
        const res = await api.get('backend/database-demo/overview.php');
        if (res.success && res.data) {
          const { tables, views, mysql_version, database_name } = res.data;
          container.innerHTML = tables.map(t => `
            <div style="background: rgba(2, 4, 9, 0.6); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 1.25rem;">
              <div style="font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Table</div>
              <div style="font-family: var(--font-display); font-size: 1.05rem; font-weight: 700; color: var(--gold-primary); margin: 0.2rem 0 0.5rem;">
                ${t.table_name}
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 0.84rem;">
                <span style="color: var(--text-secondary);">Tuples:</span>
                <strong style="color: var(--text-primary);">${t.row_count}</strong>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-muted); margin-top: 0.2rem;">
                <span>Attributes:</span>
                <span>${t.columns.length} columns</span>
              </div>
            </div>
          `).join('');
        }
      } catch (e) {
        container.innerHTML = `<div style="color:var(--status-closing);padding:1rem;">Failed to load table metrics: ${e.message}</div>`;
      }
    }

    async function runDemoQuery(type, targetContainerId) {
      const container = document.getElementById(targetContainerId);
      container.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
          <span class="material-symbols-outlined" style="font-size: 28px; color: var(--gold-primary); animation: spin 1s infinite linear;">sync</span>
          <p style="color: var(--text-muted); font-size: 0.84rem; margin-top: 0.5rem;">Executing query against MySQL engine...</p>
        </div>
      `;

      try {
        const res = await api.get(`backend/database-demo/queries.php?type=${type}`);
        if (res.success && res.data) {
          const { sql, results, row_count, title } = res.data;
          
          if (!results || results.length === 0) {
            container.innerHTML = `
              <div class="demo-code-box">${escapeHtml(sql)}</div>
              <div style="color: var(--text-muted); font-size: 0.85rem; padding: 1rem; text-align: center;">Query executed successfully (0 rows returned).</div>
            `;
            return;
          }

          const columns = Object.keys(results[0]);

          container.innerHTML = `
            <div class="demo-code-box">${escapeHtml(sql)}</div>
            <div style="font-size: 0.8rem; color: var(--gold-primary); margin-bottom: 0.5rem; font-weight: 600;">
              Returned ${row_count} tuple(s) from MySQL engine:
            </div>
            <div style="overflow-x: auto; max-height: 400px; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
              <table class="demo-output-table">
                <thead>
                  <tr>${columns.map(c => `<th>${c}</th>`).join('')}</tr>
                </thead>
                <tbody>
                  ${results.map(row => `
                    <tr>${columns.map(c => `<td>${escapeHtml(String(row[c] !== null ? row[c] : 'NULL'))}</td>`).join('')}</tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          `;
        }
      } catch (err) {
        container.innerHTML = `<div style="color:var(--status-closing);padding:1rem;">Execution error: ${err.message}</div>`;
      }
    }

    async function runConstraintTest(testType) {
      const outputArea = document.getElementById('constraint-output-area');
      outputArea.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
          <span class="material-symbols-outlined" style="font-size: 28px; color: var(--gold-primary); animation: spin 1s infinite linear;">sync</span>
          <p style="color: var(--text-muted); font-size: 0.84rem; margin-top: 0.5rem;">Triggering constraint test against MySQL...</p>
        </div>
      `;

      try {
        const res = await api.post('backend/database-demo/test_constraint.php', { test: testType });
        const data = res.data || {};

        outputArea.innerHTML = `
          <div style="background: rgba(2, 4, 9, 0.7); border: 1px solid var(--border-gold); border-radius: 6px; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
              <span class="status-badge-ok">Constraint Actively Enforced</span>
              <span style="font-size: 0.78rem; color: var(--gold-primary); font-weight: 600;">Error Code: ${data.mysql_error_code || '1062'}</span>
            </div>
            <div style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
              ${res.message}
            </div>
            <div class="demo-code-box" style="margin-bottom: 0.75rem;">
              ${escapeHtml(data.attempted_query || '')}
            </div>
            <div style="font-size: 0.82rem; color: var(--status-closing); background: rgba(239, 68, 68, 0.08); border-left: 3px solid var(--status-closing); padding: 0.6rem 0.85rem; margin-bottom: 0.75rem; font-family: monospace;">
              ${escapeHtml(data.mysql_message || '')}
            </div>
            <p style="font-size: 0.86rem; color: var(--text-secondary); line-height: 1.6;">
              <strong>Academic Rationale:</strong> ${data.academic_explanation || ''}
            </p>
          </div>
        `;
      } catch (err) {
        outputArea.innerHTML = `<div style="color:var(--status-closing);padding:1rem;">Error during test: ${err.message}</div>`;
      }
    }

    async function runTransaction(action) {
      const outputArea = document.getElementById('transaction-output-area');
      outputArea.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
          <span class="material-symbols-outlined" style="font-size: 28px; color: var(--gold-primary); animation: spin 1s infinite linear;">sync</span>
          <p style="color: var(--text-muted); font-size: 0.84rem; margin-top: 0.5rem;">Executing atomic transaction sequence...</p>
        </div>
      `;

      try {
        const res = await api.post('backend/database-demo/test_transaction.php', { action });
        const data = res.data || {};

        outputArea.innerHTML = `
          <div style="background: rgba(2, 4, 9, 0.7); border: 1px solid ${action === 'commit' ? 'var(--border-blue)' : 'rgba(239, 68, 68, 0.4)'}; border-radius: 6px; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
              <span class="${action === 'commit' ? 'status-badge-ok' : 'status-badge-err'}">
                ACID Guarantee: ${data.action}
              </span>
              <span style="font-size: 0.8rem; color: var(--text-muted);">Atomic Isolation Boundary</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.25rem;">
              ${(data.steps || []).map(step => `
                <div style="display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.82rem; color: var(--text-secondary);">
                  <span class="material-symbols-outlined" style="font-size: 16px; color: ${action === 'commit' ? 'var(--status-open)' : 'var(--gold-primary)'}; margin-top: 2px;">arrow_right</span>
                  <span>${step}</span>
                </div>
              `).join('')}
            </div>

            <div style="padding: 0.85rem; background: rgba(255, 255, 255, 0.03); border-radius: 4px; border-left: 3px solid ${action === 'commit' ? 'var(--status-open)' : 'var(--status-closing)'}; font-size: 0.86rem; color: var(--text-primary);">
              <strong>DBMS Outcome:</strong> ${data.explanation}
            </div>
          </div>
        `;
      } catch (err) {
        outputArea.innerHTML = `<div style="color:var(--status-closing);padding:1rem;">Transaction error: ${err.message}</div>`;
      }
    }

    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadOverview();
      document.getElementById('refresh-overview-btn')?.addEventListener('click', loadOverview);

      // Query buttons
      document.querySelectorAll('.demo-query-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const type = btn.dataset.type;
          let target = 'join-results-container';
          if (type === 'aggregate') target = 'aggregate-results-container';
          else if (type.startsWith('group_by')) target = 'groupby-results-container';
          else if (type.startsWith('subquery')) target = 'subquery-results-container';
          else if (type.startsWith('view_')) target = 'views-results-container';

          runDemoQuery(type, target);
        });
      });

      // Constraint buttons
      document.querySelectorAll('.constraint-test-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          runConstraintTest(btn.dataset.test);
        });
      });

      // Transaction buttons
      document.getElementById('demo-tx-commit-btn')?.addEventListener('click', () => runTransaction('commit'));
      document.getElementById('demo-tx-rollback-btn')?.addEventListener('click', () => runTransaction('rollback'));
    });
  </script>
</body>
</html>
