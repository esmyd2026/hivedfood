<script setup>
import ResourceManager from '../components/ResourceManager.vue';

const columns = [
    { key: 'author', label: 'Autor', strong: true },
    { key: 'business', label: 'Negocio' },
    { key: 'quote', label: 'Testimonio', truncate: true },
    { key: 'rating', label: 'Valoración' },
    { key: 'is_active', label: 'Publicado', type: 'toggle' },
];

const fields = [
    { key: 'author', label: 'Autor', type: 'text', required: true, maxlength: 120 },
    { key: 'role', label: 'Cargo', type: 'text', maxlength: 120, placeholder: 'Propietario, Gerente…' },
    { key: 'business', label: 'Negocio', type: 'text', maxlength: 120 },
    { key: 'rating', label: 'Valoración (1-5)', type: 'number', min: 1, max: 5, default: 5 },
    { key: 'quote', label: 'Testimonio', type: 'textarea', span: 2, rows: 4, required: true, maxlength: 1000 },
    { key: 'avatar', label: 'Foto', type: 'image' },
    { key: 'is_active', label: 'Publicado', type: 'boolean', default: true },
];
</script>

<template>
    <div>
        <div class="mb-5 max-w-3xl rounded-xl border border-honey/40 bg-honey-soft/50 px-4 py-3 text-sm">
            <strong class="font-sans">Solo testimonios reales.</strong>
            Publica únicamente frases autorizadas por el negocio que las emite. La sección de testimonios permanece
            oculta en el sitio mientras no haya ninguno publicado.
        </div>

        <ResourceManager endpoint="testimonios" singular="testimonio" :columns="columns" :fields="fields" reorderable />
    </div>
</template>
