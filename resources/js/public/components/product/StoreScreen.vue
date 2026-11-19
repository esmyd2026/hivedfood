<script setup>
import IconGlyph from '../ui/IconGlyph.vue';

/**
 * Reconstrucción de la tienda tal como la ve el cliente en el móvil.
 * Es una maqueta: cuando haya capturas reales se suben y reemplazan esto.
 */
defineProps({
    brand: { type: String, default: 'Tu marca' },
    categories: { type: Array, default: () => ['Hamburguesas', 'Pizzas', 'Bebidas'] },
    products: {
        type: Array,
        default: () => [
            { name: 'Doble carne', note: 'Con queso y salsa de la casa', price: '8,90', tone: 'coral' },
            { name: 'Pizza familiar', note: 'Elige hasta 3 ingredientes', price: '14,50', tone: 'honey' },
            { name: 'Combo del día', note: 'Hamburguesa + papas + bebida', price: '11,20', tone: 'green' },
        ],
    },
    cart: { type: String, default: '2 productos · $18,40' },
    cta: { type: String, default: 'Hacer pedido' },
});

const TONES = {
    coral: 'var(--color-coral)',
    honey: 'var(--color-honey)',
    green: 'var(--color-charcoal-500)',
};
</script>

<template>
    <div class="flex h-full flex-col bg-ivory text-charcoal" aria-hidden="true">
        <!-- Cabecera de la tienda -->
        <div class="flex items-center justify-between px-4 pb-3 pt-7">
            <div class="flex items-center gap-2">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-honey text-charcoal">
                    <IconGlyph name="storefront" :size="15" />
                </span>
                <span class="font-sans text-[0.78rem] font-extrabold">{{ brand }}</span>
            </div>
            <IconGlyph name="menu" :size="15" class="opacity-40" />
        </div>

        <!-- Banner promocional -->
        <div class="mx-4 rounded-xl bg-honey px-3.5 py-3">
            <p class="font-sans text-[0.55rem] font-bold uppercase tracking-[0.12em] text-charcoal/65">Promoción</p>
            <p class="mt-0.5 font-sans text-[0.82rem] font-extrabold leading-tight">2x1 en hamburguesas</p>
        </div>

        <!-- Categorías -->
        <div class="mt-3.5 flex gap-1.5 overflow-hidden px-4">
            <span
                v-for="(category, i) in categories"
                :key="category"
                :class="[
                    'shrink-0 rounded-full px-2.5 py-1 text-[0.6rem] font-semibold',
                    i === 0 ? 'bg-charcoal text-ivory' : 'bg-charcoal/8 text-charcoal/60',
                ]"
            >
                {{ category }}
            </span>
        </div>

        <!-- Productos -->
        <div class="mt-3 flex-1 space-y-2 px-4">
            <div
                v-for="product in products"
                :key="product.name"
                class="flex items-center gap-2.5 rounded-xl bg-white p-2 shadow-[0_1px_2px_rgba(32,42,36,0.06)]"
            >
                <span
                    class="grid h-11 w-11 shrink-0 place-items-center rounded-lg"
                    :style="{ backgroundColor: TONES[product.tone] ?? TONES.honey }"
                >
                    <IconGlyph name="burger" :size="19" class="text-white/85" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate font-sans text-[0.7rem] font-bold">{{ product.name }}</span>
                    <span class="mt-0.5 block truncate text-[0.58rem] text-charcoal/50">{{ product.note }}</span>
                </span>

                <span class="shrink-0 text-right">
                    <span class="block font-sans text-[0.7rem] font-extrabold">${{ product.price }}</span>
                    <span class="mt-0.5 ml-auto grid h-5 w-5 place-items-center rounded-md bg-charcoal text-ivory">
                        <IconGlyph name="plus" :size="11" :stroke="2.6" />
                    </span>
                </span>
            </div>
        </div>

        <!-- Carrito -->
        <div class="p-4 pt-3">
            <div class="flex items-center justify-between rounded-full bg-charcoal px-4 py-2.5 text-ivory">
                <span class="text-[0.6rem] opacity-70">{{ cart }}</span>
                <span class="font-sans text-[0.7rem] font-bold text-honey">{{ cta }}</span>
            </div>
        </div>
    </div>
</template>
