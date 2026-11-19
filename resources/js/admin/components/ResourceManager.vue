<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { api, parseError } from '../lib/api';
import { useUiStore } from '../stores/ui';
import AdminIcon from './AdminIcon.vue';
import AdminModal from './AdminModal.vue';
import FormField from './FormField.vue';
import StatusChip from './StatusChip.vue';

const props = defineProps({
    /** Resource endpoint under /admin/api. */
    endpoint: { type: String, required: true },
    singular: { type: String, default: 'registro' },
    /** Table columns. */
    columns: { type: Array, required: true },
    /** Form schema passed to FormField. */
    fields: { type: Array, required: true },
    defaults: { type: Object, default: () => ({}) },
    reorderable: { type: Boolean, default: false },
    searchable: { type: Boolean, default: true },
    filters: { type: Array, default: () => [] },
    /** Extra query params always sent with the index request. */
    scope: { type: Object, default: () => ({}) },
    emptyMessage: { type: String, default: 'Todavía no hay registros.' },
    creatable: { type: Boolean, default: true },
});

const emit = defineEmits(['changed']);

const ui = useUiStore();

const rows = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const activeFilters = reactive({});

const modalOpen = ref(false);
const editing = ref(null);
const form = reactive({});
const removedFiles = reactive({});
const errors = ref({});

const confirmTarget = ref(null);

const imageFields = computed(() => props.fields.filter((field) => field.type === 'image'));

async function load() {
    loading.value = true;

    try {
        const params = { ...props.scope, ...activeFilters };

        if (search.value) {
            params.search = search.value;
        }

        const data = await api.get(props.endpoint, params);
        rows.value = data.data ?? [];
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        loading.value = false;
    }
}

onMounted(load);

watch(() => props.scope, load, { deep: true });

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(load, 300);
});

function openCreate() {
    editing.value = null;
    errors.value = {};
    Object.keys(removedFiles).forEach((key) => delete removedFiles[key]);

    Object.keys(form).forEach((key) => delete form[key]);
    props.fields.forEach((field) => {
        form[field.key] = field.type === 'image' ? null : (props.defaults[field.key] ?? field.default ?? null);

        if (field.type === 'image') {
            form[`${field.key}_path`] = null;
        }
    });
    Object.entries(props.defaults).forEach(([key, value]) => {
        if (!(key in form)) {
            form[key] = value;
        }
    });

    modalOpen.value = true;
}

function openEdit(row) {
    editing.value = row;
    errors.value = {};
    Object.keys(removedFiles).forEach((key) => delete removedFiles[key]);

    Object.keys(form).forEach((key) => delete form[key]);
    props.fields.forEach((field) => {
        form[field.key] = field.type === 'image' ? null : (row[field.key] ?? null);

        if (field.type === 'image') {
            form[`${field.key}_path`] = null;
        }
    });

    // Attributes the form does not render (e.g. a parent id) still have to be
    // submitted, so they come from the row or from the configured defaults.
    Object.keys(props.defaults).forEach((key) => {
        if (!(key in form)) {
            form[key] = row[key] ?? props.defaults[key];
        }
    });

    modalOpen.value = true;
}

async function save() {
    saving.value = true;
    errors.value = {};

    try {
        const payload = { ...form };

        Object.keys(removedFiles).forEach((key) => {
            payload[`${key}_remove`] = true;
        });

        // Drop untouched image inputs so the stored path is preserved, and
        // only send the library choice when one was actually made.
        imageFields.value.forEach((field) => {
            if (payload[field.key] === null) {
                delete payload[field.key];
            }

            if (!payload[`${field.key}_path`]) {
                delete payload[`${field.key}_path`];
            }
        });

        const path = editing.value ? `${props.endpoint}/${editing.value.id}` : props.endpoint;
        const data = await api.post(path, payload);

        ui.success(data.message ?? 'Guardado.');
        modalOpen.value = false;
        await load();
        emit('changed');
    } catch (error) {
        const parsed = parseError(error);
        errors.value = parsed.errors;
        ui.error(parsed.message);
    } finally {
        saving.value = false;
    }
}

