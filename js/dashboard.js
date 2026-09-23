/**
 * CEMS - Student Portal & Administrative Console Controller
 * Powers student pass management and administrative relational CRUD operations.
 */

import { api } from './api.js';
import { showToast, openTicketModal, escapeHtml } from './main.js';

// ============================================================================
// STUDENT DASHBOARD (student-dashboard.html)
// ============================================================================

export async function initStudentDashboard() {
  const container = document.getElementById('student-dashboard-content');
  if (!container) return;

  const status = await api.checkStatus();
  if (!status.authenticated || status.role !== 'student') {
    window.location.href = 'login.html?redirect=student-dashboard.html';
    return;
  }

  container.innerHTML = `
  container.innerHTML = `
    <div style="text-align: center; padding: 4rem 0;">
      <span class="material-symbols-outlined" style="font-size: 32px; color: var(--lime-accent); animation: spin 1s infinite linear;">sync</span>
      <p style="color: var(--text-muted); margin-top: 1rem;">Loading your campus activity hub...</p>
    </div>
  `;

  try {
    const [profileRes, regRes, clubsRes, recClubsRes] = await Promise.all([
      api.get('backend/students/get.php'),
      api.get('backend/registrations/list.php?my=1'),
      api.getMyClubs().catch(() => ({ data: [] })),
      api.getClubs({ featured: 1 }).catch(() => ({ data: [] }))
    ]);

    const student = profileRes.data;
    const registrations = regRes.data || [];
    const activePasses = registrations.filter(r => r.status === 'CONFIRMED');
    const myClubs = clubsRes.data || [];
    const recommendedClubs = recClubsRes.data || [];

    // Greeting by time of day
    const hour = new Date().getHours();
    const timeGreeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';

    container.innerHTML = `
      <!-- Editorial Campus Hub Banner -->
      <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); padding: 2.5rem; margin-bottom: 3.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
          <div>
            <span class="eyebrow-pill" style="margin-bottom: 0.75rem;">CAMPUS ACTIVITY HUB</span>
            <h1 style="font-family: var(--font-display); font-size: clamp(1.8rem, 3.5vw, 2.6rem); font-weight: 700; color: var(--text-primary); line-height: 1.15; margin-bottom: 0.5rem;">
              ${timeGreeting}, ${escapeHtml(student.name.split(' ')[0])}.
            </h1>
            <p style="color: var(--text-secondary); font-size: 1rem;">
              Here's what's happening around your campus community.
            </p>
          </div>

          <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
            <div style="text-align: right; padding-right: 1.5rem; border-right: 1px solid var(--border-subtle);">
              <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); display: block;">Joined Clubs</span>
              <strong style="font-family: var(--font-display); font-size: 1.6rem; color: var(--lime-accent);">${myClubs.length}</strong>
            </div>
            <div style="text-align: right; padding-right: 1.5rem; border-right: 1px solid var(--border-subtle);">
              <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); display: block;">Active Passes</span>
              <strong style="font-family: var(--font-display); font-size: 1.6rem; color: var(--text-primary);">${activePasses.length}</strong>
            </div>
            <a href="clubs.html" class="btn btn-lime">
              <span>Explore Clubs &rarr;</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Section 1: Your Clubs -->
      <div style="margin-bottom: 3.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem;">
          <div>
            <h2 style="font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">
              Your Campus Clubs
            </h2>
            <p style="font-size: 0.86rem; color: var(--text-secondary);">Communities where you are actively enrolled</p>
          </div>
          <a href="clubs.html" style="font-size: 0.85rem; color: var(--lime-accent); font-weight: 600;">Browse All Clubs &rarr;</a>
        </div>

        ${myClubs.length === 0 ? `
          <div style="background: var(--bg-surface); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md); padding: 3rem; text-align: center;">
            <span class="material-symbols-outlined" style="font-size: 40px; color: var(--text-muted); margin-bottom: 0.75rem;">groups</span>
            <h3 style="font-family: var(--font-display); font-size: 1.15rem; color: var(--text-primary); margin-bottom: 0.4rem;">No clubs joined yet</h3>
            <p style="color: var(--text-secondary); max-width: 420px; margin: 0 auto 1.5rem; font-size: 0.9rem;">
              Connect with fellow students, participate in weekly workshops, and build what you care about.
            </p>
            <a href="clubs.html" class="btn btn-lime" style="font-size: 0.84rem;">
              <span>Discover College Clubs &rarr;</span>
            </a>
          </div>
        ` : `
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            ${myClubs.map(cl => `
              <div class="club-editorial-card" style="height: 260px;">
                <div class="club-card-image-wrap">
                  <img src="${escapeHtml(cl.cover_image || '')}" alt="${escapeHtml(cl.club_name)}" class="club-card-image" onerror="this.src='https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop'" />
                  <div class="club-card-overlay"></div>
                </div>
                <div class="club-card-content">
                  <div class="club-card-header">
                    <span class="club-category-pill">${escapeHtml(cl.category)}</span>
                    <span style="font-size: 0.72rem; font-weight: 700; color: var(--lime-accent); background: rgba(8,9,11,0.7); padding: 0.2rem 0.5rem; border-radius: var(--radius-full); border: 1px solid var(--lime-border);">
                      ${escapeHtml(cl.membership_role || 'MEMBER')}
                    </span>
                  </div>
                  <div class="club-card-footer">
                    <h3 class="club-card-title">
                      <a href="club-details.html?id=${cl.club_id}" style="color: var(--text-primary);">${escapeHtml(cl.club_name)}</a>
                      <a href="club-details.html?id=${cl.club_id}" class="arrow-circle-btn">
                        <span class="material-symbols-outlined" style="font-size: 15px;">arrow_forward</span>
                      </a>
                    </h3>
                    <p class="club-card-tagline">${escapeHtml(cl.tagline)}</p>
                  </div>
                </div>
              </div>
            `).join('')}
          </div>
        `}
      </div>

      <!-- Section 2: Active Registered Event Passes -->
      <div style="margin-bottom: 3.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem;">
          <div>
            <h2 style="font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">
              Your Event Passes
            </h2>
            <p style="font-size: 0.86rem; color: var(--text-secondary);">Gate credentials for upcoming club events</p>
          </div>
          <a href="events.html" style="font-size: 0.85rem; color: var(--lime-accent); font-weight: 600;">Browse Events &rarr;</a>
        </div>

        ${registrations.length === 0 ? `
          <div style="background: var(--bg-surface); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md); padding: 3rem; text-align: center;">
            <span class="material-symbols-outlined" style="font-size: 40px; color: var(--text-muted); margin-bottom: 0.75rem;">confirmation_number</span>
            <h3 style="font-family: var(--font-display); font-size: 1.15rem; color: var(--text-primary); margin-bottom: 0.4rem;">No event passes issued</h3>
            <p style="color: var(--text-secondary); max-width: 400px; margin: 0 auto 1.5rem; font-size: 0.9rem;">
              You have not registered for any upcoming events. Explore the event catalog to reserve your place.
            </p>
            <a href="events.html" class="btn btn-ghost" style="font-size: 0.84rem;">
              <span>Explore Upcoming Events &rarr;</span>
            </a>
          </div>
        ` : `
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
            ${registrations.map(reg => {
              const isCancelled = reg.status === 'CANCELLED';
              return `
                <div class="event-editorial-card" style="opacity: ${isCancelled ? '0.6' : '1'};">
                  <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                      <span style="font-size: 0.72rem; color: var(--lime-accent); font-weight: 600; text-transform: uppercase;">PASS #${reg.registration_id}</span>
                      <span style="font-size: 0.72rem; padding: 0.2rem 0.5rem; border-radius: var(--radius-full); background: ${isCancelled ? 'rgba(239,68,68,0.1)' : 'rgba(200,249,54,0.1)'}; color: ${isCancelled ? 'var(--status-closed)' : 'var(--lime-accent)'}; font-weight: 600;">
                        ${reg.status}
                      </span>
                    </div>

                    <h3 class="event-card-title" style="font-size: 1.2rem; margin-bottom: 0.35rem;">
                      ${escapeHtml(reg.event ? reg.event.title : 'Event Record')}
                    </h3>
                    <div style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 1rem;">
                      TOKEN: ${escapeHtml(reg.ticket_token || 'TOKEN-ISSUED')}
                    </div>

                    <div class="event-meta-list" style="margin-bottom: 1rem;">
                      <div class="event-meta-item">
                        <span class="material-symbols-outlined" style="font-size: 16px; color: var(--lime-accent);">calendar_month</span>
                        <span>${escapeHtml(reg.event ? (reg.event.display_date || reg.event.date) : 'TBA')}</span>
                      </div>
                      <div class="event-meta-item">
                        <span class="material-symbols-outlined" style="font-size: 16px; color: var(--text-secondary);">location_on</span>
                        <span>${escapeHtml(reg.event && reg.event.venue ? reg.event.venue.venue_name : 'Campus Venue')}</span>
                      </div>
                    </div>
                  </div>

                  <div class="event-card-action-bar">
                    ${!isCancelled ? `
                      <button class="btn btn-lime view-ticket-btn" data-reg="${reg.registration_id}" style="padding: 0.4rem 0.9rem; font-size: 0.8rem;">
                        <span class="material-symbols-outlined" style="font-size: 15px;">qr_code</span>
                        <span>Show QR Pass</span>
                      </button>
                      <button class="btn btn-ghost cancel-reg-btn" data-reg="${reg.registration_id}" style="padding: 0.4rem 0.9rem; font-size: 0.8rem; color: var(--status-closed);" title="Cancel Pass">
                        <span>Cancel</span>
                      </button>
                    ` : `
                      <span style="font-size: 0.8rem; color: var(--text-muted);">Pass Withdrawn</span>
                    `}
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        `}
      </div>
    `;

    // Attach Pass Modal Handlers
    document.querySelectorAll('.view-ticket-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        openTicketModal(btn.dataset.reg);
      });
    });

    // Attach Cancel Registration Handlers
    document.querySelectorAll('.cancel-reg-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Cancel this event registration? Your reserved seat will be released back to the event.')) {
          return;
        }

        try {
          const res = await api.post('backend/registrations/cancel.php', {
            registration_id: btn.dataset.reg
          });
          showToast(res.message || 'Pass cancelled.', 'info');
          setTimeout(() => initStudentDashboard(), 600);
        } catch (err) {
          showToast(err.message || 'Failed to cancel pass.', 'error');
        }
      });
    });

  } catch (e) {
    container.innerHTML = `
      <div style="text-align: center; padding: 4rem; color: var(--status-closing);">
        Failed to load portal: ${e.message}
      </div>
    `;
  }
}

