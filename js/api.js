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
    // 1. Explicit override if set
    if (typeof window !== 'undefined' && window.CEMS_API_BASE) {
      return window.CEMS_API_BASE.replace(/\/?$/, '/');
    }

    // 2. Detect static dev servers (e.g., VS Code Live Server on 5500/5501, Vite on 5173, etc.)
    // Static servers cannot execute PHP scripts and return HTTP 405 Method Not Allowed on POST requests.
    const staticDevPorts = ['5500', '5501', '5502', '3000', '5173', '4173'];
    if (typeof window !== 'undefined' && staticDevPorts.includes(window.location.port)) {
      console.info(`[CEMS API] Static server detected on port ${window.location.port}. Routing backend requests to PHP server at http://127.0.0.1:8080/`);
      return 'http://127.0.0.1:8080/';
    }

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
      // Important: 'include' passes credentials (cookies) across same-origin and localhost cross-port (e.g. 5500 -> 8080)
      credentials: 'include'
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
        if (response.status === 405) {
          console.error(`[API 405 Method Not Allowed]`);
          console.error(`  Endpoint: ${cleanEndpoint}`);
          console.error(`  Method:   ${fetchOptions.method}`);
          console.error(`  URL:      ${url.toString()}`);
          console.error(`  Payload: `, options.body);
          console.error(`  Response:`, data);

          const error = new Error('405 Method Not Allowed');
          error.status = 405;
          error.endpoint = cleanEndpoint;
          error.method = fetchOptions.method;
          error.data = data;
          error.userMessage = 'Something went wrong while completing this action. Please try again.';
          throw error;
        }

        const errorMsg = (data && data.message) ? data.message : `HTTP error ${response.status}`;
        const error = new Error(errorMsg);
        error.status = response.status;
        error.endpoint = cleanEndpoint;
        error.method = fetchOptions.method;
        error.data = data;
        error.userMessage = errorMsg;
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

  leaveClub(clubId) {
    return this.post('backend/clubs/leave.php', { club_id: clubId });
  }

  getMyClubs() {
    return this.get('backend/clubs/my.php');
  }
}

export const api = new ApiClient();
