<template>
    <div>
        <h1 class="text-h4 mb-4">Settings</h1>

        <v-form @submit.prevent="save">
            <v-alert
                v-if="saved"
                type="success"
                variant="tonal"
                class="mb-4"
            >
                Settings saved
            </v-alert>

            <v-card>
                <v-card-title>Currency</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model.number="form['fx.usd_to_mxn']"
                                label="USD to MXN rate"
                                type="number"
                                step="0.01"
                                min="0"
                                :error-messages="fieldErrors('fx.usd_to_mxn')"
                            />
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>

            <v-card class="mt-4">
                <v-card-title>Pricing defaults</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="12" md="3">
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
                        <v-col cols="12" md="3">
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
                        <v-col cols="12" md="3">
                            <v-text-field
                                v-model.number="form['pricing.packaging_fee_mxn']"
                                label="Packaging fee (MXN)"
                                type="number"
                                min="0"
                                step="0.5"
                                :error-messages="fieldErrors('pricing.packaging_fee_mxn')"
                            />
                        </v-col>
                        <v-col cols="12" md="3">
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
                </v-card-text>
            </v-card>

            <div class="d-flex justify-end mt-4">
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