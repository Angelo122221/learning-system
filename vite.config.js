import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const appBasePath = (process.env.VITE_APP_BASE_PATH || '')
    .trim()
    .replace(/^\/+|\/+$/g, '');

export default defineConfig({
    base: appBasePath ? `/${appBasePath}/build/` : undefined,
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
    ],
});
