<template>
    <section v-if="product" class="d-flex flex-column gr-3">
        <div>
            <v-btn prepend-icon="mdi-arrow-left" variant="text" color="primary" :to="{ name: 'products.index' }">
                Go Back
            </v-btn>
        </div>

        <div class="d-flex align-center ga-3">
            <h1 class="text-headline-small font-weight-bold my-0">{{ product.name }}</h1>
            <v-chip size="small" variant="flat" class="stitch-pill" :class="statusClasses(product.status)">
                <div class="d-flex align-center gc-2">
                    <span class="fx-dot" :class="statusDotColor(product.status)" />
                    <span>{{ statusLabel(product.status) }}</span>
                </div>
            </v-chip>

            <v-spacer />
            <v-btn variant="tonal" prepend-icon="mdi-pencil-outline" :to="{ name: 'products.edit', params: { id: product.id } }">
                Edit
            </v-btn>
            <v-btn variant="tonal" color="error" prepend-icon="mdi-delete-outline" @click="deleteDialog = true">
                Delete
            </v-btn>
        </div>

        <v-row>
            <v-col cols="12" md="4">
                <v-card class="rounded-lg py-3 mb-4">
                    <v-card-text class="pb-0">
                        <div class="d-flex align-center justify-space-between mb-3">
                            <span class="text-body-small text-medium-emphasis">Listing Preview</span>
                        </div>
                        <div class="d-flex align-center justify-center rounded-lg bg-surface-light mb-3" style="height: 168px">
                            <v-avatar size="140" rounded="16">
                                <v-img v-if="product.image_path" :src="product.image_path" />
                                <v-icon v-else icon="mdi-package-variant-outline" size="48" color="on-surface-variant" />
                            </v-avatar>
                        </div>
                        <div class="text-body-large font-weight-bold mb-1">{{ product.name }}</div>
                        <div class="d-flex align-center justify-space-between border-t-sm  pt-3">
                            <div>
                                <div class="text-body-small text-medium-emphasis">From</div>
                                <div class="text-title-large font-weight-bold text-info">{{ formatMxn(minPriceMxn) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-body-small text-medium-emphasis">Target Margin</div>
                                <div class="text-title-large font-weight-bold text-info">{{ avgMargin.toFixed(1) }}%</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>

                <v-card class="rounded-lg py-3 mb-4">
                    <v-card-title class="d-flex align-center ga-3">
                        <div class="stitch-kpi-icon rounded-lg bg-warning-lighten-5">
                            <v-icon icon="mdi-cash-sync" color="premium" size="18" />
                        </div>
                        <div>
                            <h2 class="text-body-large font-weight-bold my-0">Sourcing Economics</h2>
                            <p class="text-body-small text-medium-emphasis my-0">Landed cost &amp; resale spread</p>
                        </div>
                    </v-card-title>
                    <v-card-text class="py-0">
                        <div class="d-flex align-center justify-space-between py-2 border-b-sm ">
                            <span class="text-body-medium text-medium-emphasis">US Unit Cost (avg)</span>
                            <span class="text-body-medium font-weight-semibold tabular-nums">{{ formatUsd(avgCostUsd) }}</span>
                        </div>
                        <div class="d-flex align-center justify-space-between py-2 border-b-sm ">
                            <span class="text-body-medium text-medium-emphasis">Mexico Resale (avg)</span>
                            <span class="text-body-medium font-weight-semibold tabular-nums">{{ formatMxn(avgPriceMxn) }}</span>
                        </div>
                        <div class="d-flex align-center justify-space-between py-2 border-b-sm ">
                            <span class="text-body-medium text-medium-emphasis">Total Stock (units)</span>
                            <span class="text-body-medium font-weight-semibold text-info tabular-nums">{{ product.total_stock }}</span>
                        </div>
                        <div class="d-flex align-center justify-space-between py-2">
                            <span class="text-body-medium text-medium-emphasis">Gross Spread / unit</span>
                            <span class="text-body-medium font-weight-semibold text-info tabular-nums">+{{ formatMxn(avgSpreadMxn) }}</span>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="8">
                <v-card class="rounded-lg pt-3 mb-4">
                    <v-card-title class="d-flex align-center ga-3 p-4">
                        <div class="stitch-kpi-icon rounded-lg bg-secondary-lighten-2">
                            <v-icon icon="mdi-tune" color="primary" size="18" />
                        </div>
                        <div>
                            <h2 class="text-body-large font-weight-bold my-0">Variants</h2>
                            <p class="text-body-small text-medium-emphasis my-0">Live variant pricing &amp; stock snapshot</p>
                        </div>
                    </v-card-title>

                    <v-card-text class="py-0">
                        <v-data-table-server
                            :items="product.variants"
                            :items-length="product.variants.length"
                            :headers="variantColumns"
                            density="compact"
                            disable-sort
                        >
                            <template #item.name="{ item }">
                                <span class="font-weight-medium">{{ item.name }}</span>
                            </template>

                            <template #item.attributes="{ item }">
                                <template v-if="Object.keys(item.attributes ?? {}).length">
                                    <v-chip
                                        v-for="(value, key) in item.attributes"
                                        :key="key"
                                        size="x-small"
                                        variant="flat"
                                        class="stitch-pill bg-surface-light text-on-surface-variant me-1 mb-1"
                                    >
                                        {{ key }}: {{ value }}
                                    </v-chip>
                                </template>
                                <span v-else class="text-body-small text-medium-emphasis">—</span>
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
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-dialog v-model="deleteDialog" max-width="420">
            <v-card class="pa-2">
                <v-card-text class="text-center pt-6">
                    <div class="mx-auto mb-4 d-flex align-center justify-center rounded-circle bg-error-lighten-4">
                        <v-icon icon="mdi-delete-forever" color="error" size="28" />
                    </div>
                    <h3 class="text-body-large font-weight-bold">Delete Cross-Border Product</h3>
                    <p class="text-body-medium text-medium-emphasis mb-0 mt-2">
                        Remove "{{ product.name }}" and all its variants? This cannot be undone.
                    </p>
                </v-card-text>
                <v-card-actions class="justify-end pb-4 pe-4">
                    <v-btn variant="tonal" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" :disabled="deleting" @click="destroy">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http from '../../api/http';
import { formatMxn, formatUsd } from '../../utils/format';
import { statusClasses, statusDotColor, statusLabel } from '../../utils/productStatus';
import { stockClasses } from '../../utils/stock';
import { useSettingsStore } from '../../store/settings';
import { useUiStore } from '../../store/ui';

const route = useRoute();
const router = useRouter();
const settingsStore = useSettingsStore();
const uiStore = useUiStore();

const product = ref(null);
const deleteDialog = ref(false);
const deleting = ref(false);

const variantColumns = computed(() => [
    { key: 'name', title: 'Variant', sortable: false, minWidth: '170px' },
    { key: 'attributes', title: 'Attributes', sortable: false, width: '340px' },
    { key: 'cost_usd', title: 'Cost (USD)', sortable: false, width: '140px' },
    { key: 'price_mxn', title: 'Price (MXN)', sortable: false, width: '140px' },
    { key: 'stock_quantity', title: 'Qty', sortable: false, width: '120px', align: 'center' },
]);

const variantCosts = computed(() => (product.value?.variants ?? []).map((variant) => Number(variant.cost_usd) || 0));
const variantPrices = computed(() => (product.value?.variants ?? []).map((variant) => Number(variant.price_mxn) || 0));

const avgCostUsd = computed(() => avg(variantCosts.value));
const avgPriceMxn = computed(() => avg(variantPrices.value));
const minPriceMxn = computed(() => (variantPrices.value.length ? Math.min(...variantPrices.value) : 0));
const avgSpreadMxn = computed(() => avgPriceMxn.value - avgCostUsd.value * settingsStore.fxUsdToMxn);
const avgMargin = computed(() => {
    if (avgPriceMxn.value <= 0) return 0;

    return (avgSpreadMxn.value / avgPriceMxn.value) * 100;
});

function avg(values) {
    if (!values.length) return 0;

    return values.reduce((sum, value) => sum + value, 0) / values.length;
}

async function load() {
    await settingsStore.load();

    const { data } = await http.get(`/api/products/${route.params.id}`);

    product.value = data;
}

async function destroy() {
    deleting.value = true;

    try {
        await http.delete(`/api/products/${product.value.id}`);
        uiStore.notify('Product deleted');
        router.push({ name: 'products.index' });
    } catch (error) {
        const message = Object.values(error.data?.errors ?? {}).flat()[0] ?? 'Could not delete the product.';
        uiStore.notify(message, { color: 'error', timeout: 5000 });
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>
