<script setup>
import { computed } from 'vue';
import { useSiteStore } from '../../stores/site';
import SectionShell from '../ui/SectionShell.vue';
import BaseButton from '../ui/BaseButton.vue';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();

/** Accent colours of the illustration follow the configured palette. */
const tones = computed(() => {
    const palette = store.settings.palette ?? {};

    return [palette.honey ?? '#F4B83F', palette.coral ?? '#E96D42', palette.ivory ?? '#F7F5EF'];
});
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible }">
            <div class="grid gap-10 lg:grid-cols-[1fr_1.1fr] lg:items-start lg:gap-16">
                <!-- Columna visual: composición de marca -->
                <div
                    :class="['hf-reveal relative', visible && 'hf-reveal-in']"
                    style="transition-delay: 60ms"
                    aria-hidden="true"
                >
                    <div class="relative overflow-hidden rounded-[1.75rem] bg-charcoal p-8 text-ivory">
                        <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-honey/20 blur-3xl" />
                        <div class="relative">
                            <p class="font-sans text-xs font-bold uppercase tracking-[0.2em] text-honey">
                                {{ store.text('about_showcase_title', 'Una plataforma, muchas tiendas') }}
                            </p>

                            <div class="mt-7 space-y-3">
                                <div
                                    v-for="(tone, i) in tones"
                                    :key="i"
                                    class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-4"
                                >
                                    <span
                                        class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-charcoal"
                                        :style="{ backgroundColor: tone }"
                                    >
                                        <IconGlyph name="storefront" :size="20" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="h-2.5 w-2/3 rounded-full bg-white/25" />
                                        <div class="mt-2 h-2 w-1/3 rounded-full bg-white/12" />
                                    </div>
                                    <span
                                        v-if="store.text('about_showcase_badge', 'Activa')"
                                        class="rounded-full border border-white/15 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wider text-ivory/60"
                                    >
                                        {{ store.text('about_showcase_badge', 'Activa') }}
                                    </span>
                                </div>
                            </div>

                            <p v-if="store.text('about_showcase_note')" class="mt-7 text-sm text-ivory/55">
                                {{ store.text('about_showcase_note') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Módulos administrables -->
                <div class="grid gap-x-10 gap-y-9 sm:grid-cols-2">
                    <article
                        v-for="(module, i) in section.modules"
                        :key="module.id"
                        :class="['hf-reveal', visible && 'hf-reveal-in']"
                        :style="{ transitionDelay: `${120 + i * 90}ms` }"
                    >
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-honey-soft text-charcoal">
                            <IconGlyph :name="module.icon || 'spark'" :size="22" />
                        </span>
                        <h3 class="mt-5 text-lg">{{ module.title }}</h3>
                        <p class="mt-2.5 text-sm leading-relaxed opacity-70">{{ module.description }}</p>
                    </article>
                </div>
            </div>

            <div v-if="section.buttons?.length" class="mt-14 flex flex-wrap gap-3">
                <BaseButton
                    v-for="button in section.buttons"
                    :key="button.url"
                    :href="button.url"
                    :variant="button.style === 'ghost' ? 'ghost' : 'dark'"
                    :trailing-icon="button.style === 'ghost' ? null : 'arrow-right'"
                >
                    {{ button.label }}
                </BaseButton>
            </div>
        </template>
    </SectionShell>
</template>