// ============================================================================
// ADMINISTRATIVE CONSOLE (admin.html)
// ============================================================================

export async function initAdminConsole() {
  const container = document.getElementById('admin-console-content');
  if (!container) return;

  const status = await api.checkStatus();
  if (!status.authenticated || status.role !== 'admin') {
    window.location.href = 'login.html?redirect=admin.html';
    return;
  }

  let activeTab = 'events';

  async function renderAdmin() {
    container.innerHTML = `
      <div style="text-align: center; padding: 4rem 0;">
        <span class="material-symbols-outlined" style="font-size: 36px; color: var(--gold-primary); animation: spin 1s infinite linear;">sync</span>
        <p style="color: var(--text-muted); margin-top: 1rem;">Querying CEMS relational schema...</p>
      </div>
    `;

    try {
      const [metricsRes, eventsRes, studentsRes, venuesRes, regsRes, deptsRes] = await Promise.all([
        api.get('backend/reports/dashboard.php'),
        api.get('backend/events/list.php'),
        api.get('backend/students/list.php'),
        api.get('backend/venues/list.php'),
        api.get('backend/registrations/list.php'),
        api.get('backend/departments/list.php')
      ]);

      const stats         = metricsRes.data;
      const events        = eventsRes.data || [];
      const students      = studentsRes.data || [];
      const venues        = venuesRes.data || [];
      const registrations = regsRes.data || [];
      const departments   = deptsRes.data || [];

      container.innerHTML = `
        <!-- Top Metrics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
          
          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; backdrop-filter: blur(10px);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
              <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Total Students</span>
              <span class="material-symbols-outlined" style="color: var(--blue-primary); font-size: 20px;">group</span>
            </div>
            <div style="font-family: var(--font-display); font-size: 2.2rem; font-weight: 700; color: var(--text-primary);">
              ${stats.totalStudents}
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.3rem;">
              Enrolled across ${stats.totalDepartments} departments
            </div>
          </div>

          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; backdrop-filter: blur(10px);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
              <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Campus Events</span>
              <span class="material-symbols-outlined" style="color: var(--gold-primary); font-size: 20px;">event</span>
            </div>
            <div style="font-family: var(--font-display); font-size: 2.2rem; font-weight: 700; color: var(--text-primary);">
              ${stats.totalEvents}
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.3rem;">
              Avg. Capacity: ${stats.averageEventCapacity} seats
            </div>
          </div>

          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; backdrop-filter: blur(10px);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
              <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Active Registrations</span>
              <span class="material-symbols-outlined" style="color: var(--status-open); font-size: 20px;">how_to_reg</span>
            </div>
            <div style="font-family: var(--font-display); font-size: 2.2rem; font-weight: 700; color: var(--text-primary);">
              ${stats.totalRegistrations}
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.3rem;">
              Total records: ${stats.totalAllRegistrations}
            </div>
          </div>

          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.5rem; backdrop-filter: blur(10px);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
              <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Venues Mapped</span>
              <span class="material-symbols-outlined" style="color: var(--status-closing); font-size: 20px;">meeting_room</span>
            </div>
            <div style="font-family: var(--font-display); font-size: 2.2rem; font-weight: 700; color: var(--text-primary);">
              ${stats.totalVenues}
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.3rem;">
              Total seats: ${stats.totalCampusCapacity}
            </div>
          </div>

        </div>

        <!-- Action Bar & Tabs -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
          <div style="display: flex; gap: 0.5rem; background: rgba(2, 4, 9, 0.6); padding: 0.35rem; border-radius: var(--radius-full); border: 1px solid var(--border-subtle); overflow-x: auto; max-width: 100%;">
            <button class="btn btn-ghost admin-tab ${activeTab === 'events' ? 'active' : ''}" data-tab="events" style="font-size: 0.82rem; padding: 0.45rem 0.9rem; border-radius: var(--radius-full);">
              <span class="material-symbols-outlined" style="font-size: 16px;">event</span>
              <span>Events (${events.length})</span>
            </button>
            <button class="btn btn-ghost admin-tab ${activeTab === 'registrations' ? 'active' : ''}" data-tab="registrations" style="font-size: 0.82rem; padding: 0.45rem 0.9rem; border-radius: var(--radius-full);">
              <span class="material-symbols-outlined" style="font-size: 16px;">how_to_reg</span>
              <span>Registrations (${registrations.length})</span>
            </button>
            <button class="btn btn-ghost admin-tab ${activeTab === 'students' ? 'active' : ''}" data-tab="students" style="font-size: 0.82rem; padding: 0.45rem 0.9rem; border-radius: var(--radius-full);">
              <span class="material-symbols-outlined" style="font-size: 16px;">school</span>
              <span>Students (${students.length})</span>
            </button>
            <button class="btn btn-ghost admin-tab ${activeTab === 'venues' ? 'active' : ''}" data-tab="venues" style="font-size: 0.82rem; padding: 0.45rem 0.9rem; border-radius: var(--radius-full);">
              <span class="material-symbols-outlined" style="font-size: 16px;">meeting_room</span>
              <span>Venues (${venues.length})</span>
            </button>
            <button class="btn btn-ghost admin-tab ${activeTab === 'departments' ? 'active' : ''}" data-tab="departments" style="font-size: 0.82rem; padding: 0.45rem 0.9rem; border-radius: var(--radius-full);">
              <span class="material-symbols-outlined" style="font-size: 16px;">account_tree</span>
              <span>Departments (${departments.length})</span>
            </button>
            <button class="btn btn-ghost admin-tab ${activeTab === 'reports' ? 'active' : ''}" data-tab="reports" style="font-size: 0.82rem; padding: 0.45rem 0.9rem; border-radius: var(--radius-full);">
              <span class="material-symbols-outlined" style="font-size: 16px;">analytics</span>
              <span>Reports</span>
            </button>
            <a href="admin/database-demo.php" class="btn btn-ghost" style="font-size: 0.82rem; padding: 0.45rem 0.9rem; border-radius: var(--radius-full); color: var(--gold-primary);">
              <span class="material-symbols-outlined" style="font-size: 16px;">database</span>
              <span>DBMS Demonstration &rarr;</span>
            </a>
          </div>

          <div style="display: flex; gap: 0.75rem;">
            <button id="admin-create-btn" class="btn btn-primary" style="font-size: 0.82rem; padding: 0.5rem 1rem;">
              <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
              <span>Add ${capitalize(activeTab.slice(0, -1))}</span>
            </button>
          </div>
        </div>

        <!-- Tab Content Area -->
        <div id="admin-tab-content">
          ${renderTabTable(activeTab, { events, students, venues, registrations, departments, stats })}
        </div>
      `;

      // Tab switcher
      document.querySelectorAll('.admin-tab').forEach(btn => {
        btn.addEventListener('click', () => {
          activeTab = btn.dataset.tab;
          renderAdmin();
        });
      });

      // Add Button Handler
      document.getElementById('admin-create-btn')?.addEventListener('click', () => {
        if (activeTab === 'events') openCreateEventModal(venues);
        else if (activeTab === 'students') openCreateStudentModal(departments);
        else if (activeTab === 'venues') openCreateVenueModal();
        else if (activeTab === 'departments') openCreateDepartmentModal();
        else showToast('Select an entity tab to create records.', 'info');
      });

      // Delete handlers
      attachActionListeners();

    } catch (err) {
      container.innerHTML = `
        <div style="text-align: center; padding: 4rem; color: var(--status-closing);">
          Administrative query failure: ${err.message}
        </div>
      `;
    }
  }

  function renderTabTable(tab, data) {
    if (tab === 'events') {
      return `
        <div class="cems-table-container">
          <table class="cems-table">
            <thead>
              <tr>
                <th>Event ID</th>
                <th>Title</th>
                <th>Schedule &amp; Venue</th>
                <th>Enrolled / Quota</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${data.events.length === 0 ? `
                <tr><td colspan="6" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No records available.</td></tr>
              ` : data.events.map(e => `
                <tr>
                  <td><strong style="font-family: var(--font-display); color: var(--gold-primary);">#${e.event_id}</strong></td>
                  <td>
                    <div style="font-weight: 600; color: var(--text-primary);">${escapeHtml(e.title)}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">${(e.categories || []).map(c => c.category_name).join(', ')}</div>
                  </td>
                  <td>
                    <div>${escapeHtml(e.date)}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">${escapeHtml(e.venue ? e.venue.venue_name : 'Unassigned')}</div>
                  </td>
                  <td>
                    <strong>${e.registered_count}</strong> / ${e.max_capacity}
                  </td>
                  <td>
                    <span class="event-badge ${e.status === 'UPCOMING' ? 'badge-open' : 'badge-closing'}">${e.status}</span>
                  </td>
                  <td>
                    <div style="display: flex; gap: 0.35rem;">
                      <a href="event-details.html?id=${e.event_id}" class="btn btn-ghost" style="padding: 0.35rem;" title="View Record">
                        <span class="material-symbols-outlined" style="font-size: 18px;">visibility</span>
                      </a>
                      <button class="btn btn-ghost delete-record-btn" data-type="event" data-id="${e.event_id}" style="padding: 0.35rem; color: var(--status-closing);" title="Delete Event">
                        <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                      </button>
                    </div>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    if (tab === 'registrations') {
      return `
        <div class="cems-table-container">
          <table class="cems-table">
            <thead>
              <tr>
                <th>Registration ID</th>
                <th>Ticket Token</th>
                <th>Student Attendee</th>
                <th>Event Title</th>
                <th>Registration Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${data.registrations.length === 0 ? `
                <tr><td colspan="7" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No records available.</td></tr>
              ` : data.registrations.map(r => `
                <tr>
                  <td><strong style="font-family: var(--font-display); color: var(--gold-primary);">#${r.registration_id}</strong></td>
                  <td><code>${escapeHtml(r.ticket_token)}</code></td>
                  <td>
                    <div style="font-weight: 600; color: var(--text-primary);">${escapeHtml(r.student ? r.student.name : '#' + r.student_id)}</div>
                    <div style="font-size: 0.76rem; color: var(--text-muted);">${escapeHtml(r.student && r.student.department ? r.student.department.dept_name : '')}</div>
                  </td>
                  <td>${escapeHtml(r.event ? r.event.title : '#' + r.event_id)}</td>
                  <td>${r.registration_date}</td>
                  <td><span class="event-badge ${r.status === 'CONFIRMED' ? 'badge-open' : 'badge-closed'}">${r.status}</span></td>
                  <td>
                    <div style="display: flex; gap: 0.35rem;">
                      <button class="btn btn-secondary view-ticket-btn" data-reg="${r.registration_id}" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Pass">
                        <span class="material-symbols-outlined" style="font-size: 15px;">qr_code</span>
                      </button>
                      <button class="btn btn-ghost delete-record-btn" data-type="registration" data-id="${r.registration_id}" style="padding: 0.25rem; color: var(--status-closing);" title="Delete Registration">
                        <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                      </button>
                    </div>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    if (tab === 'students') {
      return `
        <div class="cems-table-container">
          <table class="cems-table">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Email &amp; Phone</th>
                <th>Department</th>
                <th>Semester</th>
                <th>Confirmed Passes</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${data.students.length === 0 ? `
                <tr><td colspan="7" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No records available.</td></tr>
              ` : data.students.map(s => `
                <tr>
                  <td><strong style="font-family: var(--font-display); color: var(--gold-primary);">#${s.student_id}</strong></td>
                  <td><strong style="color: var(--text-primary);">${escapeHtml(s.name)}</strong></td>
                  <td>
                    <div>${escapeHtml(s.email)}</div>
                    <div style="font-size: 0.76rem; color: var(--text-muted);">${escapeHtml(s.phone)}</div>
                  </td>
                  <td>${escapeHtml(s.department_name)}</td>
                  <td>Semester ${s.semester}</td>
                  <td><span class="event-badge badge-open">${s.confirmed_registrations || 0} passes</span></td>
                  <td>
                    <button class="btn btn-ghost delete-record-btn" data-type="student" data-id="${s.student_id}" style="padding: 0.35rem; color: var(--status-closing);" title="Delete Student Record">
                      <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                    </button>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    if (tab === 'venues') {
      return `
        <div class="cems-table-container">
          <table class="cems-table">
            <thead>
              <tr>
                <th>Venue ID</th>
                <th>Facility Name</th>
                <th>Location</th>
                <th>Seating Capacity</th>
                <th>Events Hosted</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${data.venues.length === 0 ? `
                <tr><td colspan="6" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No records available.</td></tr>
              ` : data.venues.map(v => `
                <tr>
                  <td><strong style="font-family: var(--font-display); color: var(--gold-primary);">#${v.venue_id}</strong></td>
                  <td><strong style="color: var(--text-primary);">${escapeHtml(v.venue_name)}</strong></td>
                  <td>${escapeHtml(v.location)}</td>
                  <td><strong>${v.capacity}</strong> seats</td>
                  <td><span class="event-badge badge-open">${v.events_count} events</span></td>
                  <td>
                    <button class="btn btn-ghost delete-record-btn" data-type="venue" data-id="${v.venue_id}" style="padding: 0.35rem; color: var(--status-closing);" title="Delete Venue">
                      <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                    </button>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    if (tab === 'departments') {
      return `
        <div class="cems-table-container">
          <table class="cems-table">
            <thead>
              <tr>
                <th>Department ID</th>
                <th>Department Name</th>
                <th>Enrolled Students</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${data.departments.length === 0 ? `
                <tr><td colspan="4" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No records available.</td></tr>
              ` : data.departments.map(d => `
                <tr>
                  <td><strong style="font-family: var(--font-display); color: var(--gold-primary);">#${d.department_id}</strong></td>
                  <td><strong style="color: var(--text-primary);">${escapeHtml(d.department_name)}</strong></td>
                  <td><strong>${d.student_count}</strong> students</td>
                  <td>
                    <button class="btn btn-ghost delete-record-btn" data-type="department" data-id="${d.department_id}" style="padding: 0.35rem; color: var(--status-closing);" title="Delete Department">
                      <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                    </button>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    if (tab === 'reports') {
      return `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;" class="reports-grid">
          
          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.75rem;">
            <h4 style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
              <span class="material-symbols-outlined" style="color: var(--gold-primary);">pie_chart</span>
              <span>Students by Department (Report 1)</span>
            </h4>
            <div id="report-dept-content" style="font-size: 0.84rem; color: var(--text-secondary);">
              Loading department statistics...
            </div>
          </div>

          <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.75rem;">
            <h4 style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
              <span class="material-symbols-outlined" style="color: var(--blue-primary);">bar_chart</span>
              <span>Event Capacity Utilization (Report 4)</span>
            </h4>
            <div id="report-events-content" style="font-size: 0.84rem; color: var(--text-secondary);">
              Loading event utilization statistics...
            </div>
          </div>

        </div>
      `;
    }

    return '';
  }

  function attachActionListeners() {
    // Ticket view
    document.querySelectorAll('.view-ticket-btn').forEach(btn => {
      btn.addEventListener('click', () => openTicketModal(btn.dataset.reg));
    });

    // Delete record buttons
    document.querySelectorAll('.delete-record-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const type = btn.dataset.type;
        const id   = btn.dataset.id;

        if (!confirm(`Are you sure you want to permanently delete this ${type} record (#${id}) from MySQL?`)) {
          return;
        }

        let endpoint = '';
        let payload  = {};

        if (type === 'event') {
          endpoint = 'backend/events/delete.php';
          payload = { event_id: id };
        } else if (type === 'student') {
          endpoint = 'backend/students/delete.php';
          payload = { student_id: id };
        } else if (type === 'venue') {
          endpoint = 'backend/venues/delete.php';
          payload = { venue_id: id };
        } else if (type === 'department') {
          endpoint = 'backend/departments/delete.php';
          payload = { department_id: id };
        } else if (type === 'registration') {
          endpoint = 'backend/registrations/delete.php';
          payload = { registration_id: id };
        }

        try {
          const res = await api.post(endpoint, payload);
          showToast(res.message || 'Record deleted successfully.', 'success');
          setTimeout(() => renderAdmin(), 500);
        } catch (err) {
          showToast(err.message || 'Failed to delete record.', 'error');
        }
      });
    });

    // If on reports tab, load report queries
    if (activeTab === 'reports') {
      loadReportsData();
    }
  }

  async function loadReportsData() {
    try {
      const [deptStats, evtStats] = await Promise.all([
        api.get('backend/reports/department_statistics.php'),
        api.get('backend/reports/event_statistics.php')
      ]);

      const deptDiv = document.getElementById('report-dept-content');
      if (deptDiv) {
        deptDiv.innerHTML = `
          <table class="cems-table" style="font-size:0.8rem;">
            <thead><tr><th>Department</th><th>Students</th><th>% Share</th></tr></thead>
            <tbody>
              ${(deptStats.data || []).map(d => `
                <tr>
                  <td><strong>${escapeHtml(d.department_name)}</strong></td>
                  <td>${d.student_count}</td>
                  <td><span class="event-badge badge-open">${d.student_percentage}%</span></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        `;
      }

      const evtDiv = document.getElementById('report-events-content');
      if (evtDiv) {
        evtDiv.innerHTML = `
          <table class="cems-table" style="font-size:0.8rem;">
            <thead><tr><th>Event</th><th>Capacity</th><th>Enrolled</th><th>Occupancy</th></tr></thead>
            <tbody>
              ${(evtStats.data || []).map(e => `
                <tr>
                  <td><strong>${escapeHtml(e.event_name)}</strong></td>
                  <td>${e.max_capacity}</td>
                  <td>${e.enrolled_count}</td>
                  <td><span class="event-badge ${e.occupancy_percentage > 85 ? 'badge-closing' : 'badge-open'}">${e.occupancy_percentage}%</span></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        `;
      }
    } catch (e) {
      console.error(e);
    }
  }

  // Modals for Create Operations
  function openCreateEventModal(venues) {
    createGenericModal('Insert New Campus Event (MySQL)', `
      <form id="create-event-form">
        <div style="margin-bottom: 1rem;">
          <label class="auth-label">Event Title</label>
          <input type="text" id="ev-name" class="auth-input" placeholder="e.g. NATIONAL ROBOTICS SYMPOSIUM" required />
        </div>
        <div style="margin-bottom: 1rem;">
          <label class="auth-label">Description</label>
          <textarea id="ev-desc" class="auth-input" rows="3" placeholder="Overview and agenda..."></textarea>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
          <div>
            <label class="auth-label">Date (YYYY-MM-DD)</label>
            <input type="date" id="ev-date" class="auth-input" required />
          </div>
          <div>
            <label class="auth-label">Time Slot</label>
            <input type="text" id="ev-time" class="auth-input" value="10:00 AM - 04:00 PM" required />
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
          <div>
            <label class="auth-label">Assigned Venue (FK)</label>
            <select id="ev-venue" class="auth-input" required>
              ${venues.map(v => `<option value="${v.venue_id}">${escapeHtml(v.venue_name)} (Max: ${v.capacity})</option>`).join('')}
            </select>
          </div>
          <div>
            <label class="auth-label">Max Capacity (CHECK &gt; 0)</label>
            <input type="number" id="ev-capacity" class="auth-input" value="100" min="1" required />
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:1rem;">
          <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
          <button type="submit" class="btn btn-primary">Commit to MySQL</button>
        </div>
      </form>
    `, async (closeModal) => {
      document.getElementById('create-event-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
          const res = await api.post('backend/events/create.php', {
            event_name:   document.getElementById('ev-name').value,
            description:  document.getElementById('ev-desc').value,
            event_date:   document.getElementById('ev-date').value,
            event_time:   document.getElementById('ev-time').value,
            venue_id:     document.getElementById('ev-venue').value,
            max_capacity: document.getElementById('ev-capacity').value
          });
          showToast(res.message || 'Event created!', 'success');
          closeModal();
          renderAdmin();
        } catch (err) {
          showToast(err.message || 'Creation failed.', 'error');
        }
      });
    });
  }

  function openCreateStudentModal(departments) {
    createGenericModal('Enroll New Student (MySQL)', `
      <form id="create-student-form">
        <div style="margin-bottom: 1rem;">
          <label class="auth-label">Full Name</label>
          <input type="text" id="std-name" class="auth-input" placeholder="Student Name" required />
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
          <div>
            <label class="auth-label">Email (UNIQUE)</label>
            <input type="email" id="std-email" class="auth-input" placeholder="student@campus.edu" required />
          </div>
          <div>
            <label class="auth-label">Phone</label>
            <input type="tel" id="std-phone" class="auth-input" placeholder="+91 98765 00000" required />
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
          <div>
            <label class="auth-label">Academic Department (FK)</label>
            <select id="std-dept" class="auth-input" required>
              ${departments.map(d => `<option value="${d.department_id}">${escapeHtml(d.department_name)}</option>`).join('')}
            </select>
          </div>
          <div>
            <label class="auth-label">Semester (1 - 8)</label>
            <input type="number" id="std-sem" class="auth-input" value="4" min="1" max="8" required />
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:1rem;">
          <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
          <button type="submit" class="btn btn-primary">Commit to MySQL</button>
        </div>
      </form>
    `, async (closeModal) => {
      document.getElementById('create-student-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
          const res = await api.post('backend/students/create.php', {
            name:          document.getElementById('std-name').value,
            email:         document.getElementById('std-email').value,
            phone:         document.getElementById('std-phone').value,
            department_id: document.getElementById('std-dept').value,
            semester:      document.getElementById('std-sem').value
          });
          showToast(res.message || 'Student enrolled!', 'success');
          closeModal();
          renderAdmin();
        } catch (err) {
          showToast(err.message || 'Creation failed.', 'error');
        }
      });
    });
  }

  function openCreateVenueModal() {
    createGenericModal('Create Campus Facility (VENUE)', `
      <form id="create-venue-form">
        <div style="margin-bottom: 1rem;">
          <label class="auth-label">Venue Name (UNIQUE)</label>
          <input type="text" id="ven-name" class="auth-input" placeholder="e.g. Science Complex Hall B" required />
        </div>
        <div style="margin-bottom: 1rem;">
          <label class="auth-label">Campus Location</label>
          <input type="text" id="ven-loc" class="auth-input" placeholder="e.g. Academic Block 2 Level 1" required />
        </div>
        <div style="margin-bottom: 1.5rem;">
          <label class="auth-label">Seating Capacity (CHECK &gt; 0)</label>
          <input type="number" id="ven-cap" class="auth-input" value="150" min="1" required />
        </div>
        <div style="display:flex;justify-content:flex-end;gap:1rem;">
          <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Venue</button>
        </div>
      </form>
    `, async (closeModal) => {
      document.getElementById('create-venue-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
          const res = await api.post('backend/venues/create.php', {
            venue_name: document.getElementById('ven-name').value,
            location:   document.getElementById('ven-loc').value,
            capacity:   document.getElementById('ven-cap').value
          });
          showToast(res.message || 'Venue created!', 'success');
          closeModal();
          renderAdmin();
        } catch (err) {
          showToast(err.message || 'Creation failed.', 'error');
        }
      });
    });
  }

  function openCreateDepartmentModal() {
    createGenericModal('Create Academic Department (DEPARTMENT)', `
      <form id="create-dept-form">
        <div style="margin-bottom: 1.5rem;">
          <label class="auth-label">Department Name (UNIQUE)</label>
          <input type="text" id="dept-name" class="auth-input" placeholder="e.g. Artificial Intelligence & Data Science" required />
        </div>
        <div style="display:flex;justify-content:flex-end;gap:1rem;">
          <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Department</button>
        </div>
      </form>
    `, async (closeModal) => {
      document.getElementById('create-dept-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
          const res = await api.post('backend/departments/create.php', {
            department_name: document.getElementById('dept-name').value
          });
          showToast(res.message || 'Department created!', 'success');
          closeModal();
          renderAdmin();
        } catch (err) {
          showToast(err.message || 'Creation failed.', 'error');
        }
      });
    });
  }

  function createGenericModal(title, formHtml, onMount) {
    let backdrop = document.querySelector('.cems-modal-backdrop');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.className = 'cems-modal-backdrop';
      document.body.appendChild(backdrop);
    }

    backdrop.innerHTML = `
      <div class="cems-modal" style="max-width: 580px; padding: 2.25rem;">
        <button class="modal-close-btn" id="generic-modal-close">
          <span class="material-symbols-outlined">close</span>
        </button>
        <h3 style="font-family: var(--font-display); font-size: 1.45rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text-primary);">
          ${escapeHtml(title)}
        </h3>
        ${formHtml}
      </div>
    `;

    backdrop.classList.add('open');
    const close = () => backdrop.classList.remove('open');

    document.getElementById('generic-modal-close')?.addEventListener('click', close);
    backdrop.querySelectorAll('.close-modal-btn').forEach(b => b.addEventListener('click', close));

    if (onMount) onMount(close);
  }

  function capitalize(s) {
    if (!s) return '';
    return s.charAt(0).toUpperCase() + s.slice(1);
  }

  renderAdmin();
}

// Auto Initialize
function autoInitDashboard() {
  if (document.getElementById('student-dashboard-content')) {
    initStudentDashboard();
  }
  if (document.getElementById('admin-console-content')) {
    initAdminConsole();
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInitDashboard);
} else {
  autoInitDashboard();
}
