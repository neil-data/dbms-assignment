/**
 * CEMS - Events Catalog & Event Details Controller
 * Queries live events, filters, categories, and executes ACID registrations.
 */

import { api } from './api.js';
import { showToast, openTicketModal, escapeHtml } from './main.js';

// ============================================================================
// EVENTS CATALOG PAGE (events.html)
// ============================================================================

export function initEventsPage() {
  const container      = document.getElementById('events-catalog-container');
  const countBadge     = document.getElementById('events-count-label') || document.getElementById('events-count-badge');
  const searchInput    = document.getElementById('event-search-input') || document.getElementById('event-search');
  const categorySelect = document.getElementById('category-filter-select') || document.getElementById('event-category-filter');
  const statusSelect   = document.getElementById('status-filter-select') || document.getElementById('event-status-filter');
  const sortSelect     = document.getElementById('sort-filter-select') || document.getElementById('event-sort');
  const resetBtn       = document.getElementById('reset-filters-btn') || document.getElementById('filter-reset-btn');

  // Populate dynamic category dropdown from database
  if (categorySelect) {
    api.get('backend/categories/list.php')
      .then(res => {
        const categories = res.data || [];
        categorySelect.innerHTML = `
          <option value="all">All Academic Categories</option>
          ${categories.map(c => `<option value="${escapeHtml(c.slug || c.category_name)}">${escapeHtml(c.category_name)}</option>`).join('')}
        `;

        const urlParams = new URLSearchParams(window.location.search);
        const catParam = urlParams.get('category');
        if (catParam) {
          categorySelect.value = catParam;
        }
        renderList();
      })
      .catch(() => renderList());
  } else {
    renderList();
  }

  async function renderList() {
    if (!container) return;

    const filters = {
      search:   searchInput ? searchInput.value.trim() : '',
      category: categorySelect ? categorySelect.value : 'all',
      status:   statusSelect ? statusSelect.value : 'all',
      sort:     sortSelect ? sortSelect.value : 'date_asc'
    };

    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem;">
        <span class="material-symbols-outlined" style="font-size: 36px; color: var(--gold-primary); animation: spin 1s infinite linear;">sync</span>
        <p style="color: var(--text-muted); margin-top: 1rem;">Querying events from MySQL database...</p>
      </div>
    `;

    try {
      const res = await api.get('backend/events/list.php', filters);
      const events = res.data || [];

      if (countBadge) {
        countBadge.textContent = `${events.length} Event(s) in Institutional Registry`;
      }

      if (events.length === 0) {
        container.innerHTML = `
          <div style="grid-column: 1 / -1; text-align: center; padding: 5rem 2rem; background: var(--bg-card); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md);">
            <span class="material-symbols-outlined" style="font-size: 52px; color: var(--text-muted); margin-bottom: 1rem; display: inline-block;">event_busy</span>
            <h3 style="font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
              No records available.
            </h3>
            <p style="color: var(--text-secondary); max-width: 480px; margin: 0 auto 1.5rem; font-size: 0.95rem; line-height: 1.6;">
              ${filters.search || filters.category !== 'all' || filters.status !== 'all'
                ? 'No event records matched your filter criteria. Try resetting the filters.'
                : 'Campus event records will appear here once published to the institutional database.'}
            </p>
            ${filters.search || filters.category !== 'all' || filters.status !== 'all' ? `
              <button class="btn btn-secondary" id="empty-reset-btn" style="font-size: 0.88rem;">
                <span class="material-symbols-outlined" style="font-size: 18px;">restart_alt</span>
                <span>Reset Filters</span>
              </button>
            ` : `
              <a href="admin.html" class="btn btn-secondary" style="font-size: 0.88rem;">
                <span class="material-symbols-outlined" style="font-size: 18px;">admin_panel_settings</span>
                <span>Open Admin Management</span>
              </a>
            `}
          </div>
        `;

        document.getElementById('empty-reset-btn')?.addEventListener('click', () => {
          if (searchInput) searchInput.value = '';
          if (categorySelect) categorySelect.value = 'all';
          if (statusSelect) statusSelect.value = 'all';
          if (sortSelect) sortSelect.value = 'date_asc';
          renderList();
        });
        return;
      }

      container.innerHTML = events.map(event => {
        const badgeClass = event.status === 'UPCOMING' ? 'badge-open' : event.status === 'ONGOING' ? 'badge-closing' : 'badge-closed';
        const capacityPct = event.max_capacity > 0 ? Math.round((event.registered_count / event.max_capacity) * 100) : 0;

        return `
          <div class="event-card" id="event-card-${event.event_id}">
            <div class="event-card-top">
              <span class="event-number">#${event.event_id}</span>
              <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                ${(event.categories || []).map(c => `
                  <span style="font-size: 0.72rem; padding: 0.15rem 0.6rem; border-radius: var(--radius-full); background: rgba(56, 189, 248, 0.08); color: var(--blue-primary); border: 1px solid var(--border-blue); font-weight: 600;">
                    ${escapeHtml(c.category_name)}
                  </span>
                `).join('')}
                <span class="event-badge ${badgeClass}">${escapeHtml(event.ui_status || event.status)}</span>
              </div>
            </div>

            <div>
              <h3 class="event-card-title">${escapeHtml(event.title)}</h3>
              <p class="event-card-desc">${escapeHtml(event.description || '')}</p>
            </div>

            <div>
              <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.76rem; color: var(--text-muted); margin-bottom: 0.4rem;">
                  <span>Registration Quota</span>
                  <span><strong>${event.registered_count}</strong> / ${event.max_capacity} Enrolled</span>
                </div>
                <div style="width: 100%; height: 5px; background: rgba(255, 255, 255, 0.08); border-radius: 9999px; overflow: hidden;">
                  <div style="width: ${capacityPct}%; height: 100%; background: ${capacityPct > 85 ? 'var(--status-closing)' : 'var(--gold-primary)'}; border-radius: 9999px;"></div>
                </div>
              </div>

              <div class="event-meta-list">
                <div class="event-meta-item">
                  <span class="material-symbols-outlined">calendar_month</span>
                  <span><strong>${escapeHtml(event.display_date || event.date)}</strong> &bull; ${escapeHtml(event.time || '')}</span>
                </div>
                <div class="event-meta-item">
                  <span class="material-symbols-outlined">location_on</span>
                  <span>${escapeHtml(event.venue ? event.venue.venue_name : 'Campus Venue')}</span>
                </div>
              </div>

              <div class="event-card-bottom" style="margin-top: 1rem;">
                <div>
                  <span class="seat-indicator">
                    <strong>${event.available_seats}</strong> seats remaining
                  </span>
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                  <a href="event-details.html?id=${event.event_id}" class="btn btn-secondary" style="padding: 0.45rem 0.9rem; font-size: 0.82rem;">
                    <span>View Details</span>
                    <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        `;
      }).join('');

    } catch (e) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; background: var(--bg-card); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md);">
          <span class="material-symbols-outlined" style="font-size: 44px; color: var(--status-closing); margin-bottom: 0.75rem;">error</span>
          <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">Database Connection Required</h4>
          <p style="color: var(--text-secondary); font-size: 0.9rem;">
            Unable to connect to MySQL database at localhost. Ensure Apache &amp; MySQL are running in XAMPP.
          </p>
        </div>
      `;
    }
  }

  // Debounced Search Input
  let searchTimeout = null;
  searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(renderList, 300);
  });

  categorySelect?.addEventListener('change', renderList);
  statusSelect?.addEventListener('change', renderList);
  sortSelect?.addEventListener('change', renderList);

  resetBtn?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (categorySelect) categorySelect.value = 'all';
    if (statusSelect) statusSelect.value = 'all';
    if (sortSelect) sortSelect.value = 'date_asc';
    renderList();
  });
}

