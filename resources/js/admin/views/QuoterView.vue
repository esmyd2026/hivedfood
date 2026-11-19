<script setup>
import { computed, onMounted, ref } from 'vue';
import ResourceManager from '../components/ResourceManager.vue';
import AdminIcon from '../components/AdminIcon.vue';
import StatusChip from '../components/StatusChip.vue';
import { api, parseError } from '../lib/api';
import { useUiStore } from '../stores/ui';

const ui = useUiStore();

const TABS = [
    { key: 'plans', label: 'Paquetes' },
    { key: 'modules', label: 'Módulos' },
    { key: 'scalability', label: 'Escalabilidad' },
    { key: 'billing', label: 'Formas de cobro' },
    { key: 'tiers', label: 'Tramos por pedido' },
    { key: 'types', label: 'Tipos de negocio' },
    { key: 'discounts', label: 'Descuentos' },
    { key: 'history', label: 'Historial' },
];

const tab = ref('plans');

/* ------------------------------------------------- estado de publicación --- */
const pricingPublished = ref(false);
const currency = ref('USD');
const savingFlag = ref(false);

async function loadSettings() {
    try {
        const { data } = await api.get('configuracion');
        const flat = Object.values(data).flat();

        pricingPublished.value = flat.find((s) => s.key === 'pricing_published')?.value === '1';
        currency.value = flat.find((s) => s.key === 'pricing_currency')?.value ?? 'USD';
    } catch (error) {
        ui.error(parseError(error).message);
    }
}

async function togglePublished() {
    savingFlag.value = true;
    const next = !pricingPublished.value;

    try {
        await api.post('configuracion', { values: { pricing_published: next ? '1' : '0' } });
        pricingPublished.value = next;
        ui.success(next ? 'Precios publicados en el cotizador.' : 'Precios ocultos en el cotizador.');
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        savingFlag.value = false;
    }
}

/* ----------------------------------------------------- catálogo de slugs --- */
// Los módulos se referencian por slug tanto en los paquetes como en las
// dependencias, así que conviene tenerlos a la vista al editar.
const moduleSlugs = ref([]);

async function loadModuleSlugs() {
    try {
        const payload = await api.get('items-precio', { group: 'module' });

        moduleSlugs.value = (payload.data ?? []).map((row) => `${row.slug} — ${row.name}`);
    } catch {
        moduleSlugs.value = [];
    }
}

onMounted(() => {
    loadSettings();
    loadModuleSlugs();
    loadBillingModels();
});

const slugHint = computed(() =>
    moduleSlugs.value.length ? `Slugs disponibles: ${moduleSlugs.value.join(' · ')}` : 'Un slug por etiqueta.',
);

/* ------------------------------------------------------- tipos de negocio --- */
const typeColumns = [
    { key: 'name', label: 'Tipo de negocio', strong: true },
    { key: 'short_description', label: 'Descripción', truncate: true },
    { key: 'is_active', label: 'Activo', type: 'toggle' },
];

const typeFields = [
    { key: 'name', label: 'Nombre', type: 'text', required: true, maxlength: 120 },
    { key: 'slug', label: 'Slug', type: 'text', required: true, maxlength: 120 },
    {
        key: 'icon',
        label: 'Icono',
        type: 'select',
        options: ['restaurant', 'burger', 'pizza', 'fast-food', 'coffee', 'ice-cream', 'more', 'storefront'].map(
            (value) => ({ value, label: value }),
        ),
    },
    { key: 'short_description', label: 'Descripción corta', type: 'text', span: 2, maxlength: 255 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 2, maxlength: 1000 },
    { key: 'image', label: 'Imagen', type: 'image' },
    { key: 'allow_free_text', label: 'Permitir que el visitante escriba su rubro', type: 'boolean' },
    { key: 'is_active', label: 'Activo', type: 'boolean', span: 2, default: true },
];

