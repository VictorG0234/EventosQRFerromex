import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// Interceptor global para manejar errores CSRF
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // CSRF token mismatch - recargar página para obtener nuevo token
            alert('Tu sesión ha expirado. La página se recargará.');
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
