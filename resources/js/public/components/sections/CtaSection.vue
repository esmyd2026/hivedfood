<script setup>
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import { useReveal } from '../../composables/useReveal';
import ContactForm from '../forms/ContactForm.vue';
import IconGlyph from '../ui/IconGlyph.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();
const { settings } = storeToRefs(store);
const { target, visible } = useReveal();

const showForm = computed(() => props.section.settings?.show_form !== false);

/** Editable from Configuración → Textos del sitio. */
const perks = computed(() => {
    const configured = settings.value.texts?.cta_perks;

    return Array.isArray(configured) ? configured.filter((perk) => perk?.label) : [];
});
</script>

<template>
    <section :id="section.key" class="relative overflow-hidden bg-honey text-charcoal">
        <div class="pointer-events-none absolute inset-0 opacity-[0.07]" aria-hidden="true">
            <div class="absolute -left-20 top-10 h-72 w-72 rounded-full border-[3rem] border-charcoal" />
            <div class="absolute -bottom-24 right-10 h-80 w-80 rounded-full border-[3rem] border-charcoal" />
        </div>

        <div ref="target" class="hf-shell relative py-16 md:py-24">
            <div class="grid gap-12 lg:grid-cols-[1fr_1.05fr] lg:items-start lg:gap-16">
                <div :class="['hf-reveal', visible && 'hf-reveal-in']">
                    <p v-if="section.eyebrow" class="hf-eyebrow">{{ section.eyebrow }}</p>
                    <h2 class="mt-5 text-[2rem] md:text-[2.75rem] lg:text-[3rem]">{{ section.title }}</h2>
                    <p v-if="section.description" class="mt-5 max-w-lg text-base opacity-75 md:text-lg">
                        {{ section.description }}
                    </p>

                    <ul v-if="perks.length" class="mt-8 space-y-3">
                        <li v-for="perk in perks" :key="perk.label" class="flex items-center gap-3 font-sans text-sm font-semibold">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-charcoal text-honey">
                                <IconGlyph :name="perk.icon" :size="16" />
                            </span>
                            {{ perk.label }}
                        </li>
                    </ul>

                    <div v-if="settings.email || settings.phone" class="mt-10 border-t border-charcoal/15 pt-6 text-sm">
                        <p class="font-sans font-bold">
                            {{ store.text('cta_direct_title', '¿Prefieres escribirnos directamente?') }}
                        </p>
                        <p class="mt-2 flex flex-wrap gap-x-5 gap-y-1 opacity-80">
                            <a v-if="settings.email" :href="`mailto:${settings.email}`" class="underline-offset-4 hover:underline">
                                {{ settings.email }}
                            </a>
                            <a v-if="settings.phone" :href="`tel:${settings.phone.replace(/\s+/g, '')}`" class="underline-offset-4 hover:underline">
                                {{ settings.phone }}
                            </a>
                        </p>
                    </div>
                </div>

                <div
                    v-if="showForm"
                    :class="['hf-reveal rounded-[1.75rem] bg-white p-6 shadow-[var(--shadow-lift)] md:p-8', visible && 'hf-reveal-in']"
                    style="transition-delay: 120ms"
                >
                    <h3 class="text-xl">{{ store.text('cta_form_title', 'Solicita tu demostración') }}</h3>
                    <p v-if="store.text('cta_form_note')" class="mt-2 text-sm opacity-65">
                        {{ store.text('cta_form_note') }}
                    </p>

                    <div class="mt-6">
                        <ContactForm source="demo_request" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
