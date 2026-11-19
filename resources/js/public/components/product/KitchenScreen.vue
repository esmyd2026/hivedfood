<script setup>
/** Reconstrucción del tablero de cocina por columnas. */
defineProps({
    columns: {
        type: Array,
        default: () => [
            {
                name: 'Por preparar',
                tone: 'muted',
                tickets: [
                    { number: '045', items: ['2x Doble carne', '1x Papas grandes'], time: '1 min' },
                    { number: '046', items: ['1x Pizza familiar'], time: 'ahora' },
                ],
            },
            {
                name: 'En preparación',
                tone: 'honey',
                tickets: [
                    { number: '043', items: ['1x Combo del día', '2x Bebida'], time: '6 min' },
                    { number: '042', items: ['3x Alitas BBQ'], time: '9 min' },
                ],
            },
            {
                name: 'Listos',
                tone: 'green',
                tickets: [{ number: '041', items: ['1x Pizza mitad y mitad'], time: 'retirar' }],
            },
        ],
    },
});

const HEAD_TONES = {
    muted: 'bg-charcoal/10 text-charcoal/60',
    honey: 'bg-honey text-charcoal',
    green: 'bg-charcoal text-honey',
};
</script>

<template>
    <div class="flex h-full gap-2 bg-ivory p-3 text-charcoal" aria-hidden="true">
        <div v-for="column in columns" :key="column.name" class="flex min-w-0 flex-1 flex-col">
            <div
                :class="['rounded-md px-2 py-1 text-center text-[0.55rem] font-bold', HEAD_TONES[column.tone] ?? HEAD_TONES.muted]"
            >
                {{ column.name }}
            </div>

            <div class="mt-1.5 space-y-1.5">
                <div
                    v-for="ticket in column.tickets"
                    :key="ticket.number"
                    class="rounded-lg bg-white p-2 shadow-[0_1px_2px_rgba(32,42,36,0.06)]"
                >
                    <div class="flex items-baseline justify-between">
                        <span class="font-sans text-[0.72rem] font-extrabold">{{ ticket.number }}</span>
                        <span class="text-[0.5rem] text-charcoal/45">{{ ticket.time }}</span>
                    </div>

                    <ul class="mt-1 space-y-0.5">
                        <li v-for="item in ticket.items" :key="item" class="truncate text-[0.55rem] text-charcoal/65">
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
