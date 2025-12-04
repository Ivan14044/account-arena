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
        chunkSizeWarningLimit: 600,
        rollupOptions: {
            output: {
                manualChunks: id => {
                    // Vendor chunks - core libraries
                    if (id.includes('node_modules')) {
                        if (
                            id.includes('vue') ||
                            id.includes('vue-router') ||
                            id.includes('pinia')
                        ) {
                            return 'vendor-core';
                        }
                        if (id.includes('vuetify')) {
                            return 'vendor-vuetify';
                        }
                        if (id.includes('axios')) {
                            return 'vendor-axios';
                        }
                        if (id.includes('vue-i18n') || id.includes('@intlify')) {
                            return 'vendor-i18n';
                        }
                        if (
                            id.includes('chart') ||
                            id.includes('swiper') ||
                            id.includes('lottie')
                        ) {
                            return 'vendor-charts';
                        }
                        // Other node_modules
                        return 'vendor';
                    }
                    // Store chunks - group stores together
                    if (id.includes('/stores/')) {
                        return 'stores';
                    }
                }
            }
        }
    },
    base: '/'
});
