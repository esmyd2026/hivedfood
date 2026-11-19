<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { api, parseError } from '../lib/api';
import { useUiStore } from '../stores/ui';
import AdminIcon from '../components/AdminIcon.vue';
import AdminModal from '../components/AdminModal.vue';
import StatusChip from '../components/StatusChip.vue';

const ui = useUiStore();

const STATUS = {
    draft: { label: 'Borrador', tone: 'warning' },
    sent: { label: 'Enviada', tone: 'info' },
    negotiating: { label: 'En negociación', tone: 'info' },
    accepted: { label: 'Aceptada', tone: 'success' },
    rejected: { label: 'Rechazada', tone: 'danger' },
    expired: { label: 'Vencida', tone: 'neutral' },
};

const rows = ref([]);
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const search = ref('');
const statusFilter = ref('');

const detail = ref(null);
const detailMeta = ref({});
const saving = ref(false);
const sending = ref(false);
const duplicating = ref(false);
const converting = ref(false);
const edit = reactive({ status: '', internal_notes: '' });

async function load(page = 1) {
    loading.value = true;

    try {
        const data = await api.get('cotizaciones', {
            page,
            per_page: 20,
            search: search.value || undefined,
            status: statusFilter.value || undefined,
        });

        rows.value = data.data;
        Object.assign(meta, { current_page: data.current_page, last_page: data.last_page, total: data.total });
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());

let timer = null;

watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(() => load(1), 300);
});

watch(statusFilter, () => load(1));

async function open(row) {
    try {
        const payload = await api.get(`cotizaciones/${row.id}`);
        detail.value = payload.data;
        detailMeta.value = payload.meta ?? {};
        edit.status = payload.data.status;
        edit.internal_notes = payload.data.internal_notes ?? '';
    } catch (error) {
        ui.error(parseError(error).message);
    }
}

async function save() {
    saving.value = true;

    try {
        await api.post(`cotizaciones/${detail.value.id}`, {
            status: edit.status,
            internal_notes: edit.internal_notes,
        });
        ui.success('Cotización actualizada.');
        detail.value = null;
        await load(meta.current_page);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        saving.value = false;
    }
}

async function resend() {
    sending.value = true;

    try {
        const data = await api.post(`cotizaciones/${detail.value.id}/reenviar`, {});
        ui.success(data.message);
        await load(meta.current_page);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        sending.value = false;
    }
}

/** Duplica la propuesta y la recalcula con el tarifario vigente. */
async function duplicate() {
    duplicating.value = true;

    try {
        const data = await api.post(`cotizaciones/${detail.value.id}/duplicar`, {});
        ui.success(data.message);
        detail.value = null;
        await load(1);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        duplicating.value = false;
    }
}

/** Una propuesta aceptada pasa a ser una solicitud de implementacion. */
async function convert() {
    converting.value = true;

    try {
        const data = await api.post(`cotizaciones/${detail.value.id}/implementacion`, {});
        ui.success(data.message);
        detail.value.implementation_requested_at = data.data.implementation_requested_at;
        await load(meta.current_page);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        converting.value = false;
    }
}

async function remove(row) {
    if (!window.confirm(`¿Eliminar la cotización ${row.code}?`)) {
        return;
    }

    try {
        await api.destroy(`cotizaciones/${row.id}`);
        ui.success('Cotización eliminada.');
        await load(meta.current_page);
    } catch (error) {
        ui.error(parseError(error).message);
    }
}

function when(value) {
    return value ? new Date(value).toLocaleString('es', { dateStyle: 'short', timeStyle: 'short' }) : '—';
}

function amount(row) {
    return row.is_priced ? `${Number(row.total).toFixed(2)} ${row.currency}` : 'A cotizar';
}

function money(value, currency) {
    return value === null || value === undefined ? '—' : `${Number(value).toFixed(2)} ${currency}`;
}
</script>

