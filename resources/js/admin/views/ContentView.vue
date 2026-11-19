<script setup>
import { computed, ref } from 'vue';
import ResourceManager from '../components/ResourceManager.vue';
import AdminIcon from '../components/AdminIcon.vue';

const COMPONENTS = [
    { value: 'header', label: 'Header y navegación' },
    { value: 'hero', label: 'Hero con banners' },
    { value: 'about', label: 'Presentación (tarjetas)' },
    { value: 'showcase', label: 'Pantallas del producto' },
    { value: 'problems', label: 'Problemas que resuelve' },
    { value: 'channels', label: 'Canales de entrada de pedidos' },
    { value: 'order-flow', label: 'Flujo del pedido' },
    { value: 'benefits', label: 'Beneficios' },
    { value: 'features', label: 'Funcionalidades' },
    { value: 'customization', label: 'Personalización' },
    { value: 'demos', label: 'Demostraciones' },
    { value: 'steps', label: 'Cómo funciona' },
    { value: 'business-types', label: 'Tipos de negocio' },
    { value: 'pricing', label: 'Precios y formas de cobro' },
    { value: 'quoter', label: 'Cotizador' },
    { value: 'testimonials', label: 'Testimonios' },
    { value: 'faq', label: 'Preguntas frecuentes' },
    { value: 'cta', label: 'Llamada a la acción' },
    { value: 'footer', label: 'Footer' },
    { value: 'modules', label: 'Módulos libres (usa la presentación de cada módulo)' },
];

const LAYOUTS = [
    { value: 'card', label: 'Tarjeta' },
    { value: 'text_image', label: 'Texto e imagen' },
    { value: 'image_text', label: 'Imagen con texto lateral' },
    { value: 'grid', label: 'Cuadrícula' },
    { value: 'carousel', label: 'Carrusel' },
    { value: 'gallery', label: 'Galería' },
    { value: 'stat', label: 'Estadística / paso' },
    { value: 'testimonial', label: 'Testimonio' },
    { value: 'cta', label: 'Llamada a la acción' },
];

const selected = ref(null);

const sectionColumns = [
    { key: 'name', label: 'Sección', strong: true },
    { key: 'key', label: 'Ancla' },
    {
        key: 'component',
        label: 'Presentación',
        format: (row) => COMPONENTS.find((c) => c.value === row.component)?.label ?? row.component,
    },
    { key: 'modules_count', label: 'Módulos', format: (row) => (row.modules?.length ?? 0) },
    { key: 'is_active', label: 'Visible', type: 'toggle' },
];

const sectionFields = [
    { key: 'name', label: 'Nombre interno', type: 'text', required: true, maxlength: 120 },
    { key: 'key', label: 'Clave / ancla', type: 'text', required: true, maxlength: 60, hint: 'Se usa como #ancla en el sitio.' },
    { key: 'component', label: 'Tipo de presentación', type: 'select', required: true, options: COMPONENTS, span: 2 },
    { key: 'eyebrow', label: 'Antetítulo', type: 'text', maxlength: 80 },
    { key: 'title', label: 'Título', type: 'text', maxlength: 200 },
    { key: 'subtitle', label: 'Subtítulo', type: 'text', span: 2, maxlength: 200 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 3, maxlength: 1500 },
    { key: 'image', label: 'Imagen', type: 'image' },
    { key: 'buttons', label: 'Botones', type: 'buttons', span: 2 },
    {
        key: 'settings',
        label: 'Configuración visual',
        type: 'json',
        span: 2,
        rows: 5,
        hint: 'Ej: {"background": "ivory", "columns": 3}. Fondos: white, ivory, charcoal, honey, coral.',
    },
    { key: 'is_active', label: 'Sección visible en el sitio', type: 'boolean', span: 2, default: true },
];