// ============================================================================
// EVENT DETAILS PAGE (event-details.html)
// ============================================================================

export async function initEventDetailsPage() {
  const urlParams = new URLSearchParams(window.location.search);
  const eventId = urlParams.get('id');
  const container = document.getElementById('event-detail-view');
  if (!container) return;

  if (!eventId) {
    container.innerHTML = `
      <div style="text-align: center; padding: 6rem 2rem;">
        <h2 style="font-family: var(--font-display); font-size: 1.8rem; margin-bottom: 1rem; color: var(--text-primary);">
          No Event Specified
        </h2>
        <a href="events.html" class="btn btn-primary">Browse Campus Events</a>
      </div>
    `;
    return;
  }

  try {
    const res = await api.get(`backend/events/get.php?id=${eventId}`);
    const event = res.data;

    const badgeClass = event.status === 'UPCOMING' ? 'badge-open' : event.status === 'ONGOING' ? 'badge-closing' : 'badge-closed';
    const capacityPct = event.max_capacity > 0 ? Math.round((event.registered_count / event.max_capacity) * 100) : 0;

    container.innerHTML = `
      <div style="max-width: var(--max-width); margin: 0 auto;">
        <!-- Breadcrumb Navigation -->
        <div style="display: flex; align-items: center; gap: 0.65rem; font-size: 0.84rem; color: var(--text-muted); margin-bottom: 2rem;">
          <a href="index.html" style="color: var(--text-secondary);">Home</a>
          <span>/</span>
          <a href="events.html" style="color: var(--text-secondary);">Events</a>
          <span>/</span>
          <span style="color: var(--gold-primary); font-family: var(--font-display); font-weight: 600;">${escapeHtml(event.title)}</span>
        </div>

        <!-- Two-Column Layout -->
        <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 3rem; align-items: start;" class="event-details-grid">
          
          <!-- Left: Main Information -->
          <div>
            <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
              <span class="event-badge ${badgeClass}">${escapeHtml(event.ui_status || event.status)}</span>
              ${(event.categories || []).map(c => `
                <span style="font-size: 0.78rem; padding: 0.2rem 0.75rem; border-radius: var(--radius-full); background: rgba(56, 189, 248, 0.08); color: var(--blue-primary); border: 1px solid var(--border-blue); font-weight: 600;">
                  ${escapeHtml(c.category_name)}
                </span>
              `).join('')}
              <span style="font-family: var(--font-display); font-size: 0.82rem; color: var(--text-muted); margin-left: auto;">
                RECORD ID: #${event.event_id}
              </span>
            </div>

            <h1 style="font-family: var(--font-display); font-size: clamp(2.2rem, 3.8vw, 3.2rem); font-weight: 700; line-height: 1.15; letter-spacing: -0.03em; margin-bottom: 1.25rem; color: var(--text-primary);">
              ${escapeHtml(event.title)}
            </h1>

            <!-- Overview & Description -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2.25rem; margin-bottom: 2rem;">
              <h3 style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-outlined" style="color: var(--gold-primary);">description</span>
                <span>Event Overview</span>
              </h3>
              <p style="color: var(--text-secondary); line-height: 1.8; font-size: 0.98rem;">
                ${escapeHtml(event.description || 'No description recorded in database.')}
              </p>
            </div>

            <!-- Venue Details & Relational Mapping -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2.25rem;">
              <h3 style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-outlined" style="color: var(--gold-primary);">meeting_room</span>
                <span>Assigned Campus Facility (VENUE)</span>
              </h3>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                  <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Facility Name</span>
                  <strong style="color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(event.venue.venue_name)}</strong>
                </div>
                <div>
                  <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Physical Campus Location</span>
                  <strong style="color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(event.venue.location)}</strong>
                </div>
                <div>
                  <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Facility Max Capacity</span>
                  <span style="color: var(--blue-primary); font-size: 0.92rem; font-weight: 600;">${event.venue.capacity} Seats</span>
                </div>
                <div>
                  <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Relational Integrity</span>
                  <span style="color: var(--status-open); font-size: 0.92rem; font-weight: 600;">FK &rarr; VENUE.venue_id</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Registration Action Card -->
          <div>
            <div style="position: sticky; top: calc(var(--header-height) + 2rem); display: flex; flex-direction: column; gap: 2rem;">
              <div style="background: linear-gradient(180deg, rgba(16, 28, 58, 0.85) 0%, rgba(11, 19, 41, 0.95) 100%); border: 1px solid var(--border-highlight); border-radius: var(--radius-lg); padding: 2.25rem; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                  <span style="font-family: var(--font-display); font-size: 0.8rem; font-weight: 700; letter-spacing: 0.1em; color: var(--gold-primary); text-transform: uppercase;">
                    Registration Quota
                  </span>
                  <span style="font-size: 0.78rem; color: var(--text-muted);">
                    Status: <strong>${escapeHtml(event.status)}</strong>
                  </span>
                </div>

                <!-- Schedule Box -->
                <div style="padding: 1.25rem; background: rgba(2, 4, 9, 0.5); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 1.5rem;">
                  <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <span class="material-symbols-outlined" style="color: var(--blue-primary);">calendar_month</span>
                    <div>
                      <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Schedule Date</div>
                      <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(event.display_date || event.date)}</div>
                    </div>
                  </div>
                  <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="material-symbols-outlined" style="color: var(--gold-primary);">schedule</span>
                    <div>
                      <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Time Slot</div>
                      <div style="font-weight: 500; color: var(--text-secondary); font-size: 0.92rem;">${escapeHtml(event.time || 'Schedule TBA')}</div>
                    </div>
                  </div>
                </div>

                <!-- Capacity Breakdown -->
                <div style="margin-bottom: 1.75rem;">
                  <div style="display: flex; justify-content: space-between; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                    <span>Confirmed Enrollment:</span>
                    <strong style="color: var(--text-primary);">${event.registered_count} / ${event.max_capacity}</strong>
                  </div>
                  <div style="width: 100%; height: 6px; background: rgba(255, 255, 255, 0.08); border-radius: 9999px; overflow: hidden; margin-bottom: 0.5rem;">
                    <div style="width: ${capacityPct}%; height: 100%; background: ${capacityPct > 85 ? 'var(--status-closing)' : 'var(--gold-primary)'};"></div>
                  </div>
                  <div style="font-size: 0.78rem; color: var(--gold-primary); text-align: right;">
                    ${event.available_seats} remaining seats
                  </div>
                </div>

                <!-- Registration Action Area -->
                <div id="registration-action-area">
                  ${event.is_user_registered ? `
                    <div style="text-align: center; background: rgba(16, 185, 129, 0.08); border: 1px solid var(--border-gold); padding: 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1rem;">
                      <span class="material-symbols-outlined" style="color: var(--status-open); font-size: 28px; margin-bottom: 0.25rem;">check_circle</span>
                      <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">You Are Enrolled</h4>
                      <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 1rem;">Seat confirmed in relational database.</p>
                      <button class="btn btn-primary" id="view-my-pass-btn" style="width: 100%; font-size: 0.84rem;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">qr_code</span>
                        <span>View Access Pass</span>
                      </button>
                    </div>
                  ` : (event.available_seats <= 0 || event.status !== 'UPCOMING') ? `
                    <button class="btn btn-secondary" style="width: 100%; opacity: 0.6; cursor: not-allowed;" disabled>
                      <span>Registration Closed</span>
                    </button>
                  ` : `
                    <button class="btn btn-primary btn-pill-arrow" id="register-now-btn" style="width: 100%; padding: 0.85rem;">
                      <span>Register for Event</span>
                      <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                    </button>
                    <p style="font-size: 0.76rem; color: var(--text-muted); text-align: center; margin-top: 0.75rem;">
                      Atomic ACID transaction: duplicate check &amp; quota validation.
                    </p>
                  `}
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    `;

    // Attach Pass View Button
    document.getElementById('view-my-pass-btn')?.addEventListener('click', () => {
      if (event.user_registration_id) {
        openTicketModal(event.user_registration_id);
      }
    });

    // Attach Register Button
    document.getElementById('register-now-btn')?.addEventListener('click', async () => {
      const status = await api.checkStatus();

      if (!status.authenticated || status.role !== 'student') {
        showToast('Please sign in with your student account to register.', 'info');
        setTimeout(() => {
          window.location.href = `login.html?redirect=event-details.html?id=${eventId}`;
        }, 1200);
        return;
      }

      const regBtn = document.getElementById('register-now-btn');
      if (regBtn) {
        regBtn.disabled = true;
        regBtn.innerHTML = `
          <span class="material-symbols-outlined" style="font-size:16px;animation:spin 1s infinite linear;">sync</span>
          <span>Committing Transaction...</span>
        `;
      }

      try {
        const res = await api.post('backend/registrations/create.php', {
          event_id: eventId
        });

        showToast(res.message || 'Registration confirmed! Pass generated.', 'success');
        setTimeout(() => {
          window.location.reload();
        }, 900);
      } catch (err) {
        if (regBtn) {
          regBtn.disabled = false;
          regBtn.innerHTML = `
            <span>Register for Event</span>
            <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
          `;
        }
        showToast(err.message || 'Registration transaction failed.', 'error');
      }
    });

  } catch (err) {
    container.innerHTML = `
      <div style="text-align: center; padding: 6rem 2rem; max-width: 600px; margin: 0 auto;">
        <span class="material-symbols-outlined" style="font-size: 56px; color: var(--text-muted); margin-bottom: 1.25rem;">event_busy</span>
        <h2 style="font-family: var(--font-display); font-size: 2rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-primary);">
          No Event Record Found
        </h2>
        <p style="color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6; font-size: 0.95rem;">
          ${err.message || 'Database record not found.'}
        </p>
        <a href="events.html" class="btn btn-primary">
          <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
          <span>Return to Events Directory</span>
        </a>
      </div>
    `;
  }
}

// Auto Initialize
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('events-catalog-container')) {
    initEventsPage();
  }
  if (document.getElementById('event-detail-view')) {
    initEventDetailsPage();
  }
});
