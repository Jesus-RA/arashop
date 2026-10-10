import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./resources/js/tests/setup.js'],
        include: ['resources/js/**/*.test.js'],
        css: true,
        server: {
            deps: {
                inline: ['vuetify'],
            },
        },
    },
});