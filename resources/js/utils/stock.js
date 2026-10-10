export function stockClasses(quantity, threshold = 5) {
    if (Number(quantity) === 0) return 'bg-red';

    if (Number(quantity) <= threshold) return 'bg-orange-lighten-2';

    return 'bg-grey-lighten-4';
}