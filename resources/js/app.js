import { createApp } from 'vue';

import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import '@mdi/font/css/materialdesignicons.css';

import App from './App.vue';
import router from './router';
import pinia from './store';
import { defaults, getDefaultTheme, themes } from './theme';

const vuetify = createVuetify({
    components,
    directives,
    defaults,
    theme: {
        defaultTheme: getDefaultTheme(),
        themes,
    },
});

createApp(App).use(pinia).use(router).use(vuetify).mount('#app');