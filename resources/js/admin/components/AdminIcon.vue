<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 18 },
    stroke: { type: [Number, String], default: 1.8 },
});

const PATHS = {
    dashboard:
        '<rect x="3.5" y="3.5" width="7" height="7.5" rx="1.4"/><rect x="13.5" y="3.5" width="7" height="4.5" rx="1.4"/><rect x="3.5" y="14" width="7" height="6.5" rx="1.4"/><rect x="13.5" y="11" width="7" height="9.5" rx="1.4"/>',
    image: '<rect x="3.5" y="4.5" width="17" height="15" rx="2.4"/><circle cx="9" cy="9.8" r="1.7"/><path d="m4.5 17.2 4.6-4.3 3.4 3.1 3-2.6 4 3.6"/>',
    layers: '<path d="m12 3.2 8.5 4.3L12 11.8 3.5 7.5Z"/><path d="m3.5 12 8.5 4.3 8.5-4.3M3.5 16.4l8.5 4.3 8.5-4.3"/>',
    spark: '<path d="M12 3v4M12 17v4M4.9 7.1l2.8 2.8M16.3 14.1l2.8 2.8M3 12h4M17 12h4M4.9 16.9l2.8-2.8M16.3 9.9l2.8-2.8"/>',
    storefront:
        '<path d="M3.5 9 5.2 4.9A1.5 1.5 0 0 1 6.6 4h10.8a1.5 1.5 0 0 1 1.4.9L20.5 9M3.5 9h17M3.5 9v10.5a.5.5 0 0 0 .5.5h16a.5.5 0 0 0 .5-.5V9M9.5 20v-5.5h5V20"/>',
    quote: '<path d="M9.5 6.5c-2.8 1-4.5 3.4-4.5 6.6V18h5.5v-5.4H7.8c0-1.9.7-3.3 2.3-4.1Zm9 0c-2.8 1-4.5 3.4-4.5 6.6V18h5.5v-5.4h-2.7c0-1.9.7-3.3 2.3-4.1Z"/>',
    help: '<circle cx="12" cy="12" r="8.5"/><path d="M9.6 9.3a2.5 2.5 0 1 1 3.4 2.3c-.6.3-1 .9-1 1.6v.4"/><circle cx="12" cy="16.6" r="1"/>',
    coins: '<ellipse cx="9" cy="7" rx="5.5" ry="2.6"/><path d="M3.5 7v4.4c0 1.4 2.5 2.6 5.5 2.6s5.5-1.2 5.5-2.6V7"/><path d="M14.5 10.6c3 .2 6 1.3 6 2.6v4.4c0 1.4-2.5 2.6-5.5 2.6s-5.5-1.2-5.5-2.6v-3.5"/>',
    receipt:
        '<path d="M5.5 3.5h13v17l-2.2-1.4-2.2 1.4-2.1-1.4-2.2 1.4-2.1-1.4-2.2 1.4Z"/><path d="M9 8.5h6M9 12.5h6"/>',
    users: '<circle cx="9.2" cy="8.4" r="3.3"/><path d="M3.5 19.6c0-3.1 2.6-5.3 5.7-5.3s5.7 2.2 5.7 5.3"/><path d="M16.2 5.6a3.3 3.3 0 0 1 0 6.3M17.5 14.6c1.9.6 3 2.4 3 4.4"/>',
    search: '<circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.4 15.4 4.6 4.6"/>',
    sliders:
        '<path d="M4 7.5h7M15 7.5h5M4 16.5h4M12 16.5h8"/><circle cx="13" cy="7.5" r="2.1"/><circle cx="10" cy="16.5" r="2.1"/>',
    close: '<path d="M6 6l12 12M18 6 6 18"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h12"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    check: '<path d="m5 12.5 4.6 4.5L19 7"/>',
    edit: '<path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3Z"/><path d="m14.5 6 3 3"/>',
    trash: '<path d="M4.5 6.5h15M9.5 6.5V4.8A1.3 1.3 0 0 1 10.8 3.5h2.4a1.3 1.3 0 0 1 1.3 1.3v1.7"/><path d="M6.5 6.5 7.6 19a1.5 1.5 0 0 0 1.5 1.4h5.8a1.5 1.5 0 0 0 1.5-1.4L17.5 6.5"/><path d="M10.5 10.5v6M13.5 10.5v6"/>',
    drag: '<circle cx="9" cy="6" r="1.4"/><circle cx="15" cy="6" r="1.4"/><circle cx="9" cy="12" r="1.4"/><circle cx="15" cy="12" r="1.4"/><circle cx="9" cy="18" r="1.4"/><circle cx="15" cy="18" r="1.4"/>',
    up: '<path d="M12 19V5M6 11l6-6 6 6"/>',
    down: '<path d="M12 5v14M18 13l-6 6-6-6"/>',
    'chevron-down': '<path d="m6 9.5 6 6 6-6"/>',
    'chevron-right': '<path d="m9.5 6 6 6-6 6"/>',
    external: '<path d="M14 4.5h5.5V10M19 5l-7.5 7.5"/><path d="M18 14v4.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>',
    logout: '<path d="M14.5 4.5h3A1.5 1.5 0 0 1 19 6v12a1.5 1.5 0 0 1-1.5 1.5h-3"/><path d="M11 8.5 14.5 12 11 15.5M4.5 12h10"/>',
    download: '<path d="M12 4v10.5M7.8 10.6 12 14.8l4.2-4.2"/><path d="M4.5 17.5v1.2A1.3 1.3 0 0 0 5.8 20h12.4a1.3 1.3 0 0 0 1.3-1.3v-1.2"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2.4"/><path d="m3.8 7 7.3 5.3a1.5 1.5 0 0 0 1.8 0L20.2 7"/>',
    eye: '<path d="M2.5 12S6 5.8 12 5.8 21.5 12 21.5 12 18 18.2 12 18.2 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.8"/>',
    'eye-off': '<path d="M4 4l16 16"/><path d="M9.5 6.4A9.6 9.6 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.6 3.3M7 7.9A15.6 15.6 0 0 0 2.5 12s3.5 6 9.5 6c1 0 1.9-.2 2.7-.5"/>',
    alert: '<path d="M12 4.5 21 19.5H3Z"/><path d="M12 10v4.2"/><circle cx="12" cy="17" r=".9"/>',
    clock: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.2V12l3.2 2"/>',
    copy: '<rect x="8.5" y="8.5" width="11.5" height="11.5" rx="2.2"/><path d="M15.5 8.5v-2a2.2 2.2 0 0 0-2.2-2.2H6.2A2.2 2.2 0 0 0 4 6.5v7.1a2.2 2.2 0 0 0 2.2 2.2h2"/>',
    refresh: '<path d="M20 11a8 8 0 1 0-.6 4.3"/><path d="M20.5 5.5V11H15"/>',
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
        class="shrink-0"
        v-html="inner"
    />
</template>
