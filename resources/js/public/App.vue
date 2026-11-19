<script setup>
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from './stores/site';

import SiteHeader from './components/layout/SiteHeader.vue';
import SiteFooter from './components/layout/SiteFooter.vue';
import WhatsAppFloat from './components/layout/WhatsAppFloat.vue';

import HeroBanners from './components/sections/HeroBanners.vue';
import AboutSection from './components/sections/AboutSection.vue';
import ProductShowcaseSection from './components/sections/ProductShowcaseSection.vue';
import ProblemsSection from './components/sections/ProblemsSection.vue';
import ChannelsSection from './components/sections/ChannelsSection.vue';
import OrderFlowSection from './components/sections/OrderFlowSection.vue';
import BenefitsSection from './components/sections/BenefitsSection.vue';
import FeaturesSection from './components/sections/FeaturesSection.vue';
import CustomizationSection from './components/sections/CustomizationSection.vue';
import DemosSection from './components/sections/DemosSection.vue';
import StepsSection from './components/sections/StepsSection.vue';
import BusinessTypesSection from './components/sections/BusinessTypesSection.vue';
import PricingSection from './components/sections/PricingSection.vue';
import QuoterSection from './components/sections/QuoterSection.vue';
import TestimonialsSection from './components/sections/TestimonialsSection.vue';
import FaqSection from './components/sections/FaqSection.vue';
import CtaSection from './components/sections/CtaSection.vue';
import ModulesSection from './components/sections/ModulesSection.vue';

const store = useSiteStore();
const { sections } = storeToRefs(store);

/**
 * Maps the "component" column of content_sections to a Vue component.
 * Adding a presentation type is a matter of registering it here.
 */
const REGISTRY = {
    about: AboutSection,
    // Pantallas reales del producto en composiciones alternadas.
    showcase: ProductShowcaseSection,
    problems: ProblemsSection,
    // Líneas de entrada de pedidos y su gestión.
    channels: ChannelsSection,
    'order-flow': OrderFlowSection,
    benefits: BenefitsSection,
    features: FeaturesSection,
    customization: CustomizationSection,
    demos: DemosSection,
    steps: StepsSection,
    'business-types': BusinessTypesSection,
    // Metodologías de cobro y tramos por volumen.
    pricing: PricingSection,
    quoter: QuoterSection,
    testimonials: TestimonialsSection,
    faq: FaqSection,
    cta: CtaSection,
    // Free-form section: honours each module's own presentation type.
    modules: ModulesSection,
};

function resolve(section) {
    return REGISTRY[section.component] ?? null;
}

onMounted(() => {
    // Laravel serves /demostraciones and /cotizador with their own meta tags;
    // once mounted the app scrolls to the matching section.
    const target = window.__HIVEDFOOD_TARGET__;

    const anchors = {
        demostraciones: 'demostraciones',
        cotizador: 'cotizador',
    };

    const id = anchors[target] ?? (window.location.hash ? window.location.hash.slice(1) : null);

    if (!id) {
        return;
    }

    requestAnimationFrame(() => {
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>

<template>
    <div class="min-h-screen bg-ivory">
        <a
            href="#contenido"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-honey focus:px-5 focus:py-2.5 focus:font-sans focus:text-sm focus:font-bold focus:text-charcoal"
        >
            Saltar al contenido
        </a>

        <SiteHeader :section="store.headerSection ?? {}" />

        <main id="contenido">
            <HeroBanners v-if="store.heroSection && store.hasHero" :section="store.heroSection" />

            <template v-for="section in store.flowSections" :key="section.id">
                <component :is="resolve(section)" v-if="resolve(section)" :section="section" />
            </template>
        </main>

        <SiteFooter :section="store.footerSection ?? {}" />

        <WhatsAppFloat />
    </div>
</template>
