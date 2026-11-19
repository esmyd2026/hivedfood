<script setup>
import { computed } from 'vue';
import { useQuoterStore } from '../../stores/quoter';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    // En móvil el resumen vive dentro de una hoja desplegable.
    compact: { type: Boolean, default: false },
});

const quoter = useQuoterStore();
const site = useSiteStore();

const result = computed(() => quoter.result);

/** Conceptos que sí generan cargo, ya sea de implementación o mensual. */
const chargedLines = computed(() => (result.value?.lines ?? []).filter((line) => !line.is_included));

const includedCount = computed(() => (result.value?.lines ?? []).filter((line) => line.is_included).length);

const scalability = computed(() =>
    Object.values(result.value?.scalability ?? {}).filter((unit) => unit.total > 0),
);

const months = computed(() => result.value?.projection_months ?? site.quoter.projection_months ?? 12);

/** Consumo por pedidos: existe solo si la modalidad elegida lo cobra. */
const usage = computed(() => result.value?.usage ?? null);
</script>

<template>
    <aside
        :class="[
            'rounded-[1.5rem] bg-white text-charcoal',
            compact ? 'p-5' : 'p-6 shadow-[var(--shadow-lift)]',
        ]"
        aria-label="Resumen de tu inversión"
    >
        <div class="flex items-center justify-between gap-3">
            <p class="font-sans text-xs font-bold uppercase tracking-[0.15em] text-coral">Tu inversión</p>
            <span v-if="quoter.calculating" class="flex items-center gap-1.5 text-[0.68rem] opacity-55">
                <span class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-coral border-t-transparent" />
                Calculando
            </span>
        </div>

        <!-- Todavía sin paquete elegido -->
        <template v-if="!result">
            <p class="mt-4 text-sm leading-relaxed opacity-70">
                Elige un paquete y verás aquí, en tiempo real, cuánto cuesta la plataforma que estás armando.
            </p>
        </template>

        <template v-else>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="opacity-60">Paquete</dt>
                    <dd class="text-right font-sans font-bold">{{ result.plan?.name ?? '—' }}</dd>
                </div>
                <div v-if="result.billing_model" class="flex items-baseline justify-between gap-4">
                    <dt class="opacity-60">Forma de pago</dt>
                    <dd class="text-right font-sans font-bold">{{ result.billing_model.name }}</dd>
                </div>
                <div v-if="includedCount" class="flex items-baseline justify-between gap-4">
                    <dt class="opacity-60">Módulos incluidos</dt>
                    <dd class="text-right font-sans font-bold">{{ includedCount }}</dd>
                </div>
                <div v-if="quoter.selectedCount" class="flex items-baseline justify-between gap-4">
                    <dt class="opacity-60">Módulos adicionales</dt>
                    <dd class="text-right font-sans font-bold">{{ quoter.selectedCount }}</dd>
                </div>
                <div v-for="unit in scalability" :key="unit.slug" class="flex items-baseline justify-between gap-4">
                    <dt class="opacity-60">{{ unit.name }}</dt>
                    <dd class="text-right font-sans font-bold">
                        {{ unit.total }}
                        <span v-if="unit.chargeable > 0" class="text-xs font-medium opacity-60">
                            (+{{ unit.chargeable }})
                        </span>
                    </dd>
                </div>
            </dl>

            <!-- Totales -->
            <div class="mt-5 border-t border-charcoal/10 pt-4">
                <dl class="space-y-2 text-sm">
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="opacity-60">Inversión inicial</dt>
                        <dd class="font-sans font-bold">{{ site.money(result.setup_total) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="opacity-60">Servicio mensual fijo</dt>
                        <dd class="font-sans font-bold">{{ site.money(result.monthly_total) }}</dd>
                    </div>
                    <div v-if="usage" class="flex items-baseline justify-between gap-4">
                        <dt class="opacity-60">
                            Consumo por pedidos
                            <span class="block text-xs opacity-60">
                                {{ usage.total_orders.toLocaleString('es') }} pedidos al mes
                            </span>
                        </dt>
                        <dd class="font-sans font-bold">
                            {{ usage.is_priced ? site.money(usage.monthly) : 'A cotizar' }}
                        </dd>
                    </div>
                    <div
                        v-for="discount in result.discounts"
                        :key="discount.id"
                        class="flex items-baseline justify-between gap-4 text-coral"
                    >
                        <dt class="opacity-85">{{ discount.name }}</dt>
                        <dd class="font-sans font-bold">
                            −{{ site.money(discount.setup_amount + discount.monthly_amount) }}
                        </dd>
                    </div>
                </dl>

                <div
                    v-if="usage"
                    class="mt-3 flex items-baseline justify-between gap-4 border-t border-charcoal/10 pt-3 text-sm"
                >
                    <span class="font-sans font-bold">Mensual estimado</span>
                    <span class="font-sans font-extrabold">{{ site.money(result.monthly_estimated_total) }}</span>
                </div>

                <div class="mt-4 flex items-baseline justify-between gap-4 border-t border-charcoal/10 pt-4">
                    <span class="font-sans text-sm font-bold">Primer pago</span>
                    <span class="font-sans text-2xl font-extrabold">{{ site.money(result.first_payment) }}</span>
                </div>
                <p class="mt-1 text-xs opacity-55">
                    Implementación más la primera mensualidad.
                    <template v-if="usage">El consumo se factura al cierre de cada mes.</template>
                </p>

                <p class="mt-3 flex items-baseline justify-between gap-4 text-xs opacity-65">
                    <span>Proyección de {{ months }} meses</span>
                    <span class="font-sans font-bold">{{ site.money(result.year_projection) }}</span>
                </p>
            </div>

            <!-- Precios aún no aprobados -->
            <p
                v-if="result.unpriced_count > 0"
                class="mt-4 flex items-start gap-2 rounded-xl bg-ivory p-3 text-xs opacity-75"
            >
                <IconGlyph name="clock" :size="15" class="mt-0.5 shrink-0 text-coral" />
                <span>
                    {{ result.unpriced_count }}
                    {{ result.unpriced_count === 1 ? 'concepto queda' : 'conceptos quedan' }}
                    pendiente{{ result.unpriced_count === 1 ? '' : 's' }} de tarifa; el equipo comercial los confirma
                    contigo.
                </span>
            </p>

            <p v-else-if="!result.pricing_published" class="mt-4 text-xs leading-relaxed opacity-70">
                {{ site.quoter.unpriced_message }}
            </p>

            <!-- Detalle plegable -->
            <details v-if="chargedLines.length" class="group mt-4 border-t border-charcoal/10 pt-4">
                <summary class="flex cursor-pointer list-none items-center justify-between font-sans text-xs font-bold">
                    Ver el detalle
                    <IconGlyph name="chevron-down" :size="14" class="transition-transform group-open:rotate-180" />
                </summary>

                <table class="mt-3 w-full text-xs">
                    <thead>
                        <tr class="text-[0.62rem] uppercase tracking-wider opacity-45">
                            <th class="pb-1.5 text-left font-semibold">Concepto</th>
                            <th class="pb-1.5 text-right font-semibold">Impl.</th>
                            <th class="pb-1.5 text-right font-semibold">Mes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in chargedLines" :key="`${line.slug}-${line.sort_order}`" class="border-t border-charcoal/8">
                            <td class="py-1.5 pr-2">
                                {{ line.name }}
                                <span v-if="line.quantity > 1" class="opacity-50">× {{ line.quantity }}</span>
                            </td>
                            <td class="py-1.5 text-right font-sans font-bold">
                                {{ line.is_priced ? site.money(line.setup_price) : '—' }}
                            </td>
                            <td class="py-1.5 text-right font-sans font-bold">
                                {{ line.is_priced ? site.money(line.monthly_price) : '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </details>

            <p v-if="result.validity_days" class="mt-4 text-xs opacity-50">
                Vigencia de la cotización: {{ result.validity_days }} días.
            </p>
        </template>
    </aside>
</template>
