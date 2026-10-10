const classes = {
    primary: 'bg-secondary-lighten-2',
    success: 'bg-green-lighten-5',
    info: 'bg-info-lighten-5',
    warning: 'bg-amber-lighten-5',
    red: 'bg-red-lighten-5',
    error: 'bg-error-lighten-5',
};

export function iconBg(color) {
    return classes[color] ?? '';
}

export default { iconBg };
