<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** phone | browser */
    type: { type: String, default: 'browser' },
    /** Real screenshot. When absent, the slot renders a faithful mockup. */
    image: { type: String, default: null },
    alt: { type: String, default: '' },
    /** Address shown in the browser bar. */
    label: { type: String, default: 'tutienda.com' },
    /** Drop shadow intensity: the hero wants a heavier one. */
    elevated: { type: Boolean, default: true },
});

const isPhone = computed(() => props.type === 'phone');
</script>

<template>
    <!-- Teléfono -->
    <div
        v-if="isPhone"
        :class="[
            'relative mx-auto w-full max-w-[17rem] rounded-[2.25rem] border-[9px] border-charcoal-700 bg-charcoal-700',
            elevated && 'shadow-[0_40px_80px_-30px_rgba(32,42,36,0.65)]',
        ]"
    >
        <!-- Muesca -->
        <span
            class="absolute left-1/2 top-0 z-10 h-5 w-24 -translate-x-1/2 rounded-b-2xl bg-charcoal-700"
            aria-hidden="true"
        />

        <div class="relative aspect-[9/18.5] overflow-hidden rounded-[1.6rem] bg-ivory">
            <img
                v-if="image"
                :src="image"
                :alt="alt"
                class="h-full w-full object-cover object-top"
                loading="lazy"
                decoding="async"
            />
            <slot v-else />
        </div>
    </div>

    <!-- Navegador -->
    <div
        v-else
        :class="[
            'relative w-full overflow-hidden rounded-2xl border border-white/10 bg-charcoal-700',
            elevated && 'shadow-[0_40px_80px_-30px_rgba(32,42,36,0.65)]',
        ]"
    >
        <!-- Barra del navegador -->
        <div class="flex items-center gap-2 px-4 py-3" aria-hidden="true">
            <span class="h-2.5 w-2.5 rounded-full bg-coral/80" />
            <span class="h-2.5 w-2.5 rounded-full bg-honey/80" />
            <span class="h-2.5 w-2.5 rounded-full bg-white/20" />

            <span class="ml-3 flex-1 truncate rounded-md bg-black/25 px-3 py-1 text-[0.65rem] text-ivory/45">
                {{ label }}
            </span>
        </div>

        <div class="relative aspect-[16/10] overflow-hidden bg-ivory">
            <img
                v-if="image"
                :src="image"
                :alt="alt"
                class="h-full w-full object-cover object-top"
                loading="lazy"
                decoding="async"
            />
            <slot v-else />
        </div>
    </div>
</template>
