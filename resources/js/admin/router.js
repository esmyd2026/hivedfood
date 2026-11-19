import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/admin',
        name: 'dashboard',
        component: () => import('./views/DashboardView.vue'),
        meta: { title: 'Dashboard' },
    },
    {
        path: '/admin/banners',
        name: 'banners',
        component: () => import('./views/BannersView.vue'),
        meta: { title: 'Banners' },
    },
    {
        path: '/admin/imagenes',
        name: 'media',
        component: () => import('./views/MediaView.vue'),
        meta: { title: 'Biblioteca de imágenes' },
    },
    {
        path: '/admin/contenido',
        name: 'content',
        component: () => import('./views/ContentView.vue'),
        meta: { title: 'Contenido' },
    },
    {
        path: '/admin/funcionalidades',
        name: 'features',
        component: () => import('./views/FeaturesView.vue'),
        meta: { title: 'Funcionalidades' },
    },
    {
        path: '/admin/demostraciones',
        name: 'demos',
        component: () => import('./views/DemosView.vue'),
        meta: { title: 'Demostraciones' },
    },
    {
        path: '/admin/testimonios',
        name: 'testimonials',
        component: () => import('./views/TestimonialsView.vue'),
        meta: { title: 'Testimonios' },
    },
    {
        path: '/admin/faqs',
        name: 'faqs',
        component: () => import('./views/FaqsView.vue'),
        meta: { title: 'Preguntas frecuentes' },
    },
    {
        path: '/admin/cotizador',
        name: 'quoter',
        component: () => import('./views/QuoterView.vue'),
        meta: { title: 'Cotizador' },
    },
    {
        path: '/admin/leads',
        name: 'leads',
        component: () => import('./views/LeadsView.vue'),
        meta: { title: 'Leads' },
    },
    {
        path: '/admin/cotizaciones',
        name: 'quotations',
        component: () => import('./views/QuotationsView.vue'),
        meta: { title: 'Cotizaciones' },
    },
    {
        path: '/admin/seo',
        name: 'seo',
        component: () => import('./views/SeoView.vue'),
        meta: { title: 'SEO' },
    },
    {
        path: '/admin/configuracion',
        name: 'settings',
        component: () => import('./views/SettingsView.vue'),
        meta: { title: 'Configuración' },
    },
    { path: '/admin/:pathMatch(.*)*', redirect: { name: 'dashboard' } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.afterEach((to) => {
    document.title = `${to.meta.title ?? 'Panel'} · HIVEDFOOD`;
});

export default router;