/* ------------------------------------------------------------- paquetes --- */
const planColumns = [
    { key: 'level', label: 'Nivel' },
    { key: 'name', label: 'Paquete', strong: true },
    {
        key: 'setup_price',
        label: 'Implementación',
        format: (row) => (row.setup_price === null ? 'Sin definir' : `${row.setup_price} ${row.currency}`),
    },
    {
        key: 'monthly_price',
        label: 'Mensual',
        format: (row) => (row.monthly_price === null ? 'Sin definir' : `${row.monthly_price} ${row.currency}`),
    },
    {
        key: 'included_item_slugs',
        label: 'Módulos incluidos',
        format: (row) => `${(row.included_item_slugs ?? []).length} módulo(s)`,
    },
    { key: 'is_recommended', label: 'Recomendado', type: 'toggle' },
    { key: 'is_active', label: 'Activo', type: 'toggle' },
];

const planFields = computed(() => [
    { key: 'name', label: 'Nombre', type: 'text', required: true, maxlength: 140 },
    { key: 'slug', label: 'Slug', type: 'text', required: true, maxlength: 140 },
    {
        key: 'level',
        label: 'Nivel',
        type: 'number',
        min: 1,
        max: 20,
        default: 1,
        hint: 'Los paquetes son acumulativos: el nivel 2 incluye todo lo del 1.',
    },
    { key: 'currency', label: 'Moneda', type: 'text', maxlength: 8, default: 'USD' },
    { key: 'headline', label: 'Frase corta', type: 'text', span: 2, maxlength: 200 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 3, maxlength: 1000 },
    {
        key: 'icon',
        label: 'Icono',
        type: 'select',
        options: ['storefront', 'receipt', 'layers', 'dashboard', 'rocket'].map((value) => ({ value, label: value })),
    },
    {
        key: 'setup_price',
        label: 'Precio de implementación',
        type: 'number',
        min: 0,
        step: 0.01,
        hint: 'Déjalo vacío si aún no está aprobado comercialmente.',
    },
    { key: 'monthly_price', label: 'Precio mensual', type: 'number', min: 0, step: 0.01 },
    {
        key: 'included_item_slugs',
        label: 'Módulos incluidos (slugs)',
        type: 'tags',
        span: 2,
        hint: slugHint.value,
    },
    { key: 'included', label: 'Qué incluye (texto comercial)', type: 'tags', span: 2 },
    { key: 'valid_from', label: 'Vigente desde', type: 'date' },
    { key: 'valid_until', label: 'Vigente hasta', type: 'date' },
    { key: 'is_recommended', label: 'Marcar como recomendado', type: 'boolean' },
    { key: 'is_active', label: 'Activo', type: 'boolean', default: true },
]);

/* -------------------------------------------------------------- módulos --- */
const CATEGORIES = [
    { value: 'canales', label: 'Canales de venta' },
    { value: 'operacion', label: 'Operación' },
    { value: 'integraciones', label: 'Integraciones' },
    { value: 'automatizacion', label: 'Automatización e IA' },
    { value: 'rrhh', label: 'Recursos humanos' },
];

const moduleColumns = [
    { key: 'name', label: 'Módulo', strong: true },
    {
        key: 'category',
        label: 'Categoría',
        format: (row) => CATEGORIES.find((c) => c.value === row.category)?.label ?? row.category ?? '—',
    },
    {
        key: 'setup_price',
        label: 'Implementación',
        format: (row) => (row.setup_price === null ? 'Sin definir' : `${row.setup_price} ${row.currency}`),
    },
    {
        key: 'monthly_price',
        label: 'Mensual',
        format: (row) => (row.monthly_price === null ? 'Sin definir' : `${row.monthly_price} ${row.currency}`),
    },
    {
        key: 'requires',
        label: 'Dependencias',
        format: (row) => {
            const hard = (row.requires ?? []).length;
            const soft = (row.requires_any ?? []).length;

            if (!hard && !soft) {
                return '—';
            }

            return [hard ? `${hard} obligatoria(s)` : null, soft ? `${soft} alternativa(s)` : null]
                .filter(Boolean)
                .join(' · ');
        },
    },
    { key: 'is_available', label: 'Se vende suelto', type: 'toggle' },
    { key: 'is_active', label: 'Activo', type: 'toggle' },
];

