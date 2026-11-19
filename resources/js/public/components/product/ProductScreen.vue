<script setup>
import { computed } from 'vue';
import DeviceFrame from './DeviceFrame.vue';
import StoreScreen from './StoreScreen.vue';
import OrdersScreen from './OrdersScreen.vue';
import CatalogScreen from './CatalogScreen.vue';
import KitchenScreen from './KitchenScreen.vue';

const props = defineProps({
    /** store | orders | catalog | kitchen */
    screen: { type: String, default: 'orders' },
    /** Real screenshot; when present it replaces the mockup inside the frame. */
    image: { type: String, default: null },
    alt: { type: String, default: '' },
    label: { type: String, default: null },
    elevated: { type: Boolean, default: true },
});

const SCREENS = {
    store: { component: StoreScreen, device: 'phone', label: 'tutienda.com' },
    orders: { component: OrdersScreen, device: 'browser', label: 'panel.hivedfood.com/pedidos' },
    catalog: { component: CatalogScreen, device: 'browser', label: 'panel.hivedfood.com/catalogo' },
    kitchen: { component: KitchenScreen, device: 'browser', label: 'panel.hivedfood.com/cocina' },
};

const resolved = computed(() => SCREENS[props.screen] ?? SCREENS.orders);
</script>

<template>
    <DeviceFrame
        :type="resolved.device"
        :image="image"
        :alt="alt"
        :label="label ?? resolved.label"
        :elevated="elevated"
    >
        <component :is="resolved.component" />
    </DeviceFrame>
</template>
