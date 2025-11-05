import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import checker from 'vite-plugin-checker';
import { VitePWA } from 'vite-plugin-pwa';
import { collectModuleAssetsPaths, collectModulePlugins } from './vite-module-loader.js';

async function getConfig() {
    const paths = [
        'resources/js/app.ts',
        'resources/css/app.css',
        'resources/css/filament/admin/theme.css',
    ];
    const modulePaths = await collectModuleAssetsPaths('extensions');
    const additionalPlugins = await collectModulePlugins('extensions');

    return defineConfig({
        build: {
            sourcemap: true, // Source map generation must be turned on
        },
        plugins: [
            laravel({
                input: [...paths, ...modulePaths],
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
            checker({
                // e.g. use TypeScript check
                typescript: true,
                vueTsc: true,
                lintCommand: 'eslint "./**/*.{ts,vue}"',
            }),
            VitePWA({
                registerType: 'autoUpdate',
                injectRegister: 'auto',
                workbox: {
                    globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                    runtimeCaching: [
                        {
                            urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                            handler: 'CacheFirst',
                            options: {
                                cacheName: 'google-fonts-cache',
                                expiration: {
                                    maxEntries: 10,
                                    maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
                                },
                                cacheableResponse: {
                                    statuses: [0, 200]
                                }
                            }
                        },
                        {
                            urlPattern: /^https:\/\/fonts\.gstatic\.com\/.*/i,
                            handler: 'CacheFirst',
                            options: {
                                cacheName: 'gstatic-fonts-cache',
                                expiration: {
                                    maxEntries: 10,
                                    maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
                                },
                                cacheableResponse: {
                                    statuses: [0, 200]
                                },
                            }
                        },
                        {
                            urlPattern: /\/api\/v1\/.*/i,
                            handler: 'NetworkFirst',
                            options: {
                                cacheName: 'api-cache',
                                expiration: {
                                    maxEntries: 100,
                                    maxAgeSeconds: 60 * 5 // 5 minutes
                                },
                                cacheableResponse: {
                                    statuses: [0, 200]
                                },
                                networkTimeoutSeconds: 10
                            }
                        },
                        {
                            urlPattern: /\.(png|jpg|jpeg|svg|gif|webp)$/i,
                            handler: 'CacheFirst',
                            options: {
                                cacheName: 'image-cache',
                                expiration: {
                                    maxEntries: 60,
                                    maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                                },
                                cacheableResponse: {
                                    statuses: [0, 200]
                                }
                            }
                        }
                    ]
                },
                manifest: {
                    name: 'Timeclocker - EU Time Tracking',
                    short_name: 'Timeclocker',
                    description: 'Privacy-first time tracking for EU freelancers and teams',
                    theme_color: '#0891b2',
                    background_color: '#ffffff',
                    display: 'standalone',
                    scope: '/',
                    start_url: '/dashboard',
                    orientation: 'portrait-primary',
                    icons: [
                        {
                            src: '/images/pwa-192x192.png',
                            sizes: '192x192',
                            type: 'image/png',
                            purpose: 'any'
                        },
                        {
                            src: '/images/pwa-512x512.png',
                            sizes: '512x512',
                            type: 'image/png',
                            purpose: 'any'
                        },
                        {
                            src: '/images/pwa-192x192-maskable.png',
                            sizes: '192x192',
                            type: 'image/png',
                            purpose: 'maskable'
                        },
                        {
                            src: '/images/pwa-512x512-maskable.png',
                            sizes: '512x512',
                            type: 'image/png',
                            purpose: 'maskable'
                        }
                    ],
                    categories: ['productivity', 'business', 'utilities'],
                    shortcuts: [
                        {
                            name: 'Start Timer',
                            short_name: 'Timer',
                            description: 'Quickly start time tracking',
                            url: '/time?action=start',
                            icons: [{ src: '/images/shortcut-timer.png', sizes: '96x96' }]
                        },
                        {
                            name: 'View Reports',
                            short_name: 'Reports',
                            description: 'View time tracking reports',
                            url: '/reporting',
                            icons: [{ src: '/images/shortcut-reports.png', sizes: '96x96' }]
                        }
                    ]
                },
                devOptions: {
                    enabled: true,
                    type: 'module'
                }
            }),
            ...additionalPlugins,
        ],
        server: {
            host: true,
            hmr: {
                host: process.env.VITE_HOST_NAME,
                clientPort: 80,
            },
        },
    });
}

export default getConfig();