const moduleFields = computed(() => [
    { key: 'name', label: 'Nombre', type: 'text', required: true, maxlength: 140 },
    { key: 'slug', label: 'Slug', type: 'text', required: true, maxlength: 140 },
    { key: 'category', label: 'Categoría', type: 'select', options: CATEGORIES, required: true },
    { key: 'currency', label: 'Moneda', type: 'text', maxlength: 8, default: 'USD' },
    {
        key: 'setup_price',
        label: 'Precio de implementación',
        type: 'number',
        min: 0,
        step: 0.01,
        hint: 'Vacío = pendiente de aprobación; el cotizador lo muestra como «a cotizar».',
    },
    { key: 'monthly_price', label: 'Precio mensual', type: 'number', min: 0, step: 0.01 },
    { key: 'short_description', label: 'Descripción corta', type: 'text', span: 2, maxlength: 255 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 2, maxlength: 1000 },
    { key: 'benefits', label: 'Beneficios', type: 'tags', span: 2 },
    {
        key: 'requires',
        label: 'Requiere siempre (slugs)',
        type: 'tags',
        span: 2,
        hint: 'Se activan automáticamente al elegir este módulo. '+slugHint.value,
    },
    {
        key: 'requires_any',
        label: 'Requiere al menos uno de (slugs)',
        type: 'tags',
        span: 2,
        hint: 'Si ninguno está presente, el cotizador bloquea la combinación y lo explica.',
    },
    {
        key: 'notes',
        label: 'Observaciones operativas',
        type: 'tags',
        span: 2,
        hint: 'Informativas: se muestran al cliente pero no bloquean nada.',
    },
    {
        key: 'icon',
        label: 'Icono',
        type: 'select',
        options: ['chat', 'storefront', 'receipt', 'card', 'coins', 'list', 'mobile', 'users', 'chart', 'map', 'layers', 'spark'].map(
            (value) => ({ value, label: value }),
        ),
    },
    {
        key: 'is_available',
        label: 'Se puede contratar por separado',
        type: 'boolean',
        default: true,
        hint: 'Desactívalo para los módulos que solo llegan dentro de un paquete.',
    },
    { key: 'valid_from', label: 'Vigente desde', type: 'date' },
    { key: 'valid_until', label: 'Vigente hasta', type: 'date' },
    { key: 'is_active', label: 'Activo', type: 'boolean', span: 2, default: true },
]);

/* --------------------------------------------------------- escalabilidad --- */
const UNIT_KINDS = [
    { value: 'branch', label: 'Sucursal adicional' },
    { value: 'brand', label: 'Marca adicional' },
    { value: 'whatsapp', label: 'Número de WhatsApp' },
    { value: 'training', label: 'Capacitación' },
];

const scaleColumns = [
    { key: 'name', label: 'Concepto', strong: true },
    {
        key: 'unit_kind',
        label: 'Unidad',
        format: (row) => UNIT_KINDS.find((u) => u.value === row.unit_kind)?.label ?? row.unit_kind,
    },
    { key: 'included_quantity', label: 'Incluidas' },
    {
        key: 'setup_price',
        label: 'Implementación',
        format: (row) => (row.setup_price === null ? 'Sin definir' : `${row.setup_price} ${row.currency}`),
    },
    {
        key: 'monthly_price',
        label: 'Mensual',
        format: (row) => (row.monthly_price === null ? 'Sin definir' : `${row.monthly_price} ${row.currency}`),
    },
    { key: 'is_active', label: 'Activo', type: 'toggle' },
];

