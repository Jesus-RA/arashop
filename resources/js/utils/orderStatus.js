const labels = {
    pending: 'Pending',
    paid: 'Paid',
    cancelled: 'Cancelled',
};

const classes = {
    pending: 'bg-surface-cream-subtle text-status-warning',
    paid: 'bg-surface-mint-subtle text-mint-deep',
    cancelled: 'bg-surface-rose-subtle text-rose-deep',
};

const dotColors = {
    pending: 'rgb(var(--v-theme-status-warning))',
    paid: 'rgb(var(--v-theme-status-success))',
    cancelled: 'rgb(var(--v-theme-status-error))',
};

export function orderStatusLabel(status) {
    return labels[status] ?? status;
}

export function orderStatusClasses(status) {
    return classes[status];
}

export function orderStatusDotColor(status) {
    return dotColors[status];
}

export default { orderStatusLabel, orderStatusClasses, orderStatusDotColor };