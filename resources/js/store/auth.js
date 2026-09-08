import { defineStore } from 'pinia';
import { http } from '../api/http';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        checked: false,
    }),

    getters: {
        isAuthenticated: (state) => state.user !== null,
    },

    actions: {
        async loadCurrentUser() {
            const data = await http.get('/api/me');

            this.user = data.user;
            this.checked = true;
        },

        async login(email, password) {
            const data = await http.post('/login', { email, password });

            this.user = data.user;
            this.checked = true;
        },

        async logout() {
            await http.post('/logout');

            this.clearSession();
        },

        clearSession() {
            this.user = null;
            this.checked = true;
        },
    },
});

export default useAuthStore;