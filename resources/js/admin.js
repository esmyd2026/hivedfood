import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './admin/router';
import AdminApp from './admin/AdminApp.vue';

createApp(AdminApp).use(createPinia()).use(router).mount('#admin-app');
