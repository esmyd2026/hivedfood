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
        :class="[
            'group flex h-full flex-col rounded-[1.25rem] border p-6 transition-all duration-500 hover:-translate-y-1.5',
            dark
                ? 'border-white/10 bg-white/[0.04] hover:border-white/25'
                : 'border-charcoal/8 bg-white shadow-[var(--shadow-soft)] hover:shadow-[var(--shadow-lift)]',
        ]"
    >
        <span
            v-if="module.icon"
            :class="[
                'grid h-12 w-12 place-items-center rounded-2xl',
                dark ? 'bg-honey text-charcoal' : 'bg-honey-soft text-charcoal',
            ]"
        >
            <IconGlyph :name="module.icon" :size="22" />
        </span>

        <p v-if="module.eyebrow" :class="['mt-4 hf-eyebrow', dark ? 'text-honey' : 'text-coral']">
            {{ module.eyebrow }}
        </p>

        <h3 v-if="module.title" :class="[module.icon ? 'mt-5' : 'mt-0', 'text-lg']">{{ module.title }}</h3>
        <p v-if="module.subtitle" class="mt-1.5 font-sans text-sm font-semibold opacity-75">{{ module.subtitle }}</p>
        <p v-if="module.description" class="mt-2.5 flex-1 text-sm leading-relaxed opacity-70">
            {{ module.description }}
        </p>

        <ul
            v-if="module.bullets?.length"
            :class="['mt-5 space-y-2 border-t pt-5', dark ? 'border-white/10' : 'border-charcoal/8']"
        >
            <li v-for="bullet in module.bullets" :key="bullet" class="flex items-start gap-2 text-sm opacity-75">
                <IconGlyph name="check" :size="15" class="mt-0.5 shrink-0 text-coral" />
                {{ bullet }}
            </li>
        </ul>

        <ModuleButtons :buttons="module.buttons" :dark="dark" class="mt-5" />
    </article>
</template>
