import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            workbox: {
                navigateFallback: null,
                // skipWaiting is already handled by registerType: 'autoUpdate' via
                // postMessage({ type: 'SKIP_WAITING' }) — setting it here as well
                // made the SW skip waiting twice (redundant) and could interrupt
                // in-flight Inertia requests on deployment.
                clientsClaim: true,
                // Do NOT cache Inertia XHR responses or full HTML pages.
                // Doing so causes stale-version reload loops after deployment:
                // SW serves old HTML → Inertia detects version mismatch → reload
                // → SW serves old HTML again. Let Vite's precache handle assets only.
                runtimeCaching: [],
            },
            includeAssets: ['logo.png', 'favicon.ico', 'robots.txt'],
            manifest: {
                name: 'Gathr',
                short_name: 'Gathr',
                description: 'Group payment never been this easy',
                theme_color: '#0096E3',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                background_color: '#ffffff',
                icons: [
                    {
                        src: '/logo.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/logo.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'
                    }
                ]
            }
        })
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
        },
    },
});
