<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { useQuoterStore } from '../../stores/quoter';
import { useReveal } from '../../composables/useReveal';
import QuoterProgress from '../quoter/QuoterProgress.vue';
import QuoterSummary from '../quoter/QuoterSummary.vue';
import StepBusiness from '../quoter/StepBusiness.vue';
import StepPackage from '../quoter/StepPackage.vue';
import StepBilling from '../quoter/StepBilling.vue';
import StepModules from '../quoter/StepModules.vue';
import StepScale from '../quoter/StepScale.vue';
import StepResult from '../quoter/StepResult.vue';
import BaseButton from '../ui/BaseButton.vue';
import IconGlyph from '../ui/IconGlyph.vue';
import { useSiteStore } from '../../stores/site';

defineProps({
    section: { type: Object, required: true },
});

const quoter = useQuoterStore();
const site = useSiteStore();
const { target, visible } = useReveal();

const COMPONENTS = {
    business: StepBusiness,
    package: StepPackage,
    billing: StepBilling,
    modules: StepModules,
    scale: StepScale,
    result: StepResult,
};

const currentComponent = computed(() => COMPONENTS[quoter.current.key]);
const isLast = computed(() => quoter.step === quoter.lastStepIndex);

/** Permite que otras secciones entren directamente con un tipo de negocio. */
function onPreset(event) {
    const id = event.detail?.businessTypeId;

    if (id) {
        quoter.selectBusinessType(id);
    }
}

onMounted(() => window.addEventListener('hivedfood:quoter-preset', onPreset));
onBeforeUnmount(() => window.removeEventListener('hivedfood:quoter-preset', onPreset));
</script>

<template>
    <section :id="section.key" class="relative overflow-hidden bg-charcoal text-ivory">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute right-0 top-0 h-[30rem] w-[30rem] rounded-full bg-honey/10 blur-[130px]" />
            <div class="absolute bottom-0 left-0 h-[26rem] w-[26rem] rounded-full bg-coral/10 blur-[130px]" />
        </div>

        <div ref="target" class="hf-shell relative py-16 md:py-24 lg:py-28">
            <header :class="['hf-reveal max-w-3xl', visible && 'hf-reveal-in']">
                <p v-if="section.eyebrow" class="hf-eyebrow text-honey">{{ section.eyebrow }}</p>
                <h2 v-if="section.title" class="mt-5 text-[2rem] md:text-[2.75rem] lg:text-[3.25rem]">
                    {{ section.title }}
                </h2>
                <p v-if="section.description" class="mt-5 text-base text-ivory/70 md:text-lg">
                    {{ section.description }}
                </p>
            </header>

            <!-- Escritorio: configurador a la izquierda, resumen fijo a la derecha -->
            <div
                :class="['hf-reveal mt-10 grid gap-6 md:mt-14 lg:grid-cols-[minmax(0,1fr)_21rem] lg:gap-8', visible && 'hf-reveal-in']"
                style="transition-delay: 100ms"
            >
                <div class="rounded-[1.75rem] border border-white/10 bg-white/[0.03] p-6 backdrop-blur-sm md:p-9">
                    <QuoterProgress />

                    <div class="mt-9">
                        <Transition name="step" mode="out-in">
                            <component :is="currentComponent" :key="quoter.current.key" />
                        </Transition>
                    </div>

                    <!-- Navegación -->
                    <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-7">
                        <BaseButton v-if="quoter.step > 0" variant="ghost" icon="arrow-left" @click="quoter.back()">
                            Atrás
                        </BaseButton>
                        <span v-else />

                        <BaseButton
                            v-if="!isLast"
                            variant="primary"
                            trailing-icon="arrow-right"
                            :disabled="!quoter.canAdvance"
                            @click="quoter.next()"
                        >
                            Continuar
                        </BaseButton>
                    </div>

                    <p v-if="quoter.calcError" class="mt-5 flex items-start gap-2 rounded-xl bg-coral/15 px-4 py-3 text-sm text-coral-soft">
                        <IconGlyph name="close" :size="16" class="mt-0.5 shrink-0" />
                        {{ quoter.calcError }}
                    </p>
                </div>

                <!-- El resumen acompaña el scroll en escritorio -->
                <div class="hidden lg:block">
                    <div class="sticky top-28">
                        <QuoterSummary />
                    </div>
                </div>
            </div>
        </div>

        <!-- Móvil: barra inferior con el total y el detalle desplegable -->
        <Teleport to="body">
            <div v-if="quoter.result" class="fixed inset-x-0 bottom-0 z-40 lg:hidden">
                <Transition name="sheet">
                    <div v-if="quoter.summaryOpen" class="max-h-[70vh] overflow-y-auto bg-charcoal/95 px-4 pb-4 pt-4 backdrop-blur">
                        <QuoterSummary compact />
                    </div>
                </Transition>

                <div class="flex items-center justify-between gap-4 border-t border-white/10 bg-charcoal px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-[0.62rem] uppercase tracking-wider text-ivory/45">Primer pago</p>
                        <p class="font-sans text-lg font-extrabold leading-tight text-ivory">
                            {{ site.money(quoter.result.first_payment) }}
                            <span class="text-xs font-medium text-ivory/50">
                                · {{ site.money(quoter.result.monthly_estimated_total) }}/mes
                            </span>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex shrink-0 items-center gap-1.5 rounded-full bg-honey px-4 py-2.5 font-sans text-xs font-bold text-charcoal"
                        :aria-expanded="quoter.summaryOpen"
                        @click="quoter.toggleSummary()"
                    >
                        {{ quoter.summaryOpen ? 'Ocultar' : 'Ver detalle' }}
                        <IconGlyph
                            name="chevron-down"
                            :size="13"
                            :class="['transition-transform', quoter.summaryOpen && 'rotate-180']"
                        />
                    </button>
                </div>
            </div>
        </Teleport>
    </section>
</template>

<style scoped>
.step-enter-active,
.step-leave-active {
    transition:
        opacity 0.28s ease,
        transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.step-enter-from {
    opacity: 0;
    transform: translateX(18px);
}

.step-leave-to {
    opacity: 0;
    transform: translateX(-14px);
}

.sheet-enter-active,
.sheet-leave-active {
    transition:
        opacity 0.25s ease,
        transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.sheet-enter-from,
.sheet-leave-to {
    opacity: 0;
    transform: translateY(16px);
}
</style>
