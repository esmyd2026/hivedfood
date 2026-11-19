<script setup>
import ResourceManager from '../components/ResourceManager.vue';

const columns = [
    { key: 'name', label: 'Funcionalidad', strong: true },
    { key: 'category', label: 'Categoría' },
    { key: 'short_description', label: 'Descripción corta', truncate: true },
    { key: 'is_highlighted', label: 'Destacada', type: 'toggle' },
    { key: 'is_available', label: 'Disponible', type: 'toggle' },
    { key: 'is_active', label: 'Publicada', type: 'toggle' },
];

const fields = [
    { key: 'name', label: 'Nombre', type: 'text', required: true, maxlength: 140 },
    { key: 'slug', label: 'Slug', type: 'text', required: true, maxlength: 140, hint: 'Solo minúsculas, números y guiones.' },
    { key: 'category', label: 'Categoría', type: 'text', maxlength: 60, placeholder: 'tienda, operacion, personalizacion…' },
    {
        key: 'icon',
        label: 'Icono',
        type: 'select',
        options: [
            'storefront', 'menu', 'receipt', 'palette', 'image', 'dashboard', 'mobile',
            'card', 'map', 'tag', 'chart', 'building', 'globe', 'users', 'coins', 'spark',
        ].map((value) => ({ value, label: value })),
    },
    { key: 'short_description', label: 'Descripción corta', type: 'text', span: 2, maxlength: 255 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 3, maxlength: 1500 },
    { key: 'bullets', label: 'Puntos destacados', type: 'tags', span: 2, hint: 'Se muestran como lista en la tarjeta.' },
    { key: 'image', label: 'Imagen', type: 'image' },
    { key: 'is_highlighted', label: 'Mostrar como destacada', type: 'boolean', hint: 'Las destacadas usan tarjeta grande.' },
    {
        key: 'is_available',
        label: 'Disponible en la plataforma',
        type: 'boolean',
        default: true,
        hint: 'Desmárcala si la funcionalidad aún no existe en el producto.',
    },
    { key: 'is_active', label: 'Publicada en el sitio', type: 'boolean', default: true },
];
</script>

<template>
    <div>
        <div class="mb-5 max-w-3xl rounded-xl border border-honey/40 bg-honey-soft/50 px-4 py-3 text-sm">
            <strong class="font-sans">Importante.</strong>
            Publica únicamente funcionalidades que la plataforma realmente ofrece hoy. Las que están sin publicar
            quedan como borrador hasta que el equipo de producto las confirme.
        </div>

        <ResourceManager
            endpoint="funcionalidades"
            singular="funcionalidad"
            :columns="columns"
            :fields="fields"
            reorderable
        />
    </div>
</template>
