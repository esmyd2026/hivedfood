<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import BaseButton from '../ui/BaseButton.vue';
import IconGlyph from '../ui/IconGlyph.vue';
import DeviceFrame from '../product/DeviceFrame.vue';
import StoreScreen from '../product/StoreScreen.vue';
import OrdersScreen from '../product/OrdersScreen.vue';

const props = defineProps({
    section: { type: Object, default: () => ({}) },
});

const store = useSiteStore();
const { banners } = storeToRefs(store);

const index = ref(0);
const paused = ref(false);
const progress = ref(0);
const direction = ref(1);
const reduceMotion = ref(false);

let timer = null;
let startedAt = 0;
let elapsedBeforePause = 0;

const settings = computed(() => props.section?.settings ?? {});
const autoplay = computed(() => settings.value.autoplay !== false && banners.value.length > 1);
const showIndicators = computed(() => settings.value.show_indicators !== false && banners.value.length > 1);
const showArrows = computed(() => settings.value.show_arrows !== false && banners.value.length > 1);

const current = computed(() => banners.value[index.value] ?? null);
const duration = computed(() => Math.max(3000, current.value?.duration ?? 6000));

/**
 * The hero shows the product itself.
 *
 * A banner's desktop image is the admin panel capture and its mobile image is
 * the storefront capture; when either is missing, a faithful mockup of that
 * screen is rendered in its place.
 */
const panelImage = computed(() => current.value?.image_desktop ?? null);
const phoneImage = computed(() => current.value?.image_mobile ?? null);

/** Floating chips: illustrative product events, editable from the panel. */
const chips = computed(() => {
    const configured = settings.value.chips;

    if (Array.isArray(configured)) {
        return configured.filter((chip) => chip?.label);
    }

    return [
        { icon: 'chat', label: 'Nuevo pedido', detail: 'WhatsApp · #047' },
        { icon: 'receipt', label: 'Comanda impresa', detail: 'Cocina · #045' },
    ];
});

/** Entry channels listed under the call to action. */
const channels = computed(() => {
    const configured = settings.value.channels;

    return Array.isArray(configured) && configured.length
        ? configured
        : ['WhatsApp', 'Tienda en línea', 'Kiosco', 'Mostrador', 'Marketplaces'];
});

const transitionName = computed(() => {
    if (reduceMotion.value) {
        return 'hero-fade';
    }

    const mode = current.value?.transition ?? 'fade';

    if (mode === 'slide') {
        return direction.value > 0 ? 'hero-slide-next' : 'hero-slide-prev';
    }

    return mode === 'zoom' ? 'hero-zoom' : 'hero-fade';
});

function stop() {
    if (timer) {
        cancelAnimationFrame(timer);
        timer = null;
    }
}

function tick() {
    const elapsed = elapsedBeforePause + (performance.now() - startedAt);
    progress.value = Math.min(1, elapsed / duration.value);

    if (progress.value >= 1) {
        go(1);

        return;
    }

    timer = requestAnimationFrame(tick);
}

function start() {
    stop();

    if (!autoplay.value || paused.value || reduceMotion.value) {
        return;
    }

    startedAt = performance.now();
    timer = requestAnimationFrame(tick);
}

function reset() {
    elapsedBeforePause = 0;
    progress.value = 0;
    start();
}

function go(step) {
    if (!banners.value.length) {
        return;
    }

    direction.value = step >= 0 ? 1 : -1;
    index.value = (index.value + step + banners.value.length) % banners.value.length;
    reset();
}

function goTo(target) {
    if (target === index.value) {
        return;
    }

    direction.value = target > index.value ? 1 : -1;
    index.value = target;
    reset();
}

function pause() {
    if (paused.value) {
        return;
    }

    paused.value = true;
    elapsedBeforePause += performance.now() - startedAt;
    stop();
}

function resume() {
    if (!paused.value) {
        return;
    }

    paused.value = false;
    start();
}

const touchStart = ref(null);

function onTouchStart(event) {
    touchStart.value = event.changedTouches[0].clientX;
    pause();
}

function onTouchEnd(event) {
    if (touchStart.value === null) {
        return;
    }

    const delta = event.changedTouches[0].clientX - touchStart.value;

    if (Math.abs(delta) > 48) {
        go(delta < 0 ? 1 : -1);
    }

    touchStart.value = null;
    resume();
}

function onKeydown(event) {
    if (event.key === 'ArrowRight') {
        go(1);
    } else if (event.key === 'ArrowLeft') {
        go(-1);
    }
}

function onVisibility() {
    document.hidden ? pause() : resume();
}

onMounted(() => {
    reduceMotion.value = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
    document.addEventListener('visibilitychange', onVisibility);
    start();
});

onBeforeUnmount(() => {
    stop();
    document.removeEventListener('visibilitychange', onVisibility);
});

watch(() => banners.value.length, reset);
</script>

<template>
    <section
        v-if="current"
        id="inicio"
        class="relative isolate overflow-hidden bg-charcoal text-ivory"
        aria-roledescription="carrusel"
        aria-label="Destacados de HIVEDFOOD"
        @mouseenter="pause"
        @mouseleave="resume"
        @focusin="pause"
        @focusout="resume"
        @keydown="onKeydown"
        @touchstart.passive="onTouchStart"
        @touchend.passive="onTouchEnd"
    >
        <!-- Ambiente -->
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute -left-[12%] top-[-28%] h-[42rem] w-[42rem] rounded-full bg-honey/16 blur-[140px]" />
            <div class="absolute -right-[16%] bottom-[-34%] h-[40rem] w-[40rem] rounded-full bg-coral/16 blur-[140px]" />
            <div class="hf-hero-grid absolute inset-0" />
        </div>

        <div class="hf-shell relative pb-14 pt-28 md:pb-20 md:pt-32 lg:pb-24 lg:pt-36">
            <div class="grid items-center gap-12 lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] lg:gap-14 xl:gap-20">
                <!-- Discurso -->
                <div class="relative">
                    <Transition :name="transitionName" mode="out-in">
                        <div :key="current.id">
                            <!-- Etiqueta de producto -->
                            <p
                                v-if="current.eyebrow"
                                class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.06] py-1.5 pl-2 pr-4 font-sans text-xs font-bold tracking-wide text-ivory/85 backdrop-blur"
                            >
                                <span class="relative grid h-5 w-5 place-items-center">
                                    <span class="absolute inset-0 rounded-full bg-honey/25" />
                                    <span class="hf-pulse h-1.5 w-1.5 rounded-full bg-honey" />
                                </span>
                                {{ current.eyebrow }}
                            </p>

                            <h1 class="mt-6 text-balance text-[2.5rem] leading-[1.02] tracking-[-0.03em] sm:text-[3.25rem] lg:text-[3.75rem] xl:text-[4.25rem]">
                                {{ current.title }}
                            </h1>

                            <p v-if="current.subtitle" class="mt-4 font-sans text-xl font-semibold text-honey-soft">
                                {{ current.subtitle }}
                            </p>

                            <p v-if="current.description" class="mt-5 max-w-xl text-lg leading-relaxed text-ivory/70">
                                {{ current.description }}
                            </p>

                            <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                                <BaseButton
                                    v-if="current.cta_text"
                                    :href="current.cta_url || '#contacto'"
                                    variant="primary"
                                    trailing-icon="arrow-right"
                                    class="hf-cta-lg"
                                >
                                    {{ current.cta_text }}
                                </BaseButton>
                                <BaseButton
                                    v-if="current.cta_secondary_text"
                                    :href="current.cta_secondary_url || '#plataforma'"
                                    variant="ghost"
                                    class="hf-cta-lg"
                                >
                                    {{ current.cta_secondary_text }}
                                </BaseButton>
                            </div>
                        </div>
                    </Transition>

                    <!-- Líneas de entrada: constante entre banners -->
                    <div class="mt-10 border-t border-white/10 pt-6">
                        <p class="font-sans text-[0.68rem] font-bold uppercase tracking-[0.18em] text-ivory/35">
                            Los pedidos entran por
                        </p>
                        <ul class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2">
                            <li
                                v-for="channel in channels"
                                :key="channel"
                                class="flex items-center gap-1.5 font-sans text-sm font-semibold text-ivory/65"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-honey" aria-hidden="true" />
                                {{ channel }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Producto -->
                <div class="relative">
                    <Transition :name="transitionName">
                        <div :key="current.id" class="relative">
                            <!-- Panel administrativo -->
                            <div class="relative lg:pl-10">
                                <DeviceFrame
                                    type="browser"
                                    :image="panelImage"
                                    :alt="current.image_alt || 'Panel de pedidos de HIVEDFOOD'"
                                    label="panel.hivedfood.com/pedidos"
                                >
                                    <OrdersScreen />
                                </DeviceFrame>
                            </div>

                            <!-- Tienda en el móvil, superpuesta -->
                            <div class="absolute -bottom-10 -left-2 w-[42%] max-w-[12.5rem] sm:-bottom-12 sm:w-[38%] lg:left-0 lg:w-[44%] lg:max-w-[14.5rem]">
                                <DeviceFrame
                                    type="phone"
                                    :image="phoneImage"
                                    :alt="current.image_alt || 'Tienda de un restaurante vista en el móvil'"
                                >
                                    <StoreScreen />
                                </DeviceFrame>
                            </div>

                            <!-- Eventos flotantes -->
                            <ul class="pointer-events-none absolute -right-1 top-6 hidden space-y-3 sm:block lg:-right-4">
                                <li
                                    v-for="(chip, i) in chips"
                                    :key="chip.label"
                                    :class="['hf-float flex items-center gap-2.5 rounded-xl border border-white/12 bg-charcoal/90 py-2.5 pl-2.5 pr-4 backdrop-blur', i === 1 && 'hf-float-delayed ml-6']"
                                >
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-honey text-charcoal">
                                        <IconGlyph :name="chip.icon || 'chat'" :size="16" />
                                    </span>
                                    <span>
                                        <span class="block font-sans text-xs font-bold text-ivory">{{ chip.label }}</span>
                                        <span class="block text-[0.68rem] text-ivory/55">{{ chip.detail }}</span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Controles -->
            <div v-if="showIndicators || showArrows" class="mt-20 flex items-center gap-6 sm:mt-16">
                <div v-if="showIndicators" class="flex flex-1 items-center gap-3" role="tablist">
                    <button
                        v-for="(banner, i) in banners"
                        :key="banner.id"
                        type="button"
                        role="tab"
                        :aria-selected="i === index"
                        :aria-label="`Ir al banner ${i + 1}: ${banner.title}`"
                        class="group relative h-1 flex-1 overflow-hidden rounded-full bg-white/12 transition hover:bg-white/22"
                        @click="goTo(i)"
                    >
                        <span
                            class="absolute inset-y-0 left-0 rounded-full bg-honey transition-[width]"
                            :style="{
                                width: i < index ? '100%' : i === index ? `${progress * 100}%` : '0%',
                                transitionDuration: i === index ? '80ms' : '400ms',
                            }"
                        />
                    </button>
                </div>

                <div v-if="showArrows" class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        class="grid h-10 w-10 place-items-center rounded-full border border-white/12 text-ivory/70 transition hover:border-honey hover:text-honey"
                        aria-label="Banner anterior"
                        @click="go(-1)"
                    >
                        <IconGlyph name="arrow-left" :size="17" />
                    </button>
                    <button
                        type="button"
                        class="grid h-10 w-10 place-items-center rounded-full border border-white/12 text-ivory/70 transition hover:border-honey hover:text-honey"
                        aria-label="Siguiente banner"
                        @click="go(1)"
                    >
                        <IconGlyph name="arrow-right" :size="17" />
                    </button>
                </div>
            </div>
        </div>

        <p class="sr-only" aria-live="polite">Banner {{ index + 1 }} de {{ banners.length }}: {{ current.title }}</p>
    </section>
</template>

<style scoped>
.hero-fade-enter-active,
.hero-fade-leave-active,
.hero-zoom-enter-active,
.hero-zoom-leave-active,
.hero-slide-next-enter-active,
.hero-slide-next-leave-active,
.hero-slide-prev-enter-active,
.hero-slide-prev-leave-active {
    transition:
        opacity 0.55s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
}

.hero-fade-enter-from,
.hero-fade-leave-to {
    opacity: 0;
}

.hero-zoom-enter-from {
    opacity: 0;
    transform: scale(1.04);
}

.hero-zoom-leave-to {
    opacity: 0;
    transform: scale(0.98);
}

.hero-slide-next-enter-from {
    opacity: 0;
    transform: translateX(36px);
}

.hero-slide-next-leave-to {
    opacity: 0;
    transform: translateX(-28px);
}

.hero-slide-prev-enter-from {
    opacity: 0;
    transform: translateX(-36px);
}

.hero-slide-prev-leave-to {
    opacity: 0;
    transform: translateX(28px);
}

@media (prefers-reduced-motion: reduce) {
    [class*='hero-'] {
        transition: opacity 0.2s linear !important;
        transform: none !important;
    }
}
</style>