const scaleFields = [
    { key: 'name', label: 'Nombre', type: 'text', required: true, maxlength: 140 },
    { key: 'slug', label: 'Slug', type: 'text', required: true, maxlength: 140 },
    { key: 'unit_kind', label: 'Unidad que escala', type: 'select', options: UNIT_KINDS, required: true },
    { key: 'unit', label: 'Etiqueta de la unidad', type: 'text', maxlength: 40, placeholder: 'sucursal, marca, número…' },
    {
        key: 'included_quantity',
        label: 'Cantidad incluida en los paquetes',
        type: 'number',
        min: 0,
        max: 1000,
        default: 1,
        hint: 'Solo se cobra lo que supere esta cantidad.',
    },
    { key: 'currency', label: 'Moneda', type: 'text', maxlength: 8, default: 'USD' },
    {
        key: 'setup_price',
        label: 'Implementación por unidad adicional',
        type: 'number',
        min: 0,
        step: 0.01,
        hint: 'Vacío = tarifa pendiente de aprobación comercial.',
    },
    { key: 'monthly_price', label: 'Mensual por unidad adicional', type: 'number', min: 0, step: 0.01 },
    { key: 'min_quantity', label: 'Cantidad mínima', type: 'number', min: 0, max: 1000, default: 1 },
    { key: 'max_quantity', label: 'Cantidad máxima', type: 'number', min: 1, max: 1000, default: 200 },
    { key: 'short_description', label: 'Descripción corta', type: 'text', span: 2, maxlength: 255 },
    { key: 'notes', label: 'Observaciones', type: 'tags', span: 2 },
    {
        key: 'icon',
        label: 'Icono',
        type: 'select',
        options: ['building', 'storefront', 'chat', 'users', 'headset', 'globe'].map((value) => ({ value, label: value })),
    },
    { key: 'is_active', label: 'Activo', type: 'boolean', span: 2, default: true },
];

/* ---------------------------------------------------------- formas de cobro --- */
// Una modalidad solo decide dos cosas: si se cobra la mensualidad fija del
// catálogo y si además se cobra el consumo por pedidos. Ningún precio de
// paquete, módulo o local se configura aquí.
const billingModels = ref([]);

async function loadBillingModels() {
    try {
        const payload = await api.get('formas-cobro');
        billingModels.value = payload.data ?? [];
    } catch (error) {
        ui.error(parseError(error).message);
    }
}

const usageModels = computed(() => billingModels.value.filter((model) => model.charges_usage));

const billingColumns = [
    { key: 'name', label: 'Forma de cobro', strong: true },
    {
        key: 'charges',
        label: 'Qué cobra',
        format: (row) =>
            [row.charges_monthly ? 'Mensualidad fija' : null, row.charges_usage ? 'Consumo por pedido' : null]
                .filter(Boolean)
                .join(' + ') || 'Solo implementación',
    },
    {
        key: 'tiers',
        label: 'Tramos',
        format: (row) => (row.charges_usage ? `${(row.tiers ?? []).length} tramo(s)` : '—'),
    },
    {
        key: 'volume_basis',
        label: 'Volumen',
        format: (row) => (row.charges_usage ? (row.volume_basis === 'total' ? 'Por cuenta' : 'Por local') : '—'),
    },
    { key: 'is_recommended', label: 'Destacada', type: 'toggle' },
    { key: 'is_active', label: 'Activa', type: 'toggle' },
];

