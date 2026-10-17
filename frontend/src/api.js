let csrfToken = '';

export function setCsrfToken(token) {
    csrfToken = token || '';
}

export async function api(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});

    if (options.body !== undefined && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
    }
    if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && csrfToken) {
        headers.set('X-CSRF-TOKEN', csrfToken);
    }

    const response = await fetch(path, {
        credentials: 'same-origin',
        ...options,
        method,
        headers,
        body: options.body === undefined || options.body instanceof FormData
            ? options.body
            : JSON.stringify(options.body),
    });

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : await response.text();
    if (!response.ok) {
        const message = typeof data === 'object' && data?.message ? data.message : `Request failed (${response.status}).`;
        const error = new Error(message);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}
