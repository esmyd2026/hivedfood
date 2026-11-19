<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import AdminIcon from './AdminIcon.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    wide: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

function onKey(event) {
    if (event.key === 'Escape' && props.open) {
        emit('close');
    }
}

onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKey);
    document.body.style.removeProperty('overflow');
});

watch(
    () => props.open,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
    },
);
</script>

<template>
    <Transition name="modal">
        <div v-if="open" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-charcoal/55 backdrop-blur-sm" aria-hidden="true" @click="emit('close')" />

            <div class="relative flex min-h-full items-start justify-center p-4 md:p-8">
                <div
                    :class="['relative w-full rounded-2xl bg-white shadow-2xl', wide ? 'max-w-4xl' : 'max-w-2xl']"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-charcoal/8 px-6 py-5">
                        <div>
                            <h2 class="text-lg">{{ title }}</h2>
                            <p v-if="subtitle" class="mt-1 text-xs text-charcoal/55">{{ subtitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="grid h-9 w-9 place-items-center rounded-lg text-charcoal/50 transition hover:bg-ivory hover:text-charcoal"
                            aria-label="Cerrar"
                            @click="emit('close')"
                        >
                            <AdminIcon name="close" :size="18" />
                        </button>
                    </div>

                    <div class="px-6 py-6">
                        <slot />
                    </div>

                    <div v-if="$slots.footer" class="flex flex-wrap items-center justify-end gap-2 border-t border-charcoal/8 bg-ivory/60 px-6 py-4">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
