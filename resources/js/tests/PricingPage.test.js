import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PricingPage from '../pages/Pricing/PricingPage.vue';

const http = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
}));

vi.mock('../api/http', () => ({ default: http }));

const stubComponents = {
    'v-row': { template: '<div><slot /></div>' },
    'v-col': { template: '<div><slot /></div>' },
    'v-card': { template: '<div><slot /></div>' },
    'v-card-title': { template: '<div><slot /></div>' },
    'v-card-text': { template: '<div><slot /></div>' },
    'v-text-field': { template: '<div><slot /></div>' },
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

const pricingResult = {
    cost_usd: 10,
    shipping_usd: 2,
    fx_rate: 18,
    subtotal_usd: 12,
    subtotal_mxn: 216,
    packaging_fee_mxn: 15,
    platform_fee_percent: 10,
    margin_percent: 35,
    discount_percent: 0,
    selling_price_mxn: 420,
    suggested_price_mxn: 420,
    platform_fee_mxn: 42,
    earnings_mxn: 147,
    earnings_usd: 8.17,
    discounted_price_mxn: 420,
    discounted_platform_fee_mxn: 42,
    earnings_with_discount_mxn: 147,
    earnings_with_discount_usd: 8.17,
};

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

        http.get.mockResolvedValue(settingsDefaults);
        http.post.mockResolvedValue(pricingResult);
    });

    it('loads settings overrides on mount', async () => {
        await mountPage();

        expect(http.get).toHaveBeenCalledWith('/api/settings');
    });

    it('does not calculate until a cost is entered', async () => {
        await mountPage();

        expect(http.post).not.toHaveBeenCalled();
    });

    it('sends the settings overrides with the calculation', async () => {
        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        await flushPromises();

        expect(http.post).toHaveBeenCalledWith('/api/pricing/calculate', expect.objectContaining({
            cost_usd: 10,
            fx_rate: 18,
            margin_percent: 35,
            platform_fee_percent: 10,
            packaging_fee_mxn: 15,
            discount_percent: 0,
        }));
    });

    it('renders the earnings in the breakdown table', async () => {
        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        await flushPromises();

        expect(wrapper.text()).toContain('MX$216.00');
        expect(wrapper.text()).toContain('MX$420.00');
        expect(wrapper.text()).toContain('MX$147.00');
        expect(wrapper.text()).toContain('$8.17');
    });

    it('ignores stale responses when a newer calculation started', async () => {
        let resolveFirst;
        let resolveSecond;

        http.post
            .mockImplementationOnce(() => new Promise((resolve) => { resolveFirst = resolve; }))
            .mockImplementationOnce(() => new Promise((resolve) => { resolveSecond = resolve; }));

        const wrapper = await mountPage();

        wrapper.vm.run.cost_usd = 10;
        await flushPromises();
        wrapper.vm.run.shipping_usd = 3;
        await flushPromises();

        resolveFirst({ ...pricingResult, selling_price_mxn: 999, suggested_price_mxn: 999 });
        resolveSecond(pricingResult);

        await flushPromises();

        expect(wrapper.text()).toContain('MX$420.00');
        expect(wrapper.text()).not.toContain('MX$999.00');
    });
});