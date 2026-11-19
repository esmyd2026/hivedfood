<script setup>
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();
const { quoter } = storeToRefs(store);

/** Jump into the quoter with this business type preselected. */
function pick(type) {
    window.dispatchEvent(new CustomEvent('hivedfood:quoter-preset', { detail: { businessTypeId: type.id } }));
    document.getElementById('cotizador')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible }">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <button
                    v-for="(type, i) in quoter.business_types"
                    :key="type.id"
                    type="button"
                    :class="[
                        'hf-reveal group flex items-start gap-4 rounded-2xl border border-charcoal/10 bg-white p-5 text-left transition-all duration-400 hover:-translate-y-1 hover:border-honey hover:shadow-[var(--shadow-soft)]',
                        visible && 'hf-reveal-in',
                    ]"
                    :style="{ transitionDelay: `${i * 60}ms` }"
                    @click="pick(type)"
                >
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-ivory text-charcoal transition-colors group-hover:bg-honey">
                        <IconGlyph :name="type.icon || 'restaurant'" :size="20" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block font-sans text-base font-bold">{{ type.name }}</span>
                        <span class="mt-1 block text-sm leading-snug opacity-65">{{ type.short_description }}</span>
                        <span class="mt-3 inline-flex items-center gap-1.5 font-sans text-xs font-bold text-coral opacity-0 transition-opacity group-hover:opacity-100">
                            Cotizar
                            <IconGlyph name="arrow-right" :size="14" />
                        </span>
                    </span>
                </button>
            </div>
        </template>
    </SectionShell>
</template>
