<script setup>
import IconGlyph from '../ui/IconGlyph.vue';

/** Reconstrucción del catálogo: producto con sus grupos de opciones y extras. */
defineProps({
    product: { type: String, default: 'Hamburguesa doble carne' },
    price: { type: String, default: '8,90' },
    groups: {
        type: Array,
        default: () => [
            { name: 'Punto de la carne', rule: 'Elige 1', options: ['Término medio', 'Bien cocida'] },
            { name: 'Extras', rule: 'Hasta 4', options: ['Queso +$0,80', 'Tocino +$1,20', 'Huevo +$0,70'] },
            { name: 'Acompañamiento', rule: 'Elige 1', options: ['Papas', 'Ensalada'] },
        ],
    },
});
</script>

<template>
    <div class="flex h-full bg-ivory text-charcoal" aria-hidden="true">
        <!-- Árbol de categorías -->
        <div class="hidden w-[26%] shrink-0 border-r border-charcoal/8 bg-white px-3 py-4 sm:block">
            <p class="px-1 text-[0.5rem] font-bold uppercase tracking-wider text-charcoal/35">Categorías</p>

            <div class="mt-2 space-y-0.5">
                <div class="rounded-md bg-charcoal px-2 py-1.5 text-[0.58rem] font-semibold text-ivory">Hamburguesas</div>
                <div v-for="c in ['Pizzas', 'Alitas', 'Bebidas', 'Postres']" :key="c" class="px-2 py-1.5 text-[0.58rem] text-charcoal/45">
                    {{ c }}
                </div>
            </div>

            <div class="mt-4 flex items-center gap-1 rounded-md border border-dashed border-charcoal/20 px-2 py-1.5 text-[0.55rem] text-charcoal/40">
                <IconGlyph name="plus" :size="10" />
                Nueva categoría
            </div>
        </div>

        <!-- Ficha del producto -->
        <div class="flex min-w-0 flex-1 flex-col px-4 py-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-coral">
                        <IconGlyph name="burger" :size="18" class="text-white/90" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-sans text-[0.75rem] font-extrabold">{{ product }}</p>
                        <p class="mt-0.5 text-[0.55rem] text-charcoal/50">Disponible · 3 sucursales</p>
                    </div>
                </div>

                <span class="shrink-0 rounded-md bg-white px-2 py-1 font-sans text-[0.7rem] font-extrabold">${{ price }}</span>
            </div>

            <!-- Grupos de opciones -->
            <div class="mt-3.5 flex-1 space-y-2">
                <div v-for="group in groups" :key="group.name" class="rounded-lg bg-white p-2.5">
                    <div class="flex items-center justify-between">
                        <span class="font-sans text-[0.6rem] font-bold">{{ group.name }}</span>
                        <span class="rounded bg-honey/25 px-1.5 py-px text-[0.5rem] font-bold text-charcoal/65">{{ group.rule }}</span>
                    </div>

                    <div class="mt-1.5 flex flex-wrap gap-1">
                        <span
                            v-for="option in group.options"
                            :key="option"
                            class="rounded border border-charcoal/10 px-1.5 py-0.5 text-[0.52rem] text-charcoal/60"
                        >
                            {{ option }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
