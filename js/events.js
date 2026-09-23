/**
 * CEMS - Events Catalog & Event Details Controller (js/events.js)
 * Editorial Club-Connected Events, Filters, and ACID Registrations.
 */

import { api } from './api.js';
import { showToast, escapeHtml } from './main.js';

// ============================================================================
// EVENTS CATALOG PAGE (events.html)
// ============================================================================

export function initEventsPage() {
  const container      = document.getElementById('events-catalog-container');
  const countBadge     = document.getElementById('events-count-label');
  const searchInput    = document.getElementById('event-search-input');
  const categorySelect = document.getElementById('category-filter-select');
  const clubSelect     = document.getElementById('club-filter-select');
  const statusSelect   = document.getElementById('status-filter-select');
  const sortSelect     = document.getElementById('sort-filter-select');
  const resetBtn       = document.getElementById('reset-filters-btn');

  // Populate dynamic category and club dropdowns from database
  Promise.all([
    api.get('backend/categories/list.php'),
    api.getClubs()
  ]).then(([catRes, clubRes]) => {
    if (categorySelect && catRes.data) {
      categorySelect.innerHTML = `
        <option value="all">All Categories</option>
        ${catRes.data.map(c => `<option value="${escapeHtml(c.slug || c.category_name)}">${escapeHtml(c.category_name)}</option>`).join('')}
      `;
    }
    if (clubSelect && clubRes.data) {
      clubSelect.innerHTML = `
        <option value="all">All Clubs</option>
        ${clubRes.data.map(cl => `<option value="${cl.club_id}">${escapeHtml(cl.club_name)}</option>`).join('')}
      `;
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('category') && categorySelect) categorySelect.value = urlParams.get('category');
    if (urlParams.get('club') && clubSelect) clubSelect.value = urlParams.get('club');
    renderList();
  }).catch(() => renderList());

  async function renderList() {
    if (!container) return;

    const filters = {
      search:   searchInput ? searchInput.value.trim() : '',
      category: categorySelect ? categorySelect.value : 'all',
      club:     clubSelect ? clubSelect.value : 'all',
      status:   statusSelect ? statusSelect.value : 'all',
      sort:     sortSelect ? sortSelect.value : 'date_asc'
    };

    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem;">
        <span class="material-symbols-outlined" style="font-size: 32px; color: var(--lime-accent); animation: spin 1s infinite linear;">sync</span>
        <p style="color: var(--text-muted); margin-top: 1rem;">Loading upcoming club events...</p>
      </div>
    `;

    try {
      const res = await api.get('backend/events/list.php', filters);
      const events = res.data || [];

      if (countBadge) {
        countBadge.textContent = `${events.length} Event${events.length === 1 ? '' : 's'} Found`;
      }

      if (events.length === 0) {
        container.innerHTML = `
          <div style="grid-column: 1 / -1; text-align: center; padding: 5rem 2rem; background: var(--bg-surface); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md);">
            <span class="material-symbols-outlined" style="font-size: 40px; color: var(--text-muted); margin-bottom: 1rem;">event_busy</span>
            <h3 style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
              No events found
            </h3>
            <p style="color: var(--text-secondary); max-width: 440px; margin: 0 auto 1.5rem; font-size: 0.92rem;">
              No events matched your selected filter criteria. Try resetting the filters or check back soon.
            </p>
          </div>
        `;
        return;
      }

      container.innerHTML = events.map(event => {
        const capacityPct = event.max_capacity > 0 ? Math.round((event.registered_count / event.max_capacity) * 100) : 0;
        const hostClub = event.club || { club_name: 'Campus Society', logo_icon: 'groups', club_id: 1 };

        return `
          <div class="event-editorial-card">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <a href="club-details.html?id=${hostClub.club_id}" class="event-host-badge" title="View Organizing Club">
                  <span class="material-symbols-outlined" style="font-size: 15px;">${escapeHtml(hostClub.logo_icon || 'groups')}</span>
                  <span>${escapeHtml(hostClub.club_name)}</span>
                </a>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-family: var(--font-display);">#${event.event_id}</span>
              </div>

              <h3 class="event-card-title">
                <a href="event-details.html?id=${event.event_id}" style="color: var(--text-primary);">${escapeHtml(event.title || event.event_name)}</a>
              </h3>
              <p class="event-card-desc">${escapeHtml(event.description || '')}</p>

              <div class="event-meta-list">
                <div class="event-meta-item">
                  <span class="material-symbols-outlined" style="font-size: 16px; color: var(--lime-accent);">calendar_today</span>
                  <span>${escapeHtml(event.display_date || event.date)} &bull; ${escapeHtml(event.time || event.event_time)}</span>
                </div>
                <div class="event-meta-item">
                  <span class="material-symbols-outlined" style="font-size: 16px; color: var(--text-secondary);">location_on</span>
                  <span>${escapeHtml(event.venue.venue_name)}</span>
                </div>
              </div>
            </div>

            <div class="event-card-action-bar">
              <div>
                <span class="seats-badge"><strong>${event.available_seats}</strong> seats left</span>
              </div>
              <a href="event-details.html?id=${event.event_id}" class="btn btn-lime" style="padding: 0.45rem 1.15rem; font-size: 0.8rem;">
                <span>Register &rarr;</span>
              </a>
            </div>
          </div>
        `;
      }).join('');

    } catch (err) {
      console.error('Events list error:', err);
      container.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 0; color: var(--text-muted);">
          <p>Failed to retrieve events from database. Please check your connection.</p>
        </div>
      `;
    }
  }

  // Filter Listeners
  if (searchInput) {
    let debounce;
    searchInput.addEventListener('input', () => {
      clearTimeout(debounce);
      debounce = setTimeout(renderList, 250);
    });
  }

  categorySelect?.addEventListener('change', renderList);
  clubSelect?.addEventListener('change', renderList);
  statusSelect?.addEventListener('change', renderList);
  sortSelect?.addEventListener('change', renderList);

  resetBtn?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (categorySelect) categorySelect.value = 'all';
    if (clubSelect) clubSelect.value = 'all';
    if (statusSelect) statusSelect.value = 'all';
    if (sortSelect) sortSelect.value = 'date_asc';
    renderList();
  });
}

