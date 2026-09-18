<template>
    <div>
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-5">
            <div>
                <div class="d-flex align-center ga-2 mb-1">
                    <span class="stitch-label">Workspace</span>
                    <v-chip size="small" variant="flat" class="stitch-pill bg-warning-lighten-5 text-premium">Environment Config</v-chip>
                </div>
                <h1 class="text-headline-small font-weight-bold mb-0">Settings</h1>
                <p class="text-body-medium text-medium-emphasis mb-0 mt-1">
                    Configure currency, FX and default pricing automations.
                </p>
            </div>
        </div>

        <v-form ref="formRef" @submit.prevent="save" class="d-flex flex-column ga-3">
            <v-row class="d-flex ga-3">
                <v-col cols="12" lg="5">
                    <v-card class="rounded-lg px-6 py-5">
                        <v-card-title class="d-flex align-center justify-space-between ga-3 pt-0 px-0 pb-3">
                            <div class="d-flex align-center gc-2">
                                <div class="stitch-kpi-icon rounded-lg bg-info-lighten-5">
                                    <span class="text-info">$</span>
                                </div>
                                <div>
                                    <h2 class="text-body-large font-weight-bold my-0">Foreign Exchange</h2>
                                    <p class="text-body-small text-medium-emphasis my-0">US Dollar → Mexican Peso</p>
                                </div>
                            </div>
                        </v-card-title>
                        <v-card-text class="px-0 pt-2">
                            <v-text-field
                                v-model.number="form['fx.usd_to_mxn']"
                                label="USD to MXN rate"
                                suffix="MXN"
                                type="number"
                                step="0.01"
                                min="0"
                                prefix="1 USD = "
                                :rules="fxRateRules"
                            />
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" lg="7">
                    <v-card class="rounded-lg px-6 py-5">
                        <v-card-title class="d-flex align-center ga-3 pa-0 pb-3">
                            <div class="stitch-kpi-icon rounded-lg bg-primary-lighten-5">
                                <span class="text-primary">%</span>
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold my-0">Pricing Defaults</h2>
                                <p class="text-body-small text-medium-emphasis my-0">Basis for the pricing calculator automation</p>
                            </div>
                        </v-card-title>
                        <v-card-text class="px-0 pt-3">
                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.number="form['pricing.margin_percent']"
                                        label="Margin (%)"
                                        type="number"
                                        min="0"
                                        max="100"
                                        suffix="%"
                                        :rules="percentRules"
                                        hint="% you want to earn from each product"
                                        persistent-hint
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.number="form['pricing.platform_fee_percent']"
                                        label="Platform fee (%)"
                                        type="number"
                                        min="0"
                                        max="100"
                                        suffix="%"
                                        :rules="percentRules"
                                        hint="% fee a platform charges you for selling there"
                                        persistent-hint
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.number="form['pricing.packaging_fee_mxn']"
                                        label="Packaging fee (MXN)"
                                        type="number"
                                        min="0"
                                        step="0.5"
                                        :rules="packagingRules"
                                    />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.number="form['pricing.discount_percent']"
                                        label="Discount (%)"
                                        type="number"
                                        min="0"
                                        max="100"
                                        suffix="%"
                                        :rules="percentRules"
                                    />
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <div class="form-actions gap-3 px-5 py-4 border-sm rounded-lg">
                <div class="d-flex align-center ga-2">
                    <span class="text-body-small text-medium-emphasis">Changes apply to future calculations</span>
                </div>
                <v-btn
                    type="submit"
                    color="primary"
                    :loading="saving"
                    :disabled="saving"
                >
                    Save settings
                </v-btn>
            </div>
        </v-form>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import http from '../../api/http';
import { useUiStore } from '../../store/ui';
import { formatMxn } from '../../utils/format';
import { max, min, numeric, required } from '../../utils/validation';

const uiStore = useUiStore();

const saving = ref(false);
const formRef = ref(null);

const form = reactive({
    'fx.usd_to_mxn': 0,
    'pricing.margin_percent': 0,
    'pricing.platform_fee_percent': 0,
    'pricing.packaging_fee_mxn': 0,
    'pricing.discount_percent': 0,
});

const fxRateRules = [required('Rate is required'), numeric('Must be a number'), min(0.01, 'Must be greater than 0')];
const percentRules = [required('Value is required'), numeric('Must be a number'), min(0, 'Must be between 0 and 100'), max(100, 'Must be between 0 and 100')];
const packagingRules = [required('Value is required'), numeric('Must be a number'), min(0, 'Must be 0 or more')];

async function loadSettings() {
    const data = await http.get('/api/settings');
    Object.assign(form, data);
}

async function save() {
    const { valid } = await formRef.value.validate();

    if (!valid) {
        return;
    }

    saving.value = true;

    try {
        const data = await http.put('/api/settings', form);
        Object.assign(form, data);
        uiStore.notify('Settings saved');
    } catch (error) {
        const message = Object.values(error.data?.errors ?? {}).flat()[0] ?? 'Could not save settings. Review the highlighted fields.';
        uiStore.notify(message, { color: 'error', timeout: 5000 });
    } finally {
        saving.value = false;
    }
}

onMounted(loadSettings);
</script>

<style scoped>
.summary-banner {
    background: rgb(var(--v-theme-surface-light));
}

.form-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    background: rgb(var(--v-theme-surface));
}
</style>
