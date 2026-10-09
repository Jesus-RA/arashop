<template>
    <main class="d-flex flex-column gr-4">
        <header>
            <div class="d-flex flex-wrap align-center justify-space-between ga-3">
                <div>
                    <h1 class="text-headline-small font-weight-bold my-0">Settings</h1>
                    <p class="text-body-medium text-medium-emphasis my-0">
                        Configure USD to MXN rate, and defaults to use on pricing calculation.
                    </p>
                </div>
            </div>
        </header>

        <v-card>
            <v-card-text class="d-flex flex-column ga-5">
                <v-form ref="formRef" @submit.prevent="save" class="d-flex flex-column ga-10">
                    <section class="d-flex flex-column ga-5 w-sm-25">
                        <div class="d-flex align-center gc-2">
                            <div class="stitch-kpi-icon rounded-lg bg-secondary-lighten-2">
                                <span class="text-primary">$</span>
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold my-0">Foreign Exchange</h2>
                                <p class="text-body-small text-medium-emphasis my-0">US Dollar → Mexican Peso</p>
                            </div>
                        </div>
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
                    </section>


                    <section class="d-flex flex-column ga-5">
                        <div class="d-flex ga-3">
                            <div class="stitch-kpi-icon rounded-lg bg-secondary-lighten-2">
                                <span class="text-primary">%</span>
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold my-0">Pricing Defaults</h2>
                                <p class="text-body-small text-medium-emphasis my-0">Basis for the pricing calculator automation</p>
                            </div>
                        </div>

                        <div class="d-flex flex-column ga-3 w-md-25">
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
                            <v-text-field
                                v-model.number="form['pricing.packaging_fee_mxn']"
                                label="Packaging fee (MXN)"
                                type="number"
                                min="0"
                                step="0.5"
                                :rules="packagingRules"
                            />
                            <v-text-field
                                v-model.number="form['pricing.discount_percent']"
                                label="Discount (%)"
                                type="number"
                                min="0"
                                max="100"
                                suffix="%"
                                :rules="percentRules"
                            />
                        </div>
                    </section>

                    <div class="d-flex align-center ga-3">
                        <v-btn
                            type="submit"
                            color="primary"
                            :loading="saving"
                            :disabled="saving"
                        >
                            Save settings
                        </v-btn>
                        <span class="text-body-small text-medium-emphasis">Changes apply to future calculations</span>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>
    </main>
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
