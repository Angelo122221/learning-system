import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import {
    asset,
    configureAxiosBasePath,
    configureDomBasePath,
    configureFetchBasePath,
    configureInertiaRouterBasePath,
    configureWayfinderBasePath,
    configureZiggyBasePath,
} from './lib/basePath';

configureFetchBasePath();
configureAxiosBasePath();
configureDomBasePath();
configureInertiaRouterBasePath(router);
configureZiggyBasePath();
configureWayfinderBasePath([
    ...Object.values(import.meta.glob('./routes/**/*.js', { eager: true })),
    ...Object.values(import.meta.glob('./actions/**/*.js', { eager: true })),
]);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const userPortalTitle = 'Crystal Portal';

createInertiaApp({
    title: (title) => title === userPortalTitle ? userPortalTitle : `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) }).use(plugin);

        vueApp.config.globalProperties.$asset = asset;
        vueApp.provide('asset', asset);

        return vueApp.use(ZiggyVue).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
