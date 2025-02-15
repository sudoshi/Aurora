import axios from 'axios';

// Configure axios before it's used anywhere else
const configureAxios = () => {
    // Set default configs
    // No need for baseURL since we're serving from the same origin
    // axios.defaults.baseURL = 'http://localhost:8000';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.headers.common['Content-Type'] = 'application/json';
    axios.defaults.headers.common['Accept'] = 'application/json';
    axios.defaults.withCredentials = true; // Important for CSRF cookie and CORS

    // Add CSRF token if it exists (for Laravel)
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    }

    // Add response interceptor to handle common API errors
    axios.interceptors.response.use(
        response => response,
        error => {
            console.error('API Error:', error);
            if (error.response?.status === 401) {
                // Clear auth data and redirect to login on unauthorized
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            }
            return Promise.reject(error);
        }
    );

    // Add auth token if it exists
    const authToken = localStorage.getItem('auth_token');
    if (authToken) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`;
    }
};

// Configure axios immediately
configureAxios();

// Make axios available globally
window.axios = axios;

export default configureAxios;
