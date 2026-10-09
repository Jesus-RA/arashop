const labels = {
    active: 'Active',
    draft: 'Draft',
    archived: 'Archived',
};

const classes = {
    active: 'bg-green-lighten-5',
    draft: 'bg-orange-lighten-5',
    archived: 'bg-grey-lighten-2',
};

const dotColors = {
    active: 'bg-success',
    draft: 'bg-orange',
    archived: 'bg-grey-darken-1',
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