const billingFields = [
    { key: 'name', label: 'Nombre', type: 'text', required: true, maxlength: 140 },
    { key: 'slug', label: 'Slug', type: 'text', required: true, maxlength: 140 },
    {
        key: 'type',
        label: 'Tipo',
        type: 'select',
        required: true,
        default: 'monthly',
        span: 2,
        options: [
            { value: 'monthly', label: 'Mensualidad fija' },
            { value: 'per_order', label: 'Con consumo por pedido' },
            { value: 'commission', label: 'Comisión sobre ventas' },
        ],
    },
    { key: 'headline', label: 'Frase corta', type: 'text', span: 2, maxlength: 200 },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 3, maxlength: 1000 },
    {
        key: 'icon',
        label: 'Icono',
        type: 'select',
        options: ['clock', 'receipt', 'percent', 'coins', 'card', 'chart', 'growth'].map((value) => ({ value, label: value })),
    },
    { key: 'currency', label: 'Moneda', type: 'text', maxlength: 8, default: 'USD' },
    {
        key: 'charges_monthly',
        label: 'Cobra la mensualidad fija del catálogo',
        type: 'boolean',
        default: true,
        hint: 'Desactívalo solo para una modalidad sin cuota fija: entonces la mensualidad del paquete, los módulos y los locales no se cobra y todo lo recurrente sale del consumo.',
    },
    {
        key: 'charges_usage',
        label: 'Cobra además el consumo por pedidos',
        type: 'boolean',
        hint: 'Al activarlo, el cotizador pregunta cuántos pedidos hace el cliente y aplica los tramos de la pestaña siguiente.',
    },
    {
        key: 'included_orders',
        label: 'Pedidos incluidos sin cargo',
        type: 'number',
        min: 0,
        max: 1000000,
        default: 0,
        hint: 'Se descuentan antes de empezar a cobrar tramos.',
    },
    {
        key: 'volume_basis',
        label: 'El volumen se cuenta',
        type: 'select',
        required: true,
        default: 'branch',
        options: [
            { value: 'branch', label: 'Por cada local (cada uno corre su propia escalera)' },
            { value: 'total', label: 'Por el total de la cuenta' },
        ],
    },
    {
        key: 'min_monthly',
        label: 'Mínimo mensual de consumo',
        type: 'number',
        min: 0,
        step: 0.01,
        hint: 'Opcional. Si el consumo del mes queda por debajo, se factura este mínimo.',
    },
    {
        key: 'commission_percent',
        label: 'Comisión (%)',
        type: 'number',
        min: 0,
        max: 100,
        step: 0.01,
        hint: 'Solo para el tipo «Comisión sobre ventas».',
    },
    { key: 'included', label: 'Qué incluye', type: 'tags', span: 2 },
    { key: 'conditions', label: 'Condiciones comerciales', type: 'tags', span: 2 },
    { key: 'valid_from', label: 'Vigente desde', type: 'date' },
    { key: 'valid_until', label: 'Vigente hasta', type: 'date' },
    { key: 'is_recommended', label: 'Destacar como «más elegida»', type: 'boolean' },
    { key: 'is_active', label: 'Activa', type: 'boolean', default: true },
];

/* ------------------------------------------------------ tramos por pedido --- */
const tierColumns = [
    {
        key: 'billing_model_id',
        label: 'Forma de cobro',
        format: (row) => billingModels.value.find((m) => m.id === row.billing_model_id)?.name ?? row.billing_model_id,
    },
    { key: 'label', label: 'Tramo', strong: true },
    {
        key: 'range',
        label: 'Pedidos del mes',
        format: (row) => (row.to_orders === null ? `del ${row.from_orders} en adelante` : `del ${row.from_orders} al ${row.to_orders}`),
    },
    { key: 'price_per_order', label: 'Precio por pedido' },
    { key: 'is_active', label: 'Activo', type: 'toggle' },
];

const tierFields = computed(() => [
    {
        key: 'billing_model_id',
        label: 'Forma de cobro',
        type: 'select',
        required: true,
        span: 2,
        options: usageModels.value.map((model) => ({ value: model.id, label: model.name })),
        hint: 'Los tramos solo aplican a las formas de cobro que cobran consumo por pedido.',
    },
    { key: 'label', label: 'Nombre del tramo', type: 'text', span: 2, maxlength: 120, placeholder: 'Los primeros 500 pedidos' },
    { key: 'from_orders', label: 'Desde el pedido número', type: 'number', min: 1, max: 1000000, required: true, default: 1 },
    {
        key: 'to_orders',
        label: 'Hasta el pedido número',
        type: 'number',
        min: 1,
        max: 1000000,
        hint: 'Vacío = sin tope superior.',
    },
    {
        key: 'price_per_order',
        label: 'Precio de cada pedido de este tramo',
        type: 'number',
        min: 0,
        step: 0.0001,
        required: true,
        hint: 'Admite cuatro decimales: 0,25 son veinticinco centavos.',
    },
    { key: 'is_active', label: 'Activo', type: 'boolean', default: true },
]);

