<script setup>
import { computed } from 'vue';
import { useQuoterStore } from '../../stores/quoter';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

const quoter = useQuoterStore();
const site = useSiteStore();

/** Los paquetes son acumulativos: cada uno incluye todo lo del anterior. */
const packages = computed(() => quoter.packages);
</script>

<template>
    <div>
        <h3 class="text-xl text-ivory md:text-2xl">
            {{ site.text('quoter_package_title', 'Elige tu punto de partida') }}
        </h3>
        <p v-if="site.text('quoter_package_description')" class="mt-2 text-sm text-ivory/60">
            {{ site.text('quoter_package_description') }}
        </p>

        <div class="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="item in packages"
                :key="item.id"
                :class="[
                    'relative flex flex-col rounded-[1.4rem] border p-6 transition-all duration-300',
                    quoter.planId === item.id
                        ? 'border-honey bg-honey/10 ring-1 ring-honey'
                        : 'border-white/12 hover:border-white/28 hover:bg-white/[0.04]',
                ]"
            >
                <span
                    v-if="item.is_recommended"
                    class="absolute -top-2.5 left-6 rounded-full bg-coral px-3 py-1 font-sans text-[0.62rem] font-bold uppercase tracking-wider text-white"
                >
                    Recomendado
                </span>

                <header class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/8 text-honey">
                        <IconGlyph :name="item.icon || 'layers'" :size="19" />
                    </span>
                    <div class="min-w-0">
                        <h4 class="font-sans text-base font-extrabold leading-tight text-ivory">{{ item.name }}</h4>
                        <p v-if="item.headline" class="mt-1 text-xs leading-snug text-ivory/60">{{ item.headline }}</p>
                    </div>
                </header>

                <!-- Precio -->
                <div class="mt-5 border-y border-white/10 py-4">
                    <template v-if="item.has_pricing">
                        <p class="font-sans text-[1.7rem] font-extrabold leading-none text-ivory">
                            {{ site.money(item.monthly_price) }}
                            <span class="font-sans text-sm font-medium text-ivory/50">/ mes</span>
                        </p>
                        <p class="mt-1.5 text-xs text-ivory/55">
                            Implementación {{ site.money(item.setup_price) }}, pago único
                        </p>
                    </template>
                    <p v-else class="font-sans text-base font-bold text-ivory/70">A cotizar</p>
                </div>

                <ul v-if="item.included?.length" class="mt-4 flex-1 space-y-2">
                    <li v-for="line in item.included" :key="line" class="flex items-start gap-2 text-xs leading-relaxed text-ivory/70">
                        <IconGlyph name="check" :size="13" :stroke="3" class="mt-0.5 shrink-0 text-honey" />
                        <span>{{ line }}</span>
                    </li>
                </ul>

                <button
                    type="button"
                    :class="[
                        'mt-6 w-full rounded-full px-5 py-3 font-sans text-sm font-bold transition-colors',
                        quoter.planId === item.id
                            ? 'bg-honey text-charcoal'
                            : 'border border-white/20 text-ivory hover:border-honey hover:text-honey',
                    ]"
                    :aria-pressed="quoter.planId === item.id"
                    @click="quoter.selectPlan(item.id)"
                >
                    <span v-if="quoter.planId === item.id" class="inline-flex items-center gap-2">
                        <IconGlyph name="check" :size="14" :stroke="3" />
                        Seleccionado
                    </span>
                    <span v-else>Elegir {{ item.name }}</span>
                </button>
            </article>
        </div>
    </div>
</template>
