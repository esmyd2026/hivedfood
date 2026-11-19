<script setup>
import { computed } from 'vue';
import { useQuoterStore } from '../../stores/quoter';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

const quoter = useQuoterStore();
const site = useSiteStore();

const groups = computed(() => quoter.modulesByCategory);

/** Lo que ya trae el paquete se muestra marcado y no se puede contratar. */
function state(module) {
    if (quoter.isIncluded(module)) {
        return 'included';
    }

    if (quoter.autoAddedSlugs.has(module.slug)) {
        return 'auto';
    }

    return quoter.isSelected(module.id) ? 'selected' : 'available';
}
</script>

<template>
    <div>
        <h3 class="text-xl text-ivory md:text-2xl">
            {{ site.text('quoter_modules_title', 'Añade lo que tu operación necesite') }}
        </h3>
        <p v-if="site.text('quoter_modules_description')" class="mt-2 text-sm text-ivory/60">
            {{ site.text('quoter_modules_description') }}
        </p>

        <!-- Combinación rechazada por una dependencia -->
        <p
            v-if="quoter.blockedBy"
            class="mt-5 flex items-start gap-2 rounded-xl border border-coral/35 bg-coral/12 px-4 py-3 text-sm text-coral-soft"
        >
            <IconGlyph name="close" :size="16" class="mt-0.5 shrink-0" />
            {{ quoter.blockedBy }}
        </p>

        <section v-for="group in groups" :key="group.key" class="mt-9 first:mt-7">
            <h4 class="font-sans text-xs font-bold uppercase tracking-[0.18em] text-honey">{{ group.label }}</h4>

            <ul class="mt-4 grid gap-3 md:grid-cols-2">
                <li v-for="module in group.modules" :key="module.id">
                    <button
                        type="button"
                        :disabled="state(module) === 'included'"
                        :aria-pressed="state(module) === 'selected'"
                        :class="[
                            'flex h-full w-full items-start gap-3.5 rounded-2xl border p-5 text-left transition-all duration-300',
                            state(module) === 'selected' && 'border-honey bg-honey/10 ring-1 ring-honey',
                            state(module) === 'included' && 'cursor-default border-white/10 bg-white/[0.02] opacity-70',
                            state(module) === 'auto' && 'border-honey/40 bg-honey/[0.06]',
                            state(module) === 'available' && 'border-white/12 hover:border-white/28 hover:bg-white/[0.04]',
                        ]"
                        @click="quoter.toggleModule(module)"
                    >
                        <span
                            :class="[
                                'mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-md border-2 transition-colors',
                                state(module) === 'available'
                                    ? 'border-white/25 text-transparent'
                                    : 'border-honey bg-honey text-charcoal',
                            ]"
                        >
                            <IconGlyph name="check" :size="14" :stroke="3" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-sans text-sm font-bold text-ivory">{{ module.name }}</span>
                                <span
                                    v-if="state(module) === 'included'"
                                    class="rounded-full bg-honey/20 px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wide text-honey"
                                >
                                    Incluido en tu paquete
                                </span>
                                <span
                                    v-else-if="state(module) === 'auto'"
                                    class="rounded-full bg-white/10 px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wide text-ivory/60"
                                >
                                    Activado por dependencia
                                </span>
                            </span>

                            <span v-if="module.short_description" class="mt-1.5 block text-xs leading-relaxed text-ivory/55">
                                {{ module.short_description }}
                            </span>

                            <span class="mt-2.5 block font-sans text-xs font-bold text-honey">
                                <template v-if="state(module) === 'included'">
                                    <span class="font-medium text-ivory/45">Sin costo adicional</span>
                                </template>
                                <template v-else-if="module.has_pricing">
                                    {{ site.money(module.monthly_price) }}
                                    <span class="font-medium text-ivory/45">/ mes</span>
                                    <span class="font-medium text-ivory/45">
                                        · implementación {{ site.money(module.setup_price) }}
                                    </span>
                                </template>
                                <span v-else class="font-medium text-ivory/45">A cotizar</span>
                            </span>
                        </span>
                    </button>
                </li>
            </ul>
        </section>

        <p v-if="!groups.length" class="mt-7 text-sm text-ivory/55">
            Todos los módulos del catálogo ya vienen dentro del paquete que elegiste.
        </p>
    </div>
</template>
