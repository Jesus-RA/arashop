<template>
    <div>
        <h1 class="text-h4 mb-4">Pricing calculator</h1>

        <v-row>
            <v-col cols="12" lg="5">
                <v-card class="mb-4">
                    <v-card-title>Run</v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col cols="12">
                                <v-text-field
                                    v-model.number="run.cost_usd"
                                    label="Cost (USD)"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :error-messages="fieldErrors('cost_usd')"
                                    hint="What you paid for the item"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model.number="run.shipping_usd"
                                    label="Shipping (USD)"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :error-messages="fieldErrors('shipping_usd')"
                                    hint="Per-unit shipping"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model.number="run.selling_price_mxn"
                                    label="Target selling price (MXN)"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :error-messages="fieldErrors('selling_price_mxn')"
                                    :clearable="true"
                                    hint="Leave empty to suggest a price from your margin"
                                    @update:model-value="clearEmpty(run, 'selling_price_mxn')"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <v-card>
                    <v-card-title class="d-flex align-center">
                        Overrides
                        <v-spacer />
                        <v-btn size="small" variant="tonal" @click="applySettingsOverrides">
                            Reset to settings
                        </v-btn>
                    </v-card-title>
                    <v-card-text>
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
                <v-card>
                    <v-card-title>Breakdown</v-card-title>
                    <v-card-text>
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
                                    <td>Cost + shipping</td>
                                    <td class="text-right">{{ formatUsd(result.subtotal_usd) }}</td>
                                    <td class="text-right text-medium-emphasis">{{ formatMxn(result.subtotal_mxn) }}</td>
                                </tr>
                                <tr>
                                    <td>Packaging</td>
                                    <td class="text-right">—</td>
                                    <td class="text-right text-medium-emphasis">{{ formatMxn(result.packaging_fee_mxn) }}</td>
                                </tr>
                                <tr>
                                    <td>Platform fee ({{ result.platform_fee_percent }}%)</td>
                                    <td class="text-right">—</td>
                                    <td class="text-right text-medium-emphasis">{{ formatMxn(result.platform_fee_mxn) }}</td>
                                </tr>
                                <tr>
                                    <td>Suggested price</td>
                                    <td class="text-right">—</td>
                                    <td class="text-right primary--text font-weight-bold">
                                        {{ result.suggested_price_mxn !== null ? formatMxn(result.suggested_price_mxn) : formatMxn(result.selling_price_mxn) }}
                                    </td>
                                </tr>
                                <tr class="border-top-thin">
                                    <td>Earnings at full price</td>
                                    <td class="text-right font-weight-bold">{{ formatUsd(result.earnings_usd) }}</td>
                                    <td class="text-right font-weight-bold">{{ formatMxn(result.earnings_mxn) }}</td>
                                </tr>
                                <template v-if="result.discount_percent > 0">
                                    <tr>
                                        <td>Discounted price ({{ result.discount_percent }}% off)</td>
                                        <td class="text-right">—</td>
                                        <td class="text-right text-medium-emphasis">{{ formatMxn(result.discounted_price_mxn) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Earnings after discount</td>
                                        <td class="text-right text-medium-emphasis">{{ formatUsd(result.earnings_with_discount_usd) }}</td>
                                        <td class="text-right text-medium-emphasis">{{ formatMxn(result.earnings_with_discount_mxn) }}</td>
                                    </tr>
                                </template>
                            </tbody>
                        </v-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import http from '../../api/http';
import { formatMxn, formatUsd } from '../../utils/format';

const error = ref('');
const errors = ref({});
const settings = ref({});
let calculateSequence = 0;

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

function fieldErrors(key) {
    return errors.value[key] ?? [];
}

function clearEmpty(target, key) {
    if (target[key] === '') {
        target[key] = null;
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

async function calculate() {
    if (run.cost_usd === null || run.cost_usd === '') {
        return;
    }

    errors.value = {};
    error.value = '';

    const payload = {
        cost_usd: run.cost_usd,
        shipping_usd: run.shipping_usd || 0,
        selling_price_mxn: run.selling_price_mxn ?? null,
        fx_rate: run.fx_rate,
        margin_percent: run.margin_percent,
        platform_fee_percent: run.platform_fee_percent,
        packaging_fee_mxn: run.packaging_fee_mxn,
        discount_percent: run.discount_percent,
    };

    const sequence = ++calculateSequence;

    try {
        const data = await http.post('/api/pricing/calculate', payload);

        if (sequence !== calculateSequence) {
            return;
        }

        Object.assign(result, data);
    } catch (err) {
        if (sequence !== calculateSequence) {
            return;
        }

        errors.value = err.data?.errors ?? {};
        error.value = err.data?.message ?? 'Calculation failed.';
    }
}

watch(run, () => calculate(), { deep: true });

onMounted(async () => {
    await loadSettings();
    calculate();
});
</script>