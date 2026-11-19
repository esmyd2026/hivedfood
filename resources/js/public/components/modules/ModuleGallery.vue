<script setup>
import { computed } from 'vue';
import IconGlyph from '../ui/IconGlyph.vue';

const props = defineProps({
    /** Every module assigned the "gallery" layout. */
    modules: { type: Array, required: true },
    dark: { type: Boolean, default: false },
});

const items = computed(() => props.modules.filter((module) => module.image));
</script>

<template>
    <div v-if="items.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <figure
            v-for="module in items"
            :key="module.id"
            class="group relative overflow-hidden rounded-[1.25rem]"
        >
            <img
                :src="module.image"
                :alt="module.image_alt || module.title || ''"
                class="aspect-[4/3] w-full object-cover transition-transform duration-700 group-hover:scale-105"
                width="800"
                height="600"
                loading="lazy"
                decoding="async"
            />

            <figcaption
                v-if="module.title || module.description"
                class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-charcoal/90 to-transparent p-5 pt-10 text-ivory"
            >
                <p v-if="module.title" class="font-sans text-base font-bold">{{ module.title }}</p>
                <p v-if="module.description" class="mt-1 text-xs opacity-75">{{ module.description }}</p>
            </figcaption>
        </figure>
    </div>

    <p
        v-else
        :class="['flex items-center gap-2 text-sm', dark ? 'text-ivory/55' : 'opacity-60']"
    >
        <IconGlyph name="image" :size="17" />
        Añade imágenes a estos módulos para mostrar la galería.
    </p>
</template>
