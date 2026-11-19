<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: [String, Boolean], required: true },
    map: { type: Object, default: () => ({}) },
});

const TONES = {
    neutral: 'bg-charcoal/8 text-charcoal/70',
    success: 'bg-charcoal text-honey',
    warning: 'bg-honey-soft text-charcoal',
    danger: 'bg-coral-soft text-coral-dark',
    info: 'bg-ivory-dark text-charcoal/75',
};

const entry = computed(() => props.map[String(props.value)] ?? { label: String(props.value), tone: 'neutral' });
</script>

<template>
    <span :class="['ad-chip', TONES[entry.tone] ?? TONES.neutral]">{{ entry.label }}</span>
</template>
