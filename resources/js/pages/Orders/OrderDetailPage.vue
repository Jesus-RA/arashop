<template>
    <div>
        <div class="d-flex align-center ga-3 mb-4">
            <v-btn icon="mdi-arrow-left" variant="outlined" width="42" height="42" :to="{ name: 'orders.index' }" />
            <div class="flex-1-1-auto">
                <div class="stitch-label mb-1">Sales Orders</div>
                <h1 class="text-headline-small font-weight-bold mb-0">{{ order?.order_number ?? 'Order' }}</h1>
            </div>
            <v-spacer />
            <v-chip
                v-if="order"
                size="small"
                variant="flat"
                class="stitch-pill"
                :class="orderStatusClasses(order.status)"
            >
                <span class="fx-dot me-1" :style="{ background: orderStatusDotColor(order.status) }" />
                {{ orderStatusLabel(order.status) }}
            </v-chip>
        </div>

        <template v-if="order">
            <v-row class="mb-4">
                <v-col cols="12" sm="6" lg="3">
                    <KpiCard title="Total" icon="mdi-cash-multiple" icon-color="mint-deep">
                        {{ formatMxn(order.total_amount) }}
                    </KpiCard>
                </v-col>
                <v-col cols="12" sm="6" lg="3">
                    <KpiCard title="Items" icon="mdi-package-variant" icon-color="primary">
                        {{ itemCount }} Unit{{ itemCount === 1 ? '' : 's' }}
                    </KpiCard>
                </v-col>
                <v-col cols="12" sm="6" lg="3">
                    <KpiCard title="Unit Cost" icon="mdi-currency-usd" icon-color="status-warning">
                        {{ formatUsd(unitCostTotal) }}
                    </KpiCard>
                </v-col>
                <v-col cols="12" sm="6" lg="3">
                    <KpiCard title="Margin" icon="mdi-trending-up" icon-color="primary">
                        <span class="text-mint-deep">{{ margin.toFixed(1) }}%</span>
                    </KpiCard>
                </v-col>
            </v-row>

            <v-card class="rounded-lg pa-4 mb-4">
                <div class="d-flex flex-wrap align-center justify-space-between gap-3">
                    <div>
                        <div class="stitch-label mb-1">Created by</div>
                        <div class="font-weight-medium">{{ order.user?.name ?? 'System' }}</div>
                    </div>
                    <div>
                        <div class="stitch-label mb-1">Placed</div>
                        <div class="font-weight-medium">{{ formatDateTime(order.created_at) }}</div>
                    </div>
                    <div>
                        <div class="stitch-label mb-1">Updated</div>
                        <div class="font-weight-medium">{{ formatDateTime(order.updated_at) }}</div>
                    </div>
                    <div class="d-flex align-center ga-2">
                        <v-btn
                            v-if="canMarkPaid"
                            variant="tonal"
                            color="mint-deep"
                            prepend-icon="mdi-cash"
                            :loading="updating"
                            :disabled="updating"
                            @click="setStatus('paid')"
                        >
                            Mark as paid
                        </v-btn>
                        <v-btn
                            v-if="canCancel"
                            variant="tonal"
                            color="error"
                            prepend-icon="mdi-close-circle-outline"
                            :loading="updating"
                            :disabled="updating"
                            @click="confirmCancel = true"
                        >
                            Cancel order
                        </v-btn>
                    </div>
                </div>
            </v-card>

            <v-card class="overflow-hidden rounded-lg">
                <v-card-title class="d-flex align-center justify-space-between ga-2 p-4">
                    <div class="d-flex align-center ga-3">
                        <div class="stitch-kpi-icon rounded-lg bg-surface-cream-subtle">
                            <v-icon icon="mdi-receipt-text-outline" color="status-warning" size="18" />
                        </div>
                        <div>
                            <h2 class="text-body-large font-weight-bold my-0">Order Lines</h2>
                            <p class="text-body-small text-medium-emphasis my-0">Snapshotted at checkout — prices never re-open.</p>
                        </div>
                    </div>
                </v-card-title>
                <v-data-table-virtual
                    :items="order.items"
                    :headers="headers"
                    density="comfortable"
                    disable-sort
                    class="stitch-table"
                >
                    <template #item.name="{ item }">
                        <div class="font-weight-medium">{{ item.variant?.name ?? 'Deleted variant' }}</div>
                        <div class="d-flex align-center ga-1 mt-1">
                            <v-chip
                                v-for="(value, key) in item.variant?.attributes ?? {}"
                                :key="key"
                                size="x-small"
                                variant="flat"
                                class="stitch-pill bg-canvas-tint text-on-surface-variant"
                            >
                                {{ key }}: {{ value }}
                            </v-chip>
                        </div>
                    </template>

                    <template #item.unit_price_mxn="{ item }">
                        <span class="tabular-nums">{{ formatMxn(item.unit_price_mxn) }}</span>
                    </template>

                    <template #item.unit_cost_usd="{ item }">
                        <span class="tabular-nums">{{ formatUsd(item.unit_cost_usd) }}</span>
                    </template>

                    <template #item.subtotal="{ item }">
                        <span class="tabular-nums font-weight-semibold">{{ formatMxn(item.subtotal) }}</span>
                    </template>
                </v-data-table-virtual>
            </v-card>
        </template>

        <v-dialog v-model="confirmCancel" max-width="420">
            <v-card class="pa-2">
                <v-card-text class="text-center pt-6">
                    <div class="mx-auto mb-4 d-flex align-center justify-center rounded-circle bg-error-container delete-icon">
                        <v-icon icon="mdi-close-circle-outline" color="status-error" size="28" />
                    </div>
                    <h3 class="text-body-large font-weight-bold">Cancel order</h3>
                    <p class="text-body-medium text-medium-emphasis mb-0 mt-2">
                        Cancel {{ order?.order_number }}? Sold stock will be returned to the variants.
                    </p>
                </v-card-text>
                <v-card-actions class="justify-end pb-4 pe-4">
                    <v-btn variant="tonal" @click="confirmCancel = false">Keep order</v-btn>
                    <v-btn color="error" :loading="updating" :disabled="updating" @click="setStatus('cancelled')">Cancel order</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import KpiCard from '../../components/KpiCard.vue';
