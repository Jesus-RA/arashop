import { beforeEach, describe, expect, it } from 'vitest';
import { themeConfig } from '../theme/theme.config';
import { defaults, getDefaultTheme, themes } from '../theme/vuetify';

const resolve = (value, themeName) => (typeof value === 'string' ? value : value?.[themeName]);

describe('theme', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('defines light and dark themes', () => {
        expect(themes.light.dark).toBe(false);
        expect(themes.dark.dark).toBe(true);
    });

    it('maps every configured color into both themes', () => {
        for (const name of ['light', 'dark']) {
            for (const [color, value] of Object.entries(themeConfig.colors)) {
                expect(themes[name].colors[color], `${color} in ${name}`).toBe(resolve(value, name));
            }
        }
    });

    it('does not define a tertiary color', () => {
        expect(themeConfig.colors.tertiary).toBeUndefined();
        expect(themes.light.colors.tertiary).toBeUndefined();
    });

    it('exposes the custom premium semantic color', () => {
        expect(themes.light.colors.premium).toMatch(/^#[0-9a-f]{6}$/i);
        expect(themes.dark.colors.premium).toMatch(/^#[0-9a-f]{6}$/i);
    });

    it('merges configured structural colors into each theme', () => {
        for (const name of ['light', 'dark']) {
            for (const [token, value] of Object.entries(themeConfig[name]?.structural ?? {})) {
                expect(themes[name].colors[token], `${token} in ${name}`).toBe(value);
            }
        }
    });

    it('only exposes structural colors that are configured', () => {
        expect(themes.light.colors['on-surface']).toBeUndefined();
        expect(themes.dark.colors['on-surface']).toBeUndefined();
    });

    it('keeps validation errors distinguishable from the primary color in both themes', () => {
        expect(themes.light.colors.error).not.toBe(themes.light.colors.primary);
        expect(themes.dark.colors.error).not.toBe(themes.dark.colors.primary);
    });

    it('nudges core components toward the app look', () => {
        expect(defaults.VCard).toEqual({ rounded: 'lg', class: 'elevation-0.25' });
        expect(defaults.VTextField.variant).toBe('outlined');
        expect(defaults.VChip.rounded).toBe('pill');
    });

    it('resolves the persisted theme from localStorage', () => {
        expect(getDefaultTheme()).toBe('light');

        localStorage.setItem('arashop.theme', 'dark');

        expect(getDefaultTheme()).toBe('dark');
    });
});
