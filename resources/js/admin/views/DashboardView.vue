<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api, parseError } from '../lib/api';
import { useUiStore } from '../stores/ui';
import AdminIcon from '../components/AdminIcon.vue';
import StatusChip from '../components/StatusChip.vue';

const ui = useUiStore();

const data = ref(null);
const loading = ref(true);

const LEAD_STATUS = {
    new: { label: 'Nuevo', tone: 'warning' },
    contacted: { label: 'Contactado', tone: 'info' },
    qualified: { label: 'Calificado', tone: 'info' },
    proposal: { label: 'Propuesta', tone: 'info' },
    won: { label: 'Ganado', tone: 'success' },
    lost: { label: 'Perdido', tone: 'danger' },
};

const QUOTE_STATUS = {
    new: { label: 'Nueva', tone: 'warning' },
    sent: { label: 'Enviada', tone: 'info' },
    negotiating: { label: 'Negociación', tone: 'info' },
    accepted: { label: 'Aceptada', tone: 'success' },
    rejected: { label: 'Rechazada', tone: 'danger' },
    expired: { label: 'Vencida', tone: 'neutral' },
};

onMounted(async () => {
    try {
        const response = await api.get('dashboard');
        data.value = response.data;
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        loading.value = false;
    }
});

const tiles = computed(() => {
    const summary = data.value?.summary ?? {};

    return [
        { label: 'Leads totales', value: summary.leads_total, hint: `${summary.leads_last_30 ?? 0} en 30 días`, icon: 'users', to: 'leads' },
        { label: 'Leads pendientes', value: summary.leads_pending, hint: 'Nuevos o contactados', icon: 'clock', to: 'leads', accent: true },
        { label: 'Cotizaciones', value: summary.quotations_total, hint: `${summary.quotations_last_30 ?? 0} en 30 días`, icon: 'receipt', to: 'quotations' },
        { label: 'Cotizaciones abiertas', value: summary.quotations_open, hint: 'Sin cerrar', icon: 'coins', to: 'quotations' },
    ];
});

const content = computed(() => {
    const summary = data.value?.summary ?? {};

    return [
        { label: 'Banners publicados', value: summary.banners_active, to: 'banners' },
        { label: 'Secciones visibles', value: summary.sections_active, to: 'content' },
        { label: 'Demostraciones publicadas', value: summary.demos_published, to: 'demos' },
    ];
});

const ROUTE_BY_PATH = {
    '/admin/configuracion': 'settings',
    '/admin/demostraciones': 'demos',
    '/admin/cotizador': 'quoter',
};

function when(value) {
    return value ? new Date(value).toLocaleDateString('es', { day: '2-digit', month: 'short' }) : '—';
}

function maxOf(record) {
    return Math.max(1, ...Object.values(record ?? {}));
}
</script>

