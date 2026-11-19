<script setup>
import IconGlyph from '../ui/IconGlyph.vue';
import ModuleButtons from './ModuleButtons.vue';

defineProps({
    module: { type: Object, required: true },
    dark: { type: Boolean, default: false },
});
</script>

<template>
    <article
        class="flex flex-col items-start gap-6 rounded-[1.5rem] bg-honey p-7 text-charcoal md:flex-row md:items-center md:justify-between md:p-9"
    >
        <div>
            <p v-if="module.eyebrow" class="hf-eyebrow">{{ module.eyebrow }}</p>
            <h3 v-if="module.title" class="mt-3 text-2xl md:text-3xl">{{ module.title }}</h3>
            <p v-if="module.description" class="mt-3 max-w-xl text-base opacity-75">{{ module.description }}</p>
        </div>

        <div class="shrink-0">
            <ModuleButtons :buttons="module.buttons" dark />
            <span v-if="!module.buttons?.length" class="grid h-14 w-14 place-items-center rounded-full bg-charcoal text-honey">
                <IconGlyph name="arrow-right" :size="22" />
            </span>
        </div>
    </article>
</template>
