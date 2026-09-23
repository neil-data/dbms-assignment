/**
 * CEMS - Main Application Logic & Global UI Coordinator
 * Dark Editorial Architecture with Real Authentication & Database Sync.
 */

import { api } from './api.js';
import { initAnimations } from './animations.js';

/**
 * Global Toast Notification (Dark Editorial Pill)
 */
export function showToast(message, type = 'info') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  let iconName = 'info';
  let borderCol = 'var(--border-medium)';
  if (type === 'success') {
    iconName = 'check_circle';
    borderCol = 'var(--lime-accent)';
  } else if (type === 'error') {
    iconName = 'error';
    borderCol = 'var(--status-closed)';
  } else if (type === 'warning') {
    iconName = 'warning';
    borderCol = 'var(--status-closing)';
  }

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.style.borderLeftColor = borderCol;
  toast.innerHTML = `
    <span class="material-symbols-outlined" style="font-size: 18px; color: ${borderCol};">${iconName}</span>
    <span style="font-size: 0.86rem; color: var(--text-primary); font-weight: 500;">${escapeHtml(message)}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 350);
  }, 4000);
}

/**
 * Update Header session links according to active session
 */
export async function updateHeaderSessionUI() {
  const navActions = document.querySelector('.nav-actions');
  const mobileDrawer = document.getElementById('mobile-drawer-links');
  if (!navActions) return;

  const status = await api.checkStatus();

  if (status.authenticated && status.user) {
    if (status.user.role === 'student') {
      const studentFirstName = status.user.name ? escapeHtml(status.user.name.split(' ')[0]) : 'Student';
      navActions.innerHTML = `
        <button class="nav-icon-btn" id="global-search-btn" title="Search Clubs & Events" aria-label="Search">
          <span class="material-symbols-outlined" style="font-size: 19px;">search</span>
        </button>
        <a href="profile.html" class="nav-signin-link" style="display:flex;align-items:center;gap:0.4rem;" title="View Profile">
          <span class="material-symbols-outlined" style="font-size: 18px; color: var(--lime-accent);">account_circle</span>
          <span>${studentFirstName}</span>
        </a>
        <a href="student-dashboard.html" class="btn btn-lime" style="padding: 0.5rem 1.15rem; font-size: 0.82rem;">
          <span>Campus Hub</span>
        </a>
        <button id="logout-btn" class="nav-icon-btn" title="Sign Out" aria-label="Sign Out">
          <span class="material-symbols-outlined" style="font-size: 19px;">logout</span>
        </button>
      `;

      if (mobileDrawer) {
        mobileDrawer.innerHTML += `
          <li style="border-top: 1px solid var(--border-subtle); padding-top: 1.5rem; margin-top: 1rem;">
            <a href="student-dashboard.html" class="mobile-nav-link" style="color: var(--lime-accent);">Campus Hub (${studentFirstName})</a>
          </li>
          <li><a href="profile.html" class="mobile-nav-link">Student Profile</a></li>
          <li><a href="#" id="mobile-logout-btn" class="mobile-nav-link" style="color: var(--status-closed);">Sign Out</a></li>
        `;
        document.getElementById('mobile-logout-btn')?.addEventListener('click', handleLogout);
      }

      document.getElementById('logout-btn')?.addEventListener('click', handleLogout);

    } else if (status.user.role === 'admin') {
      navActions.innerHTML = `
        <button class="nav-icon-btn" id="global-search-btn" title="Search Clubs & Events" aria-label="Search">
          <span class="material-symbols-outlined" style="font-size: 19px;">search</span>
        </button>
        <a href="admin.html" class="nav-signin-link" style="color: var(--lime-accent); display:flex; align-items:center; gap:0.4rem;">
          <span class="material-symbols-outlined" style="font-size: 18px;">admin_panel_settings</span>
          <span>Admin</span>
        </a>
        <a href="admin.html" class="btn btn-lime" style="padding: 0.5rem 1.15rem; font-size: 0.82rem;">
          <span>Admin Console</span>
        </a>
        <button id="logout-btn" class="nav-icon-btn" title="Sign Out" aria-label="Sign Out">
          <span class="material-symbols-outlined" style="font-size: 19px;">logout</span>
        </button>
      `;

      if (mobileDrawer) {
        mobileDrawer.innerHTML += `
          <li style="border-top: 1px solid var(--border-subtle); padding-top: 1.5rem; margin-top: 1rem;">
            <a href="admin.html" class="mobile-nav-link" style="color: var(--lime-accent);">Admin Console</a>
          </li>
          <li><a href="#" id="mobile-logout-btn" class="mobile-nav-link" style="color: var(--status-closed);">Sign Out</a></li>
        `;
        document.getElementById('mobile-logout-btn')?.addEventListener('click', handleLogout);
      }

      document.getElementById('logout-btn')?.addEventListener('click', handleLogout);
    }
  } else {
    navActions.innerHTML = `
      <button class="nav-icon-btn" id="global-search-btn" title="Search Clubs & Events" aria-label="Search">
        <span class="material-symbols-outlined" style="font-size: 19px;">search</span>
      </button>
      <a href="login.html" class="nav-signin-link">Sign In</a>
      <a href="register.html" class="btn btn-lime" style="padding: 0.5rem 1.15rem; font-size: 0.82rem;">
        <span>Join CEMS &rarr;</span>
      </a>
    `;
  }

  // Setup search modal trigger
  document.getElementById('global-search-btn')?.addEventListener('click', () => {
    window.location.href = 'clubs.html';
  });
}

async function handleLogout(e) {
  e.preventDefault();
  try {
    const status = await api.checkStatus();
    if (status.role === 'admin') {
      await api.post('backend/auth/admin_logout.php');
    } else {
      await api.post('backend/auth/student_logout.php');
    }
    showToast('Signed out successfully.', 'info');
    setTimeout(() => {
      window.location.href = 'index.html';
    }, 400);
  } catch (err) {
    window.location.reload();
  }
}

/**
 * Setup Mobile Navigation Drawer & Backdrop
 */
export function setupMobileNav() {
  const toggleBtn = document.querySelector('.mobile-nav-toggle');
  const drawer = document.querySelector('.mobile-nav-drawer');
  const closeBtn = document.querySelector('.mobile-nav-close');
  let backdrop = document.querySelector('.mobile-drawer-backdrop');

  if (!toggleBtn || !drawer) return;

  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'mobile-drawer-backdrop';
    document.body.appendChild(backdrop);
  }

  function openDrawer() {
    drawer.classList.add('open');
    backdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    drawer.classList.remove('open');
    backdrop.classList.remove('active');
    document.body.style.overflow = '';
  }

  toggleBtn.addEventListener('click', openDrawer);
  closeBtn?.addEventListener('click', closeDrawer);
  backdrop.addEventListener('click', closeDrawer);
}

/**
 * Setup Sticky Header Background on Scroll
 */
export function setupHeaderScroll() {
  const header = document.querySelector('.cems-header');
  if (!header) return;

  function onScroll() {
    if (window.scrollY > 20) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

/**
 * HTML String Sanitizer
 */
export function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Interactive Digital Ticket / QR Pass Modal
 */
export async function openTicketModal(registrationId) {
  let modalBackdrop = document.getElementById('ticket-modal-backdrop');
  if (!modalBackdrop) {
    modalBackdrop = document.createElement('div');
    modalBackdrop.id = 'ticket-modal-backdrop';
    modalBackdrop.className = 'modal-backdrop';
    document.body.appendChild(modalBackdrop);
  }

  modalBackdrop.innerHTML = `
    <div class="modal-dialog" style="max-width: 480px; text-align: center; padding: 2.5rem; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg);">
      <span class="material-symbols-outlined" style="font-size: 32px; color: var(--lime-accent); animation: spin 1s infinite linear;">sync</span>
      <p style="color: var(--text-muted); margin-top: 1rem; font-size: 0.9rem;">Retrieving digital pass...</p>
    </div>
  `;
  modalBackdrop.classList.add('open');

  try {
    const res = await api.get('backend/registrations/list.php?my=1');
    const registrations = res.data || [];
    const reg = registrations.find(r => String(r.registration_id) === String(registrationId));

    if (!reg) {
      modalBackdrop.classList.remove('open');
      showToast('Registration pass record not found.', 'error');
      return;
    }

    const token = reg.ticket_token || `CEMS-PASS-${String(reg.registration_id).padStart(6, '0')}`;
    const studentName = reg.student ? reg.student.name : 'Student Attendee';
    const deptName = reg.student && reg.student.department ? reg.student.department.dept_name : 'Academic Dept';
    const eventTitle = reg.event ? reg.event.title : 'Campus Event';
    const eventDate = reg.event ? (reg.event.display_date || reg.event.date) : 'TBA';
    const venueName = reg.event && reg.event.venue ? reg.event.venue.venue_name : 'Campus Venue';

    modalBackdrop.innerHTML = `
      <div class="modal-dialog" style="max-width: 480px; text-align: center; padding: 2.5rem 2rem; background: var(--bg-surface); border: 1px solid var(--lime-border); border-radius: var(--radius-lg); position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.6);">
        <button id="modal-close" style="position: absolute; top: 1.25rem; right: 1.25rem; background: transparent; border: none; color: var(--text-muted); cursor: pointer;" aria-label="Close">
          <span class="material-symbols-outlined" style="font-size: 22px;">close</span>
        </button>

        <span class="eyebrow-pill" style="margin-bottom: 1rem;">DIGITAL ADMISSION PASS</span>
        
        <h2 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.35rem;">
          ${escapeHtml(eventTitle)}
        </h2>
        <p style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 1.75rem;">
          ${escapeHtml(eventDate)} &bull; ${escapeHtml(venueName)}
        </p>

        <div style="background: #ffffff; padding: 1.25rem; border-radius: 8px; display: inline-block; margin-bottom: 1.25rem;">
          <svg width="140" height="140" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" fill="white" />
            <rect x="10" y="10" width="24" height="24" rx="2" fill="#08090b" />
            <rect x="14" y="14" width="16" height="16" rx="1" fill="white" />
            <rect x="17" y="17" width="10" height="10" fill="#08090b" />
            
            <rect x="66" y="10" width="24" height="24" rx="2" fill="#08090b" />
            <rect x="70" y="14" width="16" height="16" rx="1" fill="white" />
            <rect x="73" y="17" width="10" height="10" fill="#08090b" />
            
            <rect x="10" y="66" width="24" height="24" rx="2" fill="#08090b" />
            <rect x="14" y="70" width="16" height="16" rx="1" fill="white" />
            <rect x="17" y="73" width="10" height="10" fill="#08090b" />

            <rect x="42" y="10" width="6" height="14" fill="#08090b" />
            <rect x="52" y="16" width="6" height="18" fill="#08090b" />
            <rect x="10" y="42" width="14" height="6" fill="#08090b" />
            <rect x="28" y="42" width="14" height="6" fill="#08090b" />
            <rect x="48" y="42" width="16" height="16" fill="#08090b" />
            <rect x="70" y="42" width="20" height="6" fill="#08090b" />
            <rect x="42" y="66" width="6" height="24" fill="#08090b" />
            <rect x="54" y="74" width="16" height="6" fill="#08090b" />
            <rect x="76" y="66" width="14" height="14" fill="#08090b" />
          </svg>
        </div>

        <div style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: var(--lime-accent); letter-spacing: 0.08em; margin-bottom: 0.25rem;">
          ${escapeHtml(token)}
        </div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.5rem;">
          PASS #${reg.registration_id} &bull; ${escapeHtml(studentName)} (${escapeHtml(deptName)})
        </div>

        <div style="display: flex; gap: 0.75rem; justify-content: center;">
          <button class="btn btn-lime" onclick="window.print()" style="font-size: 0.84rem; padding: 0.5rem 1.25rem;">
            <span class="material-symbols-outlined" style="font-size: 16px;">print</span>
            <span>Print Pass</span>
          </button>
          <button class="btn btn-ghost" id="modal-done-btn" style="font-size: 0.84rem; padding: 0.5rem 1.25rem;">
            <span>Done</span>
          </button>
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
 * Safe document ready utility for ES modules
 */
export function onReady(fn) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    // DOM is already parsed in deferred ES modules
    fn();
  }
}

/**
 * Global App Initialization
 */
function initGlobal() {
  setupHeaderScroll();
  setupMobileNav();
  updateHeaderSessionUI();
  initAnimations();
}

onReady(initGlobal);
