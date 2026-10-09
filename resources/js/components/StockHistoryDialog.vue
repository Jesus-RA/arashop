<template>
    <v-dialog v-model="model" max-width="900">
        <v-card>
            <v-card-title>
                <div class="d-flex align-center justify-space-between">
                    <span class="text-h6">Stock history</span>
                    <v-btn icon="mdi-close" variant="text" @click="model = false"></v-btn>
                </div>
                <div class="d-flex flex-column">
                    <p class="my-0 text-body-small text-medium-emphasis">
                        Product - Variant
                    </p>
                    <p class="text-body-medium font-weight-semibold my-0">
                        {{ item?.product_name }} — {{ item?.name }}
                    </p>
                </div>
            </v-card-title>

            <v-card-text class="px-2">
                <v-data-table
                    :headers="headers"
                    :items="history"
                    item-key="id"
                    :loading="loading"
                    density="compact"
                >
                    <template #item.created_at="{ item }">
                        <span>{{ formatDateTime(item.created_at) }}</span>
                    </template>

                    <template #item.quantity_change="{ item }">
                        <span
                            :class="{ 'text-green-darken-1': item.quantity_change >= 0, 'text-red': item.quantity_change < 0 }"
                        >
                            {{ item.quantity_change >= 0 ? '+' : '' }}{{ item.quantity_change }}
                        </span>
                    </template>

                    <template #item.transaction_type="{ item }">
                        {{ transactionTypeLabel(item.transaction_type) }}
                    </template>

                    <template #item.reason="{ item }">
                        <div class="font-weight-medium">{{ item.reason ?? '—' }}</div>
                        <div v-if="item.notes" class="text-body-small text-medium-emphasis">{{ item.notes }}</div>
                    </template>

                    <template #item.user="{ item }">
                        <span class="text-body-small">{{ item.user?.name ?? 'Unknown' }}</span>
                    </template>

                    <template #no-data>
                        <p class="text-body-medium text-medium-emphasis my-4">No recorded adjustments yet.</p>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import http from '../api/http';
import { formatDateTime } from '../utils/format';

const props = defineProps({
    item: { type: Object, default: null },
});

const model = defineModel({ type: Boolean, default: false });

const headers = [
    { title: 'Date', key: 'created_at', sortable: false },
    { title: 'Qty', key: 'quantity_change', sortable: false, align: 'end'},
    { title: 'Type', key: 'transaction_type', sortable: false },
    { title: 'Reason / Notes', key: 'reason', sortable: false },
    { title: 'By', key: 'user', sortable: false },
];

const transactionTypeLabels = {
    SALE: 'Sale',
    RESTOCK: 'Restock',
    MANUAL_ADJUSTMENT: 'Manual adjustment',
    ORDER_CANCELLATION: 'Order cancellation',
};

const history = ref([]);
const loading = ref(false);

function transactionTypeLabel(type) {
    return transactionTypeLabels[type] ?? type;
}

watch(model, async (open) => {
    if (!open || !props.item?.id) {
        return;
    }

    loading.value = true;
    history.value = [];

    try {
        const data = await http.get(`/api/variants/${props.item.id}/transactions?per_page=50`);

        history.value = data.data;
    } finally {
        loading.value = false;
    }
});
</script>
