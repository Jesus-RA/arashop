import { beforeEach, describe, expect, it } from 'vitest';
import { defaults, getDefaultTheme, themes } from '../theme';

describe('theme', () => {
    const reserved = [
        'primary',
        'secondary',
        'error',
        'warning',
        'success',
        'info',
        'surface',
        'background',
        'on-surface',
    ];

    beforeEach(() => {
        localStorage.clear();
    });

    it('defines light and dark themes', () => {
        expect(themes.light.dark).toBe(false);
        expect(themes.dark.dark).toBe(true);
    });

    it('maps every reserved word to a color in both themes', () => {
        for (const theme of [themes.light, themes.dark]) {
            for (const word of reserved) {
                expect(theme.colors[word], `${word} in ${theme.dark ? 'dark' : 'light'}`).toMatch(/^#[0-9a-f]{6}$/i);
            }
        }
    });

    it('uses the Stitch rose primary in light mode', () => {
        expect(themes.light.colors.primary).toBe('#c24566');
    });

    it('exposes the Stitch brand tokens as colors', () => {
        for (const token of ['rose-deep', 'rose-pastel', 'mint-seafoam', 'mint-deep', 'border-warm', 'peach-warm', 'cream-champagne']) {
            expect(themes.light.colors[token], token).toMatch(/^#[0-9a-f]{6}$/i);
        }
    });

    it('nudges core components toward the Stitch look', () => {
        expect(defaults.VCard).toEqual({ rounded: 'lg', elevation: 1 });
        expect(defaults.VTextField.variant).toBe('outlined');
        expect(defaults.VChip.rounded).toBe('pill');
    });

    it('resolves the persisted theme from localStorage', () => {
        expect(getDefaultTheme()).toBe('light');

        localStorage.setItem('arashop.theme', 'dark');

        expect(getDefaultTheme()).toBe('dark');
    });
});