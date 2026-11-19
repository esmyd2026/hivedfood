import axios from 'axios';

const token = document.querySelector('meta[name="csrf-token"]')?.content;

const http = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
    withCredentials: true,
});

if (token) {
    http.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

/**
 * Normalise an Axios error into { message, errors } so every form in the app
 * can render validation feedback the same way.
 */
export function parseError(error) {
    const response = error?.response;

    if (!response) {
        return {
            message: 'No pudimos conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.',
            errors: {},
        };
    }

    if (response.status === 419) {
        return {
            message: 'Tu sesión expiró. Actualiza la página e inténtalo de nuevo.',
            errors: {},
        };
    }

    if (response.status === 429) {
        return {
            message: 'Demasiados intentos seguidos. Espera un momento e inténtalo de nuevo.',
            errors: {},
        };
    }

    return {
        message: response.data?.message || 'Ocurrió un error inesperado.',
        errors: response.data?.errors || {},
    };
}

export default http;
