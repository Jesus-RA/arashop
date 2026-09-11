const usdFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

const mxnFormatter = new Intl.NumberFormat('en-MX', {
    style: 'currency',
    currency: 'MXN',
});

export function formatUsd(value) {
    return usdFormatter.format(Number(value ?? 0));
}

export function formatMxn(value) {
    return mxnFormatter.format(Number(value ?? 0));
}

export function formatDate(value) {
    return new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(value));
}

export function formatDateTime(value) {
    const time = new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit' }).format(new Date(value));

    return `${formatDate(value)} @ ${time}`;
}

export default { formatUsd, formatMxn, formatDate, formatDateTime };