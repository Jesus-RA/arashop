import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

import { themeConfig } from './theme.config';

const THEME_STORAGE_KEY = 'arashop.theme';

/** Resolve the persisted theme, defaulting to 'light'. */
export function getDefaultTheme() {
    return localStorage.getItem(THEME_STORAGE_KEY) === 'dark' ? 'dark' : 'light';
}

/** Drop `undefined` entries so omitted colors never overwrite Vuetify defaults. */
const defined = (source = {}) =>
    Object.fromEntries(Object.entries(source).filter(([, value]) => value !== undefined));

/** Resolve shared hexes and `{ light, dark }` entries for a single theme. */
const resolveColors = (themeName) =>
    defined(
        Object.fromEntries(
            Object.entries(themeConfig.colors).map(([name, value]) => [
                name,
                typeof value === 'string' ? value : value?.[themeName],
            ]),
        ),
    );

const buildTheme = (themeName, dark) => ({
    dark,
    colors: {
        ...resolveColors(themeName),
        ...defined(themeConfig[themeName]?.structural),
    },
});

export const themes = {
    light: buildTheme('light', false),
    dark: buildTheme('dark', true),
};

/** Component-level defaults that nudge the UI without changing usage. */
export const defaults = {
    VCard: { rounded: 'lg', class: 'elevation-0.25' },
    VDialog: { rounded: 'xl' },
    VBtn: { rounded: 'md' },
    VChip: { rounded: 'pill' },
    VTextField: { variant: 'outlined', color: 'primary', density: 'compact', hideDetails: 'auto' },
    VSelect: { variant: 'outlined', color: 'primary', density: 'compact', hideDetails: 'auto' },
    VAutocomplete: { variant: 'outlined', density: 'compact', hideDetails: 'auto' },
    VCombobox: { variant: 'outlined', color: 'primary' },
    VTextarea: { variant: 'outlined', color: 'primary', hideDetails: 'auto' },
    VFileUpload: { density: 'compact' },
    VDataTableServer: { hover: true },
    VDataTableVirtual: { hover: true },
    VDataTable: { hover: true },
};

export const vuetify = createVuetify({
    components,
    directives,
    defaults,
    theme: {
        defaultTheme: getDefaultTheme(),
        themes,
        variations: {
            colors: Object.keys(themeConfig.colors),
            lighten: themeConfig.variations.lighten,
            darken: themeConfig.variations.darken,
        },
    },
});
