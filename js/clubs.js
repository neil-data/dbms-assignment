/**
 * CEMS - Clubs & Community Controller (js/clubs.js)
 * Powers club discovery, category filtering, search, and membership interactions.
 */

import { api } from './api.js';
import { showToast, escapeHtml } from './main.js';

// ============================================================================
// 1. FEATURED CLUBS CAROUSEL (Landing Page)
// ============================================================================
export async function initFeaturedClubs() {
  const container = document.getElementById('featured-clubs-container');
  if (!container) return;

  try {
    const res = await api.getClubs({ featured: 1 });
    const clubs = res.data || [];

    if (clubs.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 0; color: var(--text-muted);">
          <p>No featured clubs available at this moment.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = clubs.map((club, idx) => `
      <a href="club-details.html?id=${club.club_id}" class="club-editorial-card" data-index="${idx}">
        <div class="club-card-image-wrap">
          <img src="${escapeHtml(club.cover_image)}" alt="${escapeHtml(club.club_name)}" class="club-card-image" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop'" />
          <div class="club-card-overlay"></div>
        </div>
        <div class="club-card-content">
          <div class="club-card-header">
            <div class="club-icon-badge">
              <span class="material-symbols-outlined" style="font-size: 20px;">${escapeHtml(club.logo_icon)}</span>
            </div>
            <span class="club-category-pill">${escapeHtml(club.category)}</span>
          </div>
          <div class="club-card-footer">
            <h3 class="club-card-title">
              <span>${escapeHtml(club.club_name)}</span>
              <span class="arrow-circle-btn">
                <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
              </span>
            </h3>
            <p class="club-card-tagline">${escapeHtml(club.tagline)}</p>
            <div class="club-card-meta">
              <span><strong>${club.member_count}</strong> members</span>
              <span><strong>${club.upcoming_event_count}</strong> upcoming events</span>
            </div>
          </div>
        </div>
      </a>
    `).join('');

    // Setup counter if element exists
    const counterEl = document.getElementById('featured-carousel-counter');
    if (counterEl) {
      counterEl.textContent = `01 / 0${Math.min(clubs.length, 9)}`;
    }

  } catch (err) {
    console.error('Error loading featured clubs:', err);
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 0; color: var(--text-muted);">
        <p>Failed to load clubs. Please check your connection.</p>
      </div>
    `;
  }
}

// ============================================================================
// 2. CLUB DISCOVERY PAGE (clubs.html)
// ============================================================================
export async function initClubDiscovery() {
  const grid = document.getElementById('clubs-discovery-grid');
  const searchInput = document.getElementById('clubs-search-input');
  const filterPills = document.querySelectorAll('.club-filter-pill');
  const resultsCount = document.getElementById('clubs-count-label');

  if (!grid) return;

  let currentCategory = 'all';
  let searchQuery = '';

  async function loadClubs() {
    grid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 0;">
        <span class="material-symbols-outlined" style="font-size: 32px; color: var(--lime-accent); animation: spin 1s infinite linear;">sync</span>
        <p style="color: var(--text-muted); margin-top: 1rem;">Discovering campus clubs...</p>
      </div>
    `;

    try {
      const res = await api.getClubs({ category: currentCategory, search: searchQuery });
      const clubs = res.data || [];

      if (resultsCount) {
        resultsCount.textContent = `${clubs.length} Club${clubs.length === 1 ? '' : 's'} Found`;
      }

      if (clubs.length === 0) {
        grid.innerHTML = `
          <div style="grid-column: 1 / -1; text-align: center; padding: 5rem 0;">
            <span class="material-symbols-outlined" style="font-size: 40px; color: var(--text-muted); margin-bottom: 1rem;">groups_3</span>
            <h3 style="font-family: var(--font-display); font-size: 1.25rem; color: var(--text-primary); margin-bottom: 0.5rem;">No clubs found</h3>
            <p style="color: var(--text-secondary); max-width: 400px; margin: 0 auto;">Try clearing your search query or selecting a different category filter.</p>
          </div>
        `;
        return;
      }

      grid.innerHTML = clubs.map(club => `
        <div class="club-editorial-card" style="height: 360px;">
          <div class="club-card-image-wrap">
            <img src="${escapeHtml(club.cover_image)}" alt="${escapeHtml(club.club_name)}" class="club-card-image" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop'" />
            <div class="club-card-overlay"></div>
          </div>
          <div class="club-card-content">
            <div class="club-card-header">
              <div class="club-icon-badge">
                <span class="material-symbols-outlined" style="font-size: 20px;">${escapeHtml(club.logo_icon)}</span>
              </div>
              <span class="club-category-pill">${escapeHtml(club.category)}</span>
            </div>
            <div class="club-card-footer">
              <h3 class="club-card-title">
                <a href="club-details.html?id=${club.club_id}" style="color: var(--text-primary);">${escapeHtml(club.club_name)}</a>
                <a href="club-details.html?id=${club.club_id}" class="arrow-circle-btn">
                  <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                </a>
              </h3>
              <p class="club-card-tagline">${escapeHtml(club.tagline)}</p>
              <div class="club-card-meta">
                <span><strong>${club.member_count}</strong> members</span>
                <span><strong>${club.upcoming_event_count}</strong> events</span>
              </div>
            </div>
          </div>
        </div>
      `).join('');

    } catch (err) {
      console.error('Error fetching clubs:', err);
      grid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 0; color: var(--text-muted);">
          <p>Failed to load clubs. Please verify your connection.</p>
        </div>
      `;
    }
  }

  // Filter Pill Listeners
  filterPills.forEach(pill => {
    pill.addEventListener('click', () => {
      filterPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      currentCategory = pill.dataset.category || 'all';
      loadClubs();
    });
  });

  // Search Debounce Listener
  let debounceTimeout = null;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        searchQuery = e.target.value.trim();
        loadClubs();
      }, 250);
    });
  }

  // Initial Load
  loadClubs();
}

// ============================================================================
// 3. CLUB DETAIL PROFILE PAGE (club-details.html)
// ============================================================================
export async function initClubDetails() {
  const container = document.getElementById('club-details-container');
  if (!container) return;

  const urlParams = new URLSearchParams(window.location.search);
  const clubId = urlParams.get('id') || urlParams.get('slug');

  if (!clubId) {
    window.location.href = 'clubs.html';
    return;
  }

  container.innerHTML = `
    <div style="text-align: center; padding: 6rem 0;">
      <span class="material-symbols-outlined" style="font-size: 36px; color: var(--lime-accent); animation: spin 1s infinite linear;">sync</span>
      <p style="color: var(--text-muted); margin-top: 1rem;">Loading club profile...</p>
    </div>
  `;

  try {
    const res = await api.getClub(clubId);
    const club = res.data;

    container.innerHTML = `
      <!-- Club Hero Dossier -->
      <div style="position: relative; border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 3.5rem; border: 1px solid var(--border-subtle); background-color: var(--bg-surface);">
        <div style="height: 320px; width: 100%; position: relative;">
          <img src="${escapeHtml(club.cover_image)}" alt="${escapeHtml(club.club_name)}" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.5) contrast(1.15);" onerror="this.src='https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop'" />
          <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(8, 9, 11, 0.2) 0%, rgba(8, 9, 11, 0.95) 100%);"></div>
        </div>

        <div style="position: relative; z-index: 2; padding: 2rem 2.5rem 2.5rem; margin-top: -6rem;">
          <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem;">
            <div style="display: flex; gap: 1.5rem; align-items: flex-end;">
              <div style="width: 80px; height: 80px; border-radius: var(--radius-md); background: var(--bg-deep); border: 2px solid var(--border-medium); display: flex; align-items: center; justify-content: center; color: var(--lime-accent); box-shadow: 0 10px 25px rgba(0,0,0,0.6);">
                <span class="material-symbols-outlined" style="font-size: 40px;">${escapeHtml(club.logo_icon)}</span>
              </div>
              <div>
                <span class="club-category-pill" style="margin-bottom: 0.5rem; display: inline-block;">${escapeHtml(club.category)}</span>
                <h1 style="font-family: var(--font-display); font-size: clamp(2rem, 4vw, 3rem); font-weight: 700; color: var(--text-primary); line-height: 1.1;">
                  ${escapeHtml(club.club_name)}
                </h1>
                <p style="color: var(--text-secondary); font-size: 1.05rem; margin-top: 0.35rem;">${escapeHtml(club.tagline)}</p>
              </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: center;">
              <button id="club-join-btn" class="btn ${club.is_member ? 'btn-ghost' : 'btn-lime'}" data-club-id="${club.club_id}">
                <span class="material-symbols-outlined" style="font-size: 18px;">${club.is_member ? 'check_circle' : 'group_add'}</span>
                <span>${club.is_member ? 'Joined' : 'Join Club'}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Dossier Content Columns -->
      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 3.5rem; margin-bottom: 4rem;">
        <div>
          <!-- About -->
          <div style="margin-bottom: 2.5rem;">
            <h2 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem;">
              About ${escapeHtml(club.club_name)}
            </h2>
            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.7; white-space: pre-line;">
              ${escapeHtml(club.description)}
            </p>
          </div>

          <!-- Mission -->
          ${club.mission ? `
            <div style="margin-bottom: 3rem; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.75rem;">
              <h3 style="font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--lime-accent); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.5rem;">
                Our Mission
              </h3>
              <p style="color: var(--text-primary); font-size: 0.95rem; line-height: 1.6;">
                ${escapeHtml(club.mission)}
              </p>
            </div>
          ` : ''}

          <!-- Upcoming Club Events -->
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
              <h2 style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">
                Upcoming Club Events
              </h2>
              <span style="font-size: 0.82rem; color: var(--text-muted);">${club.upcoming_events.length} Scheduled</span>
            </div>

            ${club.upcoming_events.length === 0 ? `
              <div style="background: var(--bg-surface); border: 1px dashed var(--border-subtle); border-radius: var(--radius-md); padding: 2.5rem; text-align: center; color: var(--text-secondary);">
                <p>No upcoming events currently scheduled for this club.</p>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.35rem;">Check back soon or attend weekly club meetings.</p>
              </div>
            ` : `
              <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                ${club.upcoming_events.map(ev => `
                  <div class="event-editorial-card" style="padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                      <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--lime-accent); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                          ${escapeHtml(ev.display_date)} &bull; ${escapeHtml(ev.event_time)}
                        </div>
                        <h3 style="font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
                          <a href="event-details.html?id=${ev.event_id}" style="color: var(--text-primary);">${escapeHtml(ev.event_name)}</a>
                        </h3>
                        <div style="font-size: 0.82rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem;">
                          <span class="material-symbols-outlined" style="font-size: 16px;">location_on</span>
                          <span>${escapeHtml(ev.venue_name)}</span>
                        </div>
                      </div>
                      <div>
                        <a href="event-details.html?id=${ev.event_id}" class="btn btn-lime" style="padding: 0.5rem 1.2rem; font-size: 0.82rem;">
                          <span>Register &rarr;</span>
                        </a>
                      </div>
                    </div>
                  </div>
                `).join('')}
              </div>
            `}
          </div>
        </div>

        <!-- Sidebar Details -->
        <div>
          <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 1.75rem; position: sticky; top: calc(var(--header-height) + 2rem);">
            <h3 style="font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1.25rem; text-transform: uppercase; letter-spacing: 0.06em;">
              Club Intelligence
            </h3>

            <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.88rem;">
              <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.75rem; text-transform: uppercase;">Total Members</span>
                <strong id="club-member-count" style="font-family: var(--font-display); font-size: 1.35rem; color: var(--text-primary);">${club.member_count}</strong>
              </div>

              ${club.meeting_schedule ? `
                <div style="border-top: 1px solid var(--border-subtle); padding-top: 0.75rem;">
                  <span style="color: var(--text-muted); display: block; font-size: 0.75rem; text-transform: uppercase;">Meeting Schedule</span>
                  <span style="color: var(--text-secondary);">${escapeHtml(club.meeting_schedule)}</span>
                </div>
              ` : ''}

              <div style="border-top: 1px solid var(--border-subtle); padding-top: 0.75rem;">
                <span style="color: var(--text-muted); display: block; font-size: 0.75rem; text-transform: uppercase;">Club Coordinator</span>
                <span style="color: var(--text-primary); font-weight: 600;">${escapeHtml(club.coordinator_name)}</span>
                <a href="mailto:${escapeHtml(club.coordinator_email)}" style="color: var(--lime-accent); font-size: 0.82rem; display: block; margin-top: 0.2rem;">
                  ${escapeHtml(club.coordinator_email)}
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    // Join Button Event Handler
    const joinBtn = document.getElementById('club-join-btn');
    if (joinBtn) {
      joinBtn.addEventListener('click', async () => {
        try {
          joinBtn.disabled = true;
          const toggleRes = await api.joinClub(club.club_id);
          const isNowMember = toggleRes.data.is_member;
          const updatedCount = toggleRes.data.member_count;

          joinBtn.className = `btn ${isNowMember ? 'btn-ghost' : 'btn-lime'}`;
          joinBtn.innerHTML = `
            <span class="material-symbols-outlined" style="font-size: 18px;">${isNowMember ? 'check_circle' : 'group_add'}</span>
            <span>${isNowMember ? 'Joined' : 'Join Club'}</span>
          `;

          const countEl = document.getElementById('club-member-count');
          if (countEl) countEl.textContent = updatedCount;

          showToast(toggleRes.message, isNowMember ? 'success' : 'info');
        } catch (joinErr) {
          if (joinErr.status === 401 || joinErr.status === 403) {
            showToast('Please sign in as a student to join campus clubs.', 'warning');
            setTimeout(() => {
              window.location.href = `login.html?redirect=club-details.html?id=${club.club_id}`;
            }, 1200);
          } else {
            showToast(joinErr.message || 'Failed to update membership.', 'error');
          }
        } finally {
          joinBtn.disabled = false;
        }
      });
    }

  } catch (err) {
    console.error('Error fetching club profile:', err);
    container.innerHTML = `
      <div style="text-align: center; padding: 6rem 0;">
        <h2 style="font-family: var(--font-display); color: var(--text-primary); margin-bottom: 0.75rem;">Club Not Found</h2>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">The requested club profile does not exist or has been removed.</p>
        <a href="clubs.html" class="btn btn-lime">Back to Club Discovery &rarr;</a>
      </div>
    `;
  }
}
