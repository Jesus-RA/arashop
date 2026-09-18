import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PricingPage from '../pages/Pricing/PricingPage.vue';

const http = vi.hoisted(() => ({
    get: vi.fn(),
}));

vi.mock('../api/http', () => ({ default: http }));

const stubComponents = {
    'v-row': { template: '<div><slot /></div>' },
    'v-col': { template: '<div><slot /></div>' },
    'v-card': { template: '<div><slot /></div>' },
    'v-card-title': { template: '<div><slot /></div>' },
    'v-card-text': { template: '<div><slot /></div>' },
    'v-text-field': { template: '<div><slot /></div>' },
    'v-autocomplete': { template: '<div><slot /></div>' },
    'v-chip': { template: '<div><slot /></div>' },
    'v-btn': { template: '<button type="button"><slot /></button>' },
    'v-spacer': { template: '<div />' },
    'v-table': { template: '<table><slot /></table>' },
    'v-alert': { template: '<div><slot /></div>' },
};

const settingsDefaults = {
    'fx.usd_to_mxn': 18,
    'pricing.margin_percent': 35,
    'pricing.platform_fee_percent': 10,
    'pricing.packaging_fee_mxn': 15,
    'pricing.discount_percent': 0,
};

const variantOptions = {
    data: [
        {
            id: 42,
            product_id: 7,
            product_name: 'Essential Tee',
            product_sku: 'TEE',
            name: 'White / M',
            sku: 'TEE-W-M',
            attributes: { Color: 'White', Size: 'M' },
            cost_usd: '12.50',
            price_mxn: '289',
            stock_quantity: 5,
        },
    ],
};

function mockHttp() {
    http.get.mockImplementation((url) => {
        if (url === '/api/stock?per_page=100') {
            return Promise.resolve(variantOptions);
        }

        return Promise.resolve(settingsDefaults);
    });
}

async function mountPage() {
    const wrapper = mount(PricingPage, {
        global: { stubs: stubComponents },
    });

    await flushPromises();

    return wrapper;
}

describe('PricingPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();

        mockHttp();
    });

    it('loads settings overrides on mount', async () => {
        await mountPage();

        expect(http.get).toHaveBeenCalledWith('/api/settings');
    });

    it('loads product variants for the selector', async () => {
        await mountPage();

        expect(http.get).toHaveBeenCalledWith('/api/stock?per_page=100');
    });

    it('prefills the cost field and keeps the variant selected when picked', async () => {
        const wrapper = await mountPage();

        wrapper.vm.selectedVariantId = 42;
        wrapper.vm.onVariantPick(42);

        expect(wrapper.vm.run.cost_usd).toBe(12.5);
        expect(wrapper.vm.selectedVariantId).toBe(42);
    });

    it('keeps the cost field editable after prefilling from a variant', async () => {
        const wrapper = await mountPage();

        wrapper.vm.onVariantPick(42);
        wrapper.vm.run.cost_usd = 7.25;
        await flushPromises();

        expect(wrapper.vm.run.cost_usd).toBe(7.25);
        expect(wrapper.vm.result.subtotal_usd).toBe(7.25);
    });

    it('does not overwrite the cost for a variant without a cost', async () => {
        http.get.mockImplementation((url) => {
            if (url === '/api/stock?per_page=100') {
                return Promise.resolve({
                    data: [{ ...variantOptions.data[0], cost_usd: null }],
                });
            }

            return Promise.resolve(settingsDefaults);
        });

        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 20;
        wrapper.vm.onVariantPick(42);

        expect(wrapper.vm.run.cost_usd).toBe(20);
    });

    it('does not paint a result until a cost is entered', async () => {
        const wrapper = await mountPage();

        expect(wrapper.vm.result.selling_price_mxn).toBe(0);
    });

    it('uses the settings overrides as calculation defaults', async () => {
        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        await flushPromises();

        expect(wrapper.vm.result.fx_rate).toBe(18);
        expect(wrapper.vm.result.subtotal_mxn).toBe(180);
    });

    it('renders the earnings in the breakdown table', async () => {
        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        wrapper.vm.run.shipping_usd = 2;
        await flushPromises();

        expect(wrapper.text()).toContain('MX$216.00');
        expect(wrapper.text()).toContain('MX$420.00');
        expect(wrapper.text()).toContain('MX$147.00');
        expect(wrapper.text()).toContain('$8.17');
    });

    it('surfaces an error when fee plus margin reach 100', async () => {
        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        wrapper.vm.run.margin_percent = 90;
        await flushPromises();

        expect(wrapper.vm.error).toContain('less than 100%');
    });

    it('auto-fills the target price from the suggestion', async () => {
        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        await flushPromises();

        expect(wrapper.vm.sellingPriceFieldValue).toBe(354.55);
    });

    it('uses a manually entered target price', async () => {
        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        await flushPromises();

        wrapper.vm.onSellingPriceInput(400);
        await flushPromises();

        expect(wrapper.vm.run.selling_price_mxn).toBe(400);
        expect(wrapper.vm.result.selling_price_mxn).toBe(400);
        expect(wrapper.vm.result.suggested_price_mxn).toBeNull();
        expect(wrapper.vm.sellingPriceFieldValue).toBe(400);
    });

    it('returns to auto mode when the manual price is cleared', async () => {
        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        await flushPromises();
        wrapper.vm.onSellingPriceInput(400);
        await flushPromises();

        wrapper.vm.onSellingPriceInput(null);
        await flushPromises();

        expect(wrapper.vm.run.selling_price_mxn).toBeNull();
        expect(wrapper.vm.sellingPriceFieldValue).toBe(354.55);
    });
});