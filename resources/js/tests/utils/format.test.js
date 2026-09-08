import { describe, expect, it } from 'vitest';
import { formatMxn, formatUsd } from '../../utils/format';

describe('formatUsd', () => {
    it('formats a positive amount', () => {
        expect(formatUsd(12.5)).toBe('$12.50');
    });

    it('formats whole dollars with two decimals', () => {
        expect(formatUsd(8)).toBe('$8.00');
    });

    it('handles empty values as zero', () => {
        expect(formatUsd(null)).toBe('$0.00');
        expect(formatUsd(undefined)).toBe('$0.00');
    });
});

describe('formatMxn', () => {
    it('formats a positive amount', () => {
        expect(formatMxn(350)).toBe('MX$350.00');
    });

    it('rounds to two decimals', () => {
        expect(formatMxn(7.1666)).toBe('MX$7.17');
    });

    it('handles zero-ish values', () => {
        expect(formatMxn(0)).toBe('MX$0.00');
    });
});