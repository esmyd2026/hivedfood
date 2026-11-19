<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useUiStore } from './stores/ui';
import AdminIcon from './components/AdminIcon.vue';
import ToastStack from './components/ToastStack.vue';

const ui = useUiStore();
const route = useRoute();

const user = window.__ADMIN__?.user ?? { name: '', email: '' };
const logo = '/images/brand/hivedfood-logo-light.svg';
const siteUrl = window.__ADMIN__?.siteUrl ?? '/';

const NAV = [
    { group: 'General', items: [{ name: 'dashboard', label: 'Dashboard', icon: 'dashboard' }] },
    {
        group: 'Contenido del sitio',
        items: [
            { name: 'media', label: 'Biblioteca de imágenes', icon: 'image' },
            { name: 'banners', label: 'Banners', icon: 'layers' },
            { name: 'content', label: 'Secciones y módulos', icon: 'dashboard' },
            { name: 'features', label: 'Funcionalidades', icon: 'spark' },
            { name: 'demos', label: 'Demostraciones', icon: 'storefront' },
            { name: 'testimonials', label: 'Testimonios', icon: 'quote' },
            { name: 'faqs', label: 'Preguntas frecuentes', icon: 'help' },
        ],
    },
    {
        group: 'Comercial',
        items: [
            { name: 'quoter', label: 'Cotizador y precios', icon: 'coins' },
            { name: 'quotations', label: 'Cotizaciones', icon: 'receipt' },
            { name: 'leads', label: 'Leads', icon: 'users' },
        ],
    },
    {
        group: 'Sitio',
        items: [
            { name: 'seo', label: 'SEO', icon: 'search' },
            { name: 'settings', label: 'Configuración', icon: 'sliders' },
        ],
    },
];

const title = computed(() => route.meta.title ?? 'Panel');

function logout() {
    document.getElementById('logout-form')?.submit();
}
</script>

<template>
    <div class="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
        <!-- Barra lateral -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-40 w-[17rem] overflow-y-auto bg-charcoal text-ivory transition-transform duration-300 lg:static lg:translate-x-0',
                ui.sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <div class="flex items-center justify-between px-5 py-5">
                <img :src="logo" alt="HIVEDFOOD" class="h-8 w-auto" width="260" height="52" />
                <button
                    type="button"
                    class="grid h-9 w-9 place-items-center rounded-lg text-ivory/60 hover:bg-white/10 lg:hidden"
                    aria-label="Cerrar menú"
                    @click="ui.sidebarOpen = false"
                >
                    <AdminIcon name="close" :size="18" />
                </button>
            </div>

            <nav class="px-3 pb-8">
                <div v-for="group in NAV" :key="group.group" class="mb-6">
                    <p class="px-3 pb-2 font-sans text-[0.65rem] font-bold uppercase tracking-[0.12em] text-ivory/35">
                        {{ group.group }}
                    </p>
                    <ul class="space-y-0.5">
                        <li v-for="item in group.items" :key="item.name">
                            <RouterLink
                                :to="{ name: item.name }"
                                :class="[
                                    'flex items-center gap-2.5 rounded-xl px-3 py-2.5 font-sans text-sm font-semibold transition-colors',
                                    route.name === item.name
                                        ? 'bg-honey text-charcoal'
                                        : 'text-ivory/70 hover:bg-white/8 hover:text-ivory',
                                ]"
                                @click="ui.sidebarOpen = false"
                            >
                                <AdminIcon :name="item.icon" :size="17" />
                                {{ item.label }}
                            </RouterLink>
                        </li>
                    </ul>
                </div>

                <div class="mt-8 border-t border-white/10 pt-5">
                    <a
                        :href="siteUrl"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 font-sans text-sm font-semibold text-ivory/70 transition hover:bg-white/8 hover:text-ivory"
                    >
                        <AdminIcon name="external" :size="17" />
                        Ver el sitio
                    </a>
                </div>
            </nav>
        </aside>

        <div
            v-if="ui.sidebarOpen"
            class="fixed inset-0 z-30 bg-charcoal/50 lg:hidden"
            aria-hidden="true"
            @click="ui.sidebarOpen = false"
        />

        <!-- Contenido -->
        <div class="flex min-h-screen flex-col">
            <header class="sticky top-0 z-20 border-b border-charcoal/8 bg-ivory/90 backdrop-blur">
                <div class="flex items-center justify-between gap-4 px-5 py-4 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="grid h-10 w-10 place-items-center rounded-xl border border-charcoal/12 bg-white lg:hidden"
                            aria-label="Abrir menú"
                            @click="ui.sidebarOpen = true"
                        >
                            <AdminIcon name="menu" :size="18" />
                        </button>
                        <h1 class="text-lg md:text-xl">{{ title }}</h1>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <p class="font-sans text-xs font-bold">{{ user.name }}</p>
                            <p class="text-[0.7rem] text-charcoal/50">{{ user.email }}</p>
                        </div>
                        <button type="button" class="ad-btn ad-btn-ghost" @click="logout">
                            <AdminIcon name="logout" :size="15" />
                            <span class="hidden sm:inline">Salir</span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-5 py-7 lg:px-8">
                <RouterView v-slot="{ Component }">
                    <Transition name="page" mode="out-in">
                        <component :is="Component" />
                    </Transition>
                </RouterView>
            </main>
        </div>

        <ToastStack />
    </div>
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}

.page-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.page-leave-to {
    opacity: 0;
}
</style>
