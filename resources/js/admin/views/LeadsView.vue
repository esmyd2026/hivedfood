<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { api, parseError } from '../lib/api';
import { useUiStore } from '../stores/ui';
import AdminIcon from '../components/AdminIcon.vue';
import AdminModal from '../components/AdminModal.vue';
import StatusChip from '../components/StatusChip.vue';

const ui = useUiStore();

const STATUS = {
    new: { label: 'Nuevo', tone: 'warning' },
    contacted: { label: 'Contactado', tone: 'info' },
    qualified: { label: 'Calificado', tone: 'info' },
    proposal: { label: 'Propuesta', tone: 'info' },
    won: { label: 'Ganado', tone: 'success' },
    lost: { label: 'Perdido', tone: 'danger' },
};

const SOURCES = {
    quotation: 'Cotizador',
    demo_request: 'Solicitud de demo',
    contact_form: 'Formulario',
    whatsapp: 'WhatsApp',
    newsletter: 'Newsletter',
};

const rows = ref([]);
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const search = ref('');
const statusFilter = ref('');
const sourceFilter = ref('');

const detail = ref(null);
const saving = ref(false);
const edit = reactive({ status: '', internal_notes: '' });

async function load(page = 1) {
    loading.value = true;

    try {
        const data = await api.get('leads', {
            page,
            per_page: 20,
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            source: sourceFilter.value || undefined,
        });

        rows.value = data.data;
        Object.assign(meta, {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total,
        });
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

watch([statusFilter, sourceFilter], () => load(1));

async function open(row) {
    try {
        const { data } = await api.get(`leads/${row.id}`);
        detail.value = data;
        edit.status = data.status;
        edit.internal_notes = data.internal_notes ?? '';
    } catch (error) {
        ui.error(parseError(error).message);
    }
}

async function save() {
    saving.value = true;

    try {
        await api.post(`leads/${detail.value.id}`, { status: edit.status, internal_notes: edit.internal_notes });
        ui.success('Lead actualizado.');
        detail.value = null;
        await load(meta.current_page);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        saving.value = false;
    }
}

async function remove(row) {
    if (!window.confirm(`¿Eliminar el lead de ${row.name}?`)) {
        return;
    }

    try {
        await api.destroy(`leads/${row.id}`);
        ui.success('Lead eliminado.');
        await load(meta.current_page);
    } catch (error) {
        ui.error(parseError(error).message);
    }
}

function when(value) {
    return value ? new Date(value).toLocaleString('es', { dateStyle: 'short', timeStyle: 'short' }) : '—';
}
</script>

<template>
    <div>
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <div class="relative min-w-[12rem] flex-1">
                <AdminIcon name="search" :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-charcoal/35" />
                <input v-model="search" type="search" class="ad-field pl-9" placeholder="Nombre, correo, negocio…" aria-label="Buscar leads" />
            </div>

            <select v-model="statusFilter" class="ad-field w-auto" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option v-for="(item, key) in STATUS" :key="key" :value="key">{{ item.label }}</option>
            </select>

            <select v-model="sourceFilter" class="ad-field w-auto" aria-label="Filtrar por origen">
                <option value="">Todos los orígenes</option>
                <option v-for="(label, key) in SOURCES" :key="key" :value="key">{{ label }}</option>
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
                            <th>Contacto</th>
                            <th>Negocio</th>
                            <th>Origen</th>
                            <th>Estado</th>
                            <th>Cotizaciones</th>
                            <th>Recibido</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && !rows.length">
                            <td colspan="7" class="py-10 text-center text-charcoal/50">Cargando…</td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td colspan="7" class="py-10 text-center text-charcoal/50">No hay leads que coincidan.</td>
                        </tr>

                        <tr v-for="row in rows" :key="row.id">
                            <td>
                                <span class="block font-sans font-bold">{{ row.name }}</span>
                                <a :href="`mailto:${row.email}`" class="text-xs text-charcoal/55 hover:underline">{{ row.email }}</a>
                                <span v-if="row.phone" class="block text-xs text-charcoal/45">{{ row.phone }}</span>
                            </td>
                            <td>
                                <span class="block">{{ row.company ?? '—' }}</span>
                                <span class="text-xs text-charcoal/50">
                                    {{ row.business_type?.name ?? row.business_type_label ?? '' }}
                                </span>
                            </td>
                            <td class="text-xs">{{ SOURCES[row.source] ?? row.source }}</td>
                            <td><StatusChip :value="row.status" :map="STATUS" /></td>
                            <td class="text-center">{{ row.quotations_count }}</td>
                            <td class="text-xs text-charcoal/60">{{ when(row.created_at) }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
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
                <span class="text-charcoal/55">{{ meta.total }} leads · página {{ meta.current_page }} de {{ meta.last_page }}</span>
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
        <AdminModal :open="!!detail" :title="detail?.name ?? ''" :subtitle="detail?.email" wide @close="detail = null">
            <div v-if="detail" class="space-y-6">
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="ad-label">Negocio</dt>
                        <dd class="text-sm">{{ detail.company ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Teléfono</dt>
                        <dd class="text-sm">{{ detail.phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Ciudad</dt>
                        <dd class="text-sm">{{ detail.city ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Tipo de negocio</dt>
                        <dd class="text-sm">{{ detail.business_type?.name ?? detail.business_type_label ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Origen</dt>
                        <dd class="text-sm">{{ SOURCES[detail.source] ?? detail.source }}</dd>
                    </div>
                    <div>
                        <dt class="ad-label">Marketing</dt>
                        <dd class="text-sm">{{ detail.accepts_marketing ? 'Acepta' : 'No acepta' }}</dd>
                    </div>
                </dl>

                <div v-if="detail.message">
                    <p class="ad-label">Mensaje recibido</p>
                    <p class="rounded-xl bg-ivory p-4 text-sm">{{ detail.message }}</p>
                </div>

                <div v-if="detail.quotations?.length">
                    <p class="ad-label">Cotizaciones asociadas</p>
                    <ul class="divide-y divide-charcoal/8 rounded-xl border border-charcoal/8">
                        <li v-for="quote in detail.quotations" :key="quote.id" class="flex items-center justify-between gap-4 px-4 py-2.5 text-sm">
                            <span class="font-sans font-bold">{{ quote.code }}</span>
                            <span class="text-xs text-charcoal/55">{{ when(quote.created_at) }}</span>
                            <span>{{ quote.is_priced ? `${quote.total} ${quote.currency}` : 'A cotizar' }}</span>
                        </li>
                    </ul>
                </div>

                <div class="grid gap-4 border-t border-charcoal/8 pt-5 sm:grid-cols-[14rem_1fr]">
                    <div>
                        <label class="ad-label" for="lead-status">Estado comercial</label>
                        <select id="lead-status" v-model="edit.status" class="ad-field">
                            <option v-for="(item, key) in STATUS" :key="key" :value="key">{{ item.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="ad-label" for="lead-notes">Notas internas</label>
                        <textarea id="lead-notes" v-model="edit.internal_notes" rows="4" class="ad-field resize-y" />
                    </div>
                </div>

                <p class="text-xs text-charcoal/45">
                    Recibido {{ when(detail.created_at) }}
                    <template v-if="detail.ip_address"> · IP {{ detail.ip_address }}</template>
                </p>
            </div>

            <template #footer>
                <button type="button" class="ad-btn ad-btn-ghost" @click="detail = null">Cerrar</button>
                <button type="button" class="ad-btn ad-btn-primary" :disabled="saving" @click="save">
                    <AdminIcon name="check" :size="15" />
                    Guardar cambios
                </button>
            </template>
        </AdminModal>
    </div>
</template>
