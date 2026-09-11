const labels = {
    active: 'Active',
    draft: 'Draft',
    archived: 'Archived',
};

const classes = {
    active: 'bg-surface-mint-subtle text-mint-deep',
    draft: 'bg-surface-cream-subtle text-status-warning',
    archived: 'bg-canvas-tint text-on-surface-variant',
};

const dotColors = {
    active: 'rgb(var(--v-theme-status-success))',
    draft: 'rgb(var(--v-theme-status-warning))',
    archived: 'rgb(var(--v-theme-on-surface-variant))',
};

export function statusLabel(status) {
    return labels[status] ?? status;
}

export function statusClasses(status) {
    return classes[status];
}

export function statusDotColor(status) {
    return dotColors[status];
}

export default { statusLabel, statusClasses, statusDotColor };