const moduleFields = computed(() => [
    { key: 'content_section_id', label: 'Sección', type: 'select', required: true, options: [], span: 2, default: selected.value?.id },
    { key: 'internal_name', label: 'Nombre interno', type: 'text', required: true, maxlength: 120 },
    {
        key: 'layout',
        label: 'Presentación',
        type: 'select',
        required: true,
        options: LAYOUTS,
        default: 'card',
        hint: 'Se respeta en las secciones de tipo "Módulos libres". Las demás secciones tienen un diseño propio.',
    },
    { key: 'eyebrow', label: 'Antetítulo', type: 'text', maxlength: 80 },
    { key: 'value', label: 'Valor / número', type: 'text', maxlength: 60, hint: 'Para estadísticas o pasos: 01, 250, 98%…' },
    { key: 'title', label: 'Título', type: 'text', span: 2, maxlength: 200 },
    { key: 'subtitle', label: 'Subtítulo', type: 'text', span: 2, maxlength: 200 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 3, maxlength: 1500 },
    {
        key: 'icon',
        label: 'Icono',
        type: 'select',
        options: [
            'storefront', 'palette', 'dashboard', 'check-badge', 'percent', 'chat', 'list', 'sparkle',
            'coins', 'users', 'sliders', 'mobile', 'flow', 'growth', 'menu', 'image', 'text', 'receipt',
            'card', 'map', 'tag', 'chart', 'building', 'globe', 'rocket', 'headset', 'refresh', 'layers',
        ].map((value) => ({ value, label: value })),
    },
    { key: 'image', label: 'Imagen', type: 'image' },
    { key: 'image_alt', label: 'Texto alternativo', type: 'text', span: 2, maxlength: 200 },
    { key: 'bullets', label: 'Viñetas', type: 'tags', span: 2 },
    { key: 'buttons', label: 'Botones', type: 'buttons', span: 2 },
    {
        key: 'settings',
        label: 'Configuración',
        type: 'json',
        span: 2,
        rows: 4,
        hint: 'En secciones de tipo «Pantallas del producto»: {"screen": "store"} elige la maqueta '
            + '(store, orders, catalog, kitchen) y {"reversed": true} invierte el lado del dispositivo.',
    },
    { key: 'is_active', label: 'Módulo visible', type: 'boolean', span: 2, default: true },
]);

const moduleColumns = [
    { key: 'internal_name', label: 'Nombre interno', strong: true },
    { key: 'title', label: 'Título', truncate: true },
    {
        key: 'layout',
        label: 'Presentación',
        format: (row) => LAYOUTS.find((l) => l.value === row.layout)?.label ?? row.layout,
    },
    { key: 'icon', label: 'Icono' },
    { key: 'is_active', label: 'Visible', type: 'toggle' },
];

const moduleScope = computed(() => ({ content_section_id: selected.value?.id }));
</script>

<template>
    <div class="space-y-8">
        <section>
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base">Secciones de la página principal</h2>
                    <p class="mt-1 max-w-2xl text-sm text-charcoal/60">
                        Reordena, activa o desactiva cada bloque del sitio. Header, hero y footer no se pueden eliminar,
                        pero sí editar.
                    </p>
                </div>
            </div>

            <ResourceManager
                endpoint="secciones"
                singular="sección"
                :columns="sectionColumns"
                :fields="sectionFields"
                reorderable
            >
                <template #row-actions="{ row }">
                    <button
                        type="button"
                        :class="[
                            'grid h-8 w-8 place-items-center rounded-lg transition',
                            selected?.id === row.id
                                ? 'bg-charcoal text-honey'
                                : 'text-charcoal/50 hover:bg-ivory hover:text-charcoal',
                        ]"
                        :aria-label="`Ver módulos de ${row.name}`"
                        @click="selected = selected?.id === row.id ? null : row"
                    >
                        <AdminIcon name="layers" :size="15" />
                    </button>
                </template>
            </ResourceManager>
        </section>

        <Transition name="panel">
            <section v-if="selected">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-charcoal px-5 py-4 text-ivory">
                    <div>
                        <p class="font-sans text-xs font-bold uppercase tracking-wider text-honey">Módulos de contenido</p>
                        <h2 class="mt-1 text-base text-ivory">{{ selected.name }}</h2>
                    </div>
                    <button type="button" class="ad-btn ad-btn-ghost" @click="selected = null">
                        <AdminIcon name="close" :size="14" />
                        Cerrar
                    </button>
                </div>

                <ResourceManager
                    :key="selected.id"
                    endpoint="modulos"
                    singular="módulo"
                    :columns="moduleColumns"
                    :fields="moduleFields.filter((f) => f.key !== 'content_section_id')"
                    :defaults="{ content_section_id: selected.id, is_active: true, layout: 'card' }"
                    :scope="moduleScope"
                    reorderable
                    :searchable="false"
                    empty-message="Esta sección aún no tiene módulos de contenido."
                />
            </section>
        </Transition>
    </div>
</template>

<style scoped>
.panel-enter-active,
.panel-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.panel-enter-from,
.panel-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