async function remove() {
    const row = confirmTarget.value;
    confirmTarget.value = null;

    try {
        const data = await api.destroy(`${props.endpoint}/${row.id}`);
        ui.success(data.message ?? 'Eliminado.');
        await load();
        emit('changed');
    } catch (error) {
        ui.error(parseError(error).message);
    }
}

async function toggle(row, field) {
    const next = !row[field];
    row[field] = next;

    try {
        await api.post(`${props.endpoint}/${row.id}/toggle`, { field, value: next });
        emit('changed');
    } catch (error) {
        row[field] = !next;
        ui.error(parseError(error).message);
    }
}

async function move(index, delta) {
    const target = index + delta;

    if (target < 0 || target >= rows.value.length) {
        return;
    }

    const reordered = [...rows.value];
    [reordered[index], reordered[target]] = [reordered[target], reordered[index]];
    rows.value = reordered;

    try {
        await api.post(`${props.endpoint}/reorder`, { order: reordered.map((row) => row.id) });
        emit('changed');
    } catch (error) {
        ui.error(parseError(error).message);
        await load();
    }
}

function cell(row, column) {
    const value = row[column.key];

    if (column.format) {
        return column.format(row);
    }

    return value;
}

defineExpose({ load });
</script>

<template>
    <div>
        <!-- Barra de herramientas -->
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <div v-if="searchable" class="relative min-w-[12rem] flex-1">
                <AdminIcon name="search" :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-charcoal/35" />
                <input v-model="search" type="search" class="ad-field pl-9" placeholder="Buscar…" aria-label="Buscar" />
            </div>

            <select
                v-for="filter in filters"
                :key="filter.key"
                v-model="activeFilters[filter.key]"
                class="ad-field w-auto"
                :aria-label="filter.label"
                @change="load"
            >
                <option value="">{{ filter.label }}</option>
                <option v-for="option in filter.options" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>

            <button type="button" class="ad-btn ad-btn-ghost" :disabled="loading" @click="load">
                <AdminIcon name="refresh" :size="15" />
                <span class="hidden sm:inline">Actualizar</span>
            </button>

            <button v-if="creatable" type="button" class="ad-btn ad-btn-honey" @click="openCreate">
                <AdminIcon name="plus" :size="15" />
                Nuevo {{ singular }}
            </button>
        </div>

        <!-- Tabla -->
        <div class="ad-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th v-if="reorderable" class="w-14">Orden</th>
                            <th v-for="column in columns" :key="column.key">{{ column.label }}</th>
                            <th class="w-28 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && !rows.length">
                            <td :colspan="columns.length + (reorderable ? 2 : 1)" class="py-10 text-center text-charcoal/50">
                                Cargando…
                            </td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td :colspan="columns.length + (reorderable ? 2 : 1)" class="py-10 text-center text-charcoal/50">
                                {{ emptyMessage }}
                            </td>
                        </tr>

                        <tr v-for="(row, index) in rows" :key="row.id">
                            <td v-if="reorderable">
                                <div class="flex flex-col gap-0.5">
                                    <button
                                        type="button"
                                        class="grid h-6 w-6 place-items-center rounded text-charcoal/40 transition hover:bg-ivory hover:text-charcoal disabled:opacity-25"
                                        :disabled="index === 0"
                                        aria-label="Subir"
                                        @click="move(index, -1)"
                                    >
                                        <AdminIcon name="up" :size="13" />
                                    </button>
                                    <button
                                        type="button"
                                        class="grid h-6 w-6 place-items-center rounded text-charcoal/40 transition hover:bg-ivory hover:text-charcoal disabled:opacity-25"
                                        :disabled="index === rows.length - 1"
                                        aria-label="Bajar"
                                        @click="move(index, 1)"
                                    >
                                        <AdminIcon name="down" :size="13" />
                                    </button>
                                </div>
                            </td>

                            <td v-for="column in columns" :key="column.key">
                                <!-- Imagen -->
                                <img
                                    v-if="column.type === 'image'"
                                    :src="row[column.key] || '/images/brand/favicon.svg'"
                                    :alt="''"
                                    class="h-10 w-16 rounded-lg border border-charcoal/8 bg-ivory object-contain"
                                />

                                <!-- Interruptor -->
                                <button
                                    v-else-if="column.type === 'toggle'"
                                    type="button"
                                    role="switch"
                                    :aria-checked="!!row[column.key]"
                                    :aria-label="`${column.label}: ${row[column.key] ? 'activo' : 'inactivo'}`"
                                    :class="[
                                        'relative h-5 w-9 rounded-full transition-colors',
                                        row[column.key] ? 'bg-charcoal' : 'bg-charcoal/18',
                                    ]"
                                    @click="toggle(row, column.key)"
                                >
                                    <span
                                        :class="[
                                            'absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-all',
                                            row[column.key] ? 'left-[1.15rem]' : 'left-0.5',
                                        ]"
                                    />
                                </button>

                                <!-- Etiqueta de estado -->
                                <StatusChip v-else-if="column.type === 'chip'" :value="row[column.key]" :map="column.map ?? {}" />

                                <!-- Texto -->
                                <span v-else :class="['block', column.strong && 'font-sans font-bold', column.truncate && 'max-w-[18rem] truncate']">
                                    {{ cell(row, column) ?? '—' }}
                                </span>
                            </td>

                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <slot name="row-actions" :row="row" :reload="load" />

                                    <button
                                        type="button"
                                        class="grid h-8 w-8 place-items-center rounded-lg text-charcoal/50 transition hover:bg-ivory hover:text-charcoal"
                                        aria-label="Editar"
                                        @click="openEdit(row)"
                                    >
                                        <AdminIcon name="edit" :size="15" />
                                    </button>
                                    <button
                                        type="button"
                                        class="grid h-8 w-8 place-items-center rounded-lg text-charcoal/50 transition hover:bg-coral-soft hover:text-coral-dark"
                                        aria-label="Eliminar"
                                        @click="confirmTarget = row"
                                    >
                                        <AdminIcon name="trash" :size="15" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Formulario -->
        <AdminModal
            :open="modalOpen"
            :title="editing ? `Editar ${singular}` : `Nuevo ${singular}`"
            :subtitle="editing ? `ID ${editing.id}` : ''"
            wide
            @close="modalOpen = false"
        >
            <form id="resource-form" class="grid gap-5 sm:grid-cols-2" @submit.prevent="save">
                <FormField
                    v-for="field in fields"
                    :key="field.key"
                    v-model="form[field.key]"
                    v-model:path-value="form[`${field.key}_path`]"
                    :field="field"
                    :error="errors[field.key]?.[0] ?? errors[`${field.key}_path`]?.[0]"
                    :preview="editing?.[`${field.key}_url`] ?? editing?.[field.key] ?? null"
                    :removed="!!removedFiles[field.key]"
                    @remove-file="removedFiles[field.key] = true"
                />
            </form>

            <template #footer>
                <button type="button" class="ad-btn ad-btn-ghost" @click="modalOpen = false">Cancelar</button>
                <button type="submit" form="resource-form" class="ad-btn ad-btn-primary" :disabled="saving">
                    <AdminIcon v-if="!saving" name="check" :size="15" />
                    {{ saving ? 'Guardando…' : 'Guardar' }}
                </button>
            </template>
        </AdminModal>

        <!-- Confirmación -->
        <AdminModal :open="!!confirmTarget" title="Eliminar registro" @close="confirmTarget = null">
            <p class="text-sm">
                ¿Seguro que quieres eliminar este {{ singular }}? Esta acción no se puede deshacer.
            </p>
            <template #footer>
                <button type="button" class="ad-btn ad-btn-ghost" @click="confirmTarget = null">Cancelar</button>
                <button type="button" class="ad-btn ad-btn-danger" @click="remove">
                    <AdminIcon name="trash" :size="14" />
                    Eliminar
                </button>
            </template>
        </AdminModal>
    </div>
</template>
