import { defineConfig } from 'vite';
import vuePlugin from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vuePlugin()
    ],
    server: {
        // hmr: {
        //     host: 'localhost',
        //     port: 5173,
        // },
        watch: {
            usePolling: true,
        },
        host: '0.0.0.0',
        port: 5173,
    }
});
