import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

export function getErrorMessage(
    err: unknown,
    fallback = 'An error occurred.',
): string {
    if (err && typeof err === 'object' && 'response' in err) {
        const axiosErr = err as {
            response?: { data?: { message?: string } };
        };
        return axiosErr.response?.data?.message || fallback;
    }
    if (err instanceof Error) {
        return err.message;
    }
    return fallback;
}

export default api;
