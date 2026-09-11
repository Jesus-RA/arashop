<template>
    <div>
        <div class="d-flex flex-wrap align-end justify-space-between ga-3 mb-5">
            <div>
                <div class="d-flex align-center ga-2 mb-1">
                    <span class="stitch-label">Catalog Inventory</span>
                    <v-chip size="small" variant="flat" class="text-body-small font-weight-semibold bg-surface-rose-subtle text-rose-deep">US → MX Pipeline</v-chip>
                </div>
                <h1 class="text-headline-small font-weight-bold mb-0">Products</h1>
                <p class="text-body-medium text-medium-emphasis mb-0 mt-1">
                    Manage imported Amazon US catalog entries &amp; active Mexico retail variants.
                </p>
            </div>
            <div class="d-flex align-center ga-2">
                <v-btn variant="flat" color="surface" prepend-icon="mdi-tray-arrow-down" @click="importDialogShow = true">
                    Import from CSV
                </v-btn>
                <v-btn color="primary" prepend-icon="mdi-plus" :to="{ name: 'products.create' }">
                    New product
                </v-btn>
            </div>
        </div>

        <v-row class="mb-4">
            <v-col cols="12" sm="6" lg="3">
                <KpiCard title="Listed Catalog" icon="mdi-storefront" icon-color="primary">
                    {{ listedCatalog }} Items
                </KpiCard>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <KpiCard title="Active Arbitrage Variants" icon="mdi-check-decagram" icon-color="mint-deep">
                    {{ activeVariantCount }} Active
                </KpiCard>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <KpiCard title="Low / Out of Stock" icon="mdi-alert" icon-color="status-warning">
                    <span class="text-status-warning">{{ lowStockCount }} Alerts</span>
                </KpiCard>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <KpiCard title="Avg. Mexico Margin" icon="mdi-trending-up" icon-color="mint-deep">
                    <span class="text-mint-deep">{{ avgMargin.toFixed(1) }}%</span>
                </KpiCard>
            </v-col>
        </v-row>

        <v-card class="rounded-lg pa-3 mb-4">
            <div class="d-flex flex-wrap align-center justify-space-between ga-3">
                <div class="d-flex flex-wrap align-center ga-3 flex-1">
                    <v-text-field
                        v-model.trim="search"
                        label="Search products"
                        prepend-inner-icon="mdi-magnify"
                        clearable
                        hide-details
                        density="compact"
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
                            :color="activeFilter === pill.value ? 'primary-container' : 'surface-container-high'"
                            @click="activeFilter = pill.value"
                        >
                            {{ pill.label }}
                        </v-chip>
                    </div>
                </div>
                <span class="text-body-small text-medium-emphasis">
                    Showing {{ currentStart }}-{{ currentEnd }} of {{ total }} products
                </span>
            </div>
        </v-card>

        <v-card class="overflow-hidden">
            <v-data-table-server
                :headers="headers"
                :items="filteredProducts"
                :loading="loading"
                :items-length="total"
                :items-per-page="perPage"
                :items-per-page-options="[10, 20, 50]"
                item-key="id"
                density="comfortable"
                :hide-default-footer="false"
                @update:options="onOptions"
            >
                <template #item.image_path="{ item }">
                    <v-avatar size="40" rounded="8">
                        <v-img v-if="item.image_path" :src="item.image_path" />
                        <v-icon v-else icon="mdi-package-variant-outline" />
                    </v-avatar>
                </template>

                <template #item.name="{ item }">
                    <div class="font-weight-medium">{{ item.name }}</div>
                </template>

                <template #item.status="{ item }">
                    <v-chip
                        size="small"
                        variant="flat"
                        class="text-body-small font-weight-semibold"
                        :class="statusClasses(item.status)"
                    >
                        <div class="d-flex align-center gc-2">
                            <span
                                class="fx-dot rounded-circle"
                                :style="{ background: statusDotColor(item.status) }"
                            />
                            {{ statusLabel(item.status) }}
                        </div>
                    </v-chip>
                </template>

                <template #item.variants_count="{ item }">
                    {{ item.variants_count }} variants
                </template>

                <template #item.total_stock="{ item }">
                    <v-chip
                        size="small"
                        variant="flat"
                        class="stitch-pill"
                        :class="totalStockChip(item.total_stock).classes"
                        :prepend-icon="totalStockChip(item.total_stock).icon"
                    >
                        {{ item.total_stock }}
                    </v-chip>
                </template>

                <template #item.actions="{ item }">
                    <div class="d-flex alig-center gc-1">
                        <v-btn icon="mdi-eye-outline" size="small" variant="text" :to="{ name: 'products.show', params: { id: item.id } }" />
                        <v-btn icon="mdi-pencil-outline" size="small" variant="text" :to="{ name: 'products.edit', params: { id: item.id } }" />
                        <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click="confirmDelete(item)" />
                    </div>
                </template>
            </v-data-table-server>
        </v-card>

        <ImportProductsDialog v-model="importDialogShow" @imported="onImported" @failed="onImportFailed" />

        <v-dialog v-model="deleteDialog.show" max-width="420">
            <v-card class="pa-2">
                <v-card-text class="text-center pt-6">
                    <div class="mx-auto mb-4 d-flex align-center justify-center rounded-circle bg-error-container delete-icon">
                        <v-icon icon="mdi-delete-forever" color="status-error" size="28" />
                    </div>
                    <h3 class="text-body-large font-weight-bold">Delete Cross-Border Product</h3>
                    <p class="text-body-medium text-medium-emphasis mb-0 mt-2">
                        Are you sure you want to remove
                        <strong>{{ deleteDialog.product?.name }}</strong>? This will delist its pricing automations.
                    </p>
                </v-card-text>
                <v-card-actions class="justify-end pb-4 pe-4">
                    <v-btn variant="tonal" @click="deleteDialog.show = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="deleting" @click="destroy">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import KpiCard from '../../components/KpiCard.vue';