/* --------------------------------------------------------------- descuentos --- */
const discountColumns = [
    { key: 'name', label: 'Descuento', strong: true },
    {
        key: 'type',
        label: 'Tipo',
        type: 'chip',
        map: {
            percentage: { label: 'Porcentaje', tone: 'info' },
            fixed: { label: 'Monto fijo', tone: 'info' },
        },
    },
    { key: 'value', label: 'Valor', format: (row) => (row.type === 'percentage' ? `${row.value}%` : row.value) },
    { key: 'applies_to', label: 'Aplica a' },
    { key: 'min_items', label: 'Mín. ítems' },
    { key: 'is_active', label: 'Activo', type: 'toggle' },
];

const discountFields = [
    { key: 'name', label: 'Nombre', type: 'text', required: true, maxlength: 140 },
    { key: 'code', label: 'Código', type: 'text', maxlength: 40, hint: 'Opcional, para descuentos manuales.' },
    { key: 'description', label: 'Descripción', type: 'textarea', span: 2, rows: 2, maxlength: 500 },
    {
        key: 'type',
        label: 'Tipo',
        type: 'select',
        required: true,
        default: 'percentage',
        options: [
            { value: 'percentage', label: 'Porcentaje' },
            { value: 'fixed', label: 'Monto fijo' },
        ],
    },
    { key: 'value', label: 'Valor', type: 'number', min: 0, step: 0.01, required: true, default: 0 },
    {
        key: 'applies_to',
        label: 'Aplica sobre',
        type: 'select',
        required: true,
        default: 'setup',
        options: [
            { value: 'setup', label: 'Implementación' },
            { value: 'recurring', label: 'Mensual' },
            { value: 'total', label: 'Ambos' },
        ],
    },
    { key: 'min_items', label: 'Mínimo de módulos seleccionados', type: 'number', min: 0, max: 100, default: 0 },
    { key: 'min_subtotal', label: 'Subtotal mínimo', type: 'number', min: 0, step: 0.01 },
    { key: 'plan_slugs', label: 'Solo para estos paquetes (slugs)', type: 'tags', span: 2 },
    { key: 'valid_from', label: 'Vigente desde', type: 'date' },
    { key: 'valid_until', label: 'Vigente hasta', type: 'date' },
    { key: 'is_automatic', label: 'Aplicar automáticamente', type: 'boolean', default: true },
    { key: 'is_active', label: 'Activo', type: 'boolean', default: true },
];

/* ---------------------------------------------------------------- historial --- */
const audits = ref([]);
const loadingAudits = ref(false);

const ACTIONS = {
    created: { label: 'Creado', tone: 'success' },
    updated: { label: 'Modificado', tone: 'warning' },
    deleted: { label: 'Eliminado', tone: 'danger' },
};

async function loadAudits() {
    loadingAudits.value = true;

    try {
        const payload = await api.get('historial-precios', { per_page: 50 });
        audits.value = payload.data ?? [];
    } catch (error) {
        ui.error(parseError(error).message);
    } finally {
        loadingAudits.value = false;
    }
}

function openHistory() {
    tab.value = 'history';

    if (!audits.value.length) {
        loadAudits();
    }
}

function show(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return typeof value === 'string' ? value : JSON.stringify(value);
}

/**
 * Un alta o una baja guardan una foto del registro (campo → valor); una
 * modificación guarda el antes y el después de cada campo.
 */
function describe(changes) {
    return Object.entries(changes ?? {}).map(([field, change]) => {
        const paired = change !== null && typeof change === 'object' && !Array.isArray(change) && 'to' in change;

        return {
            field,
            from: paired ? show(change.from) : '—',
            to: show(paired ? change.to : change),
        };
    });
}

function when(value) {
    return value ? new Date(value).toLocaleString('es') : '';
}
</script>

