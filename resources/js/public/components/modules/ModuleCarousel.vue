<script setup>
import { computed, ref } from 'vue';
import IconGlyph from '../ui/IconGlyph.vue';
import ModuleCard from './ModuleCard.vue';

const props = defineProps({
    /** Every module assigned the "carousel" layout. */
    modules: { type: Array, required: true },
    dark: { type: Boolean, default: false },
});

const index = ref(0);
const total = computed(() => props.modules.length);

function go(step) {
    index.value = (index.value + step + total.value) % total.value;
}
</script>

<template>
    <div v-if="total" class="relative">
        <!-- Carril deslizable: táctil en móvil, con controles en escritorio -->
        <div class="overflow-hidden">
            <div
                class="flex transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
                :style="{ transform: `translateX(-${index * 100}%)` }"
            >
                <div v-for="module in modules" :key="module.id" class="w-full shrink-0 px-1 md:w-1/2 lg:w-1/3">
                    <ModuleCard :module="module" :dark="dark" />
                </div>
            </div>
        </div>

        <div v-if="total > 1" class="mt-6 flex items-center justify-between gap-4">
            <div class="flex gap-1.5" role="tablist">
                <button
                    v-for="(module, i) in modules"
                    :key="module.id"
                    type="button"
                    role="tab"
                    :aria-selected="i === index"
                    :aria-label="`Ir al elemento ${i + 1}`"
                    :class="[
                        'h-1.5 rounded-full transition-all',
                        i === index ? 'w-7' : 'w-1.5',
                        dark ? (i === index ? 'bg-honey' : 'bg-white/25') : i === index ? 'bg-charcoal' : 'bg-charcoal/20',
                    ]"
                    @click="index = i"
                />
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    :class="[
                        'grid h-10 w-10 place-items-center rounded-full border transition',
                        dark
                            ? 'border-white/15 text-ivory hover:border-honey hover:text-honey'
                            : 'border-charcoal/15 hover:border-charcoal hover:bg-charcoal hover:text-ivory',
                    ]"
                    aria-label="Anterior"
                    @click="go(-1)"
                >
                    <IconGlyph name="arrow-left" :size="17" />
                </button>
                <button
                    type="button"
                    :class="[
                        'grid h-10 w-10 place-items-center rounded-full border transition',
                        dark
                            ? 'border-white/15 text-ivory hover:border-honey hover:text-honey'
                            : 'border-charcoal/15 hover:border-charcoal hover:bg-charcoal hover:text-ivory',
                    ]"
                    aria-label="Siguiente"
                    @click="go(1)"
                >
                    <IconGlyph name="arrow-right" :size="17" />
                </button>
            </div>
        </div>
    </div>
</template>
