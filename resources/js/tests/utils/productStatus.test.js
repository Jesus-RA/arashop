import { describe, expect, it } from 'vitest';
import { statusClasses, statusDotColor, statusLabel } from '../../utils/productStatus';

describe('statusLabel', () => {
    it('returns the friendly label for known statuses', () => {
        expect(statusLabel('active')).toBe('Active');
        expect(statusLabel('draft')).toBe('Draft');
        expect(statusLabel('archived')).toBe('Archived');
    });

    it('falls back to the raw value for unknown statuses', () => {
        expect(statusLabel('pending')).toBe('pending');
    });
});

describe('statusClasses', () => {
    it('maps known statuses to chip classes', () => {
        expect(statusClasses('active')).toBe('bg-green-lighten-5');
        expect(statusClasses('draft')).toBe('bg-orange-lighten-5');
        expect(statusClasses('archived')).toBe('bg-grey-lighten-2');
    });
});

describe('statusDotColor', () => {
    it('maps known statuses to dot background classes', () => {
        expect(statusDotColor('active')).toBe('bg-success');
        expect(statusDotColor('draft')).toBe('bg-orange');
        expect(statusDotColor('archived')).toBe('bg-grey-darken-1');
    });
});