import ImportProductsDialog from '../../components/ImportProductsDialog.vue';
import http from '../../api/http';
import { useUiStore } from '../../store/ui';
import { statusClasses, statusDotColor, statusLabel } from '../../utils/productStatus';

const uiStore = useUiStore();

const headers = [
    { title: 'Image', key: 'image_path', sortable: false, width: '76px' },
    { title: 'Product Name', key: 'name' },
    { title: 'Status', key: 'status', sortable: false },
    { title: 'Variants', key: 'variants_count', sortable: false },
    { title: 'Stock Total', key: 'total_stock', sortable: false },
    { title: 'Actions', key: 'actions', sortable: false, width: '140px' },
];

const products = ref([]);
const loading = ref(false);
const search = ref('');
const total = ref(0);
const perPage = ref(10);
const activeFilter = ref('all');
const fxRate = ref(18.0);
const stockRows = ref([]);

const deleting = ref(false);
const deleteDialog = ref({ show: false, product: null });

const importDialogShow = ref(false);

const filterPills = [
    { label: 'All', value: 'all' },
    { label: 'Active', value: 'active' },
    { label: 'Draft', value: 'draft' },
    { label: 'Archived', value: 'archived' },
];

const filteredProducts = computed(() => {
    if (activeFilter.value === 'all') {
        return products.value;
    }

    return products.value.filter((product) => product.status === activeFilter.value);
});

const listedCatalog = computed(() => total.value);
const activeVariantCount = computed(() =>
    products.value
        .filter((product) => product.status === 'active')
        .reduce((sum, product) => sum + (product.variants_count ?? 0), 0),
);
const lowStockCount = computed(() => stockRows.value.filter((row) => row.stock_quantity <= 5).length);
const avgMargin = computed(() => {
    if (!stockRows.value.length) return 0;

    return stockRows.value.reduce((sum, row) => {
        const costMxn = (Number(row.cost_usd) || 0) * fxRate.value;
        const price = Number(row.price_mxn) || 0;

        return sum + (price > 0 ? ((price - costMxn) / price) * 100 : 0);
    }, 0) / stockRows.value.length;
});

const currentStart = computed(() => (total.value === 0 ? 0 : (perPage.value * 1) - perPage.value + 1));
const currentEnd = computed(() => Math.min(perPage.value, total.value));

function totalStockChip(quantity) {
    if (quantity <= 2) {
        return {
            icon: 'mdi-alert-octagon-outline',
            classes: 'bg-surface-rose-subtle text-rose-deep',
        };
    }

    if (quantity <= 5) {
        return {
            icon: 'mdi-alert-outline',
            classes: 'bg-surface-cream-subtle text-status-warning',
        };
    }

    return {
        icon: 'mdi-check-circle-outline',
        classes: 'bg-surface-mint-subtle text-mint-deep',
    };
}

async function load() {
    loading.value = true;

    try {
        const [data, stockData, settingsData] = await Promise.all([
            http.get(`/api/products?per_page=${perPage.value}&search=${encodeURIComponent(search.value ?? '')}`),
            http.get('/api/stock?per_page=100'),
            http.get('/api/settings'),
        ]);

        products.value = data.data;
        total.value = data.meta?.total ?? 0;
        stockRows.value = stockData.data;
        fxRate.value = Number(settingsData['fx.usd_to_mxn']) || 18;
    } finally {
        loading.value = false;
    }
}

function onOptions({ page, itemsPerPage }) {
    perPage.value = itemsPerPage;
    load();
}

function confirmDelete(product) {
    deleteDialog.value = { show: true, product };
}

function onImported(result) {
    uiStore.notify(`${result.imported} product${result.imported === 1 ? '' : 's'} imported`);
    load();
}

function onImportFailed(message) {
    uiStore.notify(message, { color: 'error', timeout: 5000 });
}

async function destroy() {
    deleting.value = true;

    try {
        await http.delete(`/api/products/${deleteDialog.value.product.id}`);
        deleteDialog.value.show = false;
        uiStore.notify('Product deleted');
        await load();
    } catch {
        uiStore.notify('The product could not be deleted.', { color: 'error', timeout: 5000 });
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>

<style scoped>
.delete-icon {
    width: 48px;
    height: 48px;
}

.search-field {
    flex: 1 1 0%;
    min-width: 260px;
}
</style>
