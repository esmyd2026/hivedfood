<script setup>
import { computed } from 'vue';
import ModuleCard from './ModuleCard.vue';
import ModuleGrid from './ModuleGrid.vue';
import ModuleStat from './ModuleStat.vue';
import ModuleTestimonial from './ModuleTestimonial.vue';
import ModuleTextImage from './ModuleTextImage.vue';
import ModuleCta from './ModuleCta.vue';
import ModuleGallery from './ModuleGallery.vue';
import ModuleCarousel from './ModuleCarousel.vue';

const props = defineProps({
    /** Consecutive modules that share the same layout. */
    layout: { type: String, required: true },
    modules: { type: Array, required: true },
    dark: { type: Boolean, default: false },
    columns: { type: Number, default: 3 },
});

/** Layouts that render the whole group at once instead of item by item. */
const COLLECTIONS = {
    gallery: ModuleGallery,
    carousel: ModuleCarousel,
};

/** Layouts that render one component per module. */
const ITEMS = {
    card: ModuleCard,
    grid: ModuleGrid,
    stat: ModuleStat,
    testimonial: ModuleTestimonial,
    text_image: ModuleTextImage,
    image_text: ModuleTextImage,
    cta: ModuleCta,
};

const collection = computed(() => COLLECTIONS[props.layout] ?? null);
const item = computed(() => ITEMS[props.layout] ?? ModuleCard);

/** Full-bleed layouts stack vertically; the rest share a responsive grid. */
const stacked = computed(() => ['text_image', 'image_text', 'cta'].includes(props.layout));

const gridClass = computed(() => {
    if (stacked.value) {
        return 'space-y-12 md:space-y-16';
    }

    const columns = {
        2: 'sm:grid-cols-2',
        3: 'sm:grid-cols-2 lg:grid-cols-3',
        4: 'sm:grid-cols-2 lg:grid-cols-4',
    };

    return ['grid gap-6', columns[props.columns] ?? columns[3]].join(' ');
});
</script>

<template>
    <component v-if="collection" :is="collection" :modules="modules" :dark="dark" />

    <div v-else :class="gridClass">
        <component
            v-for="(module, i) in modules"
            :is="item"
            :key="module.id"
            :module="module"
            :dark="dark"
            :reversed="layout === 'image_text' ? i % 2 === 0 : false"
        />
    </div>
</template>
