import { defineStore } from 'pinia';

let nextId = 1;

export const useUiStore = defineStore('ui', {
    state: () => ({
        toasts: [],
        sidebarOpen: false,
    }),

    actions: {
        notify(message, type = 'success') {
            const id = nextId++;
            this.toasts.push({ id, message, type });

            setTimeout(() => this.dismiss(id), type === 'error' ? 7000 : 4000);
        },

        success(message) {
            this.notify(message, 'success');
        },

        error(message) {
            this.notify(message, 'error');
        },

        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    },
});
