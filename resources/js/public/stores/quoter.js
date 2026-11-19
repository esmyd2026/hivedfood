import { defineStore } from 'pinia';
import http, { parseError } from '../../lib/http';
import { useSiteStore } from './site';

/** Shared debounce handle for the pricing preview. */
let previewTimer = null;

/**
 * Etapas del configurador.
 *
 * Una decisión por etapa: qué negocio tienes, qué paquete quieres, cómo
 * prefieres pagarlo, qué módulos añades y de qué tamaño es tu operación.
 */
export const STEPS = [
    { key: 'business', label: 'Datos de tu negocio', short: 'Tu negocio' },
    { key: 'package', label: 'Elige tu paquete', short: 'Paquete' },
    { key: 'billing', label: 'Cómo prefieres pagar', short: 'Cómo pagas' },
    { key: 'modules', label: 'Personalización', short: 'Módulos' },
    { key: 'scale', label: 'Tu operación', short: 'Operación' },
    { key: 'result', label: 'Resumen de inversión', short: 'Resumen' },
];

/** Unidades de escalabilidad y el rango que acepta el backend. */
const SCALE_LIMITS = {
    branches: [1, 200],
    brands: [1, 50],
    whatsapp_numbers: [1, 50],
    trainings: [0, 20],
};

export const useQuoterStore = defineStore('quoter', {
    state: () => ({
        step: 0,

        // Etapa 1: los datos de contacto se piden al principio, no al final.
        contact: {
            company: '',
            name: '',
            email: '',
            phone: '',
            city: '',
            accepts_marketing: false,
            website: '',
        },
        businessTypeId: null,
        businessTypeNote: '',

        planId: null,
        billingModelId: null,
        // Pedidos al mes de cada local. Solo cuesta si la modalidad cobra
        // consumo; en el resto de casos es informativo.
        ordersPerBranch: 0,
        // Ids de los módulos contratados aparte del paquete.
        selection: [],
        scalability: {
            branches: 1,
            brands: 1,
            whatsapp_numbers: 1,
            trainings: 0,
        },
        // Dimensionamiento informativo: nunca genera cargos.
        catalog: {
            products: null,
            categories: null,
            distribution: '',
        },
        notes: '',

        calculating: false,
        result: null,
        calcError: null,
        // Mensaje de una combinación bloqueada por dependencias.
        blockedBy: null,

        submitting: false,
        submitted: null,
        submitError: null,
        submitErrors: {},

        emailing: false,
        emailMessage: null,

        // Hoja de resumen desplegada en móvil.
        summaryOpen: false,
        startedAt: Date.now(),
    }),

    getters: {
        site: () => useSiteStore(),

        steps: () => STEPS,

        current: (state) => STEPS[state.step] ?? STEPS[0],

        lastStepIndex: () => STEPS.length - 1,

        businessType(state) {
            return this.site.quoter.business_types.find((type) => type.id === state.businessTypeId) ?? null;
        },

        packages() {
            return this.site.quoter.packages ?? [];
        },

        billingModels() {
            return this.site.quoter.billing_models ?? [];
        },

        /** Modalidad elegida, o la recomendada mientras no se elija ninguna. */
        billingModel(state) {
            const models = this.billingModels;

            return (
                models.find((model) => model.id === state.billingModelId) ??
                models.find((model) => model.is_recommended) ??
                models[0] ??
                null
            );
        },

        /** ¿La modalidad añade un cargo por pedido procesado? */
        chargesUsage() {
            return this.billingModel?.charges_usage === true;
        },

        /** Desglose del consumo que devolvió el backend. */
        usage(state) {
            return state.result?.usage ?? null;
        },

        plan(state) {
            return this.packages.find((item) => item.id === state.planId) ?? null;
        },

        /** Slugs que ya vienen dentro del paquete elegido. */
        includedSlugs() {
            return new Set(this.plan?.included_item_slugs ?? []);
        },

        /** Módulos que se contratan por separado. */
        sellableModules() {
            return (this.site.quoter.modules ?? []).filter((module) => module.is_available);
        },

        /** Módulos agrupados por categoría, en el orden configurado. */
        modulesByCategory() {
            const labels = this.site.quoter.module_categories ?? {};
            const groups = new Map();

            Object.entries(labels).forEach(([key, label]) => groups.set(key, { key, label, modules: [] }));

            this.sellableModules.forEach((module) => {
                const key = module.category || 'otros';

                if (!groups.has(key)) {
                    groups.set(key, { key, label: labels[key] ?? 'Otros módulos', modules: [] });
                }

                groups.get(key).modules.push(module);
            });

            return [...groups.values()].filter((group) => group.modules.length > 0);
        },

        scalabilityItems() {
            return this.site.quoter.scalability ?? [];
        },

        externalCosts() {
            return this.result?.external_costs ?? this.site.quoter.external_costs ?? [];
        },

        isSelected: (state) => (id) => state.selection.includes(id),

        /** Un módulo ya incluido en el paquete no se cobra ni se puede quitar. */
        isIncluded() {
            return (module) => this.includedSlugs.has(module.slug);
        },

        /** Módulos que el motor activó solo, por dependencia. */
        autoAddedSlugs(state) {
            return new Set((state.result?.dependencies?.auto_added ?? []).map((row) => row.slug));
        },

        selectedCount: (state) => state.selection.length,

        /** Payload compartido por el cálculo y el guardado. */
        configuration: (state) => ({
            business_type_id: state.businessTypeId,
            business_type_note: state.businessTypeNote || null,
            plan_id: state.planId,
            billing_model_id: state.billingModelId,
            orders_per_branch: state.ordersPerBranch || 0,
            items: state.selection.map((id) => ({ id })),
            scalability: { ...state.scalability },
            catalog: {
                products: state.catalog.products ?? null,
                categories: state.catalog.categories ?? null,
                distribution: state.catalog.distribution || null,
            },
            notes: state.notes || null,
        }),

        canAdvance(state) {
            const key = this.current?.key;

            if (key === 'business') {
                return (
                    state.contact.name.trim().length > 1 &&
                    /.+@.+\..+/.test(state.contact.email) &&
                    state.businessTypeId !== null
                );
            }

            if (key === 'package') {
                return state.planId !== null;
            }

            return true;
        },
    },

    actions: {
        reset() {
            this.$reset();
            this.startedAt = Date.now();
        },

        goTo(index) {
            // Solo se avanza a etapas ya desbloqueadas por una selección válida.
            if (index > this.step && !this.canAdvance) {
                return;
            }

            this.step = Math.max(0, Math.min(this.lastStepIndex, index));
            this.summaryOpen = false;

            if (this.planId) {
                this.calculate();
            }
        },

        next() {
            if (!this.canAdvance) {
                return;
            }

            this.goTo(this.step + 1);
        },

        back() {
            this.goTo(this.step - 1);
        },

        selectBusinessType(id) {
            this.businessTypeId = id;
        },

        selectBillingModel(id) {
            this.billingModelId = id;
            this.calculate();
        },

        setOrders(value) {
            const parsed = Number(value);

            this.ordersPerBranch = Math.max(0, Math.min(1000000, Number.isFinite(parsed) ? Math.round(parsed) : 0));
            this.calculate();
        },

        selectPlan(id) {
            this.planId = id;

            // Lo que ya venga dentro del paquete deja de contratarse aparte.
            this.selection = this.selection.filter((itemId) => {
                const module = this.sellableModules.find((row) => row.id === itemId);

                return module ? !this.includedSlugs.has(module.slug) : false;
            });

            this.calculate();
        },

        toggleModule(module) {
            if (this.isIncluded(module)) {
                return;
            }

            const previous = [...this.selection];

            this.selection = this.selection.includes(module.id)
                ? this.selection.filter((id) => id !== module.id)
                : [...this.selection, module.id];

            // Si el backend rechaza la combinación se deshace el cambio.
            this.calculate(previous);
        },

        setScale(key, value) {
            const [min, max] = SCALE_LIMITS[key] ?? [0, 100];
            const parsed = Number(value);

            this.scalability[key] = Math.max(min, Math.min(max, Number.isFinite(parsed) ? Math.round(parsed) : min));
            this.calculate();
        },

        stepScale(key, delta) {
            this.setScale(key, (this.scalability[key] ?? 0) + delta);
        },

        setCatalog(key, value) {
            if (key === 'distribution') {
                this.catalog.distribution = String(value).slice(0, 1000);

                return;
            }

            const parsed = Number(value);
            this.catalog[key] = Number.isFinite(parsed) && parsed > 0 ? Math.round(parsed) : null;
        },

        toggleSummary() {
            this.summaryOpen = !this.summaryOpen;
        },

        /**
         * Pide a Laravel el precio de la configuración actual.
         *
         * Con debounce porque los contadores disparan en cada pulsación, y el
         * backend es la única fuente de verdad de todos los importes.
         *
         * @param {Array|null} rollbackTo Selección previa, si hay que deshacer.
         */
        calculate(rollbackTo = null) {
            clearTimeout(previewTimer);

            previewTimer = setTimeout(async () => {
                if (!this.planId) {
                    this.result = null;

                    return;
                }

                this.calculating = true;
                this.calcError = null;

                try {
                    const { data } = await http.post(
                        this.site.routes.calculate ?? '/api/quotations/calculate',
                        this.configuration,
                    );

                    this.result = data.data;
                    this.blockedBy = null;
                } catch (error) {
                    const parsed = parseError(error);
                    const blocked = parsed.errors?.items?.[0] ?? null;

                    if (blocked && rollbackTo) {
                        // Una dependencia no satisfecha no rompe el cotizador:
                        // se avisa y se deja la selección anterior intacta.
                        this.selection = rollbackTo;
                        this.blockedBy = blocked;
                    } else {
                        this.calcError = blocked ?? parsed.message;
                    }
                } finally {
                    this.calculating = false;
                }
            }, 220);
        },

        async submit(sendEmail = true) {
            if (this.submitting) {
                return false;
            }

            this.submitting = true;
            this.submitError = null;
            this.submitErrors = {};

            try {
                const { data } = await http.post(this.site.routes.quotations ?? '/api/quotations', {
                    ...this.configuration,
                    contact: {
                        name: this.contact.name,
                        company: this.contact.company,
                        email: this.contact.email,
                        phone: this.contact.phone,
                        city: this.contact.city,
                        accepts_marketing: this.contact.accepts_marketing,
                    },
                    send_email: sendEmail,
                    elapsed: Date.now() - this.startedAt,
                    website: this.contact.website ?? '',
                });

                this.submitted = data.data;

                return true;
            } catch (error) {
                const parsed = parseError(error);
                this.submitError = parsed.message;
                this.submitErrors = parsed.errors;

                return false;
            } finally {
                this.submitting = false;
            }
        },

        async emailQuotation(email) {
            if (!this.submitted || this.emailing) {
                return;
            }

            this.emailing = true;
            this.emailMessage = null;

            try {
                const { data } = await http.post(`/api/quotations/${this.submitted.token}/email`, { email });
                this.emailMessage = data.message;
            } catch (error) {
                this.emailMessage = parseError(error).message;
            } finally {
                this.emailing = false;
            }
        },
    },
});