<template>
    <div v-if="loading" class="text-sm text-charcoal/55">Cargando resumen…</div>

    <div v-else-if="data" class="space-y-7">
        <!-- Avisos de configuración -->
        <section v-if="data.checklist.length" class="rounded-xl border border-honey/50 bg-honey-soft/40 p-5">
            <div class="flex items-start gap-3">
                <AdminIcon name="alert" :size="19" class="mt-0.5 text-charcoal" />
                <div class="flex-1">
                    <h2 class="text-sm">Pendientes de configuración</h2>
                    <ul class="mt-3 space-y-2">
                        <li v-for="item in data.checklist" :key="item.key" class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="flex-1">{{ item.label }}</span>
                            <RouterLink
                                v-if="ROUTE_BY_PATH[item.route]"
                                :to="{ name: ROUTE_BY_PATH[item.route] }"
                                class="ad-btn ad-btn-ghost"
                            >
                                Resolver
                                <AdminIcon name="chevron-right" :size="13" />
                            </RouterLink>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Indicadores comerciales -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <RouterLink
                v-for="tile in tiles"
                :key="tile.label"
                :to="{ name: tile.to }"
                :class="[
                    'ad-card group p-5 transition hover:border-charcoal/20',
                    tile.accent && Number(tile.value) > 0 && 'border-coral/35 bg-coral-soft/25',
                ]"
            >
                <div class="flex items-start justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-ivory text-charcoal">
                        <AdminIcon :name="tile.icon" :size="18" />
                    </span>
                    <AdminIcon name="chevron-right" :size="16" class="text-charcoal/20 transition group-hover:text-charcoal/50" />
                </div>
                <p class="mt-4 font-sans text-3xl font-extrabold">{{ tile.value ?? 0 }}</p>
                <p class="mt-0.5 font-sans text-sm font-bold">{{ tile.label }}</p>
                <p class="mt-0.5 text-xs text-charcoal/50">{{ tile.hint }}</p>
            </RouterLink>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
            <!-- Leads recientes -->
            <section class="ad-card overflow-hidden">
                <header class="flex items-center justify-between gap-3 border-b border-charcoal/8 px-5 py-4">
                    <h2 class="text-sm">Últimos leads</h2>
                    <RouterLink :to="{ name: 'leads' }" class="font-sans text-xs font-bold text-coral-dark hover:underline">
                        Ver todos
                    </RouterLink>
                </header>

                <table class="ad-table">
                    <tbody>
                        <tr v-if="!data.recent_leads.length">
                            <td class="py-8 text-center text-charcoal/50">Todavía no hay leads.</td>
                        </tr>
                        <tr v-for="lead in data.recent_leads" :key="lead.id">
                            <td>
                                <span class="block font-sans font-bold">{{ lead.name }}</span>
                                <span class="text-xs text-charcoal/55">{{ lead.company || lead.email }}</span>
                            </td>
                            <td><StatusChip :value="lead.status" :map="LEAD_STATUS" /></td>
                            <td class="text-right text-xs text-charcoal/55">{{ when(lead.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Distribución -->
            <section class="ad-card p-5">
                <h2 class="text-sm">Leads por origen</h2>

                <ul class="mt-4 space-y-3">
                    <li v-for="(total, source) in data.leads_by_source" :key="source">
                        <div class="flex items-baseline justify-between text-xs">
                            <span class="font-sans font-bold">{{ source }}</span>
                            <span class="text-charcoal/55">{{ total }}</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ivory-dark">
                            <div
                                class="h-full rounded-full bg-honey"
                                :style="{ width: `${(total / maxOf(data.leads_by_source)) * 100}%` }"
                            />
                        </div>
                    </li>
                    <li v-if="!Object.keys(data.leads_by_source).length" class="text-xs text-charcoal/50">
                        Sin datos todavía.
                    </li>
                </ul>

                <h2 class="mt-7 border-t border-charcoal/8 pt-5 text-sm">Estado de cotizaciones</h2>
                <ul class="mt-4 space-y-2">
                    <li
                        v-for="(total, status) in data.quotations_by_status"
                        :key="status"
                        class="flex items-center justify-between gap-3 text-xs"
                    >
                        <StatusChip :value="status" :map="QUOTE_STATUS" />
                        <span class="font-sans font-bold">{{ total }}</span>
                    </li>
                    <li v-if="!Object.keys(data.quotations_by_status).length" class="text-xs text-charcoal/50">
                        Sin cotizaciones todavía.
                    </li>
                </ul>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
            <!-- Contenido -->
            <section class="ad-card p-5">
                <h2 class="text-sm">Contenido del sitio</h2>
                <ul class="mt-4 divide-y divide-charcoal/8">
                    <li v-for="item in content" :key="item.label" class="flex items-center justify-between gap-3 py-2.5">
                        <RouterLink :to="{ name: item.to }" class="text-sm hover:underline">{{ item.label }}</RouterLink>
                        <span class="font-sans text-sm font-extrabold">{{ item.value ?? 0 }}</span>
                    </li>
                </ul>

                <div class="mt-5 rounded-xl bg-ivory p-4 text-xs">
                    <p class="font-sans font-bold">Estado de precios</p>
                    <p class="mt-1 text-charcoal/65">
                        {{ data.pricing.published ? 'Publicados en el cotizador.' : 'Ocultos: el cotizador muestra propuesta a medida.' }}
                    </p>
                    <p class="mt-1.5 text-charcoal/55">
                        {{ data.pricing.items_without_price }} servicios y {{ data.pricing.plans_without_price }} soluciones sin precio definido.
                    </p>
                    <RouterLink :to="{ name: 'quoter' }" class="mt-2.5 inline-block font-sans font-bold text-coral-dark hover:underline">
                        Configurar tarifas
                    </RouterLink>
                </div>
            </section>

            <!-- Cotizaciones recientes -->
            <section class="ad-card overflow-hidden">
                <header class="flex items-center justify-between gap-3 border-b border-charcoal/8 px-5 py-4">
                    <h2 class="text-sm">Últimas cotizaciones</h2>
                    <RouterLink :to="{ name: 'quotations' }" class="font-sans text-xs font-bold text-coral-dark hover:underline">
                        Ver todas
                    </RouterLink>
                </header>

                <table class="ad-table">
                    <tbody>
                        <tr v-if="!data.recent_quotations.length">
                            <td class="py-8 text-center text-charcoal/50">Todavía no hay cotizaciones.</td>
                        </tr>
                        <tr v-for="quote in data.recent_quotations" :key="quote.id">
                            <td class="font-sans font-bold">{{ quote.code }}</td>
                            <td>
                                <span class="block text-xs">{{ quote.lead?.name ?? '—' }}</span>
                                <span class="text-xs text-charcoal/50">{{ quote.plan_label }}</span>
                            </td>
                            <td class="text-xs">
                                {{ quote.is_priced ? `${Number(quote.total).toFixed(2)} ${quote.currency}` : 'A cotizar' }}
                            </td>
                            <td><StatusChip :value="quote.status" :map="QUOTE_STATUS" /></td>
                            <td class="text-right text-xs text-charcoal/55">{{ when(quote.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>
