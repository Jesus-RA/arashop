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
        expect(statusClasses('active')).toContain('text-info');
        expect(statusClasses('draft')).toContain('text-warning');
        expect(statusClasses('archived')).toContain('bg-surface-light');
    });
});

describe('statusDotColor', () => {
    it('maps known statuses to CSS color variables', () => {
        expect(statusDotColor('active')).toBe('rgb(var(--v-theme-success))');
        expect(statusDotColor('draft')).toBe('rgb(var(--v-theme-warning))');
        expect(statusDotColor('archived')).toBe('rgb(var(--v-theme-on-surface-variant))');
    });
});