import { describe, expect, it } from 'vitest';
import { calculatePricing } from '../../utils/profit';

describe('calculatePricing', () => {
    it('suggests a price from the target margin', () => {
        const result = calculatePricing({
            cost_usd: 10,
            shipping_usd: 2,
            fx_rate: 18,
            margin_percent: 35,
            platform_fee_percent: 10,
            packaging_fee_mxn: 15,
            discount_percent: 0,
        });

        expect(result.suggested_price_mxn).toBe(420);
        expect(result.subtotal_mxn).toBe(216);
        expect(result.platform_fee_mxn).toBe(42);
        expect(result.earnings_mxn).toBe(147);
    });

    it('computes the breakdown for a custom price', () => {
        const result = calculatePricing({
            cost_usd: 10,
            shipping_usd: 2,
            fx_rate: 18,
            margin_percent: 35,
            platform_fee_percent: 10,
            packaging_fee_mxn: 15,
            discount_percent: 0,
            selling_price_mxn: 400,
        });

        expect(result.suggested_price_mxn).toBeNull();
        expect(result.selling_price_mxn).toBe(400);
        expect(result.platform_fee_mxn).toBe(40);
        expect(result.earnings_mxn).toBe(129);
        expect(result.earnings_usd).toBe(7.17);
    });

    it('applies the discount to price and earnings', () => {
        const result = calculatePricing({
            cost_usd: 10,
            shipping_usd: 2,
            fx_rate: 18,
            margin_percent: 35,
            platform_fee_percent: 10,
            packaging_fee_mxn: 15,
            discount_percent: 10,
            selling_price_mxn: 400,
        });

        expect(result.discounted_price_mxn).toBe(360);
        expect(result.discounted_platform_fee_mxn).toBe(36);
        expect(result.earnings_with_discount_mxn).toBe(93);
    });

    it('never returns negative utility values from guards', () => {
        const result = calculatePricing({
            cost_usd: 10,
            fx_rate: 18,
            margin_percent: 35,
            platform_fee_percent: 10,
            packaging_fee_mxn: 15,
            discount_percent: 0,
        });

        expect(result.earnings_mxn).toBeGreaterThan(0);
    });

    it('throws when fee plus margin reaches 100', () => {
        expect(() => calculatePricing({
            cost_usd: 10,
            shipping_usd: 2,
            fx_rate: 18,
            margin_percent: 90,
            platform_fee_percent: 10,
            packaging_fee_mxn: 15,
            discount_percent: 0,
        })).toThrow('The combined platform fee and margin must be less than 100%.');
    });

    it('rejects a non-positive fx rate', () => {
        expect(() => calculatePricing({
            cost_usd: 10,
            shipping_usd: 0,
            fx_rate: 0,
            margin_percent: 35,
            platform_fee_percent: 10,
            packaging_fee_mxn: 15,
            discount_percent: 0,
        })).toThrow('The "fx_rate" value must be greater than zero.');
    });

    it('validates percentage ranges', () => {
        expect(() => calculatePricing({
            cost_usd: 10,
            shipping_usd: 2,
            fx_rate: 18,
            margin_percent: 150,
            platform_fee_percent: 10,
            packaging_fee_mxn: 15,
            discount_percent: 0,
        })).toThrow('The "margin_percent" value must be between 0 and 100.');
    });

    it('rounds money to two decimals', () => {
        const result = calculatePricing({
            cost_usd: 10.33,
            shipping_usd: 1.11,
            fx_rate: 18.5,
            margin_percent: 33,
            platform_fee_percent: 7.5,
            packaging_fee_mxn: 12.75,
            discount_percent: 0,
        });

        expect(result.subtotal_mxn).toBe(211.64);

        for (const value of Object.values(result)) {
            if (typeof value === 'number') {
                expect(Math.abs(value - Math.round(value * 100) / 100)).toBeLessThan(0.001);
            }
        }
    });
});
