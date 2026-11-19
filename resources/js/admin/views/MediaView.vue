<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { api, parseError } from '../lib/api';
import { useUiStore } from '../stores/ui';
import AdminIcon from '../components/AdminIcon.vue';
import AdminModal from '../components/AdminModal.vue';

const ui = useUiStore();

const items = ref([]);
const folders = ref({});
const meta = reactive({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const uploading = ref(false);
const syncing = ref(false);
const search = ref('');
const folder = ref('');
const uploadFolder = ref('biblioteca');

const detail = ref(null);
const usage = ref([]);
const saving = ref(false);
const replacing = ref(false);
const edit = reactive({ name: '', alt: '', description: '', folder: 'biblioteca' });
const confirmTarget = ref(null);
const conflict = ref(null);

async function load(page = 1) {
    loading.value = true;

    try {
        const response = await api.get('medios', {
            page,
            per_page: 24,
            search: search.value || undefined,
            folder: folder.value || undefined,
        });

        items.value = response.data;
        folders.value = response.folders;
        Object.assign(meta, response.meta);
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

watch(folder, () => load(1));

async function sync() {
    syncing.value = true;

    try {
        const response = await api.post('medios/sincronizar', {});
        ui.success(response.message);
        await load(1);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        syncing.value = false;
    }
}

async function upload(event) {
    const files = [...(event.target.files ?? [])];

    if (!files.length) {
        return;
    }

    uploading.value = true;

    try {
        const payload = { folder: uploadFolder.value };
        files.forEach((file, i) => {
            payload[`files[${i}]`] = file;
        });

        const response = await api.post('medios', payload);
        ui.success(response.message);
        await load(1);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

async function open(item) {
    try {
        const response = await api.get(`medios/${item.id}`);
        detail.value = response.data;
        usage.value = response.usage;
        Object.assign(edit, {
            name: response.data.name,
            alt: response.data.alt ?? '',
            description: response.data.description ?? '',
            folder: response.data.folder,
        });
    } catch (error) {
        ui.error(parseError(error).message);
    }
}

async function save() {
    saving.value = true;

    try {
        const response = await api.post(`medios/${detail.value.id}`, { ...edit });
        ui.success(response.message);
        detail.value = null;
        await load(meta.current_page);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        saving.value = false;
    }
}

/** Swap the file while keeping every reference pointing at the new one. */
async function replace(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    replacing.value = true;

    try {
        const response = await api.post(`medios/${detail.value.id}/reemplazar`, { file });
        ui.success(response.message);
        detail.value = null;
        await load(meta.current_page);
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        replacing.value = false;
        event.target.value = '';
    }
}

async function remove(force = false) {
    const target = confirmTarget.value ?? detail.value;

    if (!target) {
        return;
    }

    try {
        await api.destroy(`medios/${target.id}${force ? '?force=1' : ''}`);
        ui.success('Imagen eliminada.');
        confirmTarget.value = null;
        conflict.value = null;
        detail.value = null;
        await load(meta.current_page);
    } catch (error) {
        const response = error?.response;

        if (response?.status === 409) {
            conflict.value = { target, usage: response.data.usage ?? [] };
            confirmTarget.value = null;

            return;
        }

        ui.error(parseError(error).message);
    }
}

function size(bytes) {
    return bytes > 1024 * 1024
        ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

const totalLabel = computed(() => `${meta.total} ${meta.total === 1 ? 'imagen' : 'imágenes'}`);
</script>

<template>
    <div>
        <p class="mb-5 max-w-3xl text-sm text-charcoal/60">
            Todas las imágenes del sitio en un solo lugar: las que vienen con el proyecto y las que subes desde el
            panel. Reemplaza una imagen y se actualiza en todos los lugares donde se usa, sin tocar nada más.
        </p>

        <!-- Barra de herramientas -->
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <div class="relative min-w-[12rem] flex-1">
                <AdminIcon name="search" :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-charcoal/35" />
                <input v-model="search" type="search" class="ad-field pl-9" placeholder="Buscar por nombre, ruta o ALT…" aria-label="Buscar imagen" />
            </div>

            <select v-model="folder" class="ad-field w-auto" aria-label="Filtrar por carpeta">
                <option value="">Todas las carpetas</option>
                <option v-for="(label, key) in folders" :key="key" :value="key">{{ label }}</option>
            </select>

            <button type="button" class="ad-btn ad-btn-ghost" :disabled="syncing" @click="sync">
                <AdminIcon name="refresh" :size="15" />
                {{ syncing ? 'Revisando…' : 'Sincronizar' }}
            </button>

            <select v-model="uploadFolder" class="ad-field w-auto" aria-label="Carpeta de destino">
                <option v-for="(label, key) in folders" :key="key" :value="key">Subir a: {{ label }}</option>
            </select>

            <label class="ad-btn ad-btn-honey cursor-pointer">
                <AdminIcon name="plus" :size="15" />
                {{ uploading ? 'Subiendo…' : 'Subir imágenes' }}
                <input type="file" class="hidden" accept="image/*,.svg" multiple :disabled="uploading" @change="upload" />
            </label>
        </div>

        <!-- Rejilla -->
        <div v-if="loading && !items.length" class="ad-card py-16 text-center text-sm text-charcoal/50">Cargando…</div>

        <div v-else-if="!items.length" class="ad-card py-16 text-center">
            <AdminIcon name="image" :size="30" class="mx-auto text-charcoal/20" />
            <p class="mt-3 text-sm text-charcoal/55">No hay imágenes que coincidan.</p>
        </div>

        <ul v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <li v-for="item in items" :key="item.id" class="ad-card overflow-hidden">
                <button type="button" class="block w-full text-left" @click="open(item)">
                    <span class="block aspect-[4/3] bg-ivory">
                        <img :src="item.url" :alt="item.alt || item.name" class="h-full w-full object-contain" loading="lazy" />
                    </span>
                </button>

                <div class="border-t border-charcoal/8 px-3 py-2.5">
                    <p class="truncate font-sans text-xs font-bold" :title="item.name">{{ item.name }}</p>
                    <p class="mt-0.5 text-[0.68rem] text-charcoal/50">
                        {{ item.width ? `${item.width}×${item.height}` : '—' }} · {{ size(item.size) }}
                    </p>

                    <div class="mt-2 flex items-center justify-between gap-2">
                        <span class="ad-chip bg-ivory-dark text-charcoal/70">{{ folders[item.folder] ?? item.folder }}</span>

                        <div class="flex items-center gap-0.5">
                            <button
                                type="button"
                                class="grid h-7 w-7 place-items-center rounded-lg text-charcoal/50 transition hover:bg-ivory hover:text-charcoal"
                                aria-label="Ver detalle"
                                @click="open(item)"
                            >
                                <AdminIcon name="eye" :size="14" />
                            </button>
                            <button
                                v-if="!item.is_protected"
                                type="button"
                                class="grid h-7 w-7 place-items-center rounded-lg text-charcoal/50 transition hover:bg-coral-soft hover:text-coral-dark"
                                aria-label="Eliminar"
                                @click="confirmTarget = item"
                            >
                                <AdminIcon name="trash" :size="14" />
                            </button>
                            <span
                                v-else
                                class="grid h-7 w-7 place-items-center text-charcoal/25"
                                title="Recurso del proyecto: se puede reemplazar, no eliminar"
                            >
                                <AdminIcon name="shield" :size="14" />
                            </span>
                        </div>
                    </div>
                </div>
            </li>
        </ul>

        <!-- Paginación -->
        <div v-if="meta.last_page > 1" class="mt-5 flex items-center justify-between gap-3 text-xs">
            <span class="text-charcoal/55">{{ totalLabel }} · página {{ meta.current_page }} de {{ meta.last_page }}</span>
            <div class="flex gap-2">
                <button type="button" class="ad-btn ad-btn-ghost" :disabled="meta.current_page === 1" @click="load(meta.current_page - 1)">
                    Anterior
                </button>
                <button type="button" class="ad-btn ad-btn-ghost" :disabled="meta.current_page === meta.last_page" @click="load(meta.current_page + 1)">
                    Siguiente
                </button>
            </div>
        </div>

        <!-- Detalle -->
        <AdminModal :open="!!detail" :title="detail?.name ?? ''" :subtitle="detail?.path" wide @close="detail = null">
            <div v-if="detail" class="grid gap-6 md:grid-cols-[18rem_1fr]">
                <div>
                    <div class="grid aspect-[4/3] place-items-center overflow-hidden rounded-xl border border-charcoal/10 bg-ivory">
                        <img :src="detail.url" :alt="detail.alt || detail.name" class="h-full w-full object-contain" />
                    </div>

                    <dl class="mt-4 space-y-1.5 text-xs text-charcoal/60">
                        <div class="flex justify-between gap-3">
                            <dt>Dimensiones</dt>
                            <dd>{{ detail.width ? `${detail.width}×${detail.height}` : '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt>Peso</dt>
                            <dd>{{ size(detail.size) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt>Tipo</dt>
                            <dd>{{ detail.mime ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt>Origen</dt>
                            <dd>{{ detail.source === 'shipped' ? 'Recurso del proyecto' : 'Subida' }}</dd>
                        </div>
                    </dl>

                    <a :href="detail.url" target="_blank" rel="noopener" class="ad-btn ad-btn-ghost mt-4 w-full">
                        <AdminIcon name="external" :size="14" />
                        Abrir original
                    </a>
                </div>

                <div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ad-label" for="m-name">Nombre</label>
                            <input id="m-name" v-model="edit.name" type="text" class="ad-field" maxlength="190" />
                        </div>
                        <div>
                            <label class="ad-label" for="m-folder">Carpeta</label>
                            <select id="m-folder" v-model="edit.folder" class="ad-field">
                                <option v-for="(label, key) in folders" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="ad-label" for="m-alt">Texto alternativo (ALT)</label>
                            <input id="m-alt" v-model="edit.alt" type="text" class="ad-field" maxlength="200" />
                            <p class="ad-hint">Describe la imagen: mejora la accesibilidad y el SEO.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="ad-label" for="m-desc">Notas internas</label>
                            <textarea id="m-desc" v-model="edit.description" rows="2" class="ad-field resize-y" maxlength="500" />
                        </div>
                    </div>

                    <!-- Reemplazo -->
                    <div class="mt-6 rounded-xl border border-honey/40 bg-honey-soft/40 p-4">
                        <p class="font-sans text-sm font-bold">Reemplazar el archivo</p>
                        <p class="mt-1 text-xs text-charcoal/65">
                            Sube otra imagen y se usará en todos los lugares donde aparece esta. No hace falta editar
                            cada sección por separado.
                        </p>
                        <label class="ad-btn ad-btn-primary mt-3 cursor-pointer">
                            <AdminIcon name="refresh" :size="14" />
                            {{ replacing ? 'Reemplazando…' : 'Elegir archivo nuevo' }}
                            <input type="file" class="hidden" accept="image/*,.svg" :disabled="replacing" @change="replace" />
                        </label>
                    </div>

                    <!-- Uso -->
                    <div class="mt-6">
                        <p class="ad-label">Se usa en</p>
                        <ul v-if="usage.length" class="divide-y divide-charcoal/8 rounded-xl border border-charcoal/8">
                            <li v-for="(use, i) in usage" :key="i" class="flex items-center justify-between gap-3 px-3.5 py-2 text-sm">
                                <span class="text-charcoal/60">{{ use.type }}</span>
                                <span class="font-sans font-bold">{{ use.label }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-charcoal/55">
                            Esta imagen no se está usando en ninguna parte del sitio.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <button
                    v-if="detail && !detail.is_protected"
                    type="button"
                    class="ad-btn ad-btn-danger mr-auto"
                    @click="confirmTarget = detail"
                >
                    <AdminIcon name="trash" :size="14" />
                    Eliminar
                </button>
                <button type="button" class="ad-btn ad-btn-ghost" @click="detail = null">Cerrar</button>
                <button type="button" class="ad-btn ad-btn-primary" :disabled="saving" @click="save">
                    <AdminIcon name="check" :size="15" />
                    Guardar
                </button>
            </template>
        </AdminModal>

        <!-- Confirmación -->
        <AdminModal :open="!!confirmTarget" title="Eliminar imagen" @close="confirmTarget = null">
            <p class="text-sm">
                ¿Eliminar <strong class="font-sans">{{ confirmTarget?.name }}</strong>? El archivo se borra del servidor.
            </p>
            <template #footer>
                <button type="button" class="ad-btn ad-btn-ghost" @click="confirmTarget = null">Cancelar</button>
                <button type="button" class="ad-btn ad-btn-danger" @click="remove(false)">Eliminar</button>
            </template>
        </AdminModal>

        <!-- Imagen en uso -->
        <AdminModal :open="!!conflict" title="La imagen está en uso" @close="conflict = null">
            <p class="text-sm">
                <strong class="font-sans">{{ conflict?.target?.name }}</strong> se usa en los siguientes lugares. Si la
                eliminas, quedarán sin imagen.
            </p>
            <ul class="mt-4 divide-y divide-charcoal/8 rounded-xl border border-charcoal/8">
                <li v-for="(use, i) in conflict?.usage ?? []" :key="i" class="flex items-center justify-between gap-3 px-3.5 py-2 text-sm">
                    <span class="text-charcoal/60">{{ use.type }}</span>
                    <span class="font-sans font-bold">{{ use.label }}</span>
                </li>
            </ul>
            <p class="mt-4 text-xs text-charcoal/60">
                Si lo que quieres es cambiar la imagen, usa «Reemplazar el archivo»: conserva todas las referencias.
            </p>
            <template #footer>
                <button type="button" class="ad-btn ad-btn-ghost" @click="conflict = null">Cancelar</button>
                <button type="button" class="ad-btn ad-btn-danger" @click="remove(true)">Eliminar de todos modos</button>
            </template>
        </AdminModal>
    </div>
</template>
