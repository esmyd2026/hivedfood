<script setup>
import ResourceManager from '../components/ResourceManager.vue';
import AdminIcon from '../components/AdminIcon.vue';

const columns = [
    { key: 'cover_image_url', label: 'Portada', type: 'image' },
    { key: 'business_name', label: 'Negocio', strong: true },
    { key: 'category', label: 'Categoría' },
    {
        key: 'demo_url',
        label: 'Enlace',
        format: (row) => (row.demo_url ? row.demo_url.replace(/^https?:\/\//, '') : 'Sin enlace'),
        truncate: true,
    },
    { key: 'is_featured', label: 'Destacada', type: 'toggle' },
    { key: 'is_published', label: 'Publicada', type: 'toggle' },
];

const fields = [
    { key: 'business_name', label: 'Nombre del negocio', type: 'text', required: true, maxlength: 140 },
    { key: 'slug', label: 'Slug', type: 'text', required: true, maxlength: 140 },
    { key: 'category', label: 'Categoría', type: 'text', maxlength: 80, placeholder: 'Restaurante, Comida rápida…' },
    {
        key: 'demo_url',
        label: 'URL de la demostración',
        type: 'url',
        placeholder: 'https://',
        hint: 'Sin enlace, la tarjeta ofrece solicitar acceso en lugar de un botón muerto.',
    },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 3, maxlength: 1000 },
    { key: 'logo', label: 'Logotipo', type: 'image' },
    { key: 'cover_image', label: 'Imagen de portada', type: 'image', hint: 'Recomendado 800×550 px.' },
    { key: 'cover_alt', label: 'Texto alternativo de la portada', type: 'text', span: 2, maxlength: 200 },
    { key: 'highlights', label: 'Funcionalidades destacadas', type: 'tags', span: 2 },
    {
        key: 'screenshots',
        label: 'Capturas',
        type: 'json',
        span: 2,
        rows: 4,
        hint: 'Lista JSON: [{"url": "/ruta.png", "alt": "Descripción"}]',
    },
    { key: 'accent_color', label: 'Color de acento', type: 'color', default: '#F4B83F' },
    { key: 'is_featured', label: 'Destacar en la galería', type: 'boolean' },
    { key: 'is_published', label: 'Publicada', type: 'boolean', span: 2, default: true },
];
</script>

<template>
    <div>
        <p class="mb-5 max-w-2xl text-sm text-charcoal/60">
            Implementaciones reales de la plataforma. Carga aquí las URL autorizadas de cada tienda.
        </p>

        <ResourceManager endpoint="demostraciones" singular="demostración" :columns="columns" :fields="fields" reorderable>
            <template #row-actions="{ row }">
                <a
                    v-if="row.demo_url"
                    :href="row.demo_url"
                    target="_blank"
                    rel="noopener"
                    class="grid h-8 w-8 place-items-center rounded-lg text-charcoal/50 transition hover:bg-ivory hover:text-charcoal"
                    aria-label="Abrir demostración"
                >
                    <AdminIcon name="external" :size="15" />
                </a>
            </template>
        </ResourceManager>
    </div>
</template>
