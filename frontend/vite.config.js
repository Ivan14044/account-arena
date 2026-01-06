import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'src')
        }
    },
    server: {
        port: 3000,
        host: true,
        strictPort: true,
        proxy: {
            '/storage': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false
            },
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false
            }
        }
    },
    build: {
        outDir: 'dist',
        sourcemap: false,
        minify: 'esbuild',
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks: id => {
                    // Vendor chunks - core libraries
                    if (id.includes('node_modules')) {
                        // Vue core - отдельный chunk
                        if (
                            id.includes('vue') ||
                            id.includes('vue-router') ||
                            id.includes('pinia')
                        ) {
                            return 'vendor-core';
                        }
                        // Vuetify - большой, отдельно
                        if (id.includes('vuetify')) {
                            return 'vendor-vuetify';
                        }
                        // Axios - отдельно
                        if (id.includes('axios')) {
                            return 'vendor-axios';
                        }
                        // i18n - отдельно
                        if (id.includes('vue-i18n') || id.includes('@intlify')) {
                            return 'vendor-i18n';
                        }
                        // Тяжелые библиотеки - отдельно
                        if (
                            id.includes('swiper') ||
                            id.includes('lottie') ||
                            id.includes('chart')
                        ) {
                            return 'vendor-heavy';
                        }
                        // Lodash для debounce
                        if (id.includes('lodash')) {
                            return 'vendor-utils';
                        }
                        // Остальные vendor
                        return 'vendor';
                    }
                    // Stores - отдельный chunk
                    if (id.includes('/stores/')) {
                        return 'stores';
                    }
                    // Тяжелые компоненты - отдельно
                    if (id.includes('ProfilePage') || id.includes('CheckoutPage')) {
                        return 'pages-heavy';
                    }
                }
            }
        }
    },
    base: '/'
});
