<template>
    <v-dialog v-model="model" max-width="520">
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between">
                <span class="text-h6">Adjust Stock — {{ item?.name }}</span>
            </v-card-title>

            <v-card-text class="d-flex flex-column ga-5">
                <div class="d-flex flex-column ga-2">
                    <p class="my-0 text-body-small">
                        Adjustment Overview
                    </p>
                    <div class="pa-3 border-sm rounded-lg">
                        <div class="d-flex align-center justify-space-between">
                            <div class="d-flex flex-column align-center align-self-start ga-2">
                                <span class="text-body-medium text-medium-emphasis">Current Stock</span>
                                <span class="text-title-large font-weight-bold">{{ item?.stock_quantity }}</span>
                            </div>
                            <v-icon icon="mdi-arrow-right"></v-icon>
                            <div class="d-flex flex-column ga-2">
                                <div class="d-flex flex-column align-center ga-2">
                                    <div class="text-body-medium text-medium-emphasis">New Stock will be:</div>
                                    <span class="text-title-large font-weight-bold" :class="projectedStock < 0 ? 'text-error' : ''">
                                        {{ projectedStock }}
                                    </span>
                                </div>

                                <v-chip size="small" variant="flat" class="font-weight-bold align-self-center" :class="adjustForm.quantity_change >= 0 ? 'bg-green-lighten-5 text-green' : 'bg-red-lighten-5 text-red'">
                                    {{ adjustForm.quantity_change >= 0 ? '+' : '' }}{{ adjustForm.quantity_change }} unit(s)
                                </v-chip>
                            </div>
                        </div>
                    </div>
                </div>

                <v-form ref="adjustFormRef" class="d-flex flex-column gr-3" @submit.prevent="submitAdjust">
                    <v-text-field
                        v-model.number="adjustForm.quantity_change"
                        label="Quantity change"
                        type="number"
                        variant="outlined"
                        :rules="adjustQuantityRules"
                    />
                    <v-select
                        v-model="adjustForm.transaction_type"
                        :items="transactionTypeOptions"
                        label="Transaction type"
                        variant="outlined"
                        :rules="[required('Transaction type is required')]"
                    />
                    <v-select
                        v-if="adjustForm.transaction_type === 'MANUAL_ADJUSTMENT'"
                        v-model="adjustForm.reason"
                        :items="reasonOptions"
                        label="Reason"
                        variant="outlined"
                        :rules="[required('Reason is required')]"
                    />
                    <v-textarea
                        v-model.trim="adjustForm.notes"
                        label="Notes"
                        rows="2"
                        variant="outlined"
                    />

                    <v-alert
                        v-if="submitError"
                        type="error"
                        variant="tonal"
                        class="mt-2"
                    >
                        <ul class="mb-0 ps-4">
                            <li v-for="(message, i) in submitError" :key="i">{{ message }}</li>
                        </ul>
                    </v-alert>

                    <v-alert v-if="projectedStock < 0" type="error" variant="tonal" density="compact">
                        Stock would drop below zero. Review the quantity before applying.
                    </v-alert>
                    <v-alert v-else type="info" variant="tonal" density="compact">
                        This movement will be recorded in the stock ledger.
                    </v-alert>
                </v-form>
            </v-card-text>

            <v-card-actions class="justify-end">
                <v-btn variant="text" @click="model = false">Cancel</v-btn>
                <v-btn
                    variant="elevated"
                    color="primary"
                    :loading="adjusting"
                    :disabled="adjusting"
                    @click="submitAdjust"
                >
                    Apply
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import http from '../api/http';
import { numeric, required } from '../utils/validation';

const props = defineProps({
    item: { type: Object, default: null },
});

const emit = defineEmits(['saved']);

const model = defineModel({ type: Boolean, default: false });

const adjustFormRef = ref(null);
const adjusting = ref(false);
const submitError = ref(null);
const adjustForm = reactive({ quantity_change: 0, transaction_type: 'MANUAL_ADJUSTMENT', reason: '', notes: '' });

const reasonOptions = [
    { title: 'Initial', value: 'initial' },
    { title: 'Purchase', value: 'purchase' },
    { title: 'Sale', value: 'sale' },
    { title: 'Damaged', value: 'damaged' },
    { title: 'Adjustment', value: 'adjustment' },
    { title: 'Return', value: 'return' },
];
const transactionTypeOptions = [
    { title: 'Sale', value: 'SALE' },
    { title: 'Restock', value: 'RESTOCK' },
    { title: 'Manual adjustment', value: 'MANUAL_ADJUSTMENT' },
];

const projectedStock = computed(() => {
    if (!props.item) {
        return null;
    }

    return props.item.stock_quantity + Number(adjustForm.quantity_change ?? 0);
});

function notOversell(value) {
    const current = props.item?.stock_quantity ?? 0;
    const projected = current + Number(value ?? 0);

    return projected >= 0 || 'Stock cannot drop below zero';
}

const adjustQuantityRules = [
    required('Quantity change is required'),
    numeric('Must be a number'),
    notOversell,
];

watch(model, async (open) => {
    if (!open) {
        return;
    }

    adjustForm.quantity_change = 0;
    adjustForm.transaction_type = 'MANUAL_ADJUSTMENT';
    adjustForm.reason = '';
    adjustForm.notes = '';
    submitError.value = null;

    await nextTick();
    adjustFormRef.value?.resetValidation();
});

async function submitAdjust() {
    const { valid } = await adjustFormRef.value.validate();

    if (!valid) {
        return;
    }

    adjusting.value = true;
    submitError.value = null;

    try {
        await http.post('/api/inventory/adjust', {
            variant_id: props.item.id,
            quantity_change: adjustForm.quantity_change,
            transaction_type: adjustForm.transaction_type,
            reason: adjustForm.reason ?? null,
            notes: adjustForm.notes ?? null,
        });

        model.value = false;
        emit('saved');
    } catch (error) {
        submitError.value = Object.values(error.data?.errors ?? {}).flat();
    } finally {
        adjusting.value = false;
    }
}
</script>
