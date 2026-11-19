<script setup>
import { computed } from 'vue';
import { useQuoterStore } from '../../stores/quoter';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

const quoter = useQuoterStore();
const site = useSiteStore();

/**
 * Only the applicable steps are shown: the package step disappears when the
 * chosen billing methodology does not use packages.
 */
const steps = computed(() => quoter.steps);

/** Step names are administrable, keyed by step so the order cannot drift. */
const names = computed(() => {
    const configured = site.settings.texts?.quoter_step_names;

    return steps.value.map((step) =>
        (configured && !Array.isArray(configured) ? configured[step.key] : null) ?? step.short,
    );
});
</script>

<template>
    <nav aria-label="Pasos del cotizador">
        <ol class="flex items-center gap-1 overflow-x-auto pb-1 hf-no-scrollbar sm:gap-2">
            <li v-for="(step, i) in steps" :key="step.key" class="flex shrink-0 items-center gap-1 sm:gap-2">
                <button
                    type="button"
                    :class="[
                        'flex items-center gap-2 rounded-full px-3 py-2 font-sans text-xs font-bold transition-colors sm:px-4 sm:text-sm',
                        i === quoter.step
                            ? 'bg-honey text-charcoal'
                            : i < quoter.step
                              ? 'bg-white/10 text-ivory hover:bg-white/15'
                              : 'text-ivory/40',
                    ]"
                    :disabled="i > quoter.step"
                    :aria-current="i === quoter.step ? 'step' : null"
                    @click="quoter.goTo(i)"
                >
                    <span
                        :class="[
                            'grid h-5 w-5 place-items-center rounded-full text-[0.65rem]',
                            i === quoter.step ? 'bg-charcoal text-honey' : i < quoter.step ? 'bg-honey text-charcoal' : 'bg-white/10',
                        ]"
                    >
                        <IconGlyph v-if="i < quoter.step" name="check" :size="11" :stroke="3" />
                        <template v-else>{{ i + 1 }}</template>
                    </span>
                    <span class="hidden sm:inline">{{ names[i] }}</span>
                </button>

                <span v-if="i < steps.length - 1" class="h-px w-3 bg-white/15 sm:w-6" aria-hidden="true" />
            </li>
        </ol>
    </nav>
</template>
