function round2(value) {
    return Math.round(value * 100) / 100;
}

function requireNumber(value, key) {
    if (value === null || value === undefined || value === '' || !Number.isFinite(Number(value))) {
        throw new Error(`The "${key}" value is required.`);
    }

    return Number(value);
}

function requirePositive(value, key) {
    const number = requireNumber(value, key);

    if (number <= 0) {
        throw new Error(`The "${key}" value must be greater than zero.`);
    }

    return number;
}

function assertPercent(value, key) {
    if (value < 0 || value > 100) {
        throw new Error(`The "${key}" value must be between 0 and 100.`);
    }
}

/**
 * Calculate a cost/earnings breakdown for a product.
 *
 * All percentages are expressed as whole numbers (e.g. 35 for 35%).
 *
 * @param {Record<string, number|null|undefined>} input
 * @returns {Record<string, number|null>}
 */
export function calculatePricing(input) {
    const costUsd = requireNumber(input.cost_usd ?? null, 'cost_usd');
    const shippingUsd = Math.max(0, Number(input.shipping_usd ?? 0));
    const fxRate = requirePositive(input.fx_rate ?? null, 'fx_rate');
    const marginPercent = Number(input.margin_percent ?? 0);
    const platformFeePercent = Math.max(0, Number(input.platform_fee_percent ?? 0));
    const packagingFeeMxn = Math.max(0, Number(input.packaging_fee_mxn ?? 0));
    const discountPercent = Math.max(0, Number(input.discount_percent ?? 0));

    assertPercent(marginPercent, 'margin_percent');
    assertPercent(platformFeePercent, 'platform_fee_percent');
    assertPercent(discountPercent, 'discount_percent');

    const subtotalUsd = costUsd + shippingUsd;
    const subtotalMxn = subtotalUsd * fxRate;

    let sellingPriceMxn = input.selling_price_mxn ?? null;
    let suggestedPriceMxn = null;

    if (sellingPriceMxn !== null) {
        sellingPriceMxn = requirePositive(sellingPriceMxn, 'selling_price_mxn');
    } else {
        const denominator = 1 - platformFeePercent / 100 - marginPercent / 100;

        if (denominator <= 0) {
            throw new Error('The combined platform fee and margin must be less than 100%.');
        }

        sellingPriceMxn = round2((subtotalMxn + packagingFeeMxn) / denominator);
        suggestedPriceMxn = sellingPriceMxn;
    }

    const platformFeeMxn = round2(sellingPriceMxn * platformFeePercent / 100);
    const earningsMxn = sellingPriceMxn - platformFeeMxn - subtotalMxn - packagingFeeMxn;

    const discountedPriceMxn = round2(sellingPriceMxn * (1 - discountPercent / 100));
    const discountedPlatformFeeMxn = round2(discountedPriceMxn * platformFeePercent / 100);
    const earningsWithDiscountMxn = discountedPriceMxn - discountedPlatformFeeMxn - subtotalMxn - packagingFeeMxn;

    return {
        cost_usd: round2(costUsd),
        shipping_usd: round2(shippingUsd),
        fx_rate: fxRate,
        subtotal_usd: round2(subtotalUsd),
        subtotal_mxn: round2(subtotalMxn),
        packaging_fee_mxn: round2(packagingFeeMxn),
        platform_fee_percent: platformFeePercent,
        margin_percent: marginPercent,
        discount_percent: discountPercent,
        selling_price_mxn: round2(sellingPriceMxn),
        suggested_price_mxn: suggestedPriceMxn !== null ? round2(suggestedPriceMxn) : null,
        platform_fee_mxn: platformFeeMxn,
        earnings_mxn: round2(earningsMxn),
        earnings_usd: round2(earningsMxn / fxRate),
        discounted_price_mxn: discountedPriceMxn,
        discounted_platform_fee_mxn: discountedPlatformFeeMxn,
        earnings_with_discount_mxn: round2(earningsWithDiscountMxn),
        earnings_with_discount_usd: round2(earningsWithDiscountMxn / fxRate),
    };
}

export default { calculatePricing };
