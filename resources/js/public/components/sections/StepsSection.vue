<script setup>
import SectionShell from '../ui/SectionShell.vue';

defineProps({
    section: { type: Object, required: true },
});
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible }">
            <ol class="relative grid gap-10 md:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                <!-- Línea de tiempo -->
                <span
                    class="pointer-events-none absolute left-0 right-0 top-6 hidden h-px bg-gradient-to-r from-transparent via-charcoal/15 to-transparent lg:block"
                    aria-hidden="true"
                />

                <li
                    v-for="(module, i) in section.modules"
                    :key="module.id"
                    :class="['hf-reveal relative', visible && 'hf-reveal-in']"
                    :style="{ transitionDelay: `${i * 110}ms` }"
                >
                    <span class="relative z-10 grid h-12 w-12 place-items-center rounded-full bg-charcoal font-sans text-sm font-extrabold text-honey ring-8 ring-white">
                        {{ module.value || String(i + 1).padStart(2, '0') }}
                    </span>

                    <h3 class="mt-6 text-lg">{{ module.title }}</h3>
                    <p class="mt-2.5 text-sm leading-relaxed opacity-70 lg:pr-6">{{ module.description }}</p>
                </li>
            </ol>
        </template>
    </SectionShell>
</template>
