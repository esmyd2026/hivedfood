<script setup>
import { computed, ref } from 'vue';
import AdminIcon from './AdminIcon.vue';
import MediaPicker from './MediaPicker.vue';

const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [String, Number, Boolean, Array, Object, File, null], default: null },
    error: { type: String, default: null },
    /** Existing stored value, used to preview already uploaded images. */
    preview: { type: String, default: null },
    removed: { type: Boolean, default: false },
    /** Path chosen from the media library for this field. */
    pathValue: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'update:pathValue', 'remove-file']);

const localPreview = ref(null);
const pickerOpen = ref(false);
const libraryPreview = ref(null);
const libraryName = ref(null);

const id = computed(() => `field-${props.field.key}`);

function update(value) {
    emit('update:modelValue', value);
}

function onFile(event) {
    const file = event.target.files?.[0] ?? null;
    update(file);
    localPreview.value = file ? URL.createObjectURL(file) : null;

    // A direct upload wins over a previously picked library image.
    if (file) {
        clearLibraryChoice();
    }
}

function clearFile() {
    update(null);
    localPreview.value = null;
    clearLibraryChoice();
    emit('remove-file');
}

function clearLibraryChoice() {
    libraryPreview.value = null;
    libraryName.value = null;
    emit('update:pathValue', null);
}

/** Reuse an image that already exists in the library. */
function pickFromLibrary(media) {
    update(null);
    localPreview.value = null;
    libraryPreview.value = media.url;
    libraryName.value = media.name;
    emit('update:pathValue', media.path);
}

const imagePreview = computed(() => {
    if (localPreview.value) {
        return localPreview.value;
    }

    if (libraryPreview.value) {
        return libraryPreview.value;
    }

    return props.removed ? null : props.preview;
});

/* ---------------------------------------------------------------- tags --- */
const tagDraft = ref('');

const tags = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

function addTag() {
    const value = tagDraft.value.trim();

    if (!value) {
        return;
    }

    update([...tags.value, value]);
    tagDraft.value = '';
}

function removeTag(index) {
    update(tags.value.filter((_, i) => i !== index));
}

/* ------------------------------------------------------------- buttons --- */
const rows = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

function addRow() {
    update([...rows.value, { label: '', url: '', style: 'primary' }]);
}

function updateRow(index, key, value) {
    update(rows.value.map((row, i) => (i === index ? { ...row, [key]: value } : row)));
}

function removeRow(index) {
    update(rows.value.filter((_, i) => i !== index));
}

/* ---------------------------------------------------------------- json --- */
const jsonText = ref(null);
const jsonError = ref(null);

const jsonValue = computed(() =>
    jsonText.value !== null ? jsonText.value : JSON.stringify(props.modelValue ?? {}, null, 2),
);

function onJson(event) {
    jsonText.value = event.target.value;

    if (!event.target.value.trim()) {
        jsonError.value = null;
        update(null);

        return;
    }

    try {
        update(JSON.parse(event.target.value));
        jsonError.value = null;
    } catch (e) {
        jsonError.value = 'JSON inválido: '.concat(e.message);
    }
}
</script>

