<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

const store = useSiteStore();
const { settings } = storeToRefs(store);

const visible = ref(false);

function onScroll() {
    visible.value = window.scrollY > 420;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <Transition name="float">
        <a
            v-if="settings.whatsapp_url && visible"
            :href="settings.whatsapp_url"
            target="_blank"
            rel="noopener noreferrer"
            class="group fixed bottom-5 right-5 z-40 flex items-center gap-3 rounded-full bg-charcoal py-3 pl-3 pr-4 text-ivory shadow-xl transition hover:bg-charcoal-700 md:bottom-7 md:right-7"
            aria-label="Escríbenos por WhatsApp"
        >
            <span class="grid h-10 w-10 place-items-center rounded-full bg-honey text-charcoal">
                <IconGlyph name="whatsapp" :size="20" />
            </span>
            <span class="hidden font-sans text-sm font-bold sm:block">
                {{ store.text('whatsapp_float_label', 'Hablemos por WhatsApp') }}
            </span>
        </a>
    </Transition>
</template>

<style scoped>
.float-enter-active,
.float-leave-active {
    transition:
        opacity 0.3s ease,
        transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.float-enter-from,
.float-leave-to {
    opacity: 0;
    transform: translateY(14px) scale(0.96);
}
</style>