import http from '../../api/http';
import { useUiStore } from '../../store/ui';
import { formatDateTime, formatMxn, formatUsd } from '../../utils/format';
import { orderStatusClasses, orderStatusDotColor, orderStatusLabel } from '../../utils/orderStatus';

const route = useRoute();
const uiStore = useUiStore();

const headers = [
    { title: 'Variant', key: 'name', sortable: false },
    { title: 'Unit Price (MXN)', key: 'unit_price_mxn', sortable: false, width: '150px' },
    { title: 'Unit Cost (USD)', key: 'unit_cost_usd', sortable: false, width: '150px' },
    { title: 'Qty', key: 'quantity', sortable: false, width: '80px' },
    { title: 'Subtotal', key: 'subtotal', sortable: false, width: '150px' },
];

const order = ref(null);
const updating = ref(false);
const confirmCancel = ref(false);

const canMarkPaid = computed(() => order.value?.status === 'pending');
const canCancel = computed(() => ['pending', 'paid'].includes(order.value?.status));

const itemCount = computed(() => order.value?.items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0) ?? 0);
const unitCostTotal = computed(() =>
    order.value?.items.reduce((sum, item) => sum + (Number(item.unit_cost_usd) || 0) * (Number(item.quantity) || 0), 0) ?? 0,
);
const margin = computed(() => {
    const total = Number(order.value?.total_amount) || 0;

    if (total <= 0) {
        return 0;
    }

    return ((total - unitCostTotal.value * 20.5) / total) * 100;
});

async function loadOrder() {
    const { data } = await http.get(`/api/orders/${route.params.id}`);

    order.value = data;
}

async function setStatus(status) {
    updating.value = true;

    try {
        const { data } = await http.patch(`/api/orders/${route.params.id}`, { status });

        order.value = data;
        confirmCancel.value = false;

        uiStore.notify(`Order ${status === 'cancelled' ? 'cancelled' : `marked as ${status}`}` +
            (status === 'cancelled' ? ' — stock restored' : ''));
    } catch (error) {
        const message = Object.values(error.data?.errors ?? {}).flat()[0] ?? 'Could not update the order.';
        uiStore.notify(message, { color: 'error', timeout: 5000 });
    } finally {
        updating.value = false;
    }
}

onMounted(loadOrder);
</script>

<style scoped>
.flex-1-1-auto {
    flex: 1 1 auto;
}

.delete-icon {
    width: 48px;
    height: 48px;
}
</style>