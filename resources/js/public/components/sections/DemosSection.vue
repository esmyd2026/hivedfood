<script setup>
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import SectionShell from '../ui/SectionShell.vue';
import BaseButton from '../ui/BaseButton.vue';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();
const { demonstrations } = storeToRefs(store);
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible }">
            <div class="grid gap-7 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="(demo, i) in demonstrations"
                    :key="demo.id"
                    :class="[
                        'hf-reveal group relative flex flex-col overflow-hidden rounded-[1.5rem] border border-charcoal/8 bg-white shadow-[var(--shadow-soft)] transition-all duration-500 hover:-translate-y-2 hover:shadow-[var(--shadow-lift)]',
                        visible && 'hf-reveal-in',
                    ]"
                    :style="{ transitionDelay: `${i * 90}ms` }"
                >
                    <div class="relative aspect-[16/11] overflow-hidden bg-ivory-dark">
                        <img
                            v-if="demo.cover_image"
                            :src="demo.cover_image"
                            :alt="`Tienda digital de ${demo.business_name}`"
                            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.05]"
                            width="800"
                            height="550"
                            loading="lazy"
                            decoding="async"
                        />
                        <div
                            v-else
                            class="grid h-full place-items-center text-charcoal/25"
                            :style="{ backgroundColor: demo.accent_color ? `${demo.accent_color}1a` : undefined }"
                        >
                            <IconGlyph name="storefront" :size="44" />
                        </div>

                        <span
                            v-if="demo.category"
                            class="absolute left-4 top-4 rounded-full bg-charcoal/85 px-3 py-1.5 font-sans text-[0.68rem] font-bold uppercase tracking-wider text-ivory backdrop-blur"
                        >
                            {{ demo.category }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="demo.logo"
                                :src="demo.logo"
                                :alt="`Logotipo de ${demo.business_name}`"
                                class="h-9 w-9 rounded-lg object-contain"
                                width="36"
                                height="36"
                                loading="lazy"
                            />
                            <span
                                v-else
                                class="grid h-9 w-9 place-items-center rounded-lg font-sans text-sm font-extrabold text-white"
                                :style="{ backgroundColor: demo.accent_color || '#202A24' }"
                            >
                                {{ demo.business_name.charAt(0) }}
                            </span>
                            <h3 class="text-lg">{{ demo.business_name }}</h3>
                        </div>

                        <p class="mt-3 flex-1 text-sm leading-relaxed opacity-70">{{ demo.description }}</p>

                        <ul v-if="demo.highlights?.length" class="mt-4 flex flex-wrap gap-1.5">
                            <li
                                v-for="highlight in demo.highlights"
                                :key="highlight"
                                class="rounded-full bg-ivory px-2.5 py-1 text-[0.72rem] font-medium opacity-75"
                            >
                                {{ highlight }}
                            </li>
                        </ul>

                        <div class="mt-6">
                            <!-- Sin URL cargada, el botón abre la solicitud de demo en lugar de un enlace muerto. -->
                            <BaseButton
                                v-if="demo.demo_url"
                                :href="demo.demo_url"
                                variant="dark"
                                trailing-icon="external"
                                block
                            >
                                {{ store.text('demo_link_label', 'Ver tienda') }}
                            </BaseButton>
                            <BaseButton v-else href="#contacto" variant="ghost" trailing-icon="arrow-right" block>
                                {{ store.text('demo_request_label', 'Solicitar acceso a la demo') }}
                            </BaseButton>
                        </div>
                    </div>
                </article>
            </div>

            <p v-if="!demonstrations.length" class="text-sm opacity-60">
                Aún no hay demostraciones publicadas.
            </p>
        </template>
    </SectionShell>
</template>
