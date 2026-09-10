/**
 * Central Vuetify theme configuration for Arashop.
 *
 * This file is the single place to customize the app theme. It maps the
 * Stitch "Warm Modern FinTech" palettes (light + dark) onto Vuetify's
 * reserved semantic color words, so every component picks the colors up
 * without component-level changes:
 *
 *   https://vuetifyjs.com/en/features/theme/#custom-themes
 *
 * Structure — one naming pattern for BOTH themes:
 *
 *   - `primitives` → the 50-900 scales per color family (base hex = 500).
 *   - `semantic`   → role hues (primary, secondary, success, warning,
 *                    danger, info). Each role exposes `.light` and `.dark`
 *                    values so the two themes stay in lockstep: edit the
 *                    role once per theme, not scattered through the maps.
 *   - `stitches`   → shared Stitch Google AI UI Design accent pastels (rose-pastel, mint-deep).
 *   - `light`/`dark` → full, parallel color maps. Every key exists in both;
 *                    role colors come from `semantic.<role>.<theme>.default`,
 *                    shared accents from `primitives`/`stitches`, and
 *                    theme-specific surfaces/fillers are literal per theme.
 *
 * Light source:  warm_modern_fintech/DESIGN.md + arashop_* code.html config
 * Dark source:   warm_modern_dark_fintech/DESIGN.md + arashop_*_dark config
 */

// ---------------------------------------------------------------------------
// 50-900 scale generator. Base hex is the 500 step.
// 050/100/200/300/400 tint toward white; 600/700/800/900 shade toward black.
// ---------------------------------------------------------------------------

const hexChannels = (hex) => hex.match(/\w\w/g).map((channel) => parseInt(channel, 16));

const tint = (hex, whiteWeight) => {
    const channels = hexChannels(hex);

    return '#' + channels.map((value) => Math.round(255 * whiteWeight + value * (1 - whiteWeight)).toString(16).padStart(2, '0')).join('');
};

const shade = (hex, blackWeight) => {
    const channels = hexChannels(hex);

    return '#' + channels.map((value) => Math.round(value * (1 - blackWeight)).toString(16).padStart(2, '0')).join('');
};

const buildScale = (base) => ({
    50: tint(base, 0.9),
    100: tint(base, 0.8),
    200: tint(base, 0.6),
    300: tint(base, 0.4),
    400: tint(base, 0.2),
    500: base,
    600: shade(base, 0.2),
    700: shade(base, 0.4),
    800: shade(base, 0.6),
    900: shade(base, 0.8),
});

// ---------------------------------------------------------------------------
// Global primitives — the raw color families. Base hexes drive the scales.
// ---------------------------------------------------------------------------

const primitives = {
    white: '#ffffff',
    black: '#000000',

    rose: buildScale('#c24566'),       // brand primary rose
    mint: buildScale('#99e1e5'),       // mint-seafoam
    teal: buildScale('#00696d'),       // secondary / info
    peach: buildScale('#f2c6b4'),      // peach-warm
    cream: buildScale('#f3e8cb'),      // cream-champagne
    amber: buildScale('#c77700'),      // warning
    green: buildScale('#1b8754'),      // success
    red: buildScale('#ba1a1a'),        // danger / error
    terracotta: buildScale('#93411f'), // accent / tertiary
    slate: buildScale('#6d4e51'),      // warm neutral
};

// ---------------------------------------------------------------------------
// Stitch accent pastels shared by both themes (live off the scale ramp).
// ---------------------------------------------------------------------------

const stitches = {
    rosePastel: '#fbafaf',
    mintDeep: '#1d6b6f',
};

// ---------------------------------------------------------------------------
// Role hues — one entry per theme so light and dark are defined in lockstep.
// Both theme maps consume these with the SAME lookup:
//   light → semantic.<role>.light.<field>
//   dark  → semantic.<role>.dark.<field>
// ---------------------------------------------------------------------------

