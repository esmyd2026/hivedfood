<script setup>
import ResourceManager from '../components/ResourceManager.vue';
import AdminIcon from '../components/AdminIcon.vue';

const siteUrl = window.__ADMIN__?.siteUrl ?? '/';

const columns = [
    { key: 'page_key', label: 'Página', strong: true },
    { key: 'path', label: 'Ruta' },
    { key: 'meta_title', label: 'Meta title', truncate: true },
    {
        key: 'meta_description',
        label: 'Longitud descripción',
        format: (row) => (row.meta_description ? `${row.meta_description.length} car.` : '—'),
    },
    { key: 'robots', label: 'Robots' },
    { key: 'in_sitemap', label: 'En sitemap', type: 'toggle' },
];

const fields = [
    { key: 'page_key', label: 'Clave de página', type: 'text', required: true, maxlength: 60, hint: 'Identificador interno: home, demos, quoter…' },
    { key: 'path', label: 'Ruta', type: 'text', maxlength: 190, placeholder: '/' },
    { key: 'name', label: 'Nombre descriptivo', type: 'text', maxlength: 120 },
    { key: 'canonical_url', label: 'URL canónica', type: 'url', placeholder: 'https://' },
    { key: 'meta_title', label: 'Meta title', type: 'text', span: 2, maxlength: 180, hint: 'Ideal entre 50 y 60 caracteres.' },
    { key: 'meta_description', label: 'Meta description', type: 'textarea', span: 2, rows: 3, maxlength: 320, hint: 'Ideal entre 140 y 160 caracteres.' },
    { key: 'meta_keywords', label: 'Palabras clave', type: 'textarea', span: 2, rows: 2, maxlength: 500 },
    { key: 'og_title', label: 'Open Graph · título', type: 'text', maxlength: 180 },
    { key: 'og_type', label: 'Open Graph · tipo', type: 'text', maxlength: 40, default: 'website' },
    { key: 'og_description', label: 'Open Graph · descripción', type: 'textarea', span: 2, rows: 2, maxlength: 320 },
    { key: 'og_image', label: 'Open Graph · imagen', type: 'image', hint: 'Recomendado 1200×630 px.' },
    {
        key: 'twitter_card',
        label: 'Twitter card',
        type: 'select',
        default: 'summary_large_image',
        options: [
            { value: 'summary_large_image', label: 'summary_large_image' },
            { value: 'summary', label: 'summary' },
        ],
    },
    {
        key: 'robots',
        label: 'Robots',
        type: 'select',
        default: 'index,follow',
        options: [
            { value: 'index,follow', label: 'index,follow' },
            { value: 'noindex,follow', label: 'noindex,follow' },
            { value: 'index,nofollow', label: 'index,nofollow' },
            { value: 'noindex,nofollow', label: 'noindex,nofollow' },
        ],
    },
    { key: 'sitemap_priority', label: 'Prioridad en sitemap', type: 'number', min: 0, max: 1, step: 0.1, default: 0.8 },
    {
        key: 'sitemap_changefreq',
        label: 'Frecuencia de cambio',
        type: 'select',
        default: 'weekly',
        options: ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'].map((value) => ({ value, label: value })),
    },
    {
        key: 'structured_data',
        label: 'Datos estructurados adicionales',
        type: 'json',
        span: 2,
        rows: 6,
        hint: 'JSON-LD extra que se añade al grafo schema.org de la página.',
    },
    { key: 'in_sitemap', label: 'Incluir en el sitemap', type: 'boolean', span: 2, default: true },
];
</script>

<template>
    <div>
        <div class="mb-5 flex flex-wrap gap-2">
            <a :href="`${siteUrl}sitemap.xml`" target="_blank" rel="noopener" class="ad-btn ad-btn-ghost">
                <AdminIcon name="external" :size="14" />
                Ver sitemap.xml
            </a>
            <a :href="`${siteUrl}robots.txt`" target="_blank" rel="noopener" class="ad-btn ad-btn-ghost">
                <AdminIcon name="external" :size="14" />
                Ver robots.txt
            </a>
        </div>

        <ResourceManager endpoint="seo" singular="página" :columns="columns" :fields="fields" />
    </div>
</template>
