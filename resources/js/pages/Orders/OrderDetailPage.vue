<template>
    <section v-if="order" class="d-flex flex-column gr-3">
        <div>
            <v-btn prepend-icon="mdi-arrow-left" variant="text" color="primary" :to="{ name: 'orders.index' }">
                Go Back
            </v-btn>
        </div>

        <v-card class="d-flex flex-column ga-3">
            <v-card-title class="d-flex align-center gc-3">
                <h1 class="text-headline-small font-weight-bold my-0">{{ order.order_number }}</h1>
                <v-chip size="small" variant="flat" class="stitch-pill" :class="orderStatusClasses(order.status)">
                    <span class="fx-dot me-1" :style="{ background: orderStatusDotColor(order.status) }" />
                    {{ orderStatusLabel(order.status) }}
                </v-chip>

                <v-spacer></v-spacer>

                <div class="d-flex align-center ga-2">
                    <v-btn
                        v-if="canMarkPaid"
                        variant="tonal"
                        color="info"
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
            </v-card-title>

            <v-card-text class="d-flex flex-column ga-10">
                <article class="d-flex align-center ga-10">
                    <div class="d-flex flex-column">
                        <span class="text-body-small text-medium-emphasis">Created by</span>
                        <span class="font-weight-bold">{{ order.user?.name ?? 'System' }}</span>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-body-small text-medium-emphasis">Placed</span>
                        <span class="font-weight-bold">{{ formatDateTime(order.created_at) }}</span>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-body-small text-medium-emphasis">Updated</span>
                        <span class="font-weight-bold">{{ formatDateTime(order.updated_at) }}</span>
                    </div>
                </article>

                <v-row class="align-stretch">
                    <v-col cols="12" lg="8">
                        <div class="d-flex align-center ga-3 pb-3">
                            <div class="stitch-kpi-icon rounded-lg bg-secondary-lighten-2">
                                <v-icon icon="mdi-receipt-text-outline" color="primary" size="18" />
                            </div>
                            <h2 class="text-body-large font-weight-bold my-0">Items</h2>
                        </div>
                        <article>
                            <v-data-table-virtual
                                :items="order.items"
                                :items-length="order.items.length"
                                item-key="id"
                                :headers="headers"
                                density="comfortable"
                                disable-sort
                                hide-default-footer
                            >
                                <template #item.name="{ item }">
                                    {{ item.product?.name ?? 'Deleted product' }}
                                    <template v-if="item.variant?.name"> — {{ item.variant.name }}</template>
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
                            <div class="d-flex justify-space-between px-4 text-body-medium font-weight-bold">
                                <p class="my-0 py-3">Total</p>
                                <p class="my-0 py-3">{{ formatMxn(order.total_amount) }}</p>
                            </div>
                        </article>
                    </v-col>

                    <v-col cols="12" lg="4">
                        <article class="h-100 d-flex flex-column rounded-lg">
                            <header class="d-flex align-center ga-3 p-4">
                                <div class="stitch-kpi-icon rounded-lg bg-secondary-lighten-2">
                                    <v-icon icon="mdi-finance" color="primary" size="18" />
                                </div>
                                <div>
                                    <h2 class="text-body-large font-weight-bold my-0">Highlights</h2>
                                </div>
                            </header>

                            <div class="d-flex flex-column ga-4 pa-4">
                                <div class="d-flex justify-space-between">
                                    <div class="d-flex flex-column ga-1">
                                        <span class="text-body-small text-medium-emphasis">Order total</span>
                                        <span class="text-headline-medium font-weight-bold my-0 tabular-nums">{{ formatMxn(order.total_amount) }}</span>
                                    </div>

                                    <div class="d-flex flex-column ga-1">
                                        <span class="text-body-small text-medium-emphasis">Margin</span>
                                        <span class="text-headline-medium font-weight-bold my-0 tabular-nums text-green-darken-1">{{ margin.toFixed(2) }}%</span>
                                    </div>
                                </div>

                                <div class="d-flex flex-column ga-3 rounded-lg py-3">
                                    <div class="d-flex align-center ga-4">
                                        <div class="d-flex flex-column ga-3 flex-grow-1">
                                            <div class="d-flex justify-space-between ga-1">
                                                <span class="text-body-small text-medium-emphasis">Order Total</span>
                                                <span class="text-body-medium font-weight-semibold tabular-nums">{{ formatMxn(order.total_amount) }}</span>
                                            </div>
                                            <div class="d-flex justify-space-between ga-1">
                                                <span class="text-body-small text-medium-emphasis">Order Cost</span>
                                                <span class="text-body-medium font-weight-semibold tabular-nums">{{ formatMxn(totalCostMxn) }}</span>
                                            </div>
                                            <v-divider></v-divider>
                                            <div class="d-flex justify-space-between ga-1">
                                                <span class="text-body-small text-medium-emphasis">Order Profit</span>
                                                <span class="text-body-medium font-weight-semibold tabular-nums text-success-darken-1">{{ formatMxn(grossProfitMxn) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex flex-column ga-2">
                                    <div class="d-flex align-center rounded-lg overflow-hidden" style="height: 10px">
                                        <div class="h-100 bg-secondary-lighten-2" :style="{ width: `${costShare}%` }" />
                                        <div class="h-100 bg-success" :style="{ width: `${profitShare}%` }" />
                                    </div>
                                    <div class="d-flex justify-end ga-4 text-body-small text-medium-emphasis">
                                        <span class="d-flex align-center ga-1">
                                            <span class="fx-dot bg-secondary-lighten-2" /> Cost
                                        </span>
                                        <span class="d-flex align-center ga-1">
                                            <span class="fx-dot bg-success" /> Profit
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </v-col>
                </v-row>

            </v-card-text>
        </v-card>

        <ConfirmDialog
            v-model="confirmCancel"
            title="Cancel order"
            icon="mdi-close-circle-outline"
            icon-color="red"
            cancel-button-text="Keep order"
            confirm-button-text="Cancel order"
            confirm-button-color="red"
            :loading="updating"
            @confirm="setStatus('cancelled')"
        >
            <template #text>
                <p class="text-body-medium text-medium-emphasis my-0">
                    Cancel {{ order.order_number }}? Sold stock will be returned to the variants.
                </p>
            </template>
        </ConfirmDialog>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import http from '../../api/http';
import { useSettingsStore } from '../../store/settings';
import { useUiStore } from '../../store/ui';
import { formatDateTime, formatMxn, formatUsd } from '../../utils/format';
import { orderStatusClasses, orderStatusDotColor, orderStatusLabel } from '../../utils/orderStatus';

const route = useRoute();
const settingsStore = useSettingsStore();
const uiStore = useUiStore();

const headers = [
    { title: 'Product', key: 'name', sortable: false },
    { title: 'Unit Price (MXN)', key: 'unit_price_mxn', sortable: false, align: 'end' },
    { title: 'Unit Cost (USD)', key: 'unit_cost_usd', sortable: false, align: 'end' },
    { title: 'Qty', key: 'quantity', sortable: false, width: '80px' },
    { title: 'Subtotal', key: 'subtotal', sortable: false, align: 'end' },
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
const totalCostMxn = computed(() => unitCostTotal.value * settingsStore.fxUsdToMxn);
const grossProfitMxn = computed(() => {
    const total = Number(order.value?.total_amount) || 0;

    return total - totalCostMxn.value;
});
const margin = computed(() => {
    const total = Number(order.value?.total_amount) || 0;

    if (total <= 0) {
        return 0;
    }

    return (grossProfitMxn.value / total) * 100;
});
const clampedMargin = computed(() => Math.max(0, Math.min(100, margin.value)));
const marginColor = computed(() => {
    if (margin.value < 0) {
        return 'error';
    }

    if (margin.value < 25) {
        return 'warning';
    }

    return 'success';
});
const costShare = computed(() => {
    const total = Number(order.value?.total_amount) || 0;

    if (total <= 0) {
        return 0;
    }

    return Math.max(0, Math.min(100, (totalCostMxn.value / total) * 100));
});
const profitShare = computed(() => Math.max(0, 100 - costShare.value));

async function loadOrder() {
    await settingsStore.load();

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