<template>
    <div>
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <div class="relative min-w-[12rem] flex-1">
                <AdminIcon name="search" :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-charcoal/35" />
                <input v-model="search" type="search" class="ad-field pl-9" placeholder="Código, nombre o correo…" aria-label="Buscar cotizaciones" />
            </div>

            <select v-model="statusFilter" class="ad-field w-auto" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option v-for="(item, key) in STATUS" :key="key" :value="key">{{ item.label }}</option>
            </select>

            <button type="button" class="ad-btn ad-btn-ghost" :disabled="loading" @click="load(meta.current_page)">
                <AdminIcon name="refresh" :size="15" />
                <span class="hidden sm:inline">Actualizar</span>
            </button>
        </div>

        <div class="ad-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Contacto</th>
                            <th>Paquete</th>
                            <th>Primer pago</th>
                            <th>Estado</th>
                            <th>Creada</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && !rows.length">
                            <td colspan="7" class="py-10 text-center text-charcoal/50">Cargando…</td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td colspan="7" class="py-10 text-center text-charcoal/50">Todavía no hay cotizaciones.</td>
                        </tr>

                        <tr v-for="row in rows" :key="row.id">
                            <td class="font-sans font-bold">{{ row.code }}</td>
                            <td>
                                <span class="block">{{ row.lead?.name ?? '—' }}</span>
                                <span class="text-xs text-charcoal/55">{{ row.lead?.email }}</span>
                            </td>
                            <td class="text-xs">{{ row.plan_label ?? '—' }}</td>
                            <td :class="row.is_priced ? 'font-sans font-bold' : 'text-xs text-charcoal/55'">
                                {{ amount(row) }}
                                <span v-if="row.usage_monthly !== null" class="block text-xs font-medium text-charcoal/55">
                                    + {{ row.usage_monthly }} de consumo
                                </span>
                            </td>
                            <td><StatusChip :value="row.status" :map="STATUS" /></td>
                            <td class="text-xs text-charcoal/60">{{ when(row.created_at) }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a
                                        :href="api.url(`cotizaciones/${row.id}/pdf`)"
                                        class="grid h-8 w-8 place-items-center rounded-lg text-charcoal/50 transition hover:bg-ivory hover:text-charcoal"
                                        aria-label="Descargar PDF"
                                    >
                                        <AdminIcon name="download" :size="15" />
                                    </a>
                                    <button
                                        type="button"
                                        class="grid h-8 w-8 place-items-center rounded-lg text-charcoal/50 transition hover:bg-ivory hover:text-charcoal"
                                        aria-label="Ver detalle"
                                        @click="open(row)"
                                    >
                                        <AdminIcon name="eye" :size="15" />
                                    </button>
                                    <button
                                        type="button"
                                        class="grid h-8 w-8 place-items-center rounded-lg text-charcoal/50 transition hover:bg-coral-soft hover:text-coral-dark"
                                        aria-label="Eliminar"
                                        @click="remove(row)"
                                    >
                                        <AdminIcon name="trash" :size="15" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta.last_page > 1" class="flex items-center justify-between gap-3 border-t border-charcoal/8 px-4 py-3 text-xs">
                <span class="text-charcoal/55">{{ meta.total }} cotizaciones · página {{ meta.current_page }} de {{ meta.last_page }}</span>
                <div class="flex gap-2">
                    <button type="button" class="ad-btn ad-btn-ghost" :disabled="meta.current_page === 1" @click="load(meta.current_page - 1)">
                        Anterior
                    </button>
                    <button
                        type="button"
                        class="ad-btn ad-btn-ghost"
                        :disabled="meta.current_page === meta.last_page"
                        @click="load(meta.current_page + 1)"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        </div>

        <!-- Detalle -->
        <AdminModal
            :open="!!detail"
            :title="detail ? `Cotización ${detail.code}` : ''"
            :subtitle="detail?.lead?.name"
            wide
            @close="detail = null"
        >
            <div v-if="detail" class="space-y-6">
                <dl class="grid gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="ad-label">Tipo de negocio</dt>
                        <dd class="text-sm">{{ detail.business_type_label ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Paquete</dt>
                        <dd class="text-sm">{{ detail.plan_label ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Forma de cobro</dt>
                        <dd class="text-sm">{{ detail.billing_model_label ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Vigencia</dt>
                        <dd class="text-sm">{{ detail.valid_until ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Enviada</dt>
                        <dd class="text-sm">{{ when(detail.emailed_at) }}</dd>
                    </div>
                </dl>

                <div>
                    <p class="ad-label">Conceptos cotizados</p>
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="text-right">Cant.</th>
                                <th class="text-right">Implementación</th>
                                <th class="text-right">Mensual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in detail.items.filter((i) => !i.is_included)" :key="item.id">
                                <td>
                                    {{ item.name }}
                                    <span v-if="item.is_auto_added" class="ml-1 text-xs text-charcoal/50">
                                        (dependencia)
                                    </span>
                                </td>
                                <td class="text-right">{{ item.quantity }}</td>
                                <td class="text-right">{{ money(item.setup_price, detail.currency) }}</td>
                                <td class="text-right font-sans font-bold">
                                    {{ money(item.monthly_price, detail.currency) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <details v-if="detail.items.some((i) => i.is_included)" class="mt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-charcoal/60">
                            Incluido en el paquete, sin cargo
                        </summary>
                        <ul class="mt-2 flex flex-wrap gap-1.5">
                            <li
                                v-for="item in detail.items.filter((i) => i.is_included)"
                                :key="item.id"
                                class="rounded-full bg-ivory px-2.5 py-1 text-xs"
                            >
                                {{ item.name }}
                            </li>
                        </ul>
                    </details>
                </div>

                <div class="rounded-xl bg-ivory p-4">
                    <template v-if="detail.is_priced">
                        <div class="flex justify-between text-sm">
                            <span>Inversión inicial</span>
                            <span class="font-sans font-bold">{{ detail.setup_total }} {{ detail.currency }}</span>
                        </div>
                        <div class="mt-1 flex justify-between text-sm">
                            <span>Servicio mensual</span>
                            <span class="font-sans font-bold">{{ detail.recurring_total }} {{ detail.currency }}</span>
                        </div>
                        <div v-if="detail.usage_monthly !== null" class="mt-1 flex justify-between text-sm">
                            <span>
                                Consumo estimado por pedidos
                                <span v-if="detail.payload?.usage" class="block text-xs text-charcoal/55">
                                    {{ detail.payload.usage.total_orders.toLocaleString('es') }} pedidos al mes ·
                                    se factura al cierre
                                </span>
                            </span>
                            <span class="font-sans font-bold">{{ detail.usage_monthly }} {{ detail.currency }}</span>
                        </div>
                        <div class="mt-2 flex justify-between border-t border-charcoal/10 pt-2 text-sm">
                            <span class="font-sans font-bold">Primer pago</span>
                            <span class="font-sans font-extrabold">{{ detail.total }} {{ detail.currency }}</span>
                        </div>
                        <div v-if="detail.payload?.year_projection" class="mt-1 flex justify-between text-xs text-charcoal/60">
                            <span>Proyección de {{ detail.payload.projection_months ?? 12 }} meses</span>
                            <span>{{ detail.payload.year_projection }} {{ detail.currency }}</span>
                        </div>
                    </template>
                    <p v-else class="text-sm text-charcoal/65">
                        Esta cotización se generó sin precios publicados: el visitante recibió un mensaje de propuesta a medida.
                    </p>
                </div>

                <div v-if="detail.payload?.usage?.tiers?.length">
                    <p class="ad-label">Tramos aplicados</p>
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Tramo</th>
                                <th class="text-right">Pedidos</th>
                                <th class="text-right">Por pedido</th>
                                <th class="text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tier in detail.payload.usage.tiers" :key="tier.label">
                                <td>{{ tier.label }}</td>
                                <td class="text-right">{{ tier.orders.toLocaleString('es') }}</td>
                                <td class="text-right">{{ tier.price_per_order }}</td>
                                <td class="text-right font-sans font-bold">{{ tier.amount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-if="detail.duplicated_from" class="text-xs text-charcoal/55">
                    Duplicada de la cotización {{ detail.duplicated_from.code }}.
                </p>

                <p v-if="detail.implementation_requested_at" class="rounded-xl bg-honey-soft/60 p-3 text-sm">
                    Implementación solicitada el {{ when(detail.implementation_requested_at) }}.
                </p>

                <div v-if="detail.notes">
                    <p class="ad-label">Notas del cliente</p>
                    <p class="rounded-xl bg-ivory p-4 text-sm">{{ detail.notes }}</p>
                </div>

                <div class="grid gap-4 border-t border-charcoal/8 pt-5 sm:grid-cols-[14rem_1fr]">
                    <div>
                        <label class="ad-label" for="quote-status">Estado</label>
                        <select id="quote-status" v-model="edit.status" class="ad-field">
                            <option v-for="(item, key) in STATUS" :key="key" :value="key">{{ item.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="ad-label" for="quote-notes">Notas internas</label>
                        <textarea id="quote-notes" v-model="edit.internal_notes" rows="4" class="ad-field resize-y" />
                    </div>
                </div>
            </div>

            <template #footer>
                <a v-if="detail" :href="api.url(`cotizaciones/${detail.id}/pdf`)" class="ad-btn ad-btn-ghost">
                    <AdminIcon name="download" :size="14" />
                    PDF
                </a>
                <a
                    v-if="detailMeta.whatsapp_url"
                    :href="detailMeta.whatsapp_url"
                    target="_blank"
                    rel="noopener"
                    class="ad-btn ad-btn-ghost"
                >
                    <AdminIcon name="external" :size="14" />
                    WhatsApp
                </a>
                <button type="button" class="ad-btn ad-btn-ghost" :disabled="sending" @click="resend">
                    <AdminIcon name="mail" :size="14" />
                    Reenviar al cliente
                </button>
                <button type="button" class="ad-btn ad-btn-ghost" :disabled="duplicating" @click="duplicate">
                    <AdminIcon name="copy" :size="14" />
                    Duplicar
                </button>
                <button
                    v-if="detail?.status === 'accepted' && !detail?.implementation_requested_at"
                    type="button"
                    class="ad-btn ad-btn-ghost"
                    :disabled="converting"
                    @click="convert"
                >
                    <AdminIcon name="spark" :size="14" />
                    Pasar a implementación
                </button>
                <button type="button" class="ad-btn ad-btn-primary" :disabled="saving" @click="save">
                    <AdminIcon name="check" :size="15" />
                    Guardar
                </button>
            </template>
        </AdminModal>
    </div>
</template>
