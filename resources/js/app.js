import { createApp } from 'vue';

import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import '@mdi/font/css/materialdesignicons.css';

import App from './App.vue';
import router from './router';
import pinia from './store';

const preferredTheme = localStorage.getItem('arashop.theme') === 'dark' ? 'dark' : 'light';

const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: preferredTheme,
        themes: {
            light: {
                dark: false,
                colors: { primary: '#1867C0' },
            },
            dark: {
                dark: true,
                colors: { primary: '#90CAF9' },
            },
        },
    },
});

createApp(App).use(pinia).use(router).use(vuetify).mount('#app');