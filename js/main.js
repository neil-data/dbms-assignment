/**
 * CEMS - Main Application Logic & Central Coordinator
 * Connects frontend UI to real PHP backend and MySQL database.
 */

import { api } from './api.js';
import { initAnimations } from './animations.js';

export function showToast(message, type = 'info') {
  let toast = document.querySelector('.cems-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.className = 'cems-toast';
    document.body.appendChild(toast);
  }

  let iconName = 'info';
  let iconClass = 'toast-icon-info';
  if (type === 'success') {
    iconName = 'check_circle';
    iconClass = 'toast-icon-success';
  } else if (type === 'error') {
    iconName = 'error';
    iconClass = 'toast-icon-error';
  }

  toast.innerHTML = `
    <span class="material-symbols-outlined ${iconClass}">${iconName}</span>
    <span class="toast-text">${message}</span>
  `;

  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
  }, 4000);
}

/**
 * Update the footer database status beacon dynamically
 */
export async function updateDatabaseStatusUI() {
  const indicators = document.querySelectorAll('.system-status-indicator');
  if (indicators.length === 0) return;

  const status = await api.checkStatus();

  indicators.forEach(ind => {
    const beacon = ind.querySelector('.status-beacon');
    const textSpan = ind.querySelector('span:last-child');

    if (status.database_connected) {
      if (beacon) {
        beacon.style.background = 'var(--status-open)';
        beacon.style.boxShadow = '0 0 10px rgba(16, 185, 129, 0.8)';
      }
      if (textSpan) {
        textSpan.textContent = 'Database: Connected (MySQL)';
        textSpan.style.color = 'var(--status-open)';
      }
    } else {
      if (beacon) {
        beacon.style.background = 'var(--status-closing)';
        beacon.style.boxShadow = '0 0 10px rgba(239, 68, 68, 0.8)';
      }
      if (textSpan) {
        textSpan.textContent = 'Database: Disconnected (Check MySQL)';
        textSpan.style.color = 'var(--status-closing)';
      }
    }
  });

  return status;
}

/**
 * Update Header session links according to active session
 */
export async function updateHeaderSessionUI() {
  const navActions = document.querySelector('.nav-actions');
  if (!navActions) return;

  const status = await api.checkStatus();

  if (status.authenticated && status.user) {
    if (status.user.role === 'student') {
      navActions.innerHTML = `
        <a href="student-dashboard.html" class="nav-auth-link" style="display:flex;align-items:center;gap:0.4rem;">
          <span class="material-symbols-outlined" style="font-size:18px;color:var(--gold-primary);">account_circle</span>
          <span>${status.user.name ? escapeHtml(status.user.name.split(' ')[0]) : 'Student Portal'}</span>
        </a>
        <a href="student-dashboard.html" class="btn btn-secondary" style="padding:0.5rem 1rem;font-size:0.84rem;">
          My Passes
        </a>
        <button id="logout-btn" class="btn btn-ghost" style="padding:0.5rem 0.75rem;font-size:0.84rem;" title="Sign Out">
          <span class="material-symbols-outlined" style="font-size:18px;">logout</span>
        </button>
      `;

      document.getElementById('logout-btn')?.addEventListener('click', async () => {
        try {
          await api.post('backend/auth/student_logout.php');
          showToast('Signed out of student session.', 'info');
          setTimeout(() => {
            window.location.href = 'index.html';
          }, 400);
        } catch (e) {
          window.location.reload();
        }
      });
    } else if (status.user.role === 'admin') {
      navActions.innerHTML = `
        <a href="admin.html" class="nav-auth-link" style="color:var(--gold-primary);display:flex;align-items:center;gap:0.4rem;">
          <span class="material-symbols-outlined" style="font-size:18px;">admin_panel_settings</span>
          <span>Admin (${escapeHtml(status.user.username)})</span>
        </a>
        <a href="admin.html" class="btn btn-secondary" style="padding:0.5rem 1rem;font-size:0.84rem;">
          Console
        </a>
        <button id="logout-btn" class="btn btn-ghost" style="padding:0.5rem 0.75rem;font-size:0.84rem;" title="Sign Out">
          <span class="material-symbols-outlined" style="font-size:18px;">logout</span>
        </button>
      `;

      document.getElementById('logout-btn')?.addEventListener('click', async () => {
        try {
          await api.post('backend/auth/admin_logout.php');
          showToast('Admin session terminated.', 'info');
          setTimeout(() => {
            window.location.href = 'index.html';
          }, 400);
        } catch (e) {
          window.location.reload();
        }
      });
    }
  } else {
    navActions.innerHTML = `
      <a href="login.html" class="nav-auth-link">Sign In</a>
      <a href="events.html" class="btn btn-primary btn-pill-arrow">
        <span>Explore Events</span>
        <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span>
      </a>
    `;
  }
}

/**
 * Open Digital Pass Modal
 */
export async function openTicketModal(registrationId) {
  let modalBackdrop = document.querySelector('.cems-modal-backdrop');
  if (!modalBackdrop) {
    modalBackdrop = document.createElement('div');
    modalBackdrop.className = 'cems-modal-backdrop';
    document.body.appendChild(modalBackdrop);
  }

  modalBackdrop.innerHTML = `
    <div class="cems-modal" style="text-align: center; padding: 3rem;">
      <span class="material-symbols-outlined" style="font-size: 36px; color: var(--gold-primary); animation: spin 1s infinite linear;">sync</span>
      <p style="color: var(--text-muted); margin-top: 1rem;">Querying pass from MySQL database...</p>
    </div>
  `;
  modalBackdrop.classList.add('open');

  try {
    const res = await api.get('backend/registrations/list.php');
    const registrations = res.data || [];
    const reg = registrations.find(r => String(r.registration_id) === String(registrationId));

    if (!reg) {
      modalBackdrop.classList.remove('open');
      showToast('Registration record not found in database.', 'error');
      return;
    }

    modalBackdrop.innerHTML = `
      <div class="cems-modal" id="ticket-modal">
        <button class="modal-close-btn" id="modal-close" aria-label="Close modal">
          <span class="material-symbols-outlined">close</span>
        </button>
        <div class="ticket-pass">
          <div class="ticket-header">
            <div class="ticket-institution">Institutional Event Access Pass • 2026</div>
            <h3 class="ticket-event-title">${escapeHtml(reg.event ? reg.event.title : 'Campus Event')}</h3>
            <p style="color:var(--gold-primary);font-size:0.88rem;margin-top:0.3rem;">Verified Institutional Credential</p>
          </div>

          <div class="ticket-qr-zone">
            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="100" height="100" fill="#ffffff" />
              <rect x="10" y="10" width="24" height="24" fill="#0f172a" />
              <rect x="14" y="14" width="16" height="16" fill="#ffffff" />
              <rect x="18" y="18" width="8" height="8" fill="#0f172a" />
              <rect x="66" y="10" width="24" height="24" fill="#0f172a" />
              <rect x="70" y="14" width="16" height="16" fill="#ffffff" />
              <rect x="74" y="18" width="8" height="8" fill="#0f172a" />
              <rect x="10" y="66" width="24" height="24" fill="#0f172a" />
              <rect x="14" y="70" width="16" height="16" fill="#ffffff" />
              <rect x="18" y="74" width="8" height="8" fill="#0f172a" />
              <rect x="40" y="12" width="6" height="6" fill="#0f172a" />
              <rect x="50" y="18" width="8" height="6" fill="#0f172a" />
              <rect x="42" y="28" width="6" height="6" fill="#0f172a" />
              <rect x="52" y="34" width="8" height="8" fill="#0f172a" />
              <rect x="22" y="44" width="8" height="6" fill="#0f172a" />
              <rect x="36" y="48" width="12" height="6" fill="#0f172a" />
              <rect x="54" y="48" width="6" height="10" fill="#0f172a" />
              <rect x="66" y="44" width="12" height="6" fill="#0f172a" />
              <rect x="80" y="52" width="8" height="8" fill="#0f172a" />
              <rect x="40" y="64" width="8" height="8" fill="#0f172a" />
              <rect x="52" y="72" width="10" height="6" fill="#0f172a" />
              <rect x="70" y="66" width="8" height="12" fill="#0f172a" />
              <rect x="82" y="74" width="6" height="12" fill="#0f172a" />
              <rect x="46" y="84" width="14" height="6" fill="#0f172a" />
            </svg>
            <div class="ticket-token-code">${escapeHtml(reg.ticket_token)}</div>
            <span style="font-size:0.75rem;color:#64748b;margin-top:0.2rem;">Verification Token</span>
          </div>

          <div class="ticket-details-grid">
            <div>
              <div class="ticket-field-label">Student Attendee</div>
              <div class="ticket-field-val">${escapeHtml(reg.student ? reg.student.name : '—')}</div>
            </div>
            <div>
              <div class="ticket-field-label">ID / Department</div>
              <div class="ticket-field-val">#${reg.student ? reg.student.student_id : '—'} &bull; ${escapeHtml(reg.student && reg.student.department ? reg.student.department.dept_name : '—')}</div>
            </div>
            <div>
              <div class="ticket-field-label">Event Schedule</div>
              <div class="ticket-field-val">${escapeHtml(reg.event ? (reg.event.display_date || reg.event.date) : '—')}</div>
            </div>
            <div>
              <div class="ticket-field-label">Assigned Venue</div>
              <div class="ticket-field-val">${escapeHtml(reg.event && reg.event.venue ? reg.event.venue.venue_name : '—')}</div>
            </div>
          </div>

          <div style="display:flex;gap:1rem;justify-content:center;">
            <button class="btn btn-primary" onclick="window.print()" style="font-size:0.85rem;">
              <span class="material-symbols-outlined">print</span>
              <span>Print Pass</span>
            </button>
            <button class="btn btn-secondary" id="modal-done-btn" style="font-size:0.85rem;">
              <span>Done</span>
            </button>
          </div>
        </div>
      </div>
    `;

    const close = () => modalBackdrop.classList.remove('open');
    document.getElementById('modal-close')?.addEventListener('click', close);
    document.getElementById('modal-done-btn')?.addEventListener('click', close);
    modalBackdrop.addEventListener('click', (e) => {
      if (e.target === modalBackdrop) close();
    });

  } catch (err) {
    modalBackdrop.classList.remove('open');
    showToast('Failed to load pass: ' + err.message, 'error');
  }
}

