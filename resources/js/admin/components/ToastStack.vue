<script setup>
import { useUiStore } from '../stores/ui';
import AdminIcon from './AdminIcon.vue';

const ui = useUiStore();
</script>

<template>
    <div class="pointer-events-none fixed bottom-5 right-5 z-50 flex w-full max-w-sm flex-col gap-2" role="status" aria-live="polite">
        <TransitionGroup name="toast">
            <div
                v-for="toast in ui.toasts"
                :key="toast.id"
                :class="[
                    'pointer-events-auto flex items-start gap-3 rounded-xl px-4 py-3 text-sm font-medium shadow-lg',
                    toast.type === 'error' ? 'bg-coral text-white' : 'bg-charcoal text-ivory',
                ]"
            >
                <AdminIcon :name="toast.type === 'error' ? 'alert' : 'check'" :size="17" class="mt-0.5" />
                <span class="flex-1">{{ toast.message }}</span>
                <button type="button" class="opacity-60 transition hover:opacity-100" aria-label="Cerrar" @click="ui.dismiss(toast.id)">
                    <AdminIcon name="close" :size="15" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(24px);
}
</style>
