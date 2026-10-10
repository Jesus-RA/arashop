<template>
    <main class="d-flex flex-column gr-4">
        <header class="d-flex flex-wrap align-end justify-space-between ga-3">
            <div>
                <h1 class="text-headline-small font-weight-bold my-0">Stock</h1>
                <p class="text-body-medium text-medium-emphasis my-0">
                    Monitor unit balances across every cross-border variant.
                </p>
            </div>
        </header>

        <section class="d-flex flex-wrap ga-3">
            <KpiCard title="Total Variants Monitored" icon="mdi-tune" icon-color="primary" class="flex-grow-1">
                {{ totalVariants }}
                <span class="text-body-small text-medium-emphasis ms-1">Series</span>
            </KpiCard>
            <KpiCard title="Total Stock" icon="mdi-warehouse" icon-color="info" class="flex-grow-1">
                <span class="tabular-nums">{{ onHandTotal }}</span>
                <span class="text-body-small text-medium-emphasis ms-1">On hand</span>
            </KpiCard>
            <KpiCard title="Low-Stock Alerts" icon="mdi-alert" icon-color="warning" class="flex-grow-1">
                <span class="text-error">{{ lowStockCount }}</span>
                <span class="text-body-small text-medium-emphasis ms-1">Below {{ lowStockThreshold }}</span>
            </KpiCard>
        </section>

        <v-card class="overflow-hidden">
            <v-data-table-server
                :headers="headers"
                :items="filteredItems"
                :loading="loading"
                :items-length="total"
                :items-per-page="perPage"
                :items-per-page-options="[10, 20, 50]"
                item-key="id"
                density="comfortable"
                @update:options="onOptions"
            >
                <template #top>
                    <article class="d-flex flex-wrap align-center justify-space-between ga-3 px-4 py-2">
                        <div class="d-flex flex-wrap align-center ga-3 flex-1">
                            <v-text-field
                                v-model.trim="search"
                                label="Search stock"
                                prepend-inner-icon="mdi-magnify"
                                clearable
                                hide-details
                                variant="filled"
                                class="search-field"
                                @update:model-value="load"
                            />
                            <div class="d-flex flex-shrink-0 align-center ga-1">
                                <v-chip
                                    v-for="pill in filterPills"
                                    :key="pill.value"
                                    class="text-body-small font-weight-medium"
                                    size="small"
                                    variant="flat"
                                    :color="activeFilter === pill.value ? 'primary' : 'secondary-lighten-2'"
                                    @click="activeFilter = pill.value"
                                >
                                    {{ pill.label }}
                                </v-chip>
                            </div>
                        </div>
                        <span class="text-body-small text-medium-emphasis">
                            Showing {{ currentStart }}-{{ currentEnd }} of {{ total }} variants
                        </span>
                    </article>
                </template>
                <template #item.description="{ item }">
                    <div class="font-weight-medium">{{ item.product_name }}</div>
                </template>

                <template #item.attributes="{ item }">
                    <v-chip
                        v-for="(value, key) in item.attributes"
                        :key="key"
                        size="small"
                        variant="flat"
                        class="stitch-pill bg-surface-light text-on-surface-variant me-1"
                    >
                        {{ key }}: {{ value }}
                    </v-chip>
                </template>

                <template #item.cost_usd="{ item }">
                    <span class="tabular-nums">{{ formatUsd(item.cost_usd) }}</span>
                </template>

                <template #item.price_mxn="{ item }">
                    <span class="tabular-nums font-weight-semibold">{{ formatMxn(item.price_mxn) }}</span>
                </template>

                <template #item.stock_quantity="{ item }">
                    <v-chip
                        size="small"
                        variant="flat"
                        class="stitch-pill"
                        :class="stockClasses(item.stock_quantity)"
                    >
                        {{ item.stock_quantity }} units
                    </v-chip>
                </template>

                <template #item.actions="{ item }">
                    <v-btn size="small" variant="tonal" color="primary" class="me-1" @click="openAdjust(item)">
                        Adjust
                    </v-btn>
                    <v-btn size="small" variant="text" @click="openHistory(item)">
                        History
                    </v-btn>
                </template>
            </v-data-table-server>
        </v-card>

        <AdjustStockDialog v-model="adjustDialog.show" :item="adjustDialog.item" @saved="load" />

        <StockHistoryDialog v-model="historyDialog.show" :item="historyDialog.item" />
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import KpiCard from '../../components/KpiCard.vue';
import AdjustStockDialog from '../../components/AdjustStockDialog.vue';
import StockHistoryDialog from '../../components/StockHistoryDialog.vue';
import http from '../../api/http';
import { formatMxn, formatUsd } from '../../utils/format';
import { stockClasses } from '../../utils/stock';

const headers = [
    { title: 'Product', key: 'description', sortable: false },
    { title: 'Attributes', key: 'attributes', sortable: false },
    { title: 'Cost (USD)', key: 'cost_usd', sortable: false },
    { title: 'Price (MXN)', key: 'price_mxn', sortable: false },
    { title: 'Stock', key: 'stock_quantity', sortable: false },
    { title: '', key: 'actions', sortable: false, width: '190px' },
];

const lowStockThreshold = 5;

const items = ref([]);
const loading = ref(false);
const search = ref('');
const total = ref(0);
const perPage = ref(10);
const activeFilter = ref('all');
let loadSequence = 0;

const adjustDialog = ref({ show: false, item: null });

const historyDialog = ref({ show: false, item: null });

const filterPills = [
    { label: 'All', value: 'all' },
    { label: 'Low Stock', value: 'low' },
    { label: 'Out of Stock', value: 'out' },
];

const filteredItems = computed(() => {
    if (activeFilter.value === 'all') return items.value;

    if (activeFilter.value === 'low') {
        return items.value.filter((item) => item.stock_quantity <= lowStockThreshold && item.stock_quantity > 0);
    }

    return items.value.filter((item) => item.stock_quantity === 0);
});

const totalVariants = computed(() => items.value.length || total.value);
const onHandTotal = computed(() => items.value.reduce((sum, item) => sum + (Number(item.stock_quantity) || 0), 0));
const lowStockCount = computed(() => items.value.filter((item) => item.stock_quantity <= lowStockThreshold).length);

const currentStart = computed(() => (total.value === 0 ? 0 : (perPage.value * 1) - perPage.value + 1));
const currentEnd = computed(() => Math.min(perPage.value, total.value));

async function load() {
    const sequence = ++loadSequence;
    loading.value = true;

    try {
        const data = await http.get(
            `/api/stock?per_page=${perPage.value}&search=${encodeURIComponent(search.value ?? '')}`,
        );

        if (sequence !== loadSequence) return;

        items.value = data.data;
        total.value = data.meta?.total ?? 0;
    } finally {
        if (sequence === loadSequence) {
            loading.value = false;
        }
    }
}

function onOptions({ page, itemsPerPage }) {
    perPage.value = itemsPerPage;
    load();
}

function openAdjust(item) {
    adjustDialog.value = { show: true, item };
}

function openHistory(item) {
    historyDialog.value = { show: true, item };
}

onMounted(load);
</script>

<style scoped>
.search-field {
    flex: 1 1 0%;
    min-width: 260px;
}
</style>