const semantic = {
    primary: {
        light: { default: primitives.rose[500], on: primitives.white, container: primitives.rose[500], onContainer: '#fff6f6' },
        dark: { default: stitches.rosePastel, on: '#522123', container: stitches.rosePastel, onContainer: '#784041' },
    },
    secondary: {
        light: { default: primitives.teal[500], on: primitives.white, container: '#98f1f5', onContainer: '#007074' },
        dark: { default: '#8bd2d6', on: '#003739', container: '#005f63', onContainer: '#8ed6da' },
    },
    success: {
        light: { default: primitives.green[500], on: primitives.white },
        dark: { default: primitives.mint[500], on: primitives.white },
    },
    warning: {
        light: { default: primitives.amber[500], on: primitives.white },
        dark: { default: primitives.peach[500], on: primitives.white },
    },
    danger: {
        light: { default: primitives.red[500], on: primitives.white, container: '#ffdad6', onContainer: '#93000a' },
        dark: { default: '#ffb4ab', on: '#690005', container: '#93000a', onContainer: '#ffdad6' },
    },
    info: {
        light: { default: primitives.teal[500], on: primitives.white },
        dark: { default: '#a6acb8', on: '#003739' },
    },
};

// ---------------------------------------------------------------------------
// LIGHT ramp — warm rose canvas, white panels, rose primary. The pink-ish
// body (background #fff8f7) and rose-tinted containers are intentional; they
// give the app its warmth. Parallel to `dark` key-for-key.
// ---------------------------------------------------------------------------

const light = {
    // --- Reserved Vuetify words -------------------------------------
    primary: semantic.primary.light.default,
    'on-primary': semantic.primary.light.on,
    'primary-container': semantic.primary.light.container,
    'on-primary-container': semantic.primary.light.onContainer,
    secondary: semantic.secondary.light.default,
    'on-secondary': semantic.secondary.light.on,
    'secondary-container': semantic.secondary.light.container,
    'on-secondary-container': semantic.secondary.light.onContainer,
    accent: primitives.terracotta[500],
    error: semantic.danger.light.default,
    'on-error': semantic.danger.light.on,
    'error-container': semantic.danger.light.container,
    'on-error-container': semantic.danger.light.onContainer,
    info: semantic.info.light.default,
    success: semantic.success.light.default,
    warning: semantic.warning.light.default,

    // Cards sit on --v-theme-surface (white) above the warm rose canvas.
    surface: primitives.white,
    background: '#fff8f7',
    'on-surface': '#201a1b',
    'on-background': '#201a1b',

    'surface-container-lowest': primitives.white,
    'surface-container-low': '#fef0f1',
    'surface-container': '#f8ebec',
    'surface-container-high': '#f2e5e6',
    'on-surface-container-high': '#201a1b',
    'surface-container-highest': '#eddfe0',

    'surface-variant': '#eddfe0',
    'on-surface-variant': '#574145',
    outline: '#8a7174',
    'outline-variant': '#ddbfc3',
    'inverse-surface': '#362f30',
    'inverse-on-surface': '#fbeeee',
    'inverse-primary': '#ffb2bf',
    'surface-tint': '#a93153',
    'surface-bright': '#fff8f7',
    'surface-dim': '#e4d7d8',

    // --- Stitch brand tokens (usable as bg-*/text-*/border-*) ---
    'primary-deep': '#a22c4e',
    tertiary: primitives.terracotta[500],
    'tertiary-container': '#b25835',
    'on-tertiary': primitives.white,
    'rose-deep': '#9e2c4c',
    'rose-pastel': stitches.rosePastel,
    'peach-warm': primitives.peach[500],
    'cream-champagne': primitives.cream[500],
    'mint-seafoam': primitives.mint[500],
    'mint-deep': stitches.mintDeep,
    'border-warm': '#e8ddd9',
    'canvas-tint': '#faf6f5',
    'surface-card': primitives.white,
    'surface-rose-subtle': '#fdf3f3',
    'surface-mint-subtle': '#f0fafa',
    'surface-cream-subtle': '#fcf9f2',
    'status-success': semantic.success.light.default,
    'status-warning': semantic.warning.light.default,
    'status-error': semantic.danger.light.default,
};

// ---------------------------------------------------------------------------
// DARK ramp — warm charcoal body (#121316), slate cards (#22252d), inverted
// rose pastel primary. Parallel to `light` key-for-key; role colors use the
// exact same `semantic.<role>.dark.*` lookups.
// ---------------------------------------------------------------------------

