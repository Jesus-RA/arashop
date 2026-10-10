import { defineStore } from 'pinia';
import { http } from '../api/http';

export const useSettingsStore = defineStore('settings', {
    state: () => ({
        checked: false,
        fxUsdToMxn: 18.0,
    }),

    actions: {
        async load() {
            if (this.checked) {
                return;
            }

            try {
                const data = await http.get('/api/settings');
                this.fxUsdToMxn = Number(data['fx.usd_to_mxn']) || 18.0;
                this.checked = true;
            } catch {
                // keep the fallback rate when settings are unavailable
            }
        },
    },
});

export default useSettingsStore;