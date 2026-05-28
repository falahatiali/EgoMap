import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/bootstrap-ltr.css',
                'resources/css/bootstrap-rtl.css',
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/js/app.js',
                'resources/js/quiz-take.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: 'egomap.test',
        hmr: {
            host: 'egomap.test',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
