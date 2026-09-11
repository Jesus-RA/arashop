import { defineStore } from 'pinia';

export const useUiStore = defineStore('ui', {
    state: () => ({
        visible: false,
        message: '',
        color: 'success',
        timeout: 3000,
    }),

    actions: {
        notify(message, { color = 'success', timeout = 3000 } = {}) {
            this.message = message;
            this.color = color;
            this.timeout = timeout;
            this.visible = true;
        },
    },
});

export default useUiStore;