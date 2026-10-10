<template>
    <div class="d-flex flex-column gr-4">
        <div class="d-flex flex-wrap align-end justify-space-between ga-3">
            <div>
                <h1 class="text-headline-small font-weight-bold my-0">Dashboard</h1>
                <p class="text-body-medium text-medium-emphasis my-0">
                    Live US → MX inventory, sales performance and stock health.
                </p>
            </div>
        </div>

        <section class="d-flex flex-wrap ga-3">
            <KpiCard title="Profit" icon="mdi-cash-plus" icon-color="success" class="flex-grow-1">
                {{ formatMxn(overallProfitMxn) }}
                <span class="text-body-small text-medium-emphasis">MXN after COGS</span>
            </KpiCard>
            <KpiCard title="Orders Placed" icon="mdi-receipt-text-outline" icon-color="primary" class="flex-grow-1">
                {{ ordersCount }}
                <span class="text-body-small text-medium-emphasis">{{ unitsSold }} units sold</span>
            </KpiCard>
            <KpiCard title="Invested" icon="mdi-cash-multiple" icon-color="warning" class="flex-grow-1">
                {{ formatUsd(totalInvestedUsd) }}
                <span class="text-body-small text-medium-emphasis">USD at cost</span>
            </KpiCard>
            <KpiCard title="Projected Resale Value" icon="mdi-trending-up" icon-color="info" class="flex-grow-1">
                {{ formatMxn(projectedResaleMxn) }}
                <span class="text-body-small text-medium-emphasis">MXN if all sold</span>
            </KpiCard>
            <KpiCard title="Current Total Stock" icon="mdi-cube-outline" icon-color="primary" class="flex-grow-1">
                {{ totalStockUnits }}
                <span class="text-body-small text-medium-emphasis">units on hand</span>
            </KpiCard>
        </section>

        <section class="d-flex flex-column">
            <header class="d-flex flex-wrap align-center justify-space-between ga-3">
                <div class="d-flex align-center ga-3">
                    <div class="stitch-kpi-icon rounded-lg bg-surface-light">
                        <v-icon icon="mdi-alert" color="premium" size="18" />
                    </div>
                    <div class="d-flex flex-column">
                        <h2 class="text-body-large font-weight-bold my-0">Stock Health &amp; Restock</h2>
                        <p class="text-body-small text-medium-emphasis my-0">Cross-border replenishment indicators</p>
                    </div>
                </div>
                <v-chip size="small" variant="flat" class="stitch-pill bg-warning">{{ criticalCount }} Critical</v-chip>
            </header>

            <v-card class="rounded-lg mt-2">
                <v-data-table
                    :headers="stockAlertColumns"
                    :items="stockAlerts"
                    item-key="id"
                    density="comfortable"
                    disable-sort
                    hide-default-footer
                >
                    <template #item.product="{ item }">
                        <div class="d-flex align-center ga-3">
                            <div class="d-flex align-center justify-center rounded-lg bg-surface-light" style="width: 36px; height: 36px; flex-shrink: 0">
                                <v-icon icon="mdi-package-variant" size="18" color="on-surface-variant" />
                            </div>
                            <div style="min-width: 0">
                                <div class="text-body-medium font-weight-medium text-truncate">{{ item.product_name }}</div>
                                <div class="text-body-small text-medium-emphasis text-truncate">{{ item.name }}</div>
                            </div>
                        </div>
                    </template>

                    <template #item.stock_quantity="{ item }">
                        <v-chip
                            size="small"
                            variant="flat"
                            class="stitch-pill"
                            :class="item.stock_quantity <= lowStockThreshold ? 'bg-warning-lighten-5 text-premium' : 'bg-info-lighten-5 text-info'"
                        >
                            {{ item.stock_quantity }} units
                        </v-chip>
                    </template>

                    <template #item.action>
                        <v-btn variant="tonal" size="small" color="primary" :to="{ name: 'stock.index' }">
                            Adjust Stock
                        </v-btn>
                    </template>

                    <template #no-data>
                        <div class="text-body-medium text-medium-emphasis my-4">Every variant is above the restock threshold.</div>
                    </template>
                </v-data-table>
            </v-card>

            <v-card class="rounded-lg pa-3 mt-2">
                <div class="d-flex align-center justify-space-between mb-1">
                    <span class="text-body-small text-medium-emphasis">Healthy Stock Ratio</span>
                    <span class="text-body-medium font-weight-semibold tabular-nums">{{ healthyRatio.toFixed(1) }}%</span>
                </div>
                <v-progress-linear height="8" :model-value="healthyRatio" color="info" rounded />
            </v-card>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import KpiCard from '../components/KpiCard.vue';
import http from '../api/http';
import { formatMxn, formatUsd } from '../utils/format';
import { useSettingsStore } from '../store/settings';

const settingsStore = useSettingsStore();

const stockRows = ref([]);
const stockSummary = ref({
    variants_count: 0,
    total_stock: 0,
    low_stock_count: 0,
    total_cost_usd: '0.00',
    total_resale_mxn: '0.00',
});
const orderSummary = ref({ orders_count: 0, units_sold: 0, revenue_mxn: '0.00', cost_usd: '0.00' });

const lowStockThreshold = 5;

const stockAlertColumns = [
    { key: 'product', title: 'Product', sortable: false },
    { key: 'stock_quantity', title: 'Units Left', sortable: false, align: 'end' },
    { key: 'action', title: 'Action', sortable: false, align: 'center' },
];

const ordersCount = computed(() => orderSummary.value.orders_count ?? 0);
const unitsSold = computed(() => orderSummary.value.units_sold ?? 0);

const overallProfitMxn = computed(
    () => (Number(orderSummary.value.revenue_mxn) || 0) - (Number(orderSummary.value.cost_usd) || 0) * settingsStore.fxUsdToMxn,
);

const totalInvestedUsd = computed(() => Number(stockSummary.value.total_cost_usd) || 0);
const projectedResaleMxn = computed(() => Number(stockSummary.value.total_resale_mxn) || 0);
const totalStockUnits = computed(() => stockSummary.value.total_stock ?? 0);
const criticalCount = computed(() => stockSummary.value.low_stock_count ?? 0);

const healthyRatio = computed(() => {
    const variantsCount = stockSummary.value.variants_count ?? 0;

    if (!variantsCount) return 0;

    return ((variantsCount - (stockSummary.value.low_stock_count ?? 0)) / variantsCount) * 100;
});

const stockAlerts = computed(() =>
    [...stockRows.value]
        .sort((a, b) => a.stock_quantity - b.stock_quantity)
        .slice(0, 8),
);

async function load() {
    const [stockData, stockSummaryData, orderData] = await Promise.all([
        http.get('/api/stock?per_page=100'),
        http.get(`/api/stock/summary?low_stock_threshold=${lowStockThreshold}`),
        http.get('/api/orders/summary'),
    ]);

    await settingsStore.load();

    orderSummary.value = orderData.data;
    stockSummary.value = stockSummaryData.data;

    stockRows.value = stockData.data.map((row) => ({
        id: row.id,
        product_name: row.product_name ?? row.name,
        name: row.name,
        stock_quantity: row.stock_quantity,
    }));
}

onMounted(load);
</script>
