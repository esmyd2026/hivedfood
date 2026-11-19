<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 24 },
    stroke: { type: [Number, String], default: 1.7 },
});

/**
 * Hand-built 24x24 stroke icon set. Keeping the paths here avoids shipping an
 * icon library for the handful of glyphs the site actually uses.
 */
const PATHS = {
    storefront:
        '<path d="M3.5 9 5.2 4.9A1.5 1.5 0 0 1 6.6 4h10.8a1.5 1.5 0 0 1 1.4.9L20.5 9M3.5 9h17M3.5 9v10.5a.5.5 0 0 0 .5.5h16a.5.5 0 0 0 .5-.5V9M3.5 9a2.8 2.8 0 0 0 5.6 0 2.8 2.8 0 0 0 5.6 0 2.8 2.8 0 0 0 5.8 0M9.5 20v-5.5h5V20"/>',
    palette:
        '<path d="M12 3.5a8.5 8.5 0 0 0 0 17c1.1 0 1.8-.8 1.8-1.7 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-1 .8-1.7 1.8-1.7h1.6A3.8 3.8 0 0 0 20.5 11c0-4.2-3.8-7.5-8.5-7.5Z"/><circle cx="7.6" cy="11.4" r="1.1"/><circle cx="10.4" cy="7.6" r="1.1"/><circle cx="15" cy="8.4" r="1.1"/>',
    dashboard:
        '<rect x="3.5" y="3.5" width="7" height="7.5" rx="1.4"/><rect x="13.5" y="3.5" width="7" height="4.5" rx="1.4"/><rect x="3.5" y="14" width="7" height="6.5" rx="1.4"/><rect x="13.5" y="11" width="7" height="9.5" rx="1.4"/>',
    'check-badge':
        '<path d="m12 3 2.2 1.6 2.7-.2.9 2.6 2.2 1.6-.9 2.6.9 2.6-2.2 1.6-.9 2.6-2.7-.2L12 21l-2.2-1.6-2.7.2-.9-2.6L4 15.4l.9-2.6L4 10.2l2.2-1.6.9-2.6 2.7.2Z"/><path d="m9.2 12.2 2 2 3.6-3.9"/>',
    percent: '<path d="M19 5 5 19"/><circle cx="7.8" cy="7.8" r="2.6"/><circle cx="16.2" cy="16.2" r="2.6"/>',
    chat: '<path d="M20.5 11.6c0 3.9-3.8 7-8.5 7-1 0-2-.1-2.9-.4l-5.1 1.6 1.7-4.2a6.4 6.4 0 0 1-2.2-4.7c0-3.9 3.8-7.1 8.5-7.1s8.5 3.2 8.5 7.8Z"/>',
    list: '<path d="M9 6.5h11M9 12h11M9 17.5h11"/><circle cx="4.6" cy="6.5" r="1.1"/><circle cx="4.6" cy="12" r="1.1"/><circle cx="4.6" cy="17.5" r="1.1"/>',
    sparkle:
        '<path d="M12 3.2 13.7 9l5.8 1.7-5.8 1.7L12 18.2 10.3 12.4 4.5 10.7 10.3 9Z"/><path d="M18.4 3.5v3M16.9 5h3"/>',
    coins: '<ellipse cx="9" cy="7" rx="5.5" ry="2.6"/><path d="M3.5 7v4.4c0 1.4 2.5 2.6 5.5 2.6s5.5-1.2 5.5-2.6V7"/><path d="M14.5 10.6c3 .2 6 1.3 6 2.6v4.4c0 1.4-2.5 2.6-5.5 2.6s-5.5-1.2-5.5-2.6v-3.5"/>',
    users: '<circle cx="9.2" cy="8.4" r="3.3"/><path d="M3.5 19.6c0-3.1 2.6-5.3 5.7-5.3s5.7 2.2 5.7 5.3"/><path d="M16.2 5.6a3.3 3.3 0 0 1 0 6.3M17.5 14.6c1.9.6 3 2.4 3 4.4"/>',
    sliders:
        '<path d="M4 7.5h7M15 7.5h5M4 16.5h4M12 16.5h8"/><circle cx="13" cy="7.5" r="2.1"/><circle cx="10" cy="16.5" r="2.1"/>',
    mobile: '<rect x="6.5" y="2.8" width="11" height="18.4" rx="2.6"/><path d="M10.4 5.6h3.2"/><path d="M11 18.4h2"/>',
    flow: '<circle cx="6" cy="6.5" r="2.6"/><circle cx="18" cy="6.5" r="2.6"/><circle cx="12" cy="17.5" r="2.6"/><path d="M8.6 6.5h6.8M6.9 9l3.8 6.2M17.1 9l-3.8 6.2"/>',
    growth: '<path d="M3.5 18.5 9 12.6l3.6 3.3 7.9-9"/><path d="M15.5 6.9h5v5"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h10"/>',
    image: '<rect x="3.5" y="4.5" width="17" height="15" rx="2.4"/><circle cx="9" cy="9.8" r="1.7"/><path d="m4.5 17.2 4.6-4.3 3.4 3.1 3-2.6 4 3.6"/>',
    text: '<path d="M4.5 6.5h15M4.5 11h11M4.5 15.5h15M4.5 20h8"/>',
    receipt:
        '<path d="M5.5 3.5h13v17l-2.2-1.4-2.2 1.4-2.1-1.4-2.2 1.4-2.1-1.4-2.2 1.4Z"/><path d="M9 8.5h6M9 12.5h6"/>',
    card: '<rect x="3" y="5.5" width="18" height="13" rx="2.4"/><path d="M3 10h18M6.5 14.5h3"/>',
    map: '<path d="M12 21s6.5-5.6 6.5-10.3A6.5 6.5 0 1 0 5.5 10.7C5.5 15.4 12 21 12 21Z"/><circle cx="12" cy="10.4" r="2.4"/>',
    tag: '<path d="M11.2 3.5H20v8.8l-8.3 8.3a1.6 1.6 0 0 1-2.3 0l-6.5-6.5a1.6 1.6 0 0 1 0-2.3Z"/><circle cx="16.2" cy="7.8" r="1.4"/>',
    chart: '<path d="M4 20h16"/><rect x="5.5" y="11" width="3.4" height="6" rx="1.1"/><rect x="10.8" y="6.5" width="3.4" height="10.5" rx="1.1"/><rect x="16" y="9" width="3.4" height="8" rx="1.1"/>',
    building:
        '<path d="M4.5 20.5V5.4a1.4 1.4 0 0 1 1-1.4l7-1.5a1 1 0 0 1 1.2 1v17M4.5 20.5h15M13.7 8.5h4.4a1.4 1.4 0 0 1 1.4 1.4v10.6"/><path d="M7.8 8h2.6M7.8 12h2.6M7.8 16h2.6"/>',
    globe: '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.3 2.4 3.5 5.4 3.5 8.5s-1.2 6.1-3.5 8.5c-2.3-2.4-3.5-5.4-3.5-8.5S9.7 5.9 12 3.5Z"/>',
    rocket: '<path d="M13.5 3.5c3.6 1 6 4.4 6 8.1l-4.2 4.2-5.6-5.6 3.8-6.7Z"/><path d="M9.7 10.2 5.5 11l-1.3 2.6 3.4 1M13.7 15.8l-.8 4.2-2.6 1.3-1-3.4"/><circle cx="14.8" cy="8.6" r="1.4"/>',
    headset:
        '<path d="M4.5 14v-2a7.5 7.5 0 0 1 15 0v2"/><rect x="3" y="13" width="4" height="6" rx="1.8"/><rect x="17" y="13" width="4" height="6" rx="1.8"/><path d="M19.5 19v.6a2.4 2.4 0 0 1-2.4 2.4H13"/>',
    refresh: '<path d="M20 11a8 8 0 1 0-.6 4.3"/><path d="M20.5 5.5V11H15"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    layers: '<path d="m12 3.2 8.5 4.3L12 11.8 3.5 7.5Z"/><path d="m3.5 12 8.5 4.3 8.5-4.3M3.5 16.4l8.5 4.3 8.5-4.3"/>',
    restaurant:
        '<path d="M6.5 3v7.2a2.2 2.2 0 0 0 4.4 0V3M8.7 10.4V21"/><path d="M16.8 3c-1.5 1.2-2.3 3-2.3 5.2 0 1.6.9 2.8 2.3 3.2V21"/>',
    burger: '<path d="M4 8.5c0-2.8 3.6-4.5 8-4.5s8 1.7 8 4.5Z"/><path d="M4 12.2h16M4.6 15.8h14.8"/><path d="M4.4 19.5h15.2a2 2 0 0 0 0-3.7H4.4a2 2 0 0 0 0 3.7Z"/>',
    pizza: '<path d="M12 3.5 20.5 19a1 1 0 0 1-1.3 1.3L12 17.6l-7.2 2.7A1 1 0 0 1 3.5 19Z"/><circle cx="11.3" cy="10.5" r="1.1"/><circle cx="14.1" cy="15" r="1.1"/><circle cx="9" cy="15.4" r="1.1"/>',
    'fast-food':
        '<path d="M5.5 8.5h13l-1.6 11a1.5 1.5 0 0 1-1.5 1.3H8.6a1.5 1.5 0 0 1-1.5-1.3Z"/><path d="M8.5 8.5V6a3.5 3.5 0 0 1 7 0v2.5M7 12.5h10"/>',
    coffee: '<path d="M4.5 7.5h12v6.8a4.5 4.5 0 0 1-9 0Z"/><path d="M16.5 9.2h1.8a2.6 2.6 0 0 1 0 5.2h-1.8M4 20.5h13"/>',
    'ice-cream':
        '<path d="M8 10.8a4 4 0 1 1 8 0"/><path d="M7.5 11h9L12.6 21a.7.7 0 0 1-1.2 0Z"/><path d="M8.8 14.6h6.4"/>',
    more: '<circle cx="5.5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="18.5" cy="12" r="1.6"/>',
    'arrow-right': '<path d="M4.5 12h15M13.5 6l6 6-6 6"/>',
    'arrow-left': '<path d="M19.5 12h-15M10.5 18l-6-6 6-6"/>',
    'chevron-down': '<path d="m6 9.5 6 6 6-6"/>',
    'chevron-right': '<path d="m9.5 6 6 6-6 6"/>',
    check: '<path d="m5 12.5 4.6 4.5L19 7"/>',
    close: '<path d="M6 6l12 12M18 6 6 18"/>',
    star: '<path d="m12 3.6 2.6 5.4 5.9.8-4.3 4.2 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 9.8l5.9-.8Z"/>',
    quote: '<path d="M9.5 6.5c-2.8 1-4.5 3.4-4.5 6.6V18h5.5v-5.4H7.8c0-1.9.7-3.3 2.3-4.1Zm9 0c-2.8 1-4.5 3.4-4.5 6.6V18h5.5v-5.4h-2.7c0-1.9.7-3.3 2.3-4.1Z"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2.4"/><path d="m3.8 7 7.3 5.3a1.5 1.5 0 0 0 1.8 0L20.2 7"/>',
    phone: '<path d="M7 3.5h2.4l1.6 4-2 1.4a11 11 0 0 0 5.1 5.1l1.4-2 4 1.6V16a3.5 3.5 0 0 1-3.5 3.5C9.6 19.5 4.5 14.4 4.5 7A3.5 3.5 0 0 1 7 3.5Z"/>',
    whatsapp:
        '<path d="M3.8 20.2 5 16.4a7.9 7.9 0 1 1 3 2.9Z"/><path d="M9 9.2c0 3 2.4 5.3 5.2 5.4.6 0 1.2-.4 1.3-1l.1-.7-2-.9-.8.9a4.6 4.6 0 0 1-1.9-1.9l.9-.8-.9-2-.7.1c-.6.1-1.1.6-1.2 1.3Z"/>',
    external: '<path d="M14 4.5h5.5V10M19 5l-7.5 7.5"/><path d="M18 14v4.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>',
    pin: '<path d="M12 21s6-5.2 6-9.6A6 6 0 1 0 6 11.4C6 15.8 12 21 12 21Z"/><circle cx="12" cy="11.2" r="2.2"/>',
    clock: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.2V12l3.2 2"/>',
    download: '<path d="M12 4v10.5M7.8 10.6 12 14.8l4.2-4.2"/><path d="M4.5 17.5v1.2A1.3 1.3 0 0 0 5.8 20h12.4a1.3 1.3 0 0 0 1.3-1.3v-1.2"/>',
    shield: '<path d="M12 3.2 20 6v6c0 4.3-3.3 7.6-8 8.8-4.7-1.2-8-4.5-8-8.8V6Z"/><path d="m9 12 2.2 2.2L15.4 10"/>',
    spark: '<path d="M12 3v4M12 17v4M4.9 7.1l2.8 2.8M16.3 14.1l2.8 2.8M3 12h4M17 12h4M4.9 16.9l2.8-2.8M16.3 9.9l2.8-2.8"/>',
};

const inner = computed(() => PATHS[props.name] ?? PATHS.spark);
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        :stroke-width="stroke"
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
        v-html="inner"
    />
</template>
