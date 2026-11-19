<script setup>
import { computed } from 'vue';
import { useReveal } from '../../composables/useReveal';

const props = defineProps({
    section: { type: Object, required: true },
    align: { type: String, default: 'left' },
});

const { target, visible } = useReveal();

const BACKGROUNDS = {
    ivory: 'bg-ivory text-charcoal',
    white: 'bg-white text-charcoal',
    charcoal: 'bg-charcoal text-ivory',
    honey: 'bg-honey text-charcoal',
    coral: 'bg-coral text-white',
};

const background = computed(() => BACKGROUNDS[props.section.settings?.background] ?? BACKGROUNDS.white);
const isDark = computed(() => ['charcoal', 'coral'].includes(props.section.settings?.background));
const eyebrowColor = computed(() => (isDark.value ? 'text-honey' : 'text-coral'));
</script>

<template>
    <section :id="section.key" :class="[background, 'relative overflow-hidden']">
        <div ref="target" class="hf-shell py-16 md:py-24 lg:py-28">
            <header
                v-if="section.eyebrow || section.title || section.description"
                :class="[
                    'hf-reveal max-w-3xl',
                    visible && 'hf-reveal-in',
                    align === 'center' && 'mx-auto text-center',
                ]"
            >
                <p v-if="section.eyebrow" :class="['hf-eyebrow', eyebrowColor]">{{ section.eyebrow }}</p>
                <h2 v-if="section.title" class="mt-5 text-[2rem] leading-[1.08] md:text-[2.75rem] lg:text-[3.25rem]">
                    {{ section.title }}
                </h2>
                <p v-if="section.subtitle" class="mt-3 font-sans text-lg font-semibold opacity-80">
                    {{ section.subtitle }}
                </p>
                <p v-if="section.description" class="mt-5 text-base opacity-70 md:text-lg">
                    {{ section.description }}
                </p>
            </header>

            <div :class="[(section.eyebrow || section.title) && 'mt-12 md:mt-16']">
                <slot :visible="visible" :is-dark="isDark" />
            </div>
        </div>
    </section>
</template>
