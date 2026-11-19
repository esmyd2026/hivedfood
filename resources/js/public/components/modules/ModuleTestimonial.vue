<script setup>
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    module: { type: Object, required: true },
    dark: { type: Boolean, default: false },
});
</script>

<template>
    <figure
        :class="[
            'flex h-full flex-col rounded-[1.25rem] border p-7',
            dark ? 'border-white/10 bg-white/[0.04]' : 'border-charcoal/8 bg-white shadow-[var(--shadow-soft)]',
        ]"
    >
        <IconGlyph name="quote" :size="26" :stroke="0" class="text-honey" style="fill: currentColor" />

        <blockquote class="mt-4 flex-1 text-base leading-relaxed opacity-80">
            {{ module.description }}
        </blockquote>

        <figcaption
            :class="['mt-6 flex items-center gap-3 border-t pt-5', dark ? 'border-white/10' : 'border-charcoal/8']"
        >
            <img
                v-if="module.image"
                :src="module.image"
                :alt="module.image_alt || module.title || ''"
                class="h-11 w-11 rounded-full object-cover"
                width="44"
                height="44"
                loading="lazy"
            />
            <span
                v-else
                class="grid h-11 w-11 place-items-center rounded-full bg-charcoal font-sans text-sm font-bold text-honey"
            >
                {{ (module.title || '?').charAt(0) }}
            </span>

            <span>
                <span class="block font-sans text-sm font-bold">{{ module.title }}</span>
                <span class="block text-xs opacity-60">{{ module.subtitle }}</span>
            </span>
        </figcaption>
    </figure>
</template>
