<script setup>
import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import SectionShell from '../ui/SectionShell.vue';
import BaseButton from '../ui/BaseButton.vue';
import IconGlyph from '../ui/IconGlyph.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();
const { quoter } = storeToRefs(store);

/** Paquetes acumulativos: cada uno arranca donde termina el anterior. */
const packages = computed(() => quoter.value.packages ?? []);

const scalability = computed(() => quoter.value.scalability ?? []);

const externalCosts = computed(() => quoter.value.external_costs ?? []);

/** Formas de pagar lo que ya elegiste: con o sin consumo por pedido. */
const billingModels = computed(() => quoter.value.billing_models ?? []);

/** Calculadora de referencia: cuánto costaría ese volumen de pedidos. */
const usageModel = computed(() => billingModels.value.find((model) => model.charges_usage) ?? null);

const orders = ref(600);

const usageBreakdown = computed(() => {
    const tiers = usageModel.value?.tiers ?? [];
    const rows = [];

    // Escalonado: cada tramo cobra solo los pedidos que caen dentro de él.
    tiers.forEach((tier) => {
        const from = tier.from_orders;
        const to = tier.to_orders === null ? orders.value : Math.min(orders.value, tier.to_orders);

        if (to < from || tier.price_per_order === null) {
            return;
        }

        const count = to - from + 1;

        rows.push({
            id: tier.id,
            label: tier.range,
            orders: count,
            price: tier.price_per_order,
            amount: Math.round(count * tier.price_per_order * 100) / 100,
        });
    });

    const total = Math.round(rows.reduce((sum, row) => sum + row.amount, 0) * 100) / 100;

    return { rows, total, average: orders.value > 0 ? total / orders.value : 0 };
});

/** Qué paquete trae cada uno por debajo, para explicar la acumulación. */
function previousName(index) {
    return index > 0 ? packages.value[index - 1].name : null;
}

