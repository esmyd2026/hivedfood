<script setup>
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    section: { type: Object, required: true },
});
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible }">
            <!-- Rejilla editorial con líneas finas en lugar de tarjetas -->
            <div class="grid divide-y divide-charcoal/10 md:grid-cols-2 md:divide-y-0 lg:grid-cols-3">
                <article
                    v-for="(module, i) in section.modules"
                    :key="module.id"
                    :class="[
                        'hf-reveal group relative px-0 py-8 md:px-8 md:py-10',
                        'md:border-b md:border-charcoal/10',
                        'md:[&:nth-child(2n)]:border-l lg:[&:nth-child(2n)]:border-l-0 lg:[&:nth-child(3n+2)]:border-x',
                        'md:last:border-b-0 lg:[&:nth-last-child(-n+3)]:border-b-0',
                        'md:[&:nth-child(1)]:pt-0 md:[&:nth-child(2)]:pt-0 lg:[&:nth-child(3)]:pt-0',
                        visible && 'hf-reveal-in',
                    ]"
                    :style="{ transitionDelay: `${i * 70}ms` }"
                >
                    <span class="inline-grid h-12 w-12 place-items-center rounded-2xl bg-charcoal text-honey transition-transform duration-500 group-hover:-translate-y-1">
                        <IconGlyph :name="module.icon || 'check'" :size="21" />
                    </span>

                    <h3 class="mt-5 text-lg">{{ module.title }}</h3>
                    <p class="mt-2.5 text-sm leading-relaxed opacity-70">{{ module.description }}</p>

                    <span class="mt-5 block h-0.5 w-10 origin-left scale-x-0 rounded-full bg-coral transition-transform duration-500 group-hover:scale-x-100" />
                </article>
            </div>
        </template>
    </SectionShell>
</template>
