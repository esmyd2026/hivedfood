<script setup>
import { computed } from 'vue';
import { useQuoterStore } from '../../stores/quoter';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

const quoter = useQuoterStore();
const site = useSiteStore();

/** La unidad del catálogo decide qué contador controla. */
const KEYS = {
    branch: 'branches',
    brand: 'brands',
    whatsapp: 'whatsapp_numbers',
    training: 'trainings',
};

const usage = computed(() => quoter.usage);

const units = computed(() =>
    quoter.scalabilityItems
        .filter((item) => KEYS[item.unit_kind])
        .map((item) => {
            const key = KEYS[item.unit_kind];
            const total = quoter.scalability[key] ?? item.min_quantity ?? 0;
            const chargeable = Math.max(0, total - (item.included_quantity ?? 0));

            return {
                ...item,
                key,
                total,
                chargeable,
                min: item.min_quantity ?? 0,
                max: item.max_quantity ?? 100,
                setupTotal: item.setup_price === null ? null : item.setup_price * chargeable,
                monthlyTotal: item.monthly_price === null ? null : item.monthly_price * chargeable,
            };
        }),
);
</script>

<template>
    <div>
        <h3 class="text-xl text-ivory md:text-2xl">
            {{ site.text('quoter_scale_title', 'Cuéntanos el tamaño de tu operación') }}
        </h3>
        <p v-if="site.text('quoter_scale_description')" class="mt-2 text-sm text-ivory/60">
            {{ site.text('quoter_scale_description') }}
        </p>

        <ul class="mt-7 space-y-3">
            <li
                v-for="unit in units"
                :key="unit.id"
                class="rounded-2xl border border-white/12 p-5 transition-colors hover:border-white/22"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-3.5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/8 text-honey">
                            <IconGlyph :name="unit.icon || 'building'" :size="19" />
                        </span>
                        <div class="min-w-0">
                            <p class="font-sans text-sm font-bold text-ivory">{{ unit.name }}</p>
                            <p v-if="unit.short_description" class="mt-1 text-xs leading-snug text-ivory/55">
                                {{ unit.short_description }}
                            </p>
                            <p v-if="unit.included_quantity > 0" class="mt-1 text-xs text-ivory/45">
                                {{ unit.included_quantity }} {{ unit.included_quantity === 1 ? 'incluida' : 'incluidas' }}
                                en el paquete.
                            </p>
                        </div>
                    </div>

                    <!-- Contador -->
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            class="grid h-9 w-9 place-items-center rounded-lg border border-white/15 text-ivory transition hover:border-honey hover:text-honey disabled:opacity-35"
                            :disabled="unit.total <= unit.min"
                            :aria-label="`Reducir ${unit.name}`"
                            @click="quoter.stepScale(unit.key, -1)"
                        >
                            <IconGlyph name="close" :size="12" :stroke="2.5" style="transform: rotate(45deg)" />
                        </button>

                        <input
                            type="number"
                            class="h-9 w-16 rounded-lg border border-white/15 bg-transparent text-center font-sans text-sm font-bold text-ivory [appearance:textfield] focus:border-honey focus:outline-none [&::-webkit-inner-spin-button]:appearance-none"
                            :value="unit.total"
                            :min="unit.min"
                            :max="unit.max"
                            :aria-label="`Cantidad de ${unit.name}`"
                            @change="quoter.setScale(unit.key, $event.target.value)"
                        />

                        <button
                            type="button"
                            class="grid h-9 w-9 place-items-center rounded-lg border border-white/15 text-ivory transition hover:border-honey hover:text-honey disabled:opacity-35"
                            :disabled="unit.total >= unit.max"
                            :aria-label="`Aumentar ${unit.name}`"
                            @click="quoter.stepScale(unit.key, 1)"
                        >
                            <IconGlyph name="plus" :size="14" :stroke="2.5" />
                        </button>
                    </div>
                </div>

                <!-- Precio incremental -->
                <p
                    v-if="unit.chargeable > 0"
                    class="mt-4 border-t border-white/8 pt-3 font-sans text-xs font-bold text-honey"
                >
                    <template v-if="unit.has_pricing">
                        +{{ site.money(unit.monthlyTotal) }}
                        <span class="font-medium text-ivory/45">
                            / mes por {{ unit.chargeable }} adicional{{ unit.chargeable === 1 ? '' : 'es' }}
                            · implementación +{{ site.money(unit.setupTotal) }}
                        </span>
                    </template>
                    <span v-else class="font-medium text-ivory/50">
                        {{ unit.chargeable }} adicional{{ unit.chargeable === 1 ? '' : 'es' }} · tarifa pendiente de
                        aprobación comercial
                    </span>
                </p>

                <ul v-if="unit.notes?.length" class="mt-3 space-y-1">
                    <li v-for="note in unit.notes" :key="note" class="text-xs text-ivory/45">· {{ note }}</li>
                </ul>
            </li>
        </ul>

        <!-- Volumen de pedidos: solo cuesta si la modalidad cobra consumo -->
        <section
            v-if="quoter.chargesUsage"
            class="mt-9 rounded-2xl border border-honey/30 bg-honey/[0.07] p-5"
        >
            <h4 class="font-sans text-xs font-bold uppercase tracking-[0.18em] text-honey">
                ¿Cuántos pedidos haces al mes?
            </h4>
            <p class="mt-2 text-xs leading-relaxed text-ivory/60">
                Elegiste <strong class="font-sans text-ivory">{{ quoter.billingModel?.name }}</strong>, así que
                además de la mensualidad pagas por pedido. Dinos un estimado por local y calculamos el consumo
                con los tramos escalonados.
            </p>

            <div class="mt-4 max-w-xs">
                <label class="hf-label text-ivory" for="q-orders">Pedidos al mes por local</label>
                <input
                    id="q-orders"
                    type="number"
                    min="0"
                    step="10"
                    class="hf-field [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                    :value="quoter.ordersPerBranch"
                    @change="quoter.setOrders($event.target.value)"
                />
            </div>

            <!-- Cómo se reparte entre tramos, en claro -->
            <div v-if="usage && usage.tiers.length" class="mt-5 border-t border-white/10 pt-4">
                <ul class="space-y-1.5">
                    <li
                        v-for="tier in usage.tiers"
                        :key="tier.label"
                        class="flex items-baseline justify-between gap-4 text-xs"
                    >
                        <span class="text-ivory/60">
                            {{ tier.label }} ·
                            {{ tier.orders.toLocaleString('es') }} pedidos × {{ site.money(tier.price_per_order) }}
                        </span>
                        <span class="shrink-0 font-sans font-bold text-ivory">{{ site.money(tier.amount) }}</span>
                    </li>
                </ul>

                <p class="mt-3 flex items-baseline justify-between gap-4 border-t border-white/10 pt-3">
                    <span class="font-sans text-sm font-bold text-ivory">Consumo estimado</span>
                    <span class="font-sans text-lg font-extrabold text-honey">{{ site.money(usage.monthly) }}</span>
                </p>
                <p v-if="usage.average_per_order" class="mt-1 text-xs text-ivory/45">
                    Te sale a {{ site.money(usage.average_per_order) }} por pedido en promedio.
                </p>
            </div>

            <p v-else-if="usage" class="mt-4 text-xs text-ivory/50">
                Indica cuántos pedidos haces y te mostramos el consumo estimado.
            </p>
        </section>

        <!-- Dimensionamiento del catálogo: sirve para planificar, no para cobrar -->
        <section class="mt-9 rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <h4 class="font-sans text-xs font-bold uppercase tracking-[0.18em] text-honey">
                Tamaño de tu carta
            </h4>
            <p class="mt-2 text-xs leading-relaxed text-ivory/55">
                Nos ayuda a dimensionar la implementación. No genera ningún cargo: no cobramos por cantidad de
                productos ni de pedidos.
            </p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="hf-label text-ivory" for="q-products">Productos aproximados</label>
                    <input
                        id="q-products"
                        type="number"
                        min="0"
                        class="hf-field [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                        :value="quoter.catalog.products"
                        @change="quoter.setCatalog('products', $event.target.value)"
                    />
                </div>
                <div>
                    <label class="hf-label text-ivory" for="q-categories">Categorías</label>
                    <input
                        id="q-categories"
                        type="number"
                        min="0"
                        class="hf-field [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                        :value="quoter.catalog.categories"
                        @change="quoter.setCatalog('categories', $event.target.value)"
                    />
                </div>
            </div>

            <div class="mt-4">
                <label class="hf-label text-ivory" for="q-notes">¿Algo más que debamos saber?</label>
                <textarea
                    id="q-notes"
                    v-model="quoter.notes"
                    rows="3"
                    maxlength="1000"
                    class="hf-field"
                    placeholder="Horarios, integraciones que ya usas, equipos del local…"
                />
            </div>
        </section>
    </div>
</template>