<template>
    <div :class="field.span === 2 ? 'sm:col-span-2' : ''">
        <!-- Booleano -->
        <template v-if="field.type === 'boolean'">
            <label class="flex cursor-pointer items-start gap-2.5">
                <input
                    :id="id"
                    type="checkbox"
                    :checked="!!modelValue"
                    class="mt-0.5 h-4 w-4 accent-[#E96D42]"
                    @change="update($event.target.checked)"
                />
                <span>
                    <span class="font-sans text-sm font-bold">{{ field.label }}</span>
                    <span v-if="field.hint" class="ad-hint !mt-0.5 block">{{ field.hint }}</span>
                </span>
            </label>
        </template>

        <template v-else>
            <label :for="id" class="ad-label">
                {{ field.label }}
                <span v-if="field.required" class="text-coral">*</span>
            </label>

            <!-- Texto / número / fecha / color -->
            <input
                v-if="['text', 'email', 'url', 'number', 'date', 'datetime-local', 'color', 'tel'].includes(field.type)"
                :id="id"
                :type="field.type"
                :value="modelValue ?? ''"
                :placeholder="field.placeholder"
                :min="field.min"
                :max="field.max"
                :step="field.step"
                :maxlength="field.maxlength"
                :class="['ad-field', field.type === 'color' && 'h-10 p-1']"
                @input="update(field.type === 'number' ? ($event.target.value === '' ? null : Number($event.target.value)) : $event.target.value)"
            />

            <!-- Área de texto -->
            <textarea
                v-else-if="field.type === 'textarea'"
                :id="id"
                :value="modelValue ?? ''"
                :rows="field.rows ?? 3"
                :placeholder="field.placeholder"
                :maxlength="field.maxlength"
                class="ad-field resize-y"
                @input="update($event.target.value)"
            />

            <!-- Selección -->
            <select
                v-else-if="field.type === 'select'"
                :id="id"
                :value="modelValue ?? ''"
                class="ad-field"
                @change="update($event.target.value === '' ? null : $event.target.value)"
            >
                <option v-if="!field.required" value="">{{ field.placeholder ?? '— Sin asignar —' }}</option>
                <option v-for="option in field.options" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>

            <!-- Imagen -->
            <div v-else-if="field.type === 'image'" class="flex items-start gap-4">
                <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-xl border border-charcoal/10 bg-ivory">
                    <img v-if="imagePreview" :src="imagePreview" alt="" class="h-full w-full object-contain" />
                    <AdminIcon v-else name="image" :size="22" class="text-charcoal/25" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="ad-btn ad-btn-ghost" @click="pickerOpen = true">
                            <AdminIcon name="image" :size="14" />
                            Elegir de la biblioteca
                        </button>

                        <label class="ad-btn ad-btn-ghost cursor-pointer">
                            <AdminIcon name="plus" :size="14" />
                            Subir archivo
                            <input :id="id" type="file" accept="image/*,.svg" class="hidden" @change="onFile" />
                        </label>

                        <button
                            v-if="imagePreview"
                            type="button"
                            class="font-sans text-xs font-bold text-coral-dark hover:underline"
                            @click="clearFile"
                        >
                            Quitar
                        </button>
                    </div>

                    <p v-if="libraryName" class="ad-hint">
                        De la biblioteca: <strong class="font-sans">{{ libraryName }}</strong>
                    </p>
                    <p v-else-if="field.hint" class="ad-hint">{{ field.hint }}</p>
                    <p v-if="removed" class="ad-hint text-coral-dark">Se quitará al guardar.</p>
                </div>

                <MediaPicker
                    :open="pickerOpen"
                    :folder="field.folder ?? ''"
                    @close="pickerOpen = false"
                    @select="pickFromLibrary"
                />
            </div>

            <!-- Lista de textos -->
            <div v-else-if="field.type === 'tags'">
                <ul v-if="tags.length" class="mb-2 flex flex-wrap gap-1.5">
                    <li
                        v-for="(tag, i) in tags"
                        :key="`${tag}-${i}`"
                        class="flex items-center gap-1.5 rounded-full bg-ivory-dark px-2.5 py-1 text-xs"
                    >
                        {{ tag }}
                        <button type="button" class="text-charcoal/40 hover:text-coral" :aria-label="`Quitar ${tag}`" @click="removeTag(i)">
                            <AdminIcon name="close" :size="12" />
                        </button>
                    </li>
                </ul>

                <div class="flex gap-2">
                    <input
                        :id="id"
                        v-model="tagDraft"
                        type="text"
                        class="ad-field"
                        :placeholder="field.placeholder ?? 'Escribe y pulsa Añadir'"
                        @keydown.enter.prevent="addTag"
                    />
                    <button type="button" class="ad-btn ad-btn-ghost shrink-0" @click="addTag">Añadir</button>
                </div>
                <p v-if="field.hint" class="ad-hint">{{ field.hint }}</p>
            </div>

            <!-- Botones / enlaces -->
            <div v-else-if="field.type === 'buttons'" class="space-y-2">
                <div
                    v-for="(row, i) in rows"
                    :key="i"
                    class="grid gap-2 rounded-xl border border-charcoal/10 bg-ivory/50 p-3 sm:grid-cols-[1fr_1fr_auto_auto]"
                >
                    <input
                        type="text"
                        class="ad-field"
                        placeholder="Texto"
                        :value="row.label ?? ''"
                        @input="updateRow(i, 'label', $event.target.value)"
                    />
                    <input
                        type="text"
                        class="ad-field"
                        placeholder="#seccion o https://"
                        :value="row.url ?? ''"
                        @input="updateRow(i, 'url', $event.target.value)"
                    />
                    <select class="ad-field" :value="row.style ?? 'primary'" @change="updateRow(i, 'style', $event.target.value)">
                        <option value="primary">Principal</option>
                        <option value="ghost">Secundario</option>
                        <option value="dark">Oscuro</option>
                    </select>
                    <button type="button" class="ad-btn ad-btn-danger" :aria-label="`Quitar botón ${i + 1}`" @click="removeRow(i)">
                        <AdminIcon name="trash" :size="14" />
                    </button>
                </div>

                <button type="button" class="ad-btn ad-btn-ghost" @click="addRow">
                    <AdminIcon name="plus" :size="14" />
                    Añadir botón
                </button>
            </div>

            <!-- JSON libre -->
            <div v-else-if="field.type === 'json'">
                <textarea
                    :id="id"
                    :value="jsonValue"
                    :rows="field.rows ?? 5"
                    class="ad-field resize-y font-mono text-xs"
                    spellcheck="false"
                    @input="onJson"
                />
                <p v-if="jsonError" class="ad-hint text-coral-dark">{{ jsonError }}</p>
                <p v-else-if="field.hint" class="ad-hint">{{ field.hint }}</p>
            </div>

            <p v-if="field.hint && !['image', 'tags', 'json', 'boolean'].includes(field.type)" class="ad-hint">
                {{ field.hint }}
            </p>
        </template>

        <p v-if="error" class="mt-1 font-sans text-xs font-bold text-coral-dark">{{ error }}</p>
    </div>
</template>
