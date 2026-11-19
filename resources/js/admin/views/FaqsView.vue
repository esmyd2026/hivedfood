<script setup>
import ResourceManager from '../components/ResourceManager.vue';

const columns = [
    { key: 'question', label: 'Pregunta', strong: true, truncate: true },
    { key: 'category', label: 'Categoría' },
    { key: 'is_active', label: 'Publicada', type: 'toggle' },
];

const fields = [
    { key: 'question', label: 'Pregunta', type: 'text', span: 2, required: true, maxlength: 255 },
    { key: 'answer', label: 'Respuesta', type: 'textarea', span: 2, rows: 5, required: true, maxlength: 3000 },
    { key: 'category', label: 'Categoría', type: 'text', maxlength: 60, default: 'general' },
    { key: 'is_active', label: 'Publicada', type: 'boolean', default: true },
];
</script>

<template>
    <div>
        <p class="mb-5 max-w-2xl text-sm text-charcoal/60">
            Las preguntas publicadas alimentan la sección de FAQ y los datos estructurados
            <code class="rounded bg-ivory-dark px-1 py-0.5 text-xs">FAQPage</code> del sitio.
        </p>

        <ResourceManager endpoint="faqs" singular="pregunta" :columns="columns" :fields="fields" reorderable />
    </div>
</template>
