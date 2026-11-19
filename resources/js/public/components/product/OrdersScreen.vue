<script setup>
import IconGlyph from '../ui/IconGlyph.vue';

/**
 * Reconstrucción del tablero de pedidos: la pantalla donde convergen todos
 * los canales, con el número del día, el canal de origen y el estado.
 */
defineProps({
    title: { type: String, default: 'Pedidos de hoy' },
    branch: { type: String, default: 'Sucursal Centro' },
    orders: {
        type: Array,
        default: () => [
            { number: '042', client: 'María L.', channel: 'WhatsApp', total: '18,40', state: 'En preparación', tone: 'honey' },
            { number: '041', client: 'Carlos R.', channel: 'Tienda', total: '24,90', state: 'Listo', tone: 'green' },
            { number: '040', client: 'Ana P.', channel: 'Mostrador', total: '9,50', state: 'Entregado', tone: 'muted' },
            { number: '039', client: 'Jorge M.', channel: 'Marketplace', total: '31,20', state: 'En camino', tone: 'coral' },
        ],
    },
});

const STATE_TONES = {
    honey: 'bg-honey text-charcoal',
    green: 'bg-charcoal text-honey',
    coral: 'bg-coral text-white',
    muted: 'bg-charcoal/10 text-charcoal/60',
};

const NAV = ['Pedidos', 'Cocina', 'Catálogo', 'Delivery', 'Reportes'];
</script>

<template>
    <div class="flex h-full bg-ivory text-charcoal" aria-hidden="true">
        <!-- Menú lateral -->
        <div class="hidden w-[22%] shrink-0 flex-col bg-white px-3 py-4 sm:flex">
            <div class="flex items-center gap-1.5 px-1">
                <span class="grid h-5 w-5 place-items-center rounded bg-honey">
                    <IconGlyph name="storefront" :size="11" class="text-charcoal" />
                </span>
                <span class="font-sans text-[0.6rem] font-extrabold">Panel</span>
            </div>

            <div class="mt-4 space-y-1">
                <div
                    v-for="(item, i) in NAV"
                    :key="item"
                    :class="[
                        'rounded-md px-2 py-1.5 text-[0.58rem] font-semibold',
                        i === 0 ? 'bg-charcoal text-ivory' : 'text-charcoal/45',
                    ]"
                >
                    {{ item }}
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="flex min-w-0 flex-1 flex-col px-4 py-4">
            <div class="flex items-baseline justify-between gap-2">
                <p class="font-sans text-[0.8rem] font-extrabold">{{ title }}</p>
                <span class="rounded-full bg-white px-2 py-0.5 text-[0.55rem] text-charcoal/55">{{ branch }}</span>
            </div>

            <!-- Encabezados -->
            <div class="mt-3 grid grid-cols-[2.2rem_1fr_auto] gap-2 border-b border-charcoal/10 pb-1.5 text-[0.52rem] font-bold uppercase tracking-wider text-charcoal/35">
                <span>Nº</span>
                <span>Pedido</span>
                <span>Estado</span>
            </div>

            <!-- Filas -->
            <div class="flex-1 divide-y divide-charcoal/6">
                <div
                    v-for="order in orders"
                    :key="order.number"
                    class="grid grid-cols-[2.2rem_1fr_auto] items-center gap-2 py-2"
                >
                    <span class="font-sans text-[0.72rem] font-extrabold text-charcoal/80">{{ order.number }}</span>

                    <span class="min-w-0">
                        <span class="block truncate text-[0.62rem] font-semibold">{{ order.client }}</span>
                        <span class="mt-0.5 flex items-center gap-1.5">
                            <span class="rounded bg-charcoal/8 px-1.5 py-px text-[0.5rem] font-bold uppercase tracking-wide text-charcoal/55">
                                {{ order.channel }}
                            </span>
                            <span class="text-[0.55rem] text-charcoal/45">${{ order.total }}</span>
                        </span>
                    </span>

                    <span
                        :class="['shrink-0 rounded-full px-2 py-0.5 text-[0.52rem] font-bold', STATE_TONES[order.tone] ?? STATE_TONES.muted]"
                    >
                        {{ order.state }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
