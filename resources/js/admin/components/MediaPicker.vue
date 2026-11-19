<script setup>
import { computed, ref, watch } from 'vue';
import { api, parseError } from '../lib/api';
import { useUiStore } from '../stores/ui';
import AdminIcon from './AdminIcon.vue';
import AdminModal from './AdminModal.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    /** Pre-selects this folder when opening. */
    folder: { type: String, default: '' },
});

const emit = defineEmits(['close', 'select']);

const ui = useUiStore();

const items = ref([]);
const folders = ref({});
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const uploading = ref(false);
const search = ref('');
const activeFolder = ref('');
const chosen = ref(null);

const hasResults = computed(() => items.value.length > 0);

async function load(page = 1) {
    loading.value = true;

    try {
        const response = await api.get('medios', {
            page,
            per_page: 24,
            search: search.value || undefined,
            folder: activeFolder.value || undefined,
        });

        items.value = response.data;
        meta.value = response.meta;
        folders.value = response.folders;
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            activeFolder.value = props.folder || '';
            chosen.value = null;
            load();
        }
    },
);

let timer = null;

watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(() => load(1), 300);
});

watch(activeFolder, () => load(1));

async function upload(event) {
    const files = [...(event.target.files ?? [])];

    if (!files.length) {
        return;
    }

    uploading.value = true;

    try {
        const payload = { folder: activeFolder.value || 'biblioteca' };
        files.forEach((file, i) => {
            payload[`files[${i}]`] = file;
        });

        const response = await api.post('medios', payload);
        ui.success(response.message);

        await load(1);

        if (response.data.length === 1) {
            chosen.value = response.data[0];
        }
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

function confirm() {
    if (chosen.value) {
        emit('select', chosen.value);
        emit('close');
    }
}

function size(bytes) {
    return bytes > 1024 * 1024
        ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}
</script>

<template>
    <AdminModal :open="open" title="Biblioteca de imágenes" subtitle="Elige una imagen o sube una nueva" wide @close="emit('close')">
        <div>
            <!-- Filtros -->
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <div class="relative min-w-[11rem] flex-1">
                    <AdminIcon name="search" :size="15" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-charcoal/35" />
                    <input v-model="search" type="search" class="ad-field pl-9" placeholder="Buscar imagen…" aria-label="Buscar imagen" />
                </div>

                <select v-model="activeFolder" class="ad-field w-auto" aria-label="Filtrar por carpeta">
                    <option value="">Todas las carpetas</option>
                    <option v-for="(label, key) in folders" :key="key" :value="key">{{ label }}</option>
                </select>

                <label class="ad-btn ad-btn-honey cursor-pointer">
                    <AdminIcon name="plus" :size="15" />
                    {{ uploading ? 'Subiendo…' : 'Subir' }}
                    <input
                        type="file"
                        class="hidden"
                        accept="image/*,.svg"
                        multiple
                        :disabled="uploading"
                        @change="upload"
                    />
                </label>
            </div>

            <!-- Rejilla -->
            <div v-if="loading && !hasResults" class="py-12 text-center text-sm text-charcoal/50">Cargando…</div>

            <p v-else-if="!hasResults" class="py-12 text-center text-sm text-charcoal/50">
                No hay imágenes que coincidan. Sube una nueva con el botón «Subir».
            </p>

            <ul v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <li v-for="item in items" :key="item.id">
                    <button
                        type="button"
                        :class="[
                            'group w-full overflow-hidden rounded-xl border-2 text-left transition',
                            chosen?.id === item.id ? 'border-coral' : 'border-charcoal/10 hover:border-charcoal/30',
                        ]"
                        :aria-pressed="chosen?.id === item.id"
                        @click="chosen = item"
                        @dblclick="chosen = item; confirm()"
                    >
                        <span class="block aspect-[4/3] bg-ivory">
                            <img :src="item.url" :alt="item.alt || item.name" class="h-full w-full object-contain" loading="lazy" />
                        </span>
                        <span class="block border-t border-charcoal/8 px-2.5 py-2">
                            <span class="block truncate font-sans text-xs font-bold" :title="item.name">{{ item.name }}</span>
                            <span class="mt-0.5 block text-[0.68rem] text-charcoal/50">
                                {{ item.width ? `${item.width}×${item.height}` : 'dimensiones ?' }} · {{ size(item.size) }}
                            </span>
                        </span>
                    </button>
                </li>
            </ul>

            <!-- Paginación -->
            <div v-if="meta.last_page > 1" class="mt-4 flex items-center justify-between gap-3 text-xs">
                <span class="text-charcoal/55">{{ meta.total }} imágenes · página {{ meta.current_page }} de {{ meta.last_page }}</span>
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

        <template #footer>
            <span v-if="chosen" class="mr-auto truncate text-xs text-charcoal/55">
                Seleccionada: <strong class="font-sans">{{ chosen.name }}</strong>
            </span>
            <button type="button" class="ad-btn ad-btn-ghost" @click="emit('close')">Cancelar</button>
            <button type="button" class="ad-btn ad-btn-primary" :disabled="!chosen" @click="confirm">
                <AdminIcon name="check" :size="15" />
                Usar esta imagen
            </button>
        </template>
    </AdminModal>
</template>
