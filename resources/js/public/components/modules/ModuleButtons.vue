<script setup>
import { computed } from 'vue';
import BaseButton from '../ui/BaseButton.vue';

const props = defineProps({
    buttons: { type: Array, default: () => [] },
    dark: { type: Boolean, default: false },
});

/** Only buttons with a destination are rendered: no decorative actions. */
const usable = computed(() => (props.buttons ?? []).filter((button) => button?.label && button?.url));

function variant(style) {
    if (style === 'ghost') {
        return 'ghost';
    }

    if (style === 'dark') {
        return props.dark ? 'primary' : 'dark';
    }

    return 'primary';
}
</script>

<template>
    <div v-if="usable.length" class="flex flex-wrap gap-2.5">
        <BaseButton
            v-for="button in usable"
            :key="`${button.label}-${button.url}`"
            :href="button.url"
            :variant="variant(button.style)"
        >
            {{ button.label }}
        </BaseButton>
    </div>
</template>
