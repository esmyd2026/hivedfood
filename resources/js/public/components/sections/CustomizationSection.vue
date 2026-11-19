<script setup>
import { computed, ref } from 'vue';
import { useSiteStore } from '../../stores/site';
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();
const active = ref(0);

// Each item previews the storefront with a different brand palette, which is
// exactly what the platform does for every business. The palettes themselves
// are configurable from the panel.
const palettes = computed(() => store.previewPalettes);
const palette = computed(() => palettes.value[active.value % palettes.value.length]);
const modules = computed(() => props.section.modules ?? []);
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible }">
            <div class="grid gap-10 lg:grid-cols-[1fr_1.05fr] lg:items-center lg:gap-16">
                <!-- Lista seleccionable -->
                <ul :class="['hf-reveal space-y-2', visible && 'hf-reveal-in']">
                    <li v-for="(module, i) in modules" :key="module.id">
                        <button
                            type="button"
                            :class="[
                                'w-full rounded-2xl border p-5 text-left transition-all duration-300',
                                active === i
                                    ? 'border-honey/50 bg-white/[0.06]'
                                    : 'border-white/10 hover:border-white/25 hover:bg-white/[0.03]',
                            ]"
                            :aria-pressed="active === i"
                            @click="active = i"
                            @mouseenter="active = i"
                        >
                            <div class="flex items-start gap-4">
                                <span
                                    :class="[
                                        'grid h-10 w-10 shrink-0 place-items-center rounded-xl transition-colors',
                                        active === i ? 'bg-honey text-charcoal' : 'bg-white/8 text-ivory/70',
                                    ]"
                                >
                                    <IconGlyph :name="module.icon || 'palette'" :size="19" />
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-base text-ivory">{{ module.title }}</h3>
                                    <p
                                        :class="[
                                            'overflow-hidden text-sm leading-relaxed text-ivory/60 transition-all duration-300',
                                            active === i ? 'mt-2 max-h-24 opacity-100' : 'max-h-0 opacity-0',
                                        ]"
                                    >
                                        {{ module.description }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    </li>
                </ul>

                <!-- Vista previa de tienda -->
                <div
                    :class="['hf-reveal', visible && 'hf-reveal-in']"
                    style="transition-delay: 120ms"
                    aria-hidden="true"
                >
                    <div class="mx-auto max-w-sm overflow-hidden rounded-[2rem] border-[10px] border-charcoal-700 bg-charcoal-700 shadow-2xl">
                        <div
                            class="transition-colors duration-700"
                            :style="{ backgroundColor: palette.bg, color: palette.ink }"
                        >
                            <!-- Barra de la tienda -->
                            <div class="flex items-center justify-between px-5 pb-4 pt-6">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="grid h-8 w-8 place-items-center rounded-lg transition-colors duration-700"
                                        :style="{ backgroundColor: palette.accent, color: palette.bg }"
                                    >
                                        <IconGlyph name="storefront" :size="16" />
                                    </span>
                                    <span class="font-sans text-sm font-extrabold">
                                        {{ store.text('preview_brand_label', 'Tu marca') }}
                                    </span>
                                </div>
                                <IconGlyph name="menu" :size="18" :style="{ opacity: 0.5 }" />
                            </div>

                            <!-- Banner -->
                            <div
                                class="mx-5 rounded-2xl px-5 py-6 transition-colors duration-700"
                                :style="{ backgroundColor: palette.accent }"
                            >
                                <p class="font-sans text-xs font-bold uppercase tracking-widest" :style="{ color: palette.bg, opacity: 0.8 }">
                                    {{ store.text('preview_banner_eyebrow', 'Promoción') }}
                                </p>
                                <p class="mt-1.5 font-sans text-lg font-extrabold leading-tight" :style="{ color: palette.bg }">
                                    {{ store.text('preview_banner_title', 'Tu banner, tu mensaje') }}
                                </p>
                            </div>

                            <!-- Catálogo -->
                            <div class="grid grid-cols-2 gap-3 p-5">
                                <div
                                    v-for="n in 4"
                                    :key="n"
                                    class="rounded-xl p-3 transition-colors duration-700"
                                    :style="{ backgroundColor: palette.ink, opacity: 0.06 }"
                                >
                                    <div class="aspect-square rounded-lg" :style="{ backgroundColor: palette.ink, opacity: 0.12 }" />
                                </div>
                            </div>

                            <div class="px-5 pb-6">
                                <div
                                    class="rounded-full py-3 text-center font-sans text-sm font-bold transition-colors duration-700"
                                    :style="{ backgroundColor: palette.ink, color: palette.bg }"
                                >
                                    {{ store.text('preview_cta_label', 'Hacer pedido') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="palette.name" class="mt-5 text-center text-xs uppercase tracking-[0.18em] text-ivory/40">
                        Paleta {{ palette.name }}
                    </p>
                </div>
            </div>
        </template>
    </SectionShell>
</template>
