<script setup>
import { computed } from 'vue';
import { useReveal } from '../../composables/useReveal';
import IconGlyph from '../ui/IconGlyph.vue';
import ModuleButtons from '../modules/ModuleButtons.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

const { target, visible } = useReveal();

const problems = computed(() => props.section.modules ?? []);

/** Cierre de la sección: a dónde lleva todo esto. */
const outcome = computed(() => props.section.settings?.outcome ?? null);
</script>

<template>
    <section :id="section.key" class="relative overflow-hidden bg-charcoal text-ivory">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -left-40 top-1/3 h-[32rem] w-[32rem] rounded-full bg-coral/10 blur-[130px]" />
        </div>

        <div ref="target" class="hf-shell relative py-16 md:py-24 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:gap-20">
                <!-- Enunciado, fijo mientras se recorre la lista -->
                <div :class="['hf-reveal lg:sticky lg:top-28 lg:self-start', visible && 'hf-reveal-in']">
                    <p v-if="section.eyebrow" class="hf-eyebrow text-coral">{{ section.eyebrow }}</p>

                    <h2 v-if="section.title" class="mt-5 text-[2rem] leading-[1.06] md:text-[2.6rem] lg:text-[2.9rem]">
                        {{ section.title }}
                    </h2>

                    <p v-if="section.description" class="mt-5 text-base leading-relaxed text-ivory/65 md:text-lg">
                        {{ section.description }}
                    </p>

                    <!-- Resultado: el puente hacia la solución -->
                    <div v-if="outcome" class="mt-9 rounded-2xl border border-honey/30 bg-honey/10 p-5">
                        <p class="flex items-start gap-3 font-sans text-base font-bold leading-snug">
                            <IconGlyph name="arrow-right" :size="18" class="mt-0.5 shrink-0 text-honey" />
                            {{ outcome }}
                        </p>
                    </div>

                    <ModuleButtons :buttons="section.buttons" dark class="mt-8" />
                </div>

                <!-- Lista editorial: sin tarjetas, solo jerarquía -->
                <ol class="divide-y divide-white/10 border-t border-white/10">
                    <li
                        v-for="(problem, i) in problems"
                        :key="problem.id"
                        :class="['hf-reveal group py-7 md:py-9', visible && 'hf-reveal-in']"
                        :style="{ transitionDelay: `${120 + i * 80}ms` }"
                    >
                        <div class="flex gap-5 md:gap-8">
                            <span
                                class="shrink-0 font-sans text-[2rem] font-extrabold leading-none text-white/12 transition-colors duration-500 group-hover:text-honey md:text-[2.75rem]"
                                aria-hidden="true"
                            >
                                {{ String(i + 1).padStart(2, '0') }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <h3 class="flex items-center gap-3 text-xl md:text-2xl">
                                    <IconGlyph
                                        v-if="problem.icon"
                                        :name="problem.icon"
                                        :size="20"
                                        class="shrink-0 text-coral"
                                    />
                                    {{ problem.title }}
                                </h3>

                                <p class="mt-2.5 max-w-xl text-base leading-relaxed text-ivory/60">
                                    {{ problem.description }}
                                </p>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </section>
</template>
