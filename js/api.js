/**
 * CEMS - Centralized Relational API Client
 * Seamlessly resolves backend endpoints relative to project root across any XAMPP folder or PHP server.
 */

class ApiClient {
  constructor() {
    // Determine the base path dynamically from the current location
    this.baseUrl = this.resolveBaseUrl();
  }

  resolveBaseUrl() {
    const path = window.location.pathname;
    // Find where the project root is (if in a subdirectory like /cems/ or /cems-app/)
    // Check if we are inside /admin/ or other nested folder
    let segments = path.split('/').filter(Boolean);

    // If the last segment is an html/php file, remove it
    if (segments.length > 0 && segments[segments.length - 1].includes('.')) {
      segments.pop();
    }

    // If currently in an 'admin' directory, step back one level to root
    if (segments.length > 0 && segments[segments.length - 1] === 'admin') {
      segments.pop();
    }

    const basePath = segments.length > 0 ? '/' + segments.join('/') + '/' : '/';
    return window.location.origin + basePath;
  }

  async request(endpoint, options = {}) {
    // Strip leading slash from endpoint if present
    const cleanEndpoint = endpoint.replace(/^\/+/, '');
    const url = new URL(cleanEndpoint, this.baseUrl);

    if (options.params) {
      Object.keys(options.params).forEach(key => {
        if (options.params[key] !== undefined && options.params[key] !== null) {
          url.searchParams.append(key, options.params[key]);
        }
      });
    }

    const fetchOptions = {
      method: options.method || 'GET',
      headers: {
        'Accept': 'application/json',
        ...(options.headers || {})
      },
      // Important: Include cookies for PHP session persistence
      credentials: 'same-origin'
    };

    if (options.body) {
      if (options.body instanceof FormData) {
        fetchOptions.body = options.body;
      } else {
        fetchOptions.headers['Content-Type'] = 'application/json';
        fetchOptions.body = JSON.stringify(options.body);
      }
    }

    try {
      const response = await fetch(url.toString(), fetchOptions);
      const data = await response.json().catch(() => null);

      if (!response.ok) {
        const errorMsg = (data && data.message) ? data.message : `HTTP error ${response.status}`;
        const error = new Error(errorMsg);
        error.status = response.status;
        error.data = data;
        throw error;
      }

      return data;
    } catch (err) {
      // Enhance network or fetch errors
      if (!err.status) {
        err.isNetworkError = true;
      }
      throw err;
    }
  }

  get(endpoint, params = {}) {
    return this.request(endpoint, { method: 'GET', params });
  }

  post(endpoint, body = {}) {
    return this.request(endpoint, { method: 'POST', body });
  }

  async checkStatus() {
    try {
      const res = await this.get('backend/auth/me.php');
      return res.data || { database_connected: false, authenticated: false };
    } catch (e) {
      return { database_connected: false, authenticated: false, error: e.message };
    }
  }

  // Club Endpoints
  getClubs(params = {}) {
    return this.get('backend/clubs/list.php', params);
  }

  getClub(idOrSlug) {
    const isId = !isNaN(Number(idOrSlug));
    const param = isId ? { id: idOrSlug } : { slug: idOrSlug };
    return this.get('backend/clubs/get.php', param);
  }

  joinClub(clubId, action = 'toggle') {
    return this.post('backend/clubs/join.php', { club_id: clubId, action });
  }

  getMyClubs() {
    return this.get('backend/clubs/my.php');
  }
}

export const api = new ApiClient();
