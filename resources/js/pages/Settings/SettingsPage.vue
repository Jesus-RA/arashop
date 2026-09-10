<template>
    <div>
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-5">
            <div>
                <div class="d-flex align-center ga-2 mb-1">
                    <span class="stitch-label">Workspace</span>
                    <v-chip size="small" variant="flat" class="stitch-pill bg-surface-cream-subtle text-tertiary">Environment Config</v-chip>
                </div>
                <h1 class="text-headline-small font-weight-bold mb-0">Settings</h1>
                <p class="text-body-medium text-medium-emphasis mb-0 mt-1">
                    Configure currency, FX and default pricing automations.
                </p>
            </div>
        </div>

        <v-form @submit.prevent="save">
            <v-fade-transition>
                <v-alert
                    v-if="saved"
                    type="success"
                    variant="tonal"
                    class="mb-4"
                >
                    Settings saved
                </v-alert>
            </v-fade-transition>

            <v-row>
                <v-col cols="12" lg="5">
                    <v-card class="rounded-lg px-6 py-5 mb-4">
                        <div class="d-flex align-center ga-3 mb-4">
                            <div class="stitch-kpi-icon rounded-lg bg-surface-mint-subtle">
                                <span class="text-mint-deep">$</span>
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold mb-0">Currency Pair</h2>
                                <p class="text-body-small text-medium-emphasis mb-0">US Dollar → Mexican Peso</p>
                            </div>
                        </div>
                        <v-text-field
                            v-model.number="form['fx.usd_to_mxn']"
                            label="USD to MXN rate"
                            suffix="MXN"
                            type="number"
                            step="0.01"
                            min="0"
                            :error-messages="fieldErrors('fx.usd_to_mxn')"
                        />
                        <div class="d-flex align-center justify-space-between pa-3 summary-banner mt-2 border-sm border-warm rounded-lg">
                            <div class="d-flex align-center ga-2">
                                <span class="font-weight-bold">$1.00</span>
                                <v-icon icon="mdi-arrow-right" size="16" color="on-surface-variant" />
                                <span class="font-weight-bold text-mint-deep">{{ formatMxn(1 * (form['fx.usd_to_mxn'] || 0)) }}</span>
                            </div>
                            <span class="text-body-small text-medium-emphasis">Live conversion preview</span>
                        </div>
                    </v-card>
                </v-col>

                <v-col cols="12" lg="7">
                    <v-card class="rounded-lg px-6 py-5 mb-4">
                        <div class="d-flex align-center ga-3 mb-4">
                            <div class="stitch-kpi-icon rounded-lg bg-surface-rose-subtle">
                                <span class="text-primary">%</span>
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold mb-0">Pricing Defaults</h2>
                                <p class="text-body-small text-medium-emphasis mb-0">Basis for the pricing calculator automation</p>
                            </div>
                        </div>
                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model.number="form['pricing.margin_percent']"
                                    label="Margin (%)"
                                    type="number"
                                    min="0"
                                    max="100"
                                    suffix="%"
                                    :error-messages="fieldErrors('pricing.margin_percent')"
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
                                    :error-messages="fieldErrors('pricing.platform_fee_percent')"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model.number="form['pricing.packaging_fee_mxn']"
                                    label="Packaging fee (MXN)"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    :error-messages="fieldErrors('pricing.packaging_fee_mxn')"
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
                                    :error-messages="fieldErrors('pricing.discount_percent')"
                                />
                            </v-col>
                        </v-row>
                    </v-card>
                </v-col>
            </v-row>

            <div class="form-actions gap-3 px-5 py-4 border-sm border-warm rounded-lg">
                <div class="d-flex align-center ga-2">
                    <v-chip size="small" variant="flat" class="stitch-pill bg-surface-mint-subtle text-mint-deep">
                        <span class="fx-dot me-1" style="background: rgb(var(--v-theme-status-success))" />
                        Live Config
                    </v-chip>
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
import { formatMxn } from '../../utils/format';

const saving = ref(false);
const errors = ref({});
const saved = ref(false);

const form = reactive({
    'fx.usd_to_mxn': 0,
    'pricing.margin_percent': 0,
    'pricing.platform_fee_percent': 0,
    'pricing.packaging_fee_mxn': 0,
    'pricing.discount_percent': 0,
});

function fieldErrors(key) {
    return errors.value[key] ?? [];
}

async function loadSettings() {
    const data = await http.get('/api/settings');
    Object.assign(form, data);
}

async function save() {
    saving.value = true;
    errors.value = {};
    saved.value = false;

    try {
        const data = await http.put('/api/settings', form);
        Object.assign(form, data);
        saved.value = true;
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

onMounted(loadSettings);
</script>

<style scoped>
.summary-banner {
    background: rgb(var(--v-theme-canvas-tint));
}

.form-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    background: rgb(var(--v-theme-surface-card));
}
</style>
