import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './public/App.vue';
import { useSiteStore } from './public/stores/site';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);

// The payload is injected by Laravel into the document, so the first paint
// needs no API round trip. The REST endpoint stays available as a fallback.
const store = useSiteStore(pinia);
store.hydrate(window.__HIVEDFOOD__ ?? null, window.__HIVEDFOOD_ROUTES__ ?? {});

app.mount('#app');
