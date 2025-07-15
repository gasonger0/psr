import { defineConfig } from 'vite';
import vuePlugin from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vuePlugin()
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js/vueapp'),
            '@boards': path.resolve(__dirname, './resources/js/vueapp/components/boards'),
            '@common': path.resolve(__dirname, './resources/js/vueapp/components/common'),
            '@modals': path.resolve(__dirname, './resources/js/vueapp/components/modals'),
            '@stores': path.resolve(__dirname, './resources/js/vueapp/store'),
        }
    },
    server: {
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        watch: {
            usePolling: true,
        },
        host: true,
        port: 5173,
    }
});
