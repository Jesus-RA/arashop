<template>
    <div>
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-5">
            <div>
                <div class="d-flex align-center ga-2 mb-1">
                    <span class="stitch-label">Pricing Desk</span>
                </div>
                <h1 class="text-headline-small font-weight-bold mb-0">Pricing calculator</h1>
                <p class="text-body-medium text-medium-emphasis mb-0 mt-1">
                    Model landed cost, fees and target Mexico retail price.
                </p>
            </div>
        </div>

        <v-row>
            <v-col cols="12" lg="5">
                <v-card class="rounded-lg px-6 py-5 mb-4">
                    <v-card-title class="d-flex align-center px-0 pt-0">
                        <div class="d-flex align-center ga-3">
                            <div class="stitch-kpi-icon rounded-lg bg-secondary-lighten-2">
                                <span class="text-primary">$</span>
                            </div>
                            <div>
                                <span class="text-body-large font-weight-bold">Run</span>
                                <div class="text-body-small text-medium-emphasis">Scenario inputs</div>
                            </div>
                        </div>
                    </v-card-title>
                    <v-card-text class="d-flex flex-column gr-3 px-0 pb-0">
                        <v-autocomplete
                            v-model="selectedVariantId"
                            :items="variantItems"
                            item-title="display"
                            item-value="id"
                            label="Product variant"
                            prepend-inner-icon="mdi-package-variant"
                            :loading="variantsLoading"
                            clearable
                            hint="Pick a saved variant to prefill its cost — then adjust freely"
                            class="mb-2"
                            @update:model-value="onVariantPick"
                        />
                        <v-text-field
                            v-model.number="run.cost_usd"
                            label="Cost (USD)"
                            type="number"
                            min="0"
                            step="0.01"
                            :rules="costRules"
                            hint="What you paid for the item"
                            class="mb-2"
                        />
                        <v-text-field
                            v-model.number="run.shipping_usd"
                            label="Shipping (USD)"
                            type="number"
                            min="0"
                            step="0.01"
                            :rules="moneyRules"
                            hint="Per-unit shipping"
                            class="mb-2"
                        />
                        <v-text-field
                            :model-value="sellingPriceFieldValue"
                            label="Target selling price (MXN)"
                            type="number"
                            min="0"
                            step="1"
                            :rules="moneyRules"
                            :clearable="true"
                            hint="Auto-calculated from cost &amp; margin — type to set a manual value"
                            @update:model-value="onSellingPriceInput"
                        />
                    </v-card-text>
                </v-card>

                <v-card class="rounded-lg px-6 py-5 mb-4">
                    <v-card-title class="d-flex align-center px-0 pt-0 pb-2">
                        <div class="flex-1 d-flex flex-column gr-1">
                            <span class="text-body-large font-weight-bold">Overrides</span>
                            <div class="text-body-small text-medium-emphasis">Scope fees &amp; FX for this run</div>
                        </div>
                        <v-btn size="small" variant="tonal" @click="applySettingsOverrides">
                            Reset to settings
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="d-flex flex-column gr-3 px-0 pb-0">
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model.number="run.fx_rate"
                                    label="FX rate (USD → MXN)"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model.number="run.margin_percent"
                                    label="Margin (%)"
                                    type="number"
                                    min="0"
                                    max="100"
                                    suffix="%"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model.number="run.platform_fee_percent"
                                    label="Platform fee (%)"
                                    type="number"
                                    min="0"
                                    max="100"
                                    suffix="%"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model.number="run.packaging_fee_mxn"
                                    label="Packaging fee (MXN)"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model.number="run.discount_percent"
                                    label="Discount (%)"
                                    type="number"
                                    min="0"
                                    max="100"
                                    suffix="%"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" lg="7">
                <v-card class="rounded-lg px-6 py-5 mb-4">
                    <v-card-title class="d-flex align-center px-0 pt-0">
                        <div class="d-flex align-center ga-3">
                            <div class="stitch-kpi-icon rounded-lg bg-pale-teal-lighten-2">
                                <span class="text-pale-teal">%</span>
                            </div>
                            <div>
                                <span class="text-body-large font-weight-bold">Breakdown</span>
                                <div class="text-body-small text-medium-emphasis">Detailed P&amp;L after fees</div>
                            </div>
                        </div>
                    </v-card-title>
                    <v-card-text class="px-0 pb-0">
                        <v-alert
                            v-if="error"
                            type="error"
                            variant="tonal"
                            dismissible
                            class="mb-3"
                            @click:close="error = ''"
                        >
                            {{ error }}
                        </v-alert>

                        <v-table>
                            <tbody>
                                <tr>
                                    <td class="text-body-medium">Cost + shipping</td>
                                    <td class="text-right tabular-nums">{{ formatUsd(result.subtotal_usd) }}</td>
                                    <td class="text-right text-medium-emphasis tabular-nums">{{ formatMxn(result.subtotal_mxn) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-body-medium">Packaging</td>
                                    <td class="text-right">—</td>
                                    <td class="text-right text-medium-emphasis tabular-nums">{{ formatMxn(result.packaging_fee_mxn) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-body-medium">Platform fee ({{ result.platform_fee_percent }}%)</td>
                                    <td class="text-right">—</td>
                                    <td class="text-right text-medium-emphasis tabular-nums">{{ formatMxn(result.platform_fee_mxn) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-body-medium">Suggested price</td>
                                    <td class="text-right">—</td>
                                    <td class="text-right font-weight-bold tabular-nums text-primary">
                                        {{ result.suggested_price_mxn !== null ? formatMxn(result.suggested_price_mxn) : formatMxn(result.selling_price_mxn) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-body-medium font-weight-bold">Earnings at full price</td>
                                    <td class="text-right font-weight-bold tabular-nums">{{ formatUsd(result.earnings_usd) }}</td>
                                    <td class="text-right font-weight-bold tabular-nums text-pale-teal">{{ formatMxn(result.earnings_mxn) }}</td>
                                </tr>
                                <template v-if="result.discount_percent > 0">
                                    <tr>
                                        <td class="text-body-medium">Discounted price ({{ result.discount_percent }}% off)</td>
                                        <td class="text-right">—</td>
                                        <td class="text-right text-medium-emphasis tabular-nums">{{ formatMxn(result.discounted_price_mxn) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-body-medium">Earnings after discount</td>
                                        <td class="text-right text-medium-emphasis tabular-nums">{{ formatUsd(result.earnings_with_discount_usd) }}</td>
                                        <td class="text-right text-medium-emphasis tabular-nums">{{ formatMxn(result.earnings_with_discount_mxn) }}</td>
                                    </tr>
                                </template>
                            </tbody>
                        </v-table>

                        <div class="d-flex align-center justify-space-between pa-3 mt-3 summary-banner border-sm rounded-lg">
                            <div>
                                <div class="stitch-label mb-1">Projected Margin</div>
                                <div class="text-body-small text-medium-emphasis">After all costs &amp; fees</div>
                            </div>
                            <div class="text-right">
                                <div class="text-title-large font-weight-bold tabular-nums text-pale-teal">
                                    {{ result.margin_percent }}%
                                </div>
                                <div class="text-body-small text-medium-emphasis tabular-nums">
                                    @ FX {{ Number(result.fx_rate).toFixed(2) }}
                                </div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import http from '../../api/http';
import { formatMxn, formatUsd } from '../../utils/format';
import { calculatePricing } from '../../utils/profit';
import { min, numeric, required } from '../../utils/validation';

const error = ref('');
const settings = ref({});

const selectedVariantId = ref(null);
const variantItems = ref([]);
const variantsLoading = ref(false);
const sellingPriceIsManual = ref(false);

const run = reactive({
    cost_usd: null,
    shipping_usd: 0,
    selling_price_mxn: null,
    fx_rate: null,
    margin_percent: null,
    platform_fee_percent: null,
    packaging_fee_mxn: null,
    discount_percent: null,
});

const result = reactive({
    subtotal_usd: 0,
    subtotal_mxn: 0,
    packaging_fee_mxn: 0,
    platform_fee_percent: 0,
    platform_fee_mxn: 0,
    selling_price_mxn: 0,
    suggested_price_mxn: null,
    earnings_usd: 0,
    earnings_mxn: 0,
    discount_percent: 0,
    discounted_price_mxn: 0,
    earnings_with_discount_usd: 0,
    earnings_with_discount_mxn: 0,
});

const costRules = [required('Cost is required'), numeric('Must be a number'), min(0, 'Must be 0 or more')];
const moneyRules = [numeric('Must be a number'), min(0, 'Must be 0 or more')];

const sellingPriceFieldValue = computed(() => {
    if (sellingPriceIsManual.value) {
        return run.selling_price_mxn ?? null;
    }

    return result.suggested_price_mxn ?? null;
});

function onSellingPriceInput(value) {
    if (value === '' || value === null || value === undefined) {
        sellingPriceIsManual.value = false;
        run.selling_price_mxn = null;

        return;
    }

    sellingPriceIsManual.value = true;
    run.selling_price_mxn = Number(value);
}

async function loadVariants() {
    variantsLoading.value = true;

    try {
        const data = await http.get('/api/stock?per_page=100');

        variantItems.value = (data.data ?? []).map((variant) => ({
            ...variant,
            display: `${variant.name} — ${variant.product_name}`,
            cost_usd: Number(variant.cost_usd) || 0,
        }));
    } finally {
        variantsLoading.value = false;
    }
}

function onVariantPick(variantId) {
    if (!variantId) {
        return;
    }

    const variant = variantItems.value.find((item) => item.id === variantId);

    if (!variant) {
        return;
    }

    if (variant.cost_usd > 0) {
        run.cost_usd = variant.cost_usd;
    }
}

function applySettingsOverrides() {
    if (!settings.value['fx.usd_to_mxn']) {
        return;
    }

    run.fx_rate = settings.value['fx.usd_to_mxn'];
    run.margin_percent = settings.value['pricing.margin_percent'];
    run.platform_fee_percent = settings.value['pricing.platform_fee_percent'];
    run.packaging_fee_mxn = settings.value['pricing.packaging_fee_mxn'];
    run.discount_percent = settings.value['pricing.discount_percent'];
}

async function loadSettings() {
    const data = await http.get('/api/settings');
    settings.value = data;
    applySettingsOverrides();
}

function recalculate() {
    if (run.cost_usd === null || run.cost_usd === '') {
        return;
    }

    error.value = '';

    try {
        const data = calculatePricing({
            cost_usd: run.cost_usd,
            shipping_usd: run.shipping_usd || 0,
            selling_price_mxn: run.selling_price_mxn ?? null,
            fx_rate: run.fx_rate ?? settings.value['fx.usd_to_mxn'],
            margin_percent: run.margin_percent ?? settings.value['pricing.margin_percent'],
            platform_fee_percent: run.platform_fee_percent ?? settings.value['pricing.platform_fee_percent'],
            packaging_fee_mxn: run.packaging_fee_mxn ?? settings.value['pricing.packaging_fee_mxn'],
            discount_percent: run.discount_percent ?? settings.value['pricing.discount_percent'],
        });

        Object.assign(result, data);
    } catch (err) {
        error.value = err.message ?? 'Calculation failed.';
    }
}

watch(run, recalculate, { deep: true });

onMounted(async () => {
    await loadSettings();
    await loadVariants();
    recalculate();
});
</script>

<style scoped>
.flex-1 {
    flex: 1;
}

.summary-banner {
    background: rgb(var(--v-theme-surface-light));
}
</style>
