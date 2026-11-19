<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api, parseError } from '../lib/api';
import { useUiStore } from '../stores/ui';
import AdminIcon from '../components/AdminIcon.vue';
import MediaPicker from '../components/MediaPicker.vue';

const ui = useUiStore();

/** Key whose image is being chosen from the library, if any. */
const picking = ref(null);

const GROUPS = {
    general: { label: 'General', icon: 'sliders' },
    brand: { label: 'Identidad visual', icon: 'image' },
    contact: { label: 'Contacto', icon: 'mail' },
    social: { label: 'Redes sociales', icon: 'globe' },
    pricing: { label: 'Cotizador y precios', icon: 'coins' },
    navigation: { label: 'Navegación', icon: 'layers' },
    textos: { label: 'Textos del sitio', icon: 'quote' },
};

const groups = ref({});
const values = reactive({});
const files = reactive({});
const previews = reactive({});
const loading = ref(true);
const saving = ref(false);

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get('configuracion');
        groups.value = data;

        Object.values(data)
            .flat()
            .forEach((setting) => {
                values[setting.key] = setting.value ?? '';
                previews[setting.key] = setting.preview;
            });
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        loading.value = false;
    }
}

onMounted(load);

function onFile(key, event) {
    const file = event.target.files?.[0] ?? null;
    files[key] = file;
    previews[key] = file ? URL.createObjectURL(file) : previews[key];
}

function pickFromLibrary(media) {
    if (!picking.value) {
        return;
    }

    values[picking.value] = media.path;
    previews[picking.value] = media.url;
    delete files[picking.value];
    picking.value = null;
}

async function save() {
    saving.value = true;

    try {
        const payload = { values: { ...values } };

        Object.entries(files).forEach(([key, file]) => {
            if (file) {
                payload[`file__${key}`] = file;
            }
        });

        const data = await api.post('configuracion', payload);
        ui.success(data.message ?? 'Configuración guardada.');
        Object.keys(files).forEach((key) => delete files[key]);
        await load();
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        saving.value = false;
    }
}

/** JSON settings are edited as raw text and validated before saving. */
const jsonErrors = reactive({});

function onJson(key, event) {
    values[key] = event.target.value;

    try {
        JSON.parse(event.target.value || '[]');
        delete jsonErrors[key];
    } catch (e) {
        jsonErrors[key] = e.message;
    }
}

function pretty(value) {
    try {
        return JSON.stringify(JSON.parse(value || '[]'), null, 2);
    } catch {
        return value;
    }
}
</script>

<template>
    <div>
        <p v-if="loading" class="text-sm text-charcoal/55">Cargando configuración…</p>

        <form v-else class="space-y-6" @submit.prevent="save">
            <section v-for="(items, group) in groups" :key="group" class="ad-card p-6">
                <div class="mb-5 flex items-center gap-2.5 border-b border-charcoal/8 pb-4">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-ivory text-charcoal">
                        <AdminIcon :name="GROUPS[group]?.icon ?? 'sliders'" :size="17" />
                    </span>
                    <h2 class="text-base">{{ GROUPS[group]?.label ?? group }}</h2>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div
                        v-for="setting in items"
                        :key="setting.key"
                        :class="['text', 'textarea', 'json'].includes(setting.type) ? 'sm:col-span-2' : ''"
                    >
                        <!-- Booleano -->
                        <label v-if="setting.type === 'boolean'" class="flex cursor-pointer items-start gap-2.5">
                            <input
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 accent-[#E96D42]"
                                :checked="values[setting.key] === '1' || values[setting.key] === true"
                                @change="values[setting.key] = $event.target.checked ? '1' : '0'"
                            />
                            <span>
                                <span class="font-sans text-sm font-bold">{{ setting.label ?? setting.key }}</span>
                                <span class="ad-hint !mt-0.5 block">{{ setting.key }}</span>
                            </span>
                        </label>

                        <template v-else>
                            <label class="ad-label" :for="`s-${setting.key}`">{{ setting.label ?? setting.key }}</label>

                            <!-- Imagen -->
                            <div v-if="setting.type === 'image'" class="flex items-start gap-3">
                                <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl border border-charcoal/10 bg-ivory">
                                    <img v-if="previews[setting.key]" :src="previews[setting.key]" alt="" class="h-full w-full object-contain" />
                                    <AdminIcon v-else name="image" :size="20" class="text-charcoal/25" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="ad-btn ad-btn-ghost" @click="picking = setting.key">
                                            <AdminIcon name="image" :size="14" />
                                            Biblioteca
                                        </button>
                                        <label class="ad-btn ad-btn-ghost cursor-pointer">
                                            <AdminIcon name="plus" :size="14" />
                                            Subir
                                            <input
                                                :id="`s-${setting.key}`"
                                                type="file"
                                                accept="image/*,.svg"
                                                class="hidden"
                                                @change="onFile(setting.key, $event)"
                                            />
                                        </label>
                                    </div>
                                    <input
                                        v-model="values[setting.key]"
                                        type="text"
                                        class="ad-field mt-2 text-xs"
                                        placeholder="o una ruta/URL: /images/brand/logo.svg"
                                    />
                                </div>
                            </div>

                            <!-- Color -->
                            <div v-else-if="setting.type === 'color'" class="flex items-center gap-2">
                                <input :id="`s-${setting.key}`" v-model="values[setting.key]" type="color" class="ad-field h-10 w-16 p-1" />
                                <input v-model="values[setting.key]" type="text" class="ad-field font-mono text-xs" />
                            </div>

                            <!-- JSON -->
                            <div v-else-if="setting.type === 'json'">
                                <textarea
                                    :id="`s-${setting.key}`"
                                    :value="pretty(values[setting.key])"
                                    rows="8"
                                    class="ad-field resize-y font-mono text-xs"
                                    spellcheck="false"
                                    @input="onJson(setting.key, $event)"
                                />
                                <p v-if="jsonErrors[setting.key]" class="ad-hint text-coral-dark">JSON inválido: {{ jsonErrors[setting.key] }}</p>
                            </div>

                            <!-- Texto largo -->
                            <textarea
                                v-else-if="setting.type === 'text' || setting.type === 'textarea'"
                                :id="`s-${setting.key}`"
                                v-model="values[setting.key]"
                                rows="3"
                                class="ad-field resize-y"
                            />

                            <!-- Texto simple -->
                            <input v-else :id="`s-${setting.key}`" v-model="values[setting.key]" type="text" class="ad-field" />

                            <p class="ad-hint font-mono">{{ setting.key }}</p>
                        </template>
                    </div>
                </div>
            </section>

            <MediaPicker :open="!!picking" folder="ajustes" @close="picking = null" @select="pickFromLibrary" />

            <div class="sticky bottom-4 flex justify-end">
                <button type="submit" class="ad-btn ad-btn-honey shadow-lg" :disabled="saving || Object.keys(jsonErrors).length > 0">
                    <AdminIcon name="check" :size="15" />
                    {{ saving ? 'Guardando…' : 'Guardar configuración' }}
                </button>
            </div>
        </form>
    </div>
</template>
