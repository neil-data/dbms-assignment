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
 * Global App Initialization
 */
document.addEventListener('DOMContentLoaded', () => {
  setupHeaderScroll();
  setupMobileNav();
  updateHeaderSessionUI();
  initAnimations();
});
