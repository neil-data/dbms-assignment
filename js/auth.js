/**
 * CEMS - Authentication Controller
 * Handles Student Registration, Student Login, and Admin Authentication via real PHP endpoints.
 */

import { api } from './api.js';
import { showToast } from './main.js';

export function initAuthPage() {
  const loginForm    = document.getElementById('cems-login-form');
  const registerForm = document.getElementById('cems-register-form');

  // ==========================================================================
  // LOGIN PAGE CONTROLLER
  // ==========================================================================
  if (loginForm) {
    const roleTabs = document.querySelectorAll('.auth-role-tab');
    let currentRole = 'student';

    roleTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        roleTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentRole = tab.dataset.role;

        const adminNotice = document.getElementById('admin-credential-notice');
        if (adminNotice) {
          adminNotice.style.display = currentRole === 'admin' ? 'block' : 'none';
        }

        const emailInput = document.getElementById('login-email');
        const emailLabel = document.querySelector('label[for="login-email"]');
        if (emailInput) {
          emailInput.placeholder = currentRole === 'admin' ? 'admin (or admin@campus.edu)' : 'aarav.sharma@campus.edu';
          if (emailLabel) {
            emailLabel.textContent = currentRole === 'admin' ? 'Admin Username or Email' : 'Institutional Email';
          }
        }
      });
    });

    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const emailOrUsername = document.getElementById('login-email')?.value.trim();
      const password = document.getElementById('login-password')?.value;
      const submitBtn = loginForm.querySelector('button[type="submit"]');

      if (!emailOrUsername || !password) {
        showToast('Please enter both your credentials and password.', 'error');
        return;
      }

      const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
          <span class="material-symbols-outlined" style="font-size:18px;animation:spin 1s infinite linear;">sync</span>
          <span>Verifying Credentials...</span>
        `;
      }

      try {
        if (currentRole === 'student') {
          const res = await api.post('backend/auth/student_login.php', {
            email: emailOrUsername,
            password: password
          });

          showToast(res.message || 'Authenticated successfully.', 'success');
          setTimeout(() => {
            window.location.href = 'student-dashboard.html';
          }, 600);
        } else {
          const res = await api.post('backend/auth/admin_login.php', {
            username: emailOrUsername,
            password: password
          });

          showToast(res.message || 'Admin authorization granted.', 'success');
          setTimeout(() => {
            window.location.href = 'admin.html';
          }, 600);
        }
      } catch (err) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnHtml;
        }
        showToast(err.message || 'Authentication failed. Please check credentials.', 'error');
      }
    });
  }

  // ==========================================================================
  // REGISTRATION PAGE CONTROLLER
  // ==========================================================================
  if (registerForm) {
    const deptSelect = document.getElementById('reg-department');

    // Populate departments dynamically from backend
    if (deptSelect) {
      api.get('backend/departments/list.php')
        .then(res => {
          const departments = res.data || [];
          if (departments.length > 0) {
            deptSelect.innerHTML = departments.map(d => `
              <option value="${d.department_id}">${d.department_name}</option>
            `).join('');
          }
        })
        .catch(() => {
          // Keep existing options if offline
        });
    }

    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const name         = document.getElementById('reg-name')?.value.trim();
      const email        = document.getElementById('reg-email')?.value.trim();
      const phone        = document.getElementById('reg-phone')?.value.trim();
      const departmentId = document.getElementById('reg-department')?.value;
      const semester     = document.getElementById('reg-semester')?.value;
      const password     = document.getElementById('reg-pass')?.value;
      const confirmPass  = document.getElementById('reg-confirm-pass')?.value;
      const submitBtn    = registerForm.querySelector('button[type="submit"]');

      if (!name || !email || !phone || !departmentId || !semester || !password) {
        showToast('Please complete all required fields.', 'error');
        return;
      }

      if (password !== confirmPass) {
        showToast('Passwords do not match. Please verify.', 'error');
        return;
      }

      if (password.length < 6) {
        showToast('Password must be at least 6 characters long.', 'error');
        return;
      }

      const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
          <span class="material-symbols-outlined" style="font-size:18px;animation:spin 1s infinite linear;">sync</span>
          <span>Committing to MySQL...</span>
        `;
      }

      try {
        const res = await api.post('backend/auth/student_register.php', {
          name,
          email,
          phone,
          department_id: departmentId,
          semester,
          password
        });

        showToast(res.message || 'Account created successfully! Redirecting...', 'success');
        setTimeout(() => {
          window.location.href = 'student-dashboard.html';
        }, 800);
      } catch (err) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnHtml;
        }
        showToast(err.message || 'Failed to create student account.', 'error');
      }
    });
  }
}

// Auto Initialize
function autoInitAuth() {
  if (document.getElementById('cems-login-form') || document.getElementById('cems-register-form')) {
    initAuthPage();
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInitAuth);
} else {
  autoInitAuth();
}
