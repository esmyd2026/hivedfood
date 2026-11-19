<script setup>
import ResourceManager from '../components/ResourceManager.vue';

const columns = [
    { key: 'image_desktop_url', label: 'Imagen', type: 'image' },
    { key: 'title', label: 'Título', strong: true, truncate: true },
    { key: 'cta_text', label: 'Botón' },
    {
        key: 'transition',
        label: 'Transición',
        type: 'chip',
        map: {
            fade: { label: 'Fundido', tone: 'info' },
            slide: { label: 'Deslizar', tone: 'info' },
            zoom: { label: 'Zoom', tone: 'info' },
        },
    },
    { key: 'is_active', label: 'Activo', type: 'toggle' },
    {
        key: 'window',
        label: 'Programación',
        format: (row) => {
            if (!row.starts_at && !row.ends_at) {
                return 'Siempre';
            }

            const from = row.starts_at ? new Date(row.starts_at).toLocaleDateString('es') : '—';
            const to = row.ends_at ? new Date(row.ends_at).toLocaleDateString('es') : '—';

            return `${from} → ${to}`;
        },
    },
];

const fields = [
    { key: 'eyebrow', label: 'Antetítulo', type: 'text', maxlength: 80, hint: 'Texto pequeño sobre el título.' },
    { key: 'title', label: 'Título', type: 'text', required: true, maxlength: 160 },
    { key: 'subtitle', label: 'Subtítulo', type: 'text', maxlength: 200 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 3, maxlength: 600 },
    { key: 'cta_text', label: 'Texto del botón principal', type: 'text', maxlength: 60 },
    { key: 'cta_url', label: 'Enlace del botón principal', type: 'text', placeholder: '#plataforma' },
    { key: 'cta_secondary_text', label: 'Texto del botón secundario', type: 'text', maxlength: 60 },
    { key: 'cta_secondary_url', label: 'Enlace del botón secundario', type: 'text', placeholder: '#demostraciones' },
    {
        key: 'image_desktop',
        label: 'Captura del panel',
        type: 'image',
        folder: 'banners',
        hint: 'Se muestra dentro del marco de navegador del hero. Si lo dejas vacío se dibuja una maqueta del tablero de pedidos.',
    },
    {
        key: 'image_mobile',
        label: 'Captura de la tienda (móvil)',
        type: 'image',
        folder: 'banners',
        hint: 'Se muestra dentro del teléfono del hero. Si lo dejas vacío se dibuja una maqueta de la tienda.',
    },
    { key: 'image_alt', label: 'Texto alternativo (ALT)', type: 'text', span: 2, maxlength: 200, hint: 'Describe la imagen para accesibilidad y SEO.' },
    {
        key: 'theme',
        label: 'Tema',
        type: 'select',
        required: true,
        default: 'dark',
        options: [
            { value: 'dark', label: 'Oscuro' },
            { value: 'light', label: 'Claro' },
        ],
    },
    {
        key: 'align',
        label: 'Alineación',
        type: 'select',
        required: true,
        default: 'left',
        options: [
            { value: 'left', label: 'Izquierda' },
            { value: 'center', label: 'Centrada' },
        ],
    },
    {
        key: 'transition',
        label: 'Transición',
        type: 'select',
        required: true,
        default: 'fade',
        options: [
            { value: 'fade', label: 'Fundido' },
            { value: 'slide', label: 'Deslizar' },
            { value: 'zoom', label: 'Zoom' },
        ],
    },
    { key: 'duration', label: 'Duración (ms)', type: 'number', min: 2000, max: 20000, step: 500, default: 6000 },
    { key: 'starts_at', label: 'Publicar desde', type: 'datetime-local', hint: 'Opcional.' },
    { key: 'ends_at', label: 'Publicar hasta', type: 'datetime-local', hint: 'Opcional.' },
    { key: 'is_active', label: 'Banner activo', type: 'boolean', span: 2, default: true },
];
</script>

<template>
    <div>
        <p class="mb-5 max-w-2xl text-sm text-charcoal/60">
            Los banners alimentan el carrusel principal del sitio. El orden de esta tabla es el orden de aparición.
        </p>

        <ResourceManager
            endpoint="banners"
            singular="banner"
            :columns="columns"
            :fields="fields"
            reorderable
            empty-message="Aún no hay banners. Crea el primero para el hero del sitio."
        />
    </div>
</template>
