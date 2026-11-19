<script setup>
defineProps({
    module: { type: Object, required: true },
    dark: { type: Boolean, default: false },
});
</script>

<template>
    <article class="h-full">
        <p
            :class="[
                'font-sans text-4xl font-extrabold leading-none tracking-tight md:text-5xl',
                dark ? 'text-honey' : 'text-charcoal',
            ]"
        >
            {{ module.value || module.title }}
        </p>

        <h3 v-if="module.value && module.title" class="mt-4 text-lg">{{ module.title }}</h3>
        <p v-if="module.subtitle" class="mt-1 font-sans text-sm font-semibold opacity-70">{{ module.subtitle }}</p>
        <p v-if="module.description" class="mt-2.5 text-sm leading-relaxed opacity-70">{{ module.description }}</p>
    </article>
</template>
