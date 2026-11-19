<script setup>
import { computed } from 'vue';
import { useQuoterStore } from '../../stores/quoter';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

const quoter = useQuoterStore();
const site = useSiteStore();

const models = computed(() => quoter.billingModels);

/** La modalidad elegida, o la recomendada si todavía no se tocó nada. */
function isActive(model) {
    return quoter.billingModel?.id === model.id;
}
</script>

<template>
    <div>
        <h3 class="text-xl text-ivory md:text-2xl">
            {{ site.text('quoter_billing_title', '¿Cómo prefieres pagarlo?') }}
        </h3>
        <p v-if="site.text('quoter_billing_description')" class="mt-2 text-sm text-ivory/60">
            {{ site.text('quoter_billing_description') }}
        </p>

        <div class="mt-7 grid gap-4 md:grid-cols-2">
            <article
                v-for="model in models"
                :key="model.id"
                :class="[
                    'relative flex flex-col rounded-[1.4rem] border p-6 transition-all duration-300',
                    isActive(model)
                        ? 'border-honey bg-honey/10 ring-1 ring-honey'
                        : 'border-white/12 hover:border-white/28 hover:bg-white/[0.04]',
                ]"
            >
                <span
                    v-if="model.is_recommended"
                    class="absolute -top-2.5 left-6 rounded-full bg-coral px-3 py-1 font-sans text-[0.62rem] font-bold uppercase tracking-wider text-white"
                >
                    Más elegida
                </span>

                <header class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/8 text-honey">
                        <IconGlyph :name="model.icon || 'coins'" :size="19" />
                    </span>
                    <div class="min-w-0">
                        <h4 class="font-sans text-base font-extrabold leading-tight text-ivory">{{ model.name }}</h4>
                        <p v-if="model.headline" class="mt-1 text-xs leading-snug text-ivory/60">{{ model.headline }}</p>
                    </div>
                </header>

                <p v-if="model.description" class="mt-4 text-sm leading-relaxed text-ivory/70">
                    {{ model.description }}
                </p>

                <!-- Tramos: se explican porque es lo que más dudas genera -->
                <div v-if="model.charges_usage && model.tiers.length" class="mt-5 rounded-xl border border-white/10 p-4">
                    <p class="font-sans text-[0.68rem] font-bold uppercase tracking-wider text-honey">
                        Precio por pedido, escalonado
                    </p>

                    <ul class="mt-3 space-y-1.5">
                        <li
                            v-for="tier in model.tiers"
                            :key="tier.id"
                            class="flex items-baseline justify-between gap-4 text-xs"
                        >
                            <span class="text-ivory/65">{{ tier.range }}</span>
                            <span class="shrink-0 font-sans font-bold text-ivory">
                                {{ tier.price_per_order !== null ? site.money(tier.price_per_order) : 'A confirmar' }}
                            </span>
                        </li>
                    </ul>

                    <p class="mt-3 text-[0.7rem] leading-relaxed text-ivory/45">
                        Cada pedido paga el precio de su tramo. Llegar al pedido 501 abarata ese pedido, nunca los
                        500 anteriores.
                    </p>
                </div>

                <ul v-if="model.included?.length" class="mt-5 flex-1 space-y-2">
                    <li
                        v-for="line in model.included"
                        :key="line"
                        class="flex items-start gap-2 text-xs leading-relaxed text-ivory/70"
                    >
                        <IconGlyph name="check" :size="13" :stroke="3" class="mt-0.5 shrink-0 text-honey" />
                        <span>{{ line }}</span>
                    </li>
                </ul>

                <button
                    type="button"
                    :class="[
                        'mt-6 w-full rounded-full px-5 py-3 font-sans text-sm font-bold transition-colors',
                        isActive(model)
                            ? 'bg-honey text-charcoal'
                            : 'border border-white/20 text-ivory hover:border-honey hover:text-honey',
                    ]"
                    :aria-pressed="isActive(model)"
                    @click="quoter.selectBillingModel(model.id)"
                >
                    <span v-if="isActive(model)" class="inline-flex items-center gap-2">
                        <IconGlyph name="check" :size="14" :stroke="3" />
                        Seleccionada
                    </span>
                    <span v-else>Elegir esta forma de pago</span>
                </button>
            </article>
        </div>

        <p class="mt-6 text-xs leading-relaxed text-ivory/45">
            En todas las formas de pago la implementación se cobra una sola vez al inicio, y la mensualidad de tu
            paquete, tus módulos y tus locales es la misma. Lo único que cambia es si además pagas por los pedidos
            que procesas.
        </p>
    </div>
</template>