<template>
    <div>
        <!-- Interruptor de publicación de precios -->
        <div
            :class="[
                'mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border px-5 py-4',
                pricingPublished ? 'border-charcoal/12 bg-white' : 'border-honey/50 bg-honey-soft/50',
            ]"
        >
            <div class="flex items-start gap-3">
                <AdminIcon :name="pricingPublished ? 'eye' : 'eye-off'" :size="19" class="mt-0.5" />
                <div>
                    <p class="font-sans text-sm font-bold">
                        {{ pricingPublished ? 'Los precios se muestran en el cotizador' : 'Los precios no se muestran en el cotizador' }}
                    </p>
                    <p class="mt-1 max-w-2xl text-xs text-charcoal/60">
                        Mientras esté desactivado, el cotizador registra la configuración del visitante y muestra un
                        mensaje de propuesta a medida en lugar de totales. Actívalo solo cuando las tarifas estén
                        confirmadas comercialmente.
                    </p>
                </div>
            </div>

            <button type="button" class="ad-btn ad-btn-primary" :disabled="savingFlag" @click="togglePublished">
                {{ pricingPublished ? 'Ocultar precios' : 'Publicar precios' }}
            </button>
        </div>

        <!-- Pestañas -->
        <div class="mb-5 flex flex-wrap gap-1 border-b border-charcoal/10">
            <button
                v-for="item in TABS"
                :key="item.key"
                type="button"
                :class="[
                    'relative px-4 py-2.5 font-sans text-sm font-bold transition-colors',
                    tab === item.key ? 'text-charcoal' : 'text-charcoal/45 hover:text-charcoal/75',
                ]"
                @click="item.key === 'history' ? openHistory() : (tab = item.key)"
            >
                {{ item.label }}
                <span v-if="tab === item.key" class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-coral" />
            </button>
        </div>

        <!-- Paquetes -->
        <div v-if="tab === 'plans'">
            <div class="mb-5 max-w-3xl rounded-xl border border-charcoal/12 bg-white px-4 py-3 text-sm text-charcoal/70">
                <strong class="font-sans text-charcoal">Los paquetes son acumulativos.</strong>
                Cada nivel debe repetir en «módulos incluidos» todo lo del nivel anterior más lo nuevo: el motor de
                cálculo no cobra dos veces un módulo que ya esté en esa lista.
            </div>

            <ResourceManager
                key="plans"
                endpoint="planes"
                singular="paquete"
                :columns="planColumns"
                :fields="planFields"
                reorderable
            />
        </div>

        <!-- Módulos -->
        <div v-else-if="tab === 'modules'">
            <div class="mb-5 max-w-3xl rounded-xl border border-charcoal/12 bg-white px-4 py-3 text-sm text-charcoal/70">
                <strong class="font-sans text-charcoal">Dependencias.</strong>
                «Requiere siempre» activa el módulo que falta y se lo explica al cliente. «Requiere al menos uno de»
                bloquea la combinación cuando ninguna de las alternativas está presente.
            </div>

            <ResourceManager
                key="modules"
                endpoint="items-precio"
                singular="módulo"
                :columns="moduleColumns"
                :fields="moduleFields"
                :defaults="{ group: 'module', is_active: true, is_available: true, currency: 'USD', min_quantity: 1, max_quantity: 1 }"
                :scope="{ group: 'module' }"
                reorderable
                @changed="loadModuleSlugs"
            />
        </div>

        <!-- Escalabilidad -->
        <div v-else-if="tab === 'scalability'">
            <div class="mb-5 max-w-3xl rounded-xl border border-honey/40 bg-honey-soft/50 px-4 py-3 text-sm">
                <strong class="font-sans">Solo se cobra lo adicional.</strong>
                Si un concepto trae una unidad incluida, la primera no genera cargo. Deja los precios vacíos mientras
                la tarifa esté pendiente de aprobación: el cotizador la mostrará como «a cotizar» en lugar de inventar
                un importe.
            </div>

            <ResourceManager
                key="scalability"
                endpoint="items-precio"
                singular="concepto de escalabilidad"
                :columns="scaleColumns"
                :fields="scaleFields"
                :defaults="{ group: 'scalability', is_active: true, is_available: true, currency: 'USD', min_quantity: 1, max_quantity: 200, included_quantity: 1 }"
                :scope="{ group: 'scalability' }"
                reorderable
            />
        </div>

        <!-- Formas de cobro -->
        <div v-else-if="tab === 'billing'">
            <div class="mb-5 max-w-3xl rounded-xl border border-charcoal/12 bg-white px-4 py-3 text-sm text-charcoal/70">
                <strong class="font-sans text-charcoal">Una modalidad no fija precios.</strong>
                La implementación y la mensualidad salen siempre del paquete, los módulos y los locales. Lo único
                que decide esta pantalla es si se cobra esa mensualidad y si además se cobra el consumo por pedidos,
                para que dos sitios distintos no puedan cobrar lo mismo dos veces.
            </div>

            <ResourceManager
                key="billing"
                endpoint="formas-cobro"
                singular="forma de cobro"
                :columns="billingColumns"
                :fields="billingFields"
                reorderable
                @changed="loadBillingModels"
            />
        </div>

        <!-- Tramos por pedido -->
        <div v-else-if="tab === 'tiers'">
            <div class="mb-5 max-w-3xl rounded-xl border border-honey/40 bg-honey-soft/50 px-4 py-3 text-sm">
                <strong class="font-sans">Los tramos son escalonados.</strong>
                Cada pedido paga el precio del tramo en el que cae, no el del tramo más alto alcanzado: con 501
                pedidos se cobran los primeros 500 a su tarifa y solo el pedido 501 a la siguiente. Crecer nunca
                encarece ni abarata lo ya recorrido. Evita dejar huecos entre el «hasta» de un tramo y el
                «desde» del siguiente: los pedidos que caigan en el hueco quedan sin tarifa.
            </div>

            <ResourceManager
                key="tiers"
                endpoint="tramos-pedido"
                singular="tramo"
                :columns="tierColumns"
                :fields="tierFields"
                :defaults="{ is_active: true, from_orders: 1 }"
                :searchable="false"
                empty-message="Sin tramos configurados: el consumo por pedido no podrá calcularse."
                @changed="loadBillingModels"
            />
        </div>

        <ResourceManager
            v-else-if="tab === 'types'"
            key="types"
            endpoint="tipos-negocio"
            singular="tipo de negocio"
            :columns="typeColumns"
            :fields="typeFields"
            reorderable
        />

        <ResourceManager
            v-else-if="tab === 'discounts'"
            key="discounts"
            endpoint="descuentos"
            singular="descuento"
            :columns="discountColumns"
            :fields="discountFields"
            reorderable
        />

        <!-- Historial de cambios -->
        <div v-else>
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <p class="max-w-2xl text-sm text-charcoal/70">
                    Cada alta, cambio de tarifa o baja del modelo comercial queda registrada aquí. Las cotizaciones ya
                    emitidas conservan los precios que tenían al generarse.
                </p>
                <button type="button" class="ad-btn" :disabled="loadingAudits" @click="loadAudits">
                    <AdminIcon name="refresh" :size="15" />
                    Actualizar
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-charcoal/12 bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-ivory text-left text-xs uppercase tracking-wider text-charcoal/50">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Cuándo</th>
                            <th class="px-4 py-3 font-semibold">Concepto</th>
                            <th class="px-4 py-3 font-semibold">Acción</th>
                            <th class="px-4 py-3 font-semibold">Cambios</th>
                            <th class="px-4 py-3 font-semibold">Usuario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/8">
                        <tr v-for="audit in audits" :key="audit.id" class="align-top">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-charcoal/60">{{ when(audit.created_at) }}</td>
                            <td class="px-4 py-3">
                                <span class="font-sans font-bold">{{ audit.label }}</span>
                                <span class="mt-0.5 block text-xs text-charcoal/45">{{ audit.type }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <StatusChip :value="audit.action" :map="ACTIONS" />
                            </td>
                            <td class="px-4 py-3">
                                <ul class="space-y-1">
                                    <li v-for="change in describe(audit.changes)" :key="change.field" class="text-xs">
                                        <span class="font-semibold">{{ change.field }}:</span>
                                        <span class="text-charcoal/50">{{ change.from }}</span>
                                        →
                                        <span class="font-semibold">{{ change.to }}</span>
                                    </li>
                                </ul>
                            </td>
                            <td class="px-4 py-3 text-xs text-charcoal/60">{{ audit.user ?? '—' }}</td>
                        </tr>

                        <tr v-if="!audits.length">
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-charcoal/50">
                                {{ loadingAudits ? 'Cargando…' : 'Todavía no hay cambios registrados.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
