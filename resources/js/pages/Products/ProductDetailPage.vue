<template>
    <div v-if="product">
        <div class="d-flex align-center ga-3 mb-4">
            <v-btn icon="mdi-arrow-left" variant="outlined" width="42" height="42" :to="{ name: 'products.index' }" />
            <div>
                <div class="d-flex align-center ga-2 mb-1">
                    <router-link :to="{ name: 'products.index' }" class="text-body-small text-medium-emphasis text-decoration-none">
                        Products
                    </router-link>
                    <v-icon icon="mdi-chevron-right" size="14" color="on-surface-variant" />
                    <span class="text-body-small text-medium-emphasis">{{ product.name }}</span>
                </div>
                <h1 class="text-headline-small font-weight-bold mb-0">{{ product.name }}</h1>
            </div>
<v-chip size="small" variant="flat" class="stitch-pill" :class="statusClasses(product.status)">
                    <span class="fx-dot me-1" :style="{ background: statusDotColor(product.status) }" />
                    {{ statusLabel(product.status) }}
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
                <v-card class="rounded-lg px-6 py-5 mb-4">
                    <div class="d-flex align-center justify-space-between mb-3">
                        <span class="text-body-small text-medium-emphasis">Listing Preview</span>
                        <v-chip size="small" variant="flat" class="stitch-pill bg-surface-cream-subtle text-status-warning">
                            <span class="fx-dot me-1" style="background: rgb(var(--v-theme-status-warning))" />
                            From Amazon US
                        </v-chip>
                    </div>
                    <div class="media-pane mb-3 border-sm border-warm rounded-lg">
                        <v-avatar size="140" rounded="16">
                            <v-img v-if="product.image_path" :src="product.image_path" />
                            <v-icon v-else icon="mdi-package-variant-outline" size="48" color="on-surface-variant" />
                        </v-avatar>
                    </div>
                    <div class="text-body-large font-weight-bold mb-1">{{ product.name }}</div>
                    <div class="d-flex align-center justify-space-between border-t-sm border-warm  pt-3">
                        <div>
                            <div class="text-body-small text-medium-emphasis">From</div>
                            <div class="text-title-large font-weight-bold text-mint-deep">{{ mxn(minPriceMxn) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-body-small text-medium-emphasis">Target Margin</div>
                            <div class="text-title-large font-weight-bold text-mint-deep">{{ avgMargin.toFixed(1) }}%</div>
                        </div>
                    </div>
                </v-card>

                <v-card class="rounded-lg px-6 py-5 mb-4">
                    <div class="d-flex align-center ga-3 mb-3">
                        <div class="stitch-kpi-icon rounded-lg bg-surface-cream-subtle">
                            <v-icon icon="mdi-cash-sync" color="tertiary" size="18" />
                        </div>
                        <div>
                            <h2 class="text-body-large font-weight-bold mb-0">Sourcing Economics</h2>
                            <p class="text-body-small text-medium-emphasis mb-0">Landed cost &amp; resale spread</p>
                        </div>
                    </div>
                    <div class="d-flex align-center justify-space-between py-2 border-b-sm border-warm ">
                        <span class="text-body-medium text-medium-emphasis">US Unit Cost (avg)</span>
                        <span class="text-body-medium font-weight-semibold tabular-nums">{{ formatUsd(avgCostUsd) }}</span>
                    </div>
                    <div class="d-flex align-center justify-space-between py-2 border-b-sm border-warm ">
                        <span class="text-body-medium text-medium-emphasis">Mexico Resale (avg)</span>
                        <span class="text-body-medium font-weight-semibold tabular-nums">{{ mxn(avgPriceMxn) }}</span>
                    </div>
                    <div class="d-flex align-center justify-space-between py-2 border-b-sm border-warm ">
                        <span class="text-body-medium text-medium-emphasis">Total Stock (units)</span>
                        <span class="text-body-medium font-weight-semibold text-mint-deep tabular-nums">{{ product.total_stock }}</span>
                    </div>
                    <div class="d-flex align-center justify-space-between py-2">
                        <span class="text-body-medium text-medium-emphasis">Gross Spread / unit</span>
                        <span class="text-body-medium font-weight-semibold text-mint-deep tabular-nums">+{{ mxn(avgSpreadMxn) }}</span>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" md="8">
                <v-card class="stitch-table-card mb-4 rounded-lg">
                    <div class="d-flex align-center gap-3 p-4 border-b-sm border-warm ">
                        <div class="stitch-kpi-icon rounded-lg bg-surface-mint-subtle">
                            <v-icon icon="mdi-variant" color="mint-deep" size="18" />
                        </div>
                        <div>
                            <h2 class="text-body-large font-weight-bold mb-0">Mexico Retail Variants</h2>
                            <p class="text-body-small text-medium-emphasis mb-0">Live variant pricing &amp; stock snapshot</p>
                        </div>
                        <v-spacer />
                        <router-link
                            :to="{ name: 'stock.index' }"
                            class="text-decoration-none"
                        >
                            <v-chip size="small" variant="flat" class="stitch-pill bg-surface-rose-subtle text-rose-deep">Manage Stock</v-chip>
                        </router-link>
                    </div>
                    <div class="table-scroll px-4 pb-4">
                        <table class="stitch-table w-100">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3">Variant</th>
                                    <th class="px-4 py-3">Attributes</th>
                                    <th class="px-4 py-3 text-right">Cost (USD)</th>
                                    <th class="px-4 py-3 text-right">Price (MXN)</th>
                                    <th class="px-4 py-3 text-center">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="variant in product.variants" :key="variant.id" class="surface-row-hover">
                                    <td class="px-4 py-3 font-weight-medium">{{ variant.name }}</td>
                                    <td class="px-4 py-3">
                                        <v-chip
                                            v-for="(value, key) in variant.attributes"
                                            :key="key"
                                            size="small"
                                            variant="flat"
                                            class="stitch-pill bg-canvas-tint text-on-surface-variant me-1"
                                        >
                                            {{ key }}: {{ value }}
                                        </v-chip>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ formatUsd(variant.cost_usd) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums font-weight-semibold">{{ formatMxn(variant.price_mxn) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <v-chip
                                            size="small"
                                            variant="flat"
                                            class="stitch-pill"
                                            :class="variant.stock_quantity === 0 ? 'bg-surface-rose-subtle text-rose-deep' : 'bg-surface-mint-subtle text-mint-deep'"
                                        >
                                            {{ variant.stock_quantity }}
                                        </v-chip>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </v-card>

                <v-card class="rounded-lg px-6 py-5">
                    <div class="d-flex align-center justify-space-between mb-3">
                        <div class="d-flex align-center ga-3">
                            <div class="stitch-kpi-icon rounded-lg bg-surface-container">
                                <v-icon icon="mdi-truck-fast" color="on-surface-variant" size="18" />
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold mb-0">Logistics &amp; Source</h2>
                                <p class="text-body-small text-medium-emphasis mb-0">US origin listing information</p>
                            </div>
                        </div>
                        <v-chip size="small" variant="flat" class="stitch-pill bg-surface-cream-subtle text-tertiary">US → MX</v-chip>
                    </div>
                    <p v-if="product.description" class="text-body-medium text-medium-emphasis mb-3">{{ product.description }}</p>
                    <a
                        v-if="product.source_url"
                        :href="product.source_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-rose-deep text-body-medium text-decoration-none"
                    >
                        <v-icon icon="mdi-open-in-new" size="16" class="me-1" />
                        View original Amazon US listing
                    </a>
                    <div v-else class="text-body-small text-medium-emphasis">No source URL attached.</div>
                </v-card>
            </v-col>
        </v-row>

        <v-dialog v-model="deleteDialog" max-width="420">
            <v-card class="pa-2">
                <v-card-text class="text-center pt-6">
                    <div class="mx-auto mb-4 d-flex align-center justify-center rounded-circle bg-error-container delete-icon">
                        <v-icon icon="mdi-delete-forever" color="status-error" size="28" />
                    </div>
                    <h3 class="text-body-large font-weight-bold">Delete Cross-Border Product</h3>
                    <p class="text-body-medium text-medium-emphasis mb-0 mt-2">
                        Remove "{{ product.name }}" and all its variants? This cannot be undone.
                    </p>
                </v-card-text>
                <v-card-actions class="justify-end pb-4 pe-4">
                    <v-btn variant="tonal" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="deleting" @click="destroy">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http from '../../api/http';
import { formatMxn, formatUsd } from '../../utils/format';
import { statusClasses, statusDotColor, statusLabel } from '../../utils/productStatus';

const route = useRoute();
const router = useRouter();

const product = ref(null);
const deleteDialog = ref(false);
const deleting = ref(false);
const fxRate = ref(18.0);

const variantCosts = computed(() => (product.value?.variants ?? []).map((variant) => Number(variant.cost_usd) || 0));
const variantPrices = computed(() => (product.value?.variants ?? []).map((variant) => Number(variant.price_mxn) || 0));

const avgCostUsd = computed(() => avg(variantCosts.value));
const avgPriceMxn = computed(() => avg(variantPrices.value));
const minPriceMxn = computed(() => (variantPrices.value.length ? Math.min(...variantPrices.value) : 0));
const avgSpreadMxn = computed(() => avgPriceMxn.value - avgCostUsd.value * fxRate.value);
const avgMargin = computed(() => {
    if (avgPriceMxn.value <= 0) return 0;

    return (avgSpreadMxn.value / avgPriceMxn.value) * 100;
});

function mxn(value) {
    return `MXN ${Number(value ?? 0).toLocaleString('en-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function avg(values) {
    if (!values.length) return 0;

    return values.reduce((sum, value) => sum + value, 0) / values.length;
}

async function load() {
    const { data } = await http.get(`/api/products/${route.params.id}`);
    const settings = await http.get('/api/settings');

    product.value = data;
    fxRate.value = Number(settings['fx.usd_to_mxn']) || 18;
}

async function destroy() {
    deleting.value = true;

    try {
        await http.delete(`/api/products/${product.value.id}`);
        router.push({ name: 'products.index' });
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>

<style scoped>
.table-scroll {
    overflow-x: auto;
}

.stitch-table-card {
    overflow: hidden;
}

.media-pane {
    height: 168px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgb(var(--v-theme-canvas-tint));
}

.delete-icon {
    width: 48px;
    height: 48px;
}

.surface-row-hover:hover {
    background: rgb(var(--v-theme-surface-container-low));
}
</style>
