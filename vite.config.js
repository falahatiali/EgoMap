import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import collectModuleAssetsPaths from './vite-module-loader.js';

async function getConfig() {
    const paths = [
        'resources/css/bootstrap-ltr.css',
        'resources/css/bootstrap-rtl.css',
        'resources/css/app.css',
        'resources/css/admin.css',
        'resources/js/app.js',
        'resources/js/quiz-take.js',
    ];

    const allPaths = await collectModuleAssetsPaths(paths, 'Modules');

    return defineConfig({
        plugins: [
            laravel({
                input: allPaths,
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
}

export default getConfig();