// ============================================================================
// EVENT DETAILS PAGE (event-details.html)
// ============================================================================

export async function initEventDetails() {
  const container = document.getElementById('event-details-container');
  if (!container) return;

  const urlParams = new URLSearchParams(window.location.search);
  const eventId = urlParams.get('id');

  if (!eventId) {
    window.location.href = 'events.html';
    return;
  }

  container.innerHTML = `
    <div style="text-align: center; padding: 6rem 0;">
      <span class="material-symbols-outlined" style="font-size: 36px; color: var(--lime-accent); animation: spin 1s infinite linear;">sync</span>
      <p style="color: var(--text-muted); margin-top: 1rem;">Retrieving event dossier from database...</p>
    </div>
  `;

  try {
    const res = await api.get('backend/events/get.php', { id: eventId });
    const event = res.data;
    const hostClub = event.club || { club_name: 'Campus Community', logo_icon: 'groups', club_id: 1, tagline: 'Collegiate Life' };
    const capacityPct = event.max_capacity > 0 ? Math.round((event.registered_count / event.max_capacity) * 100) : 0;

    container.innerHTML = `
      <div style="margin-bottom: 2rem;">
        <a href="events.html" style="font-size: 0.85rem; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 0.4rem;">
          <span class="material-symbols-outlined" style="font-size: 16px;">arrow_back</span>
          <span>Back to All Events</span>
        </a>
      </div>

      <!-- Main Layout -->
      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 3.5rem; margin-bottom: 4rem;">
        <div>
          <!-- Host Club Eyebrow -->
          <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <a href="club-details.html?id=${hostClub.club_id}" class="event-host-badge" style="font-size: 0.88rem;">
              <span class="material-symbols-outlined" style="font-size: 18px;">${escapeHtml(hostClub.logo_icon || 'groups')}</span>
              <span>Organized by ${escapeHtml(hostClub.club_name)}</span>
            </a>
            <span style="font-size: 0.75rem; color: var(--text-muted);">&bull;</span>
            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Event #${event.event_id}</span>
          </div>

          <h1 style="font-family: var(--font-display); font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 700; color: var(--text-primary); line-height: 1.15; margin-bottom: 1.5rem;">
            ${escapeHtml(event.title || event.event_name)}
          </h1>

          <!-- Description -->
          <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem; margin-bottom: 2rem;">
            <h3 style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.75rem;">
              About This Event
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.7; white-space: pre-line;">
              ${escapeHtml(event.description || 'No detailed description provided.')}
            </p>
          </div>

          <!-- Venue & Facility Details -->
          <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem;">
            <h3 style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1.25rem;">
              Venue & Location
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
              <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Facility Name</span>
                <strong style="color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(event.venue.venue_name)}</strong>
              </div>
              <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Campus Location</span>
                <strong style="color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(event.venue.location)}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Sticky Registration Action Card -->
        <div>
          <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2rem; position: sticky; top: calc(var(--header-height) + 2rem);">
            <div style="padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-subtle); margin-bottom: 1.5rem;">
              <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <span class="material-symbols-outlined" style="color: var(--lime-accent); font-size: 22px;">calendar_month</span>
                <div>
                  <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Date & Time</div>
                  <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(event.display_date || event.date)}</div>
                  <div style="font-size: 0.85rem; color: var(--text-secondary);">${escapeHtml(event.time || event.event_time)}</div>
                </div>
              </div>
            </div>

            <!-- Capacity Progress -->
            <div style="margin-bottom: 1.75rem;">
              <div style="display: flex; justify-content: space-between; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                <span>Registration Quota</span>
                <strong style="color: var(--text-primary);">${event.registered_count} / ${event.max_capacity}</strong>
              </div>
              <div style="width: 100%; height: 5px; background: rgba(255, 255, 255, 0.08); border-radius: 9999px; overflow: hidden; margin-bottom: 0.5rem;">
                <div style="width: ${capacityPct}%; height: 100%; background: var(--lime-accent);"></div>
              </div>
              <div style="font-size: 0.78rem; color: var(--lime-accent); text-align: right;">
                ${event.available_seats} remaining seats
              </div>
            </div>

            <!-- Registration Action Button -->
            <div id="event-action-box">
              ${event.is_user_registered ? `
                <div style="text-align: center; background: rgba(200, 249, 54, 0.08); border: 1px solid var(--lime-border); padding: 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1rem;">
                  <span class="material-symbols-outlined" style="color: var(--lime-accent); font-size: 28px; margin-bottom: 0.25rem;">check_circle</span>
                  <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">Registered ✓</div>
                  <p style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.25rem;">Your digital pass is confirmed in your Student Hub.</p>
                </div>
                <a href="student-dashboard.html" class="btn btn-ghost" style="width: 100%; font-size: 0.86rem;">
                  View Pass in Student Hub &rarr;
                </a>
              ` : `
                <button id="register-event-btn" class="btn btn-lime" style="width: 100%;" ${event.available_seats <= 0 ? 'disabled' : ''}>
                  <span>${event.available_seats <= 0 ? 'Event Sold Out' : 'Register for Event &rarr;'}</span>
                </button>
              `}
            </div>
          </div>
        </div>
      </div>
    `;

    // Handle Registration Click
    const regBtn = document.getElementById('register-event-btn');
    if (regBtn) {
      regBtn.addEventListener('click', async () => {
        try {
          regBtn.disabled = true;
          regBtn.innerHTML = `<span>Processing registration...</span>`;

          const regRes = await api.post('backend/registrations/create.php', { event_id: event.event_id });
          showToast('Event registration confirmed! Digital pass issued.', 'success');

          // Refresh view
          setTimeout(() => {
            initEventDetails();
          }, 600);

        } catch (regErr) {
          if (regErr.status === 401 || regErr.status === 403) {
            showToast('Please sign in as a student to register.', 'warning');
            setTimeout(() => {
              window.location.href = `login.html?redirect=event-details.html?id=${event.event_id}`;
            }, 1200);
          } else {
            showToast(regErr.message || 'Registration failed.', 'error');
            regBtn.disabled = false;
            regBtn.innerHTML = `<span>Register for Event &rarr;</span>`;
          }
        }
      });
    }

  } catch (err) {
    console.error('Event details error:', err);
    container.innerHTML = `
      <div style="text-align: center; padding: 6rem 0;">
        <h2 style="font-family: var(--font-display); color: var(--text-primary); margin-bottom: 0.75rem;">Event Not Found</h2>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">This event does not exist in the database or has been concluded.</p>
        <a href="events.html" class="btn btn-lime">Back to Events &rarr;</a>
      </div>
    `;
  }
}