/**
 * Render Featured Events on Home Page
 */
export async function renderHomeFeaturedEvents() {
  const container = document.getElementById('featured-events-container');
  if (!container) return;

  try {
    const res = await api.get('backend/events/list.php', { featured: 1 });
    const events = res.data || [];

    if (events.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 4.5rem 2rem; background: var(--bg-card); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md); backdrop-filter: blur(12px);">
          <span class="material-symbols-outlined" style="font-size: 44px; color: var(--text-muted); margin-bottom: 1rem; display: inline-block;">event_busy</span>
          <h3 style="font-family: var(--font-display); font-size: 1.4rem; margin-bottom: 0.5rem; color: var(--text-primary);">No records available</h3>
          <p style="color: var(--text-secondary); max-width: 480px; margin: 0 auto 1.75rem; font-size: 0.94rem; line-height: 1.6;">
            Live campus events will appear here once committed to the MySQL database.
          </p>
          <a href="events.html" class="btn btn-secondary" style="font-size: 0.88rem;">
            <span class="material-symbols-outlined" style="font-size: 18px;">manage_search</span>
            <span>View Events Catalog</span>
          </a>
        </div>
      `;
      return;
    }

    container.innerHTML = events.map((event, i) => {
      const badgeClass = event.status === 'UPCOMING' ? 'badge-open' : event.status === 'ONGOING' ? 'badge-closing' : 'badge-closed';
      return `
        <div class="event-card" id="card-${event.event_id}">
          <div class="event-card-top">
            <span class="event-number">0${i + 1}</span>
            <span class="event-badge ${badgeClass}">${event.ui_status || event.status}</span>
          </div>
          <div>
            <h3 class="event-card-title">${escapeHtml(event.title)}</h3>
            <p class="event-card-desc">${escapeHtml(event.description || '')}</p>
          </div>
          <div>
            <div class="event-meta-list">
              <div class="event-meta-item">
                <span class="material-symbols-outlined">calendar_today</span>
                <span><strong>${escapeHtml(event.display_date || event.date)}</strong></span>
              </div>
              <div class="event-meta-item">
                <span class="material-symbols-outlined">pin_drop</span>
                <span>${escapeHtml(event.venue ? event.venue.venue_name : 'Campus Venue')}</span>
              </div>
            </div>
            <div class="event-card-bottom">
              <div class="seat-indicator">
                <strong>${event.available_seats}</strong> of ${event.max_capacity} seats remaining
              </div>
              <a href="event-details.html?id=${event.event_id}" class="event-view-link">
                <span>Details</span>
                <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span>
              </a>
            </div>
          </div>
        </div>
      `;
    }).join('');
  } catch (e) {
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: var(--bg-card); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md);">
        <p style="color: var(--text-secondary); font-size: 0.92rem;">Awaiting database connection to load featured events.</p>
      </div>
    `;
  }
}

/**
 * Render Categories List on Home Page
 */
export async function renderHomeCategories() {
  const container = document.getElementById('categories-container');
  if (!container) return;

  try {
    const res = await api.get('backend/categories/list.php');
    const categories = res.data || [];

    if (categories.length === 0) {
      container.innerHTML = `
        <div style="text-align: center; padding: 3rem 1.5rem; background: var(--bg-card); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md);">
          <p style="color: var(--text-secondary); font-size: 0.92rem;">No records available.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = categories.map((cat, idx) => {
      return `
        <div class="category-row" onclick="window.location.href='events.html?category=${cat.slug}'">
          <div class="cat-left">
            <span class="cat-num">0${idx + 1}</span>
            <span class="cat-name">${escapeHtml(cat.category_name.toUpperCase())}</span>
          </div>
          <div class="cat-right">
            <span class="cat-count" style="color: var(--text-muted); font-size: 0.84rem;">${cat.events_count || 0} Events</span>
            <div class="cat-arrow">
              <span class="material-symbols-outlined">arrow_forward</span>
            </div>
          </div>
        </div>
      `;
    }).join('');
  } catch (e) {
    container.innerHTML = `
      <div style="text-align: center; padding: 3rem; background: var(--bg-card); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md);">
        <p style="color: var(--text-secondary); font-size: 0.92rem;">Awaiting database connection to load categories.</p>
      </div>
    `;
  }
}

export function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Global App Initialization
document.addEventListener('DOMContentLoaded', () => {
  initAnimations();
  updateDatabaseStatusUI();
  updateHeaderSessionUI();

  if (document.getElementById('featured-events-container')) {
    renderHomeFeaturedEvents();
  }
  if (document.getElementById('categories-container')) {
    renderHomeCategories();
  }
});