const cta = computed(() => props.section.buttons?.[0] ?? { label: 'Arma tu plataforma', url: '#cotizador' });
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible, isDark }">
            <div v-if="packages.length" class="space-y-14">
                <!-- Los paquetes -->
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="(item, i) in packages"
                        :key="item.id"
                        :class="[
                            'hf-reveal relative flex flex-col rounded-[1.5rem] border p-7 transition-all duration-400',
                            item.is_recommended
                                ? 'border-honey bg-honey/10 shadow-[var(--shadow-soft)]'
                                : isDark
                                  ? 'border-white/12 bg-white/[0.03]'
                                  : 'border-charcoal/10 bg-white',
                            visible && 'hf-reveal-in',
                        ]"
                        :style="{ transitionDelay: `${i * 70}ms` }"
                    >
                        <span
                            v-if="item.is_recommended"
                            class="absolute -top-3 left-7 rounded-full bg-coral px-3 py-1 font-sans text-[0.65rem] font-bold uppercase tracking-wider text-white"
                        >
                            Más elegido
                        </span>

                        <span
                            :class="[
                                'grid h-12 w-12 place-items-center rounded-2xl',
                                item.is_recommended ? 'bg-honey text-charcoal' : isDark ? 'bg-white/8 text-honey' : 'bg-charcoal text-honey',
                            ]"
                        >
                            <IconGlyph :name="item.icon || 'layers'" :size="22" />
                        </span>

                        <h3 class="mt-5 text-xl">{{ item.name }}</h3>
                        <p v-if="item.headline" class="mt-2 text-sm leading-relaxed opacity-70">{{ item.headline }}</p>

                        <!-- Precio -->
                        <div class="mt-6 border-t border-current/10 pt-5">
                            <template v-if="item.has_pricing">
                                <p class="font-sans text-3xl font-extrabold leading-none">
                                    {{ store.money(item.monthly_price) }}
                                    <span class="text-base font-bold opacity-55">/ mes</span>
                                </p>
                                <p class="mt-1.5 text-xs opacity-60">
                                    Implementación {{ store.money(item.setup_price) }}, pago único
                                </p>
                            </template>
                            <template v-else>
                                <p class="font-sans text-xl font-extrabold leading-none opacity-80">A cotizar</p>
                                <p class="mt-1.5 text-xs opacity-60">La tarifa se confirma contigo</p>
                            </template>
                        </div>

                        <p v-if="previousName(i)" class="mt-5 text-xs font-semibold opacity-55">
                            Todo lo de {{ previousName(i) }}, y además:
                        </p>

                        <ul v-if="item.included?.length" class="mt-3 flex-1 space-y-2.5">
                            <li v-for="line in item.included" :key="line" class="flex items-start gap-2.5 text-sm opacity-80">
                                <IconGlyph name="check" :size="14" class="mt-0.5 shrink-0 text-coral" />
                                {{ line }}
                            </li>
                        </ul>

                        <BaseButton
                            :href="cta.url"
                            :variant="item.is_recommended ? 'primary' : isDark ? 'ghost' : 'dark'"
                            block
                            class="mt-7"
                        >
                            {{ cta.label }}
                        </BaseButton>
                    </article>
                </div>

                <!-- Formas de pagar -->
                <div v-if="billingModels.length > 1" class="grid gap-5 md:grid-cols-2">
                    <article
                        v-for="(model, i) in billingModels"
                        :key="model.id"
                        :class="[
                            'hf-reveal relative flex flex-col rounded-[1.5rem] border p-7 transition-all duration-400',
                            model.is_recommended
                                ? 'border-honey bg-honey/10'
                                : isDark
                                  ? 'border-white/12 bg-white/[0.03]'
                                  : 'border-charcoal/10 bg-white',
                            visible && 'hf-reveal-in',
                        ]"
                        :style="{ transitionDelay: `${i * 70}ms` }"
                    >
                        <span
                            v-if="model.is_recommended"
                            class="absolute -top-3 left-7 rounded-full bg-coral px-3 py-1 font-sans text-[0.65rem] font-bold uppercase tracking-wider text-white"
                        >
                            Más elegida
                        </span>

                        <span
                            :class="[
                                'grid h-12 w-12 place-items-center rounded-2xl',
                                model.is_recommended ? 'bg-honey text-charcoal' : isDark ? 'bg-white/8 text-honey' : 'bg-charcoal text-honey',
                            ]"
                        >
                            <IconGlyph :name="model.icon || 'coins'" :size="22" />
                        </span>

                        <h3 class="mt-5 text-xl">{{ model.name }}</h3>
                        <p v-if="model.headline" class="mt-2 text-sm leading-relaxed opacity-70">
                            {{ model.headline }}
                        </p>
                        <p v-if="model.description" class="mt-3 text-sm leading-relaxed opacity-65">
                            {{ model.description }}
                        </p>

                        <ul v-if="model.included?.length" class="mt-5 flex-1 space-y-2.5 border-t border-current/10 pt-5">
                            <li
                                v-for="line in model.included"
                                :key="line"
                                class="flex items-start gap-2.5 text-sm opacity-80"
                            >
                                <IconGlyph name="check" :size="14" class="mt-0.5 shrink-0 text-coral" />
                                {{ line }}
                            </li>
                        </ul>
                    </article>
                </div>

                <!-- Tramos escalonados y calculadora de referencia -->
                <div
                    v-if="usageModel?.tiers?.length"
                    :class="['hf-reveal rounded-[1.5rem] p-7 md:p-9', isDark ? 'bg-white/[0.04]' : 'bg-charcoal text-ivory', visible && 'hf-reveal-in']"
                >
                    <div class="grid gap-10 lg:grid-cols-[1fr_minmax(0,22rem)] lg:gap-14">
                        <div>
                            <p class="hf-eyebrow text-honey">Cuanto más vendes, menos pagas por pedido</p>
                            <h3 class="mt-4 text-2xl md:text-[1.9rem]">
                                {{ store.text('pricing_usage_title', 'Cómo funciona el cobro por pedido') }}
                            </h3>
                            <p class="mt-3 max-w-xl text-base leading-relaxed text-ivory/70">
                                {{
                                    store.text(
                                        'pricing_usage_description',
                                        'Cada pedido paga el precio del tramo en el que cae. Llegar al pedido 501 abarata ese pedido y los que vienen después, nunca los 500 anteriores: tu factura no puede subir de golpe por crecer un poco.',
                                    )
                                }}
                            </p>

                            <ul class="mt-7 space-y-px overflow-hidden rounded-2xl border border-white/10">
                                <li
                                    v-for="tier in usageModel.tiers"
                                    :key="tier.id"
                                    class="flex items-center justify-between gap-4 bg-white/[0.03] px-5 py-4"
                                >
                                    <span class="font-sans text-sm font-bold text-ivory">{{ tier.range }}</span>
                                    <span class="shrink-0 text-right">
                                        <span class="font-sans text-xl font-extrabold text-ivory">
                                            {{ tier.price_per_order !== null ? store.money(tier.price_per_order) : '—' }}
                                        </span>
                                        <span class="block text-[0.68rem] text-ivory/50">por pedido</span>
                                    </span>
                                </li>
                            </ul>

                            <ul v-if="usageModel.conditions?.length" class="mt-5 space-y-1.5">
                                <li
                                    v-for="line in usageModel.conditions"
                                    :key="line"
                                    class="flex items-start gap-2 text-xs text-ivory/50"
                                >
                                    <IconGlyph name="shield" :size="12" class="mt-0.5 shrink-0" />
                                    {{ line }}
                                </li>
                            </ul>
                        </div>

                        <!-- Calculadora de referencia -->
                        <div class="rounded-2xl border border-white/12 bg-charcoal/60 p-6">
                            <p class="font-sans text-sm font-bold text-ivory">Haz la cuenta</p>
                            <p class="mt-1 text-xs text-ivory/55">Mueve los pedidos y mira cómo se reparten.</p>

                            <label class="mt-5 hf-label text-ivory" for="pricing-orders">
                                Pedidos al mes:
                                <span class="text-honey">{{ orders.toLocaleString('es') }}</span>
                            </label>
                            <input
                                id="pricing-orders"
                                v-model.number="orders"
                                type="range"
                                min="50"
                                max="3000"
                                step="50"
                                class="w-full accent-[var(--color-honey)]"
                            />

                            <ul class="mt-5 space-y-1.5 border-t border-white/10 pt-4">
                                <li
                                    v-for="row in usageBreakdown.rows"
                                    :key="row.id"
                                    class="flex items-baseline justify-between gap-3 text-xs"
                                >
                                    <span class="text-ivory/55">
                                        {{ row.orders.toLocaleString('es') }} × {{ store.money(row.price) }}
                                    </span>
                                    <span class="shrink-0 font-sans font-bold text-ivory">
                                        {{ store.money(row.amount) }}
                                    </span>
                                </li>
                            </ul>

                            <p class="mt-4 border-t border-white/10 pt-4 font-sans text-[2rem] font-extrabold leading-none text-ivory">
                                {{ store.money(usageBreakdown.total) }}
                            </p>
                            <p class="mt-1.5 text-xs text-ivory/55">
                                al mes de consumo · {{ store.money(usageBreakdown.average) }} por pedido en promedio
                            </p>

                            <BaseButton :href="cta.url" variant="primary" block class="mt-6">
                                {{ cta.label }}
                            </BaseButton>
                        </div>
                    </div>
                </div>

                <!-- Escalabilidad -->
                <div
                    v-if="scalability.length"
                    :class="['hf-reveal rounded-[1.5rem] p-7 md:p-9', isDark ? 'bg-white/[0.04]' : 'bg-charcoal text-ivory', visible && 'hf-reveal-in']"
                >
                    <p class="hf-eyebrow text-honey">Creces tú, crece la plataforma</p>
                    <h3 class="mt-4 text-2xl md:text-[1.9rem]">
                        {{ store.text('pricing_scale_title', 'Suma sucursales, marcas y números cuando los necesites') }}
                    </h3>
                    <p class="mt-3 max-w-2xl text-base leading-relaxed text-ivory/70">
                        {{
                            store.text(
                                'pricing_scale_description',
                                'Nunca ponemos un tope de pedidos. Pagas por lo que conectas y, si eliges el cobro por pedido, por lo que de verdad procesas.',
                            )
                        }}
                    </p>

                    <ul class="mt-7 grid gap-px overflow-hidden rounded-2xl border border-white/10 sm:grid-cols-2">
                        <li
                            v-for="unit in scalability"
                            :key="unit.id"
                            class="bg-white/[0.03] p-5"
                        >
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/8 text-honey">
                                    <IconGlyph :name="unit.icon || 'building'" :size="19" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-sans text-sm font-bold text-ivory">{{ unit.name }}</p>
                                    <p v-if="unit.short_description" class="mt-1 text-xs leading-snug text-ivory/55">
                                        {{ unit.short_description }}
                                    </p>
                                </div>
                            </div>

                            <p class="mt-4 font-sans text-lg font-extrabold text-ivory">
                                <template v-if="unit.has_pricing">
                                    {{ store.money(unit.monthly_price) }}
                                    <span class="text-xs font-medium text-ivory/50">
                                        / mes · implementación {{ store.money(unit.setup_price) }}
                                    </span>
                                </template>
                                <span v-else class="text-base font-bold text-ivory/70">Tarifa a confirmar</span>
                            </p>

                            <p v-if="unit.included_quantity > 0" class="mt-1 text-xs text-ivory/45">
                                {{ unit.included_quantity }}
                                {{ unit.included_quantity === 1 ? 'incluida' : 'incluidas' }} en todos los paquetes.
                            </p>
                        </li>
                    </ul>
                </div>

                <!-- Servicios externos -->
                <div v-if="externalCosts.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="(cost, i) in externalCosts"
                        :key="cost.title"
                        :class="['hf-reveal', visible && 'hf-reveal-in']"
                        :style="{ transitionDelay: `${i * 60}ms` }"
                    >
                        <span
                            :class="[
                                'grid h-11 w-11 place-items-center rounded-xl',
                                isDark ? 'bg-white/8 text-honey' : 'bg-honey-soft text-charcoal',
                            ]"
                        >
                            <IconGlyph :name="cost.icon || 'layers'" :size="20" />
                        </span>
                        <h3 class="mt-4 text-base">{{ cost.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed opacity-70">{{ cost.description }}</p>
                    </article>
                </div>

                <p v-if="externalCosts.length" class="text-xs opacity-55">
                    Estos conceptos se facturan por separado según el consumo real; nunca van dentro de la mensualidad.
                </p>
            </div>

            <p v-else class="text-sm opacity-60">
                Aún no hay paquetes publicados. Configúralos en el panel.
            </p>
        </template>
    </SectionShell>
</template>
