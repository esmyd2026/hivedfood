<script setup>
import IconGlyph from '../ui/IconGlyph.vue';
import ModuleButtons from './ModuleButtons.vue';

defineProps({
    module: { type: Object, required: true },
    dark: { type: Boolean, default: false },
});
</script>

<template>
    <article class="flex h-full gap-4">
        <span
            v-if="module.icon"
            :class="[
                'grid h-11 w-11 shrink-0 place-items-center rounded-xl',
                dark ? 'bg-white/8 text-honey' : 'bg-ivory text-charcoal',
            ]"
        >
            <IconGlyph :name="module.icon" :size="20" />
        </span>

        <div class="min-w-0">
            <h3 v-if="module.title" class="font-sans text-base font-bold">{{ module.title }}</h3>
            <p v-if="module.description" class="mt-1.5 text-sm leading-relaxed opacity-70">{{ module.description }}</p>

            <ul v-if="module.bullets?.length" class="mt-2.5 space-y-1.5">
                <li v-for="bullet in module.bullets" :key="bullet" class="flex items-start gap-2 text-xs opacity-70">
                    <IconGlyph name="check" :size="13" class="mt-0.5 shrink-0 text-coral" />
                    {{ bullet }}
                </li>
            </ul>

            <ModuleButtons :buttons="module.buttons" :dark="dark" class="mt-4" />
        </div>
    </article>
</template>
