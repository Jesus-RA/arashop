const labels = {
    pending: 'Pending',
    paid: 'Paid',
    cancelled: 'Cancelled',
};

const classes = {
    pending: 'bg-warning-lighten-5 text-warning',
    paid: 'bg-info-lighten-5 text-info',
    cancelled: 'bg-primary-lighten-5 text-primary',
};

const dotColors = {
    pending: 'rgb(var(--v-theme-warning))',
    paid: 'rgb(var(--v-theme-success))',
    cancelled: 'rgb(var(--v-theme-error))',
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
