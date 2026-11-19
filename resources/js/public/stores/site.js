import { defineStore } from 'pinia';
import http from '../../lib/http';

const EMPTY = {
    settings: {},
    sections: [],
    banners: [],
    features: [],
    demonstrations: [],
    testimonials: [],
    faqs: [],
    quoter: {
        pricing_published: false,
        currency: 'USD',
        currency_symbol: '$',
        unpriced_message: '',
        projection_months: 12,
        business_types: [],
        packages: [],
        modules: [],
        billing_models: [],
        scalability: [],
        module_categories: {},
        external_costs: [],
    },
};

export const useSiteStore = defineStore('site', {
    state: () => ({
        ready: false,
        loading: false,
        routes: {},
        ...structuredClone(EMPTY),
    }),

    getters: {
        /** Sections rendered in the main flow (header/hero/footer are placed by the layout). */
        flowSections: (state) =>
            state.sections.filter((section) => !['header', 'hero', 'footer'].includes(section.component)),

        headerSection: (state) => state.sections.find((section) => section.component === 'header') ?? null,
        heroSection: (state) => state.sections.find((section) => section.component === 'hero') ?? null,
        footerSection: (state) => state.sections.find((section) => section.component === 'footer') ?? null,

        hasHero: (state) => state.banners.length > 0,

        /**
         * An administrable interface label.
         *
         * Every visible string lives in the "textos" settings group; the
         * fallback keeps the site readable if a key is blank or missing.
         */
        text: (state) => (key, fallback = '') => {
            const value = state.settings.texts?.[key];

            return value === undefined || value === null || value === '' ? fallback : value;
        },

        /** Palettes used by the storefront preview, configurable from the panel. */
        previewPalettes: (state) => {
            const palettes = state.settings.preview_palettes;

            return Array.isArray(palettes) && palettes.length
                ? palettes
                : [{ name: 'Miel', bg: '#F7F5EF', accent: '#F4B83F', ink: '#202A24' }];
        },

        sectionByComponent: (state) => (component) =>
            state.sections.find((section) => section.component === component) ?? null,

        /** Format a number using the currency configured in Laravel. */
        money: (state) => (value) => {
            if (value === null || value === undefined) {
                return null;
            }

            const symbol = state.quoter.currency_symbol || '$';
            const amount = Number(value).toLocaleString('es', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            return `${symbol}${amount}`;
        },
    },

    actions: {
        hydrate(payload, routes = {}) {
            this.routes = routes;

            if (payload) {
                this.apply(payload);
                this.ready = true;

                return;
            }

            this.fetch();
        },

        apply(payload) {
            this.settings = payload.settings ?? {};
            this.sections = payload.sections ?? [];
            this.banners = payload.banners ?? [];
            this.features = payload.features ?? [];
            this.demonstrations = payload.demonstrations ?? [];
            this.testimonials = payload.testimonials ?? [];
            this.faqs = payload.faqs ?? [];
            this.quoter = { ...EMPTY.quoter, ...(payload.quoter ?? {}) };
        },

        async fetch() {
            if (this.loading) {
                return;
            }

            this.loading = true;

            try {
                const { data } = await http.get(this.routes.content ?? '/api/content');
                this.apply(data.data);
                this.ready = true;
            } catch {
                this.ready = true;
            } finally {
                this.loading = false;
            }
        },
    },
});
