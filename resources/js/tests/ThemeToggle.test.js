import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { createVuetify } from 'vuetify';
import ThemeToggle from '../components/ThemeToggle.vue';

const stubComponents = {
    'v-btn': { template: '<button type="button"><slot /></button>' },
    'v-icon': { template: '<span><slot /></span>' },
};

function createTestVuetify() {
    return createVuetify({
        theme: {
            defaultTheme: 'light',
            themes: {
                light: { dark: false },
                dark: { dark: true },
            },
        },
    });
}

function mountToggle() {
    const vuetify = createTestVuetify();
    const wrapper = mount(ThemeToggle, {
        global: {
            plugins: [vuetify],
            stubs: stubComponents,
        },
    });

    return { wrapper, vuetify };
}

async function click(wrapper) {
    await wrapper.find('button').trigger('click');
}

describe('ThemeToggle', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('starts in the light theme', () => {
        const { wrapper, vuetify } = mountToggle();

        expect(vuetify.theme.global.name.value).toBe('light');
        expect(wrapper.find('button').attributes('aria-label')).toBe('Switch to dark mode');
    });

    it('switches to dark mode and persists the choice', async () => {
        const { wrapper, vuetify } = mountToggle();
        await click(wrapper);

        expect(vuetify.theme.global.name.value).toBe('dark');
        expect(localStorage.getItem('arashop.theme')).toBe('dark');
        expect(wrapper.find('button').attributes('aria-label')).toBe('Switch to light mode');
    });

    it('switches back to light mode', async () => {
        const { wrapper, vuetify } = mountToggle();
        await click(wrapper);
        await click(wrapper);

        expect(vuetify.theme.global.name.value).toBe('light');
        expect(localStorage.getItem('arashop.theme')).toBe('light');
    });
});