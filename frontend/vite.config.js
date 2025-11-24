import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
    plugins: [
        vue()
    ],
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
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'vue-router', 'pinia', 'axios'],
                    ui: ['vuetify', 'vue-toastification', 'sweetalert2']
                }
            }
        }
    },
    base: '/',
});
