<template>
    <div>
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-5">
            <div>
                <div class="stitch-label mb-1">Cross-Border Arbitrage Manager</div>
                <h1 class="text-headline-small font-weight-bold mb-0">Dashboard</h1>
                <p class="text-body-medium text-medium-emphasis mb-0 mt-1">
                    Live US → MX inventory, price spreads and stock health.
                </p>
            </div>
            <v-btn color="primary" prepend-icon="mdi-plus" :to="{ name: 'products.create' }">
                New product
            </v-btn>
        </div>

        <v-row>
            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Active Products &amp; Catalog</span>
                        <div class="stitch-kpi-icon rounded-lg bg-surface-rose-subtle">
                            <v-icon icon="mdi-package-variant" color="primary" size="18" />
                        </div>
                    </div>
                    <div class="d-flex align-baseline ga-2 mt-3">
                        <span class="text-headline-small font-weight-bold">{{ productsCount }}</span>
                    </div>
                    <div class="d-flex align-center ga-2 mt-1">
                        <v-chip size="small" variant="flat" class="stitch-pill bg-surface-rose-subtle text-primary">{{ variantCount }} Variants</v-chip>
                        <span class="text-body-small text-medium-emphasis">products tracked</span>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Total Portfolio Cost</span>
                        <div class="stitch-kpi-icon rounded-lg bg-surface-cream-subtle">
                            <v-icon icon="mdi-cash-multiple" color="tertiary" size="18" />
                        </div>
                    </div>
                    <div class="d-flex align-baseline ga-2 mt-3">
                        <span class="text-headline-small font-weight-bold tabular-nums">{{ formatUsd(totalCostUsd) }}</span>
                        <span class="text-body-small text-tertiary font-weight-semibold">USD</span>
                    </div>
                    <div class="text-body-small text-medium-emphasis mt-1">Sourced from Amazon US catalog</div>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Projected Resale Value</span>
                        <div class="stitch-kpi-icon rounded-lg bg-surface-mint-subtle">
                            <v-icon icon="mdi-trending-up" color="mint-deep" size="18" />
                        </div>
                    </div>
                    <div class="d-flex align-baseline ga-2 mt-3">
                        <span class="text-headline-small font-weight-bold tabular-nums">{{ formatMxn(totalResaleMxn) }}</span>
                        <span class="text-body-small text-mint-deep font-weight-semibold">MXN</span>
                    </div>
                    <v-chip size="small" variant="flat" class="stitch-pill bg-surface-mint-subtle text-mint-deep mt-1">{{ avgMargin.toFixed(1) }}% Avg Margin</v-chip>
                </v-card>
            </v-col>

            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <div class="d-flex align-center ga-2">
                            <span class="text-body-small text-medium-emphasis">USD / MXN FX Stream</span>
                            <span class="fx-dot"></span>
                        </div>
                        <span class="text-body-small text-medium-emphasis tabular-nums">10m ago</span>
                    </div>
                    <div class="d-flex align-end justify-space-between mt-2">
                        <div>
                            <div class="d-flex align-baseline ga-2">
                                <span class="text-headline-small font-weight-bold tabular-nums">{{ fxRate.toFixed(2) }}</span>
                                <span class="text-body-small text-medium-emphasis">MXN</span>
                            </div>
                            <div class="d-flex align-center ga-1 text-mint-deep text-body-small mt-1">
                                <v-icon icon="mdi-arrow-up" size="14" />
                                +0.12% today
                            </div>
                        </div>
                        <svg class="flex-shrink-0" width="80" height="36" viewBox="0 0 80 36" fill="none">
                            <path
                                d="M 0,28 Q 12,25 24,30 T 48,15 T 68,10 L 80,4"
                                stroke="#1D6B6F"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                            />
                            <path
                                d="M 0,28 Q 12,25 24,30 T 48,15 T 68,10 L 80,4 L 80,36 L 0,36 Z"
                                fill="#99E1E5"
                                opacity="0.2"
                            />
                        </svg>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" lg="7">
                <v-card class="rounded-lg px-5 py-4 mb-0">
                    <div class="d-flex align-center justify-space-between mb-4">
                        <div class="d-flex align-center ga-3">
                            <div class="stitch-kpi-icon rounded-lg bg-surface-rose-subtle">
                                <v-icon icon="mdi-candlestick-chart" color="rose-deep" size="18" />
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold mb-0">High-Margin Arbitrage Opportunities</h2>
                                <p class="text-body-small text-medium-emphasis mb-0">Best validated spreads by market margin</p>
                            </div>
                        </div>
                        <router-link :to="{ name: 'products.index' }" class="text-rose-deep text-body-medium text-decoration-none">
                            View all ({{ stockRows.length }}) →
                        </router-link>
                    </div>

                    <table class="stitch-table w-100">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3 text-right">USD Cost</th>
                                <th class="px-4 py-3 text-right">MXN Price</th>
                                <th class="px-4 py-3 text-right">Net Profit</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in opportunities" :key="row.id" class="hover surface-row">
                                <td class="px-4 py-3">
                                    <div class="d-flex align-center ga-3">
                                        <div class="chip-icon rounded-lg bg-surface-container">
                                            <v-icon icon="mdi-package-variant" size="18" color="on-surface-variant" />
                                        </div>
                                        <div class="min-width-0">
                                            <div class="text-body-medium font-weight-medium text-truncate">{{ row.product_name }}</div>
                                            <div class="text-body-small text-medium-emphasis">{{ row.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-body-medium">{{ formatUsd(row.cost_usd) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-body-medium font-weight-semibold">{{ formatMxn(row.price_mxn) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-mint-deep text-body-medium font-weight-semibold tabular-nums">
                                        +{{ formatMxn(marginMxn(row)) }}
                                    </span>
                                    <v-chip size="small" variant="flat" class="stitch-pill bg-surface-mint-subtle text-mint-deep mt-1">
                                        +{{ marginPct(row).toFixed(1) }}%
                                    </v-chip>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <router-link
                                        :to="{ name: 'pricing.index' }"
                                        class="text-decoration-none"
                                    >
                                        <v-chip size="small" variant="flat" class="stitch-pill bg-surface-rose-subtle text-rose-deep">Calculate</v-chip>
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="text-body-small text-medium-emphasis mt-3">
                        Gross spread before freight &amp; platform fees.
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="5">
                <v-card class="rounded-lg px-5 py-4 h-100">
                    <div class="d-flex align-center justify-space-between mb-4">
                        <div class="d-flex align-center ga-3">
                            <div class="stitch-kpi-icon rounded-lg bg-surface-container">
                                <v-icon icon="mdi-alert" color="tertiary" size="18" />
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold mb-0">Stock Health &amp; Restock</h2>
                                <p class="text-body-small text-medium-emphasis mb-0">Cross-border replenishment indicators</p>
                            </div>
                        </div>
                        <v-chip size="small" variant="flat" class="stitch-pill bg-surface-cream-subtle text-tertiary">{{ criticalCount }} Critical</v-chip>
                    </div>

                    <div class="d-flex flex-column ga-2">
                        <div
                            v-for="row in stockAlerts"
                            :key="row.id"
                            class="d-flex align-center justify-space-between ga-2 pa-3 stitch-alert border-sm border-warm rounded-lg"
                        >
                            <div class="min-width-0">
                                <div class="d-flex align-center ga-2">
                                    <span class="text-body-medium font-weight-medium text-truncate">{{ row.product_name }}</span>
                                    <v-chip size="small" variant="flat" class="stitch-pill bg-surface-rose-subtle text-rose-deep">
                                            {{ row.stock_quantity }} left
                                        </v-chip>
                                </div>
                                <div class="text-body-small text-medium-emphasis">{{ row.name }}</div>
                            </div>
                            <v-btn
                                variant="tonal"
                                size="small"
                                color="primary"
                                :to="{ name: 'stock.index' }"
                                class="flex-shrink-0"
                            >
                                Adjust Stock
                            </v-btn>
                        </div>
                    </div>

                    <div class="stitch-health pa-3 mt-4 rounded-lg">
                        <div class="d-flex align-center justify-space-between mb-1">
                            <span class="text-body-small text-medium-emphasis">Healthy Stock Ratio</span>
                            <span class="text-body-medium font-weight-semibold tabular-nums">{{ healthyRatio.toFixed(1) }}%</span>
                        </div>
                        <v-progress-linear height="8" :model-value="healthyRatio" color="mint-deep" rounded />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-card class="rounded-lg px-5 py-4 mt-5">
            <div class="d-flex flex-wrap align-center justify-space-between ga-2 mb-4">
                <div class="d-flex align-center ga-3">
                    <div class="stitch-kpi-icon rounded-lg bg-surface-cream-subtle">
                        <v-icon icon="mdi-history" color="tertiary" size="18" />
                    </div>
                    <div>
                        <h2 class="text-body-large font-weight-bold mb-0">Recent Cross-Border Inventory Adjustments</h2>
                        <p class="text-body-small text-medium-emphasis mb-0">Latest ledger movements across your catalog</p>
                    </div>
                </div>
                <router-link :to="{ name: 'stock.index' }" class="text-rose-deep text-body-medium text-decoration-none">
                    Open stock ledger →
                </router-link>
            </div>

            <table class="stitch-table w-100">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Operator</th>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3 text-center">Movement</th>
                        <th class="px-4 py-3">Reason / Context</th>
                        <th class="px-4 py-3 text-right">On Hand</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in recentAdjustments" :key="entry.key" class="hover surface-row">
                        <td class="px-4 py-3 text-body-small text-medium-emphasis whitespace-nowrap">{{ formatTime(entry.created_at) }}</td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-center ga-2">
                                <v-avatar size="20" color="primary" variant="flat">
                                    <span class="text-body-small">{{ (entry.user?.name ?? 'A').charAt(0) }}</span>
                                </v-avatar>
                                <span class="text-body-medium">{{ entry.user?.name ?? 'Unknown' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-body-medium font-weight-medium">{{ entry.product_name }}</td>
                        <td class="px-4 py-3 text-center">
                            <v-chip
                                size="small"
                                variant="flat"
                                class="stitch-pill"
                                :class="entry.quantity_change >= 0 ? 'bg-surface-mint-subtle text-mint-deep' : 'bg-surface-rose-subtle text-rose-deep'"
                            >
                                {{ entry.quantity_change >= 0 ? '+' : '' }}{{ entry.quantity_change }} units
                            </v-chip>
                        </td>
                        <td class="px-4 py-3 text-body-small text-medium-emphasis">{{ entry.reason ?? entry.typeLabel }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-body-medium font-weight-semibold">{{ entry.balance }} in stock</td>
                    </tr>
                </tbody>
            </table>
        </v-card>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import http from '../api/http';
import { formatMxn, formatUsd } from '../utils/format';
import { useRouter } from 'vue-router';

const router = useRouter();

const productsCount = ref(0);
const variantCount = ref(0);
const totalCostUsd = ref(0);
const totalResaleMxn = ref(0);
const fxRate = ref(18.0);
const stockRows = ref([]);
const recentAdjustments = ref([]);

const lowStockThreshold = 5;

function transactionTypeLabel(type) {
    const labels = {
        SALE: 'Sale',
        RESTOCK: 'Restock',
        MANUAL_ADJUSTMENT: 'Manual adjustment',
        ORDER_CANCELLATION: 'Order cancellation',
    };

    return labels[type] ?? type;
}

const opportunities = computed(() =>
    [...stockRows.value]
        .sort((a, b) => marginPct(b) - marginPct(a))
        .slice(0, 5),
);

const stockAlerts = computed(() =>
    [...stockRows.value]
        .sort((a, b) => a.stock_quantity - b.stock_quantity)
        .slice(0, 4),
);

const criticalCount = computed(() => stockRows.value.filter((row) => row.stock_quantity <= lowStockThreshold).length);

const healthyRatio = computed(() => {
    if (!stockRows.value.length) return 0;

    const healthy = stockRows.value.filter((row) => row.stock_quantity > lowStockThreshold).length;

    return (healthy / stockRows.value.length) * 100;
});

const avgMargin = computed(() => {
    if (!stockRows.value.length) return 0;

    return stockRows.value.reduce((sum, row) => sum + marginPct(row), 0) / stockRows.value.length;
});

function costMxn(row) {
    return (Number(row.cost_usd) || 0) * fxRate.value;
}

function marginMxn(row) {
    return (Number(row.price_mxn) || 0) - costMxn(row);
}

function marginPct(row) {
    if (!row.price_mxn) return 0;

    return (marginMxn(row) / Number(row.price_mxn)) * 100;
}

function formatTime(iso) {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) return iso;

    return date.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

async function load() {
    const [productsData, stockData, settingsData] = await Promise.all([
        http.get('/api/products?per_page=50'),
        http.get('/api/stock?per_page=100'),
        http.get('/api/settings'),
    ]);

    fxRate.value = Number(settingsData['fx.usd_to_mxn']) || 18;
    productsCount.value = productsData.meta?.total ?? productsData.data.length;
    variantCount.value = productsData.data.reduce((sum, product) => sum + (product.variants_count ?? 0), 0);

    stockRows.value = stockData.data.map((row) => ({
        id: row.id,
        product_name: row.product_name ?? row.name,
        name: row.name,
        cost_usd: row.cost_usd,
        price_mxn: row.price_mxn,
        stock_quantity: row.stock_quantity,
    }));

    totalCostUsd.value = stockRows.value.reduce((sum, row) => sum + (Number(row.cost_usd) || 0), 0);
    totalResaleMxn.value = stockRows.value.reduce((sum, row) => sum + (Number(row.price_mxn) || 0), 0);

    const ledger = await Promise.allSettled(
        stockRows.value.slice(0, 4).map((row) => http.get(`/api/variants/${row.id}/transactions?per_page=1`)),
    );

    recentAdjustments.value = ledger
        .flatMap((result, index) => {
            if (result.status !== 'fulfilled' || !result.value.data.length) return [];
            const entry = result.value.data[0];
            return {
                key: `${entry.id}-${index}`,
                created_at: entry.created_at,
                user: entry.user,
                quantity_change: entry.quantity_change,
                reason: entry.reason,
                typeLabel: transactionTypeLabel(entry.transaction_type),
                product_name: stockRows.value[index].product_name,
                balance: stockRows.value[index].stock_quantity,
            };
        })
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
        .slice(0, 4);
}

onMounted(load);
</script>

<style scoped>
.min-width-0 {
    min-width: 0;
}

.chip-icon {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.surface-row:hover {
    background: rgb(var(--v-theme-surface-container-low));
}

.stitch-alert {
    background: rgb(var(--v-theme-surface-cream-subtle));
}

.stitch-health {
    background: rgb(var(--v-theme-canvas-tint));
}
</style>
