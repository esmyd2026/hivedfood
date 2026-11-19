import http, { parseError } from '../../lib/http';

const base = window.__ADMIN__?.base ?? '/admin/api';

/** Does this payload need multipart encoding? */
function hasFiles(payload) {
    return Object.values(payload).some((value) => value instanceof File || value instanceof Blob);
}

/**
 * Build a FormData body. Laravel reads booleans as "1"/"0", nulls are dropped
 * and arrays/objects are JSON-encoded so the backend can decode them.
 */
function toFormData(payload) {
    const form = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
        if (value === null || value === undefined) {
            return;
        }

        if (value instanceof File || value instanceof Blob) {
            form.append(key, value);
        } else if (typeof value === 'boolean') {
            form.append(key, value ? '1' : '0');
        } else if (typeof value === 'object') {
            form.append(key, JSON.stringify(value));
        } else {
            form.append(key, value);
        }
    });

    return form;
}

export const api = {
    url: (path) => `${base}/${String(path).replace(/^\/+/, '')}`,

    async get(path, params = {}) {
        const { data } = await http.get(api.url(path), { params });

        return data;
    },

    async post(path, payload = {}) {
        const body = hasFiles(payload) ? toFormData(payload) : payload;
        const { data } = await http.post(api.url(path), body);

        return data;
    },

    async destroy(path) {
        const { data } = await http.delete(api.url(path));

        return data;
    },
};

export { parseError };