const dark = {
    // --- Reserved Vuetify words -------------------------------------
    primary: semantic.primary.dark.default,
    'on-primary': semantic.primary.dark.on,
    'primary-container': semantic.primary.dark.container,
    'on-primary-container': semantic.primary.dark.onContainer,
    secondary: semantic.secondary.dark.default,
    'on-secondary': semantic.secondary.dark.on,
    'secondary-container': semantic.secondary.dark.container,
    'on-secondary-container': semantic.secondary.dark.onContainer,
    accent: '#ffd5c5',
    error: semantic.danger.dark.default,
    'on-error': semantic.danger.dark.on,
    'error-container': semantic.danger.dark.container,
    'on-error-container': semantic.danger.dark.onContainer,
    info: semantic.info.dark.default,
    success: semantic.success.dark.default,
    warning: semantic.warning.dark.default,

    // Cards sit on --v-theme-surface (slate card) above the charcoal body.
    surface: '#22252d',
    background: '#121316',
    'on-surface': '#e3e2e6',
    'on-background': '#e3e2e6',

    'surface-container-lowest': '#121316',
    'surface-container-low': '#1b1b1f',
    'surface-container': '#1f1f23',
    'surface-container-high': '#292a2d',
    'on-surface-container-high': '#e3e2e6',
    'surface-container-highest': '#343538',

    'surface-variant': '#343538',
    'on-surface-variant': '#a6acb8',
    outline: '#9f8c8c',
    'outline-variant': '#524343',
    'inverse-surface': '#e3e2e6',
    'inverse-on-surface': '#2f3034',
    'inverse-primary': '#894e4e',
    'surface-tint': '#ffb3b3',
    'surface-bright': '#38393c',
    'surface-dim': '#121316',

    // --- Stitch brand tokens (dark variants) ------------------------
    'primary-deep': '#a22c4e',
    tertiary: '#ffd5c5',
    'tertiary-container': '#e4b9a7',
    'on-tertiary': '#452a1e',
    'rose-vibrant': stitches.rosePastel,
    'rose-action': '#ff8aa5',
    'rose-deep': primitives.rose[500],
    'rose-pastel': stitches.rosePastel,
    'peach-warm': primitives.peach[500],
    'cream-champagne': primitives.cream[500],
    'mint-vibrant': primitives.mint[500],
    'mint-seafoam': primitives.mint[500],
    'mint-deep': primitives.mint[500],
    'border-warm': '#2d313b',
    'border-subtle': '#242832',
    'border-contrast': '#2d313b',
    'border-highlight': '#3e4452',
    'canvas-tint': '#222530',
    'surface-card': '#22252d',
    'surface-elevated': '#22252d',
    'surface-overlay': '#2a2e38',
    'surface-rose-subtle': '#3a2b33',
    'surface-mint-subtle': '#28393a',
    'surface-cream-subtle': '#373a31',
    'status-success': semantic.success.dark.default,
    'status-warning': semantic.warning.dark.default,
    'status-error': '#ff6b81',
    'status-positive': semantic.success.dark.default,
    'status-danger': '#ff6b81',
    'text-primary': '#f5f6f8',
    'text-secondary': '#a6acb8',
    'text-muted': '#6c7382',
    'primary-fixed': '#ffdad9',
    'primary-fixed-dim': '#ffb3b3',
};

export const themes = {
    light: { dark: false, colors: { ...light } },
    dark: { dark: true, colors: { ...dark } },
};

/**
 * Component-level defaults that nudge the UI toward the Stitch aesthetic
 * without changing any component usage. Tune freely.
 */
export const defaults = {
    VCard: { rounded: 'lg', elevation: 0 },
    VDialog: { rounded: 'xl' },
    VBtn: { rounded: 'md' },
    VChip: { rounded: 'pill' },
    VTextField: { variant: 'outlined', color: 'primary', density: 'compact' },
    VSelect: { variant: 'outlined', color: 'primary', density: 'compact' },
    VCombobox: { variant: 'outlined', color: 'primary' },
    VTextarea: { variant: 'outlined', color: 'primary' },
    VAlert: { variant: 'tonal' },
};

const THEME_STORAGE_KEY = 'arashop.theme';

/** Resolve the persisted theme, defaulting to 'light'. */
export function getDefaultTheme() {
    return localStorage.getItem(THEME_STORAGE_KEY) === 'dark' ? 'dark' : 'light';
}
