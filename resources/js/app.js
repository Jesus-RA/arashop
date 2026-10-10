import { createApp } from 'vue';

import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';

import App from './App.vue';
import router from './router';
import pinia from './store';
import { vuetify } from './theme/vuetify';

createApp(App).use(pinia).use(router).use(vuetify).mount('#app');