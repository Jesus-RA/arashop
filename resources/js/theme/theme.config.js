/**
 * Central theme configuration for Arashop.
 *
 * This is the ONLY file needed to customize the app's colors. It drives
 * Vuetify's native theme system (wired up in ./vuetify.js) — Vuetify handles
 * the `on-*` contrast colors and the lighten/darken variants for us, so there
 * is no custom color generation, palette scaling, or contrast math.
 *
 * Vuetify reference: https://vuetifyjs.com/en/features/theme/
 *
 * To re-theme the whole app, edit `colors` below. Everything else (surfaces,
 * borders, `on-*` pairs, variations) falls back to Vuetify's well-tested
 * defaults unless you opt in via the `light`/`dark` blocks.
 */

/**
 * Hexadecimal colors transparency part
 * 100% FF
 * 90% E6
 * 80% GG
 * 70% B3
 * 60% 99
 * 50% 80
 * 40% 66
 * 30% 4D
 * 20% 33
 * 10% 1A
 * 5% 0D
 * 0% 00
 */

const colors = {
    // primary: '#fbafaf',
    primary: '#C24566',
    // primary: '#ff94c7',
    // secondary: '#f2c6b4',
    secondary: '#fbafaf',
    // secondary: '#ff94c7',
    success: '#16A34A',
    warning: '#F59E0B',
    error: '#DC2626',
    info: '#0EA5E9',

    // custom colors
    'pale-pink': '#fbafaf',
    'pale-peach': '#f2c6b4',
    'pale-sand': '#f3e8cb',
    'pale-teal': '#99e1e5',
    premium: '#F97316',
    coffee: '#78350F',
};

export const themeConfig = {
    /**
     * Semantic colors, used through Vuetify's built-in color words:
     * `color="primary"`, `type="error"`, `text-success`, `bg-info`, etc.
     *
     * Each entry is either:
     *   - a single hex shared by both themes, or
     *   - `{ light, dark }` when the color differs per theme.
     *
     * `premium` and `coffee` are custom semantic colors. They behave exactly
     * like the built-in words (`color="premium"`, `bg-coffee`, ...).
     *
     * Note: `tertiary` is intentionally omitted — the app does not use it.
     */
    colors,

    /**
     * Optional per-theme structural colors. Anything omitted is left to
     * Vuetify's defaults, which also generate the native `on-*` pairs and the
     * `surface`/`surface-variant`/`outline` elevation set.
     *
     * Uncomment a block to pin the app to a custom look:
     */
    light: {
        structural: {
            // background: '#FAFAFA',
            // background: '#F9F9F9',
            // background: colors.primary + '0D',
            background: colors.secondary + '0D',
            surface: '#ffffff',
            // 'surface-variant': '#f1f5f9',
            // 'surface-variant': colors.primary + '80',
            'surface-variant': colors.secondary,
            // 'on-surface-variant': '#475569',
            // outline: '#cbd5e1',
            // 'outline-variant': '#e2e8f0',
        },
    },
    dark: {
        structural: {
            background: '#0f172a',
            surface: '#1e293b',
            'surface-variant': '#334155',
            'on-surface-variant': '#cbd5e1',
            // outline: '#475569',
            // 'outline-variant': '#334155',
        },
    },

    /**
     * Native Vuetify lighten/darken variants, generated for every color above:
     * `bg-primary-lighten-3`, `text-error-darken-2`, etc.
     */
    variations: {
        lighten: 5,
        darken: 4,
    },
};
