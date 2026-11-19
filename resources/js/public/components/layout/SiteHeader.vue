<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import BaseButton from '../ui/BaseButton.vue';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    section: { type: Object, default: () => ({}) },
});

const store = useSiteStore();
const { settings } = storeToRefs(store);

const scrolled = ref(false);
const menuOpen = ref(false);
const activeAnchor = ref(null);

const links = computed(() => settings.value.nav ?? []);
const cta = computed(() => store.headerSection?.buttons?.[0] ?? { label: 'Solicitar demo', url: '#contacto' });

function onScroll() {
    scrolled.value = window.scrollY > 24;
    highlight();
}

/** Marks the section currently under the header. */
function highlight() {
    const anchors = links.value
        .map((link) => link.url)
        .filter((url) => typeof url === 'string' && url.startsWith('#'));

    let current = null;

    anchors.forEach((anchor) => {
        const node = document.getElementById(anchor.slice(1));

        if (node && node.getBoundingClientRect().top <= 140) {
            current = anchor;
        }
    });

    activeAnchor.value = current;
}

function close() {
    menuOpen.value = false;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    document.body.style.removeProperty('overflow');
});

watch(menuOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});
</script>

<template>
    <header class="fixed inset-x-0 top-0 z-50">
        <div class="hf-shell pt-3 md:pt-4">
            <!-- Barra flotante: translúcida y con blur discreto al desplazar -->
            <div
                :class="[
                    'flex items-center justify-between gap-4 rounded-2xl px-4 py-3 transition-all duration-300 md:px-5',
                    scrolled || menuOpen
                        ? 'border border-white/12 bg-charcoal/80 shadow-[0_18px_40px_-24px_rgba(0,0,0,0.75)] backdrop-blur-xl'
                        : 'border border-transparent bg-transparent',
                ]"
            >
                <a href="/" class="flex shrink-0 items-center gap-3 text-ivory" aria-label="Inicio HIVEDFOOD">
                    <img
                        v-if="settings.logo_light || settings.logo"
                        :src="settings.logo_light || settings.logo"
                        :alt="settings.site_name || 'HIVEDFOOD'"
                        class="h-8 w-auto md:h-9"
                        width="180"
                        height="36"
                    />
                    <span v-else class="font-sans text-lg font-extrabold tracking-tight">
                        {{ settings.site_name || 'HIVEDFOOD' }}
                    </span>
                </a>

                <!-- Navegación de escritorio -->
                <nav class="hidden items-center gap-1 lg:flex" aria-label="Navegación principal">
                    <a
                        v-for="link in links"
                        :key="link.url"
                        :href="link.url"
                        :aria-current="activeAnchor === link.url ? 'true' : null"
                        :class="[
                            'rounded-full px-3.5 py-2 font-sans text-sm font-semibold transition-colors',
                            activeAnchor === link.url
                                ? 'bg-white/10 text-ivory'
                                : 'text-ivory/65 hover:bg-white/[0.06] hover:text-ivory',
                        ]"
                    >
                        {{ link.label }}
                    </a>
                </nav>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        v-if="settings.whatsapp_url"
                        :href="settings.whatsapp_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden h-10 w-10 place-items-center rounded-full border border-white/12 text-ivory/75 transition hover:border-honey hover:text-honey md:grid"
                        aria-label="Escríbenos por WhatsApp"
                    >
                        <IconGlyph name="whatsapp" :size="17" />
                    </a>

                    <!-- El acceso a la demo queda visible también en móvil -->
                    <BaseButton :href="cta.url" variant="primary" class="!px-4 !py-2.5 !text-[0.8rem] md:!px-6 md:!py-3 md:!text-[0.9375rem]">
                        {{ cta.label }}
                    </BaseButton>

                    <button
                        type="button"
                        class="grid h-10 w-10 place-items-center rounded-full border border-white/12 text-ivory transition hover:border-white/30 lg:hidden"
                        :aria-expanded="menuOpen"
                        aria-controls="menu-movil"
                        :aria-label="menuOpen ? 'Cerrar menú' : 'Abrir menú'"
                        @click="menuOpen = !menuOpen"
                    >
                        <IconGlyph :name="menuOpen ? 'close' : 'menu'" :size="19" />
                    </button>
                </div>
            </div>

            <!-- Menú móvil -->
            <Transition name="drawer">
                <nav
                    v-if="menuOpen"
                    id="menu-movil"
                    class="mt-2 overflow-hidden rounded-2xl border border-white/12 bg-charcoal/95 p-2 backdrop-blur-xl lg:hidden"
                    aria-label="Navegación móvil"
                >
                    <a
                        v-for="link in links"
                        :key="link.url"
                        :href="link.url"
                        class="flex items-center justify-between rounded-xl px-4 py-3.5 font-sans text-base font-semibold text-ivory/85 transition hover:bg-white/[0.06] hover:text-ivory"
                        @click="close"
                    >
                        {{ link.label }}
                        <IconGlyph name="chevron-right" :size="16" class="text-ivory/30" />
                    </a>

                    <a
                        v-if="settings.whatsapp_url"
                        :href="settings.whatsapp_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-1 flex items-center gap-2.5 rounded-xl px-4 py-3.5 font-sans text-base font-semibold text-honey transition hover:bg-white/[0.06]"
                    >
                        <IconGlyph name="whatsapp" :size="18" />
                        WhatsApp
                    </a>
                </nav>
            </Transition>
        </div>
    </header>
</template>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
    transition:
        opacity 0.25s ease,
        transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.drawer-enter-from,
.drawer-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
