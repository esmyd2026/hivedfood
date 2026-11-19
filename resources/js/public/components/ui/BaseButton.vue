<script setup>
import { computed } from 'vue';
import IconGlyph from './IconGlyph.vue';

const props = defineProps({
    href: { type: String, default: null },
    variant: { type: String, default: 'primary' },
    icon: { type: String, default: null },
    trailingIcon: { type: String, default: null },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const VARIANTS = {
    primary: 'hf-btn-primary',
    coral: 'hf-btn-coral',
    dark: 'hf-btn-dark',
    ghost: 'hf-btn-ghost',
};

const classes = computed(() => [
    'hf-btn',
    VARIANTS[props.variant] ?? VARIANTS.primary,
    props.block ? 'w-full' : '',
]);

const isExternal = computed(() => !!props.href && /^https?:\/\//i.test(props.href));
</script>

<template>
    <a
        v-if="href"
        :href="href"
        :class="classes"
        :target="isExternal ? '_blank' : null"
        :rel="isExternal ? 'noopener noreferrer' : null"
    >
        <IconGlyph v-if="icon" :name="icon" :size="18" />
        <span><slot /></span>
        <IconGlyph v-if="trailingIcon" :name="trailingIcon" :size="18" />
    </a>

    <button v-else :type="type" :class="classes" :disabled="disabled || loading">
        <span
            v-if="loading"
            class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
            aria-hidden="true"
        />
        <IconGlyph v-else-if="icon" :name="icon" :size="18" />
        <span><slot /></span>
        <IconGlyph v-if="trailingIcon && !loading" :name="trailingIcon" :size="18" />
    </button>
</template>
