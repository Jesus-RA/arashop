<template>
    <main class="d-flex flex-column gr-4">
        <header class="d-flex flex-wrap align-center justify-space-between ga-3">
            <div>
                <h1 class="text-headline-small font-weight-bold my-0">Products</h1>
                <p class="text-body-medium text-medium-emphasis my-0">
                    Manage your products and product variants.
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
        </header>

        <section class="d-flex flex-wrap ga-3">
            <KpiCard title="Listed Catalog" icon="mdi-storefront" icon-color="primary" class="flex-grow-1">
                {{ listedCatalog }} Items
            </KpiCard>
            <KpiCard title="Active Arbitrage Variants" icon="mdi-check-decagram" icon-color="success" class="flex-grow-1">
                {{ activeVariantCount }} Active
            </KpiCard>
            <KpiCard title="Low / Out of Stock" icon="mdi-alert" icon-color="warning" class="flex-grow-1">
                <span class="text-warning">{{ lowStockCount }} Alerts</span>
            </KpiCard>
            <KpiCard title="Avg. Mexico Margin" icon="mdi-trending-up" icon-color="success" class="flex-grow-1">
                <span class="text-success">{{ avgMargin.toFixed(1) }}%</span>
            </KpiCard>
        </section>

        <v-card class="overflow-hidden">
            <v-data-table-server
                :headers="headers"
                :items="filteredProducts"
                :loading="loading"
                :items-length="total"
                :items-per-page="perPage"
                :items-per-page-options="[10, 25, 50, 75, 100]"
                item-key="id"
                density="comfortable"
                :hide-default-footer="false"
                @update:options="onOptions"
            >
                <template #top>
                    <article class="d-flex flex-wrap align-center justify-space-between ga-3 px-4 py-2">
                        <div class="d-flex flex-wrap align-center ga-3 flex-1">
                            <v-text-field
                                v-model.trim="search"
                                label="Search products"
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
                            Showing {{ currentStart }}-{{ currentEnd }} of {{ total }} products
                        </span>
                    </article>
                </template>

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
                                class="fx-dot"
                                :class="statusDotColor(item.status)"
                            />
                            {{ statusLabel(item.status) }}
                        </div>
                    </v-chip>
                </template>

                <template #item.variants_count="{ item }">
                    <v-chip size="small" variant="flat" class="stitch-pill bg-grey-lighten-4">
                        {{ item.variants_count }} variant{{ item.variants_count === 1 ? '' : 's' }}
                    </v-chip>
                </template>

                <template #item.total_stock="{ item }">
                    <v-chip
                        size="small"
                        variant="flat"
                        class="stitch-pill"
                        :class="stockClasses(item.total_stock)"
                    >
                        {{ item.total_stock }} units
                    </v-chip>
                </template>

                <template #item.actions="{ item }">
                    <div class="d-flex align-center gc-1">
                        <v-tooltip location="top">
                            <template #activator="{ props }">
                                <v-btn v-bind="props" icon="mdi-eye-outline" size="small" variant="text" :to="{ name: 'products.show', params: { id: item.id } }" />
                            </template>
                            <span>View</span>
                        </v-tooltip>
                        <v-tooltip location="top">
                            <template #activator="{ props }">
                                <v-btn v-bind="props" icon="mdi-pencil-outline" size="small" variant="text" :to="{ name: 'products.edit', params: { id: item.id } }" />
                            </template>
                            <span>Edit</span>
                        </v-tooltip>
                        <v-tooltip location="top">
                            <template #activator="{ props }">
                                <v-btn v-bind="props" icon="mdi-delete-outline" size="small" variant="text" color="error" @click="confirmDelete(item)" />
                            </template>
                            <span>Delete</span>
                        </v-tooltip>
                    </div>
                </template>
            </v-data-table-server>
        </v-card>

        <ImportProductsDialog v-model="importDialogShow" @imported="onImported" @failed="onImportFailed" />

        <ConfirmDialog
            v-model="deleteDialog.show"
            title="Delete Cross-Border Product"
            icon="mdi-delete-forever"
            icon-color="red"
            cancel-button-text="Cancel"
            confirm-button-text="Yes, delete"
            confirm-button-color="red"
            :loading="deleting"
            @confirm="destroy"
        >
            <template #text>
                <p class="text-body-medium text-medium-emphasis my-0">
                    Are you sure you want to remove
                    <strong>{{ deleteDialog.product?.name }}</strong>? This will delist its pricing automations.
                </p>
            </template>
        </ConfirmDialog>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import KpiCard from '../../components/KpiCard.vue';
import ImportProductsDialog from '../../components/ImportProductsDialog.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import http from '../../api/http';
import { useSettingsStore } from '../../store/settings';
import { useUiStore } from '../../store/ui';
import { statusClasses, statusDotColor, statusLabel } from '../../utils/productStatus';
import { stockClasses } from '../../utils/stock';

const uiStore = useUiStore();
const settingsStore = useSettingsStore();

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
        const costMxn = (Number(row.cost_usd) || 0) * settingsStore.fxUsdToMxn;
        const price = Number(row.price_mxn) || 0;

        return sum + (price > 0 ? ((price - costMxn) / price) * 100 : 0);
    }, 0) / stockRows.value.length;
});

const currentStart = computed(() => (total.value === 0 ? 0 : (perPage.value * 1) - perPage.value + 1));
const currentEnd = computed(() => Math.min(perPage.value, total.value));

async function load() {
    loading.value = true;

    try {
        await settingsStore.load();

        const [data, stockData] = await Promise.all([
            http.get(`/api/products?per_page=${perPage.value}&search=${encodeURIComponent(search.value ?? '')}`),
            http.get('/api/stock?per_page=100'),
        ]);

        products.value = data.data;
        total.value = data.meta?.total ?? 0;
        stockRows.value = stockData.data;
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
.search-field {
    flex: 1 1 0%;
    min-width: 260px;
}
</style>
