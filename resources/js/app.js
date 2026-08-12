import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import OfflineBanner from './Components/OfflineBanner.vue';

// Only run the PWA service worker in production. In dev it intercepts
// navigations and serves a cached HTML shell, which means the browser never
// gets a fresh XSRF-TOKEN cookie from Laravel -> CSRF mismatch (419) on POSTs.
if (import.meta.env.PROD) {
    import('virtual:pwa-register').then(({ registerSW }) => {
        // No { immediate: true } — that caused an update check on every page load,
        // which could activate a new SW mid-session and interrupt in-flight requests.
        // autoUpdate handles the update lifecycle without forcing an immediate check.
        registerSW();
    });
} else if ('serviceWorker' in navigator) {
    // Tear down any stale service worker left over from a previous build so it
    // stops serving cached pages and causing 419 errors during development.
    navigator.serviceWorker.getRegistrations().then((registrations) => {
        registrations.forEach((registration) => registration.unregister());
    });
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () =>
                h('div', { style: 'display:contents' }, [
                    h(App, props),
                    h(OfflineBanner),
                ]),
        });

        app.config.errorHandler = (err, _instance, info) => {
            console.error('[Gathr]', info, err);
        };

        return app.use(plugin).use(ZiggyVue).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
