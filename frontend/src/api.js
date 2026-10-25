const csrfSafeMethods = ['GET', 'HEAD', 'OPTIONS'];
const invalidCsrfTokenCode = 'invalid_csrf_token';

let csrfToken = '';
let csrfRefreshPromise = null;

export function setCsrfToken(token) {
    csrfToken = token || '';
}

async function refreshCsrfToken() {
    if (csrfRefreshPromise === null) {
        csrfRefreshPromise = apiRequest('/api/auth/session', {}, false)
            .then((session) => {
                setCsrfToken(session.csrfToken);
            })
            .finally(() => {
                csrfRefreshPromise = null;
            });
    }

    return csrfRefreshPromise;
}

async function apiRequest(path, options, retryInvalidCsrfToken) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});

    if (options.body !== undefined && !(options.body instanceof FormData) && !headers.has('Content-Type')) {
        headers.set(
            'Content-Type',
            method === 'PATCH' ? 'application/merge-patch+json' : 'application/json',
        );
    }
    if (!csrfSafeMethods.includes(method) && csrfToken) {
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
        if (
            retryInvalidCsrfToken
            && !csrfSafeMethods.includes(method)
            && response.status === 403
            && typeof data === 'object'
            && data?.code === invalidCsrfTokenCode
        ) {
            await refreshCsrfToken();

            return apiRequest(path, options, false);
        }

        const message = typeof data === 'object' && data?.message ? data.message : `Request failed (${response.status}).`;
        const error = new Error(message);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}

export async function api(path, options = {}) {
    return apiRequest(path, options, true);
}
