export class HttpError extends Error {
    constructor(status, data) {
        super(`HTTP ${status}`);
        this.status = status;
        this.data = data;
    }
}

let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? null;

function refreshCsrfToken(response) {
    const headerToken = response.headers.get('X-CSRF-TOKEN');

    if (headerToken) {
        csrfToken = headerToken;

        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', headerToken);
    }
}

async function request(method, path, body = null) {
    const headers = { Accept: 'application/json' };

    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const isFormData = body instanceof FormData;

    if (body !== null && !isFormData) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(path, {
        method,
        headers,
        credentials: 'same-origin',
        body: isFormData ? body : body !== null ? JSON.stringify(body) : null,
    });

    refreshCsrfToken(response);

    if (response.status === 401) {
        window.location.assign('/login');

        throw new HttpError(401, null);
    }

    if (!response.ok) {
        let data = null;

        try {
            data = await response.json();
        } catch {
            // ignore non-JSON error bodies
        }

        throw new HttpError(response.status, data);
    }

    if (response.status === 204) {
        return null;
    }

    return response.json();
}

export const http = {
    get: (path) => request('GET', path),
    post: (path, body = {}) => request('POST', path, body),
    put: (path, body = {}) => request('PUT', path, body),
    patch: (path, body = {}) => request('PATCH', path, body),
    delete: (path) => request('DELETE', path),
};

export default http;