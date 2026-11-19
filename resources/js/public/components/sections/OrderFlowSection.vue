<script setup>
import { computed } from 'vue';
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

const states = computed(() => props.section.modules ?? []);

/** Operating rules shown under the pipeline, editable from the section. */
const notes = computed(() => {
    const configured = props.section.settings?.notes;

    return Array.isArray(configured) ? configured.filter(Boolean) : [];
});
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible, isDark }">
            <div v-if="states.length">
                <!-- Tubería de estados -->
                <ol class="relative grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <li
                        v-for="(state, i) in states"
                        :key="state.id"
                        :class="[
                            'hf-reveal relative flex h-full flex-col rounded-[1.25rem] border p-6',
                            isDark ? 'border-white/10 bg-white/[0.04]' : 'border-charcoal/10 bg-white',
                            visible && 'hf-reveal-in',
                        ]"
                        :style="{ transitionDelay: `${i * 70}ms` }"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-sans text-xs font-bold uppercase tracking-[0.18em] text-honey">
                                {{ state.value || String(i + 1).padStart(2, '0') }}
                            </span>

                            <span
                                :class="[
                                    'grid h-10 w-10 place-items-center rounded-xl',
                                    isDark ? 'bg-white/8 text-honey' : 'bg-ivory text-charcoal',
                                ]"
                            >
                                <IconGlyph :name="state.icon || 'check'" :size="18" />
                            </span>
                        </div>

                        <h3 class="mt-4 text-lg">{{ state.title }}</h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed opacity-70">{{ state.description }}</p>

                        <!-- Flecha hacia el siguiente estado -->
                        <span
                            v-if="i < states.length - 1"
                            :class="[
                                'absolute -right-3.5 top-1/2 hidden h-7 w-7 -translate-y-1/2 place-items-center rounded-full lg:grid',
                                isDark ? 'bg-charcoal text-honey ring-1 ring-white/15' : 'bg-white text-coral ring-1 ring-charcoal/10',
                                (i + 1) % 4 === 0 && 'lg:hidden',
                            ]"
                            aria-hidden="true"
                        >
                            <IconGlyph name="chevron-right" :size="14" :stroke="2.4" />
                        </span>
                    </li>
                </ol>

                <!-- Reglas de operación -->
                <ul v-if="notes.length" class="mt-10 grid gap-4 md:grid-cols-3">
                    <li
                        v-for="(note, i) in notes"
                        :key="i"
                        :class="[
                            'hf-reveal flex items-start gap-3 rounded-2xl p-5 text-sm leading-relaxed',
                            isDark ? 'bg-white/[0.04] text-ivory/75' : 'bg-ivory text-charcoal/75',
                            visible && 'hf-reveal-in',
                        ]"
                        :style="{ transitionDelay: `${200 + i * 70}ms` }"
                    >
                        <IconGlyph name="shield" :size="17" class="mt-0.5 shrink-0 text-coral" />
                        {{ note }}
                    </li>
                </ul>
            </div>

            <p v-else class="text-sm opacity-60">
                Aún no hay estados configurados. Agrégalos como módulos de esta sección.
            </p>
        </template>
    </SectionShell>
</template>
