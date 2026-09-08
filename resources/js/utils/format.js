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

export default { formatUsd, formatMxn };