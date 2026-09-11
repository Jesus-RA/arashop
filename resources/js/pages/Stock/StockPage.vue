<template>
    <div>
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-5">
            <div>
                <div class="d-flex align-center ga-2 mb-1">
                    <span class="stitch-label">Laredo Hub</span>
                    <v-chip size="small" variant="flat" class="stitch-pill bg-surface-mint-subtle text-mint-deep">
                        <span class="fx-dot me-1" style="background: rgb(var(--v-theme-status-success))" />
                        Live Stock
                    </v-chip>
                </div>
                <h1 class="text-headline-small font-weight-bold mb-0">Stock</h1>
                <p class="text-body-medium text-medium-emphasis mb-0 mt-1">
                    Monitor unit balances across every cross-border variant.
                </p>
            </div>
        </div>

        <v-row class="mb-4">
            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Total Variants Monitored</span>
                        <div class="stitch-kpi-icon rounded-lg bg-surface-rose-subtle">
                            <v-icon icon="mdi-variant" color="primary" size="18" />
                        </div>
                    </div>
                    <div class="text-title-large font-weight-bold mt-2">{{ totalVariants }} <span class="text-body-small text-medium-emphasis">Series</span></div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Units On Order</span>
                        <div class="stitch-kpi-icon rounded-lg bg-surface-cream-subtle">
                            <v-icon icon="mdi-truck-delivery" color="status-warning" size="18" />
                        </div>
                    </div>
                    <div class="text-title-large font-weight-bold mt-2 text-status-warning">0 <span class="text-body-small text-medium-emphasis">Pending freight</span></div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Units in Portfolio</span>
                        <div class="stitch-kpi-icon rounded-lg bg-surface-mint-subtle">
                            <v-icon icon="mdi-warehouse" color="mint-deep" size="18" />
                        </div>
                    </div>
                    <div class="text-title-large font-weight-bold mt-2 tabular-nums">{{ onHandTotal }} <span class="text-body-small text-medium-emphasis">On hand</span></div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Low-Stock Alerts</span>
                        <div class="stitch-kpi-icon rounded-lg bg-surface-cream-subtle">
                            <v-icon icon="mdi-alert" color="status-error" size="18" />
                        </div>
                    </div>
                    <div class="text-title-large font-weight-bold mt-2 text-status-error">{{ lowStockCount }} <span class="text-body-small text-medium-emphasis">Below {{ lowStockThreshold }}</span></div>
                </v-card>
            </v-col>
        </v-row>

        <v-card class="rounded-lg pa-3 mb-4">
            <div class="d-flex flex-wrap align-center justify-space-between ga-3">
                <div class="d-flex flex-wrap align-center ga-3 flex-1">
                    <v-text-field
                        v-model.trim="search"
                        label="Search stock"
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
                            class="stitch-pill"
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
                    Showing {{ currentStart }}-{{ currentEnd }} of {{ total }} variants
                </span>
            </div>
        </v-card>

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
                <template #item.description="{ item }">
                    <div class="font-weight-medium">{{ item.product_name }}</div>
                    <div class="text-body-medium text-medium-emphasis">{{ item.name }}</div>
                </template>

                <template #item.attributes="{ item }">
                    <v-chip
                        v-for="(value, key) in item.attributes"
                        :key="key"
                        size="small"
                        variant="flat"
                        class="stitch-pill bg-canvas-tint text-on-surface-variant me-1"
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
                    <div class="d-flex align-center ga-2">
                        <v-chip
                            size="small"
                            variant="flat"
                            class="stitch-pill"
                            :class="stockClasses(item.stock_quantity)"
                        >
                            {{ item.stock_quantity }} units
                        </v-chip>
                        <div class="d-flex align-center ga-1">
                            <v-btn icon="mdi-minus" size="x-small" variant="tonal" @click="quickAdjust(item, -1)" />
                            <v-btn icon="mdi-plus" size="x-small" variant="tonal" @click="quickAdjust(item, 1)" />
                        </div>
                    </div>
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

        <v-dialog v-model="adjustDialog.show" max-width="520">
            <v-card class="pa-2">
                <v-card-text class="pt-4 pb-0">
                    <div class="d-flex align-center justify-space-between mb-1">
                        <span class="stitch-label">Stock Adjustment</span>
                        <v-chip size="small" variant="flat" class="stitch-pill bg-surface-mint-subtle text-mint-deep">Live Adjust</v-chip>
                    </div>
                    <h3 class="text-body-large font-weight-bold mb-4">Adjust Stock — {{ adjustDialog.item?.name }}</h3>

                    <div class="stepper d-flex align-center ga-2 mb-4">
                        <div class="stepper-step gap-3" :class="{ active: true }">
                            <span class="step-num rounded-lg">01</span>
                            <div>
                                <div class="text-body-small font-weight-bold">Adjust Quantity</div>
                                <div class="text-body-small text-medium-emphasis">Set movement amount &amp; reason</div>
                            </div>
                        </div>
                        <v-icon icon="mdi-chevron-right" size="16" color="on-surface-variant" />
                        <div class="stepper-step gap-3" :class="{ active: false }">
                            <span class="step-num rounded-lg">02</span>
                            <div>
                                <div class="text-body-small font-weight-bold">Review &amp; Apply</div>
                                <div class="text-body-small text-medium-emphasis">Confirm ledger entry</div>
                            </div>
                        </div>
                    </div>

                    <div class="preview-banner pa-4 mb-4 border-sm border-warm rounded-lg">
                        <div class="d-flex align-center justify-space-between">
                            <span class="text-body-small text-medium-emphasis">Current Stock</span>
                            <span class="text-title-large font-weight-bold tabular-nums">{{ adjustDialog.item?.stock_quantity }}</span>
                        </div>
                        <div class="d-flex align-end justify-space-between mt-2">
                            <div>
                                <div class="stitch-label mb-1">New Stock will be:</div>
                                <div class="d-flex align-baseline ga-2">
                                    <span class="text-headline-small font-weight-bold tabular-nums" :class="projectedStock < 0 ? 'text-status-error' : 'text-mint-deep'">
                                        {{ projectedStock }}
                                    </span>
                                    <span class="text-body-small text-medium-emphasis">units on-hand</span>
                                </div>
                            </div>
                            <div class="d-flex ga-2 align-center">
                                <v-chip size="small" variant="flat" class="stitch-pill" :class="adjustForm.quantity_change >= 0 ? 'bg-surface-mint-subtle text-mint-deep' : 'bg-surface-rose-subtle text-rose-deep'">
                                    {{ adjustForm.quantity_change >= 0 ? '+' : '' }}{{ adjustForm.quantity_change }} unit(s)
                                </v-chip>
                            </div>
                        </div>
                    </div>

                    <v-form @submit.prevent="submitAdjust">
                        <v-text-field
                            v-model.number="adjustForm.quantity_change"
                            label="Quantity change"
                            type="number"
                            required
                            :error-messages="adjustErrors('quantity_change')"
                            class="mb-2"
                        />
                        <v-combobox
                            v-model="adjustForm.reason"
                            :items="reasonOptions"
                            label="Reason"
                            required
                            :error-messages="adjustErrors('reason')"
                            class="mb-2"
                        />
                        <v-textarea
                            v-model.trim="adjustForm.notes"
                            label="Notes"
                            rows="2"
                            :error-messages="adjustErrors('notes')"
                        />

                        <v-alert v-if="projectedStock < 0" type="error" variant="tonal" class="mt-2">
                            Stock would drop below zero. Review the quantity before applying.
                        </v-alert>
                        <v-alert v-else type="success" variant="tonal" class="mt-2">
                            This movement will be recorded in the stock ledger.
                        </v-alert>
                    </v-form>
                </v-card-text>

                <v-card-actions class="justify-end pb-4 pe-4">
                    <v-btn variant="text" @click="adjustDialog.show = false">Cancel</v-btn>
                    <v-btn
                        color="primary"
                        :loading="adjusting"
                        :disabled="adjusting"
                        @click="submitAdjust"
                    >
                        Apply
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="historyDialog.show" max-width="600">
            <v-card class="pa-2">
                <v-card-text class="pt-4">
                    <div class="d-flex align-center justify-space-between mb-1">
                        <span class="stitch-label">Stock Ledger</span>
                        <v-chip size="small" variant="flat" class="stitch-pill bg-surface-cream-subtle text-tertiary">Audit Trail</v-chip>
                    </div>
                    <h3 class="text-body-large font-weight-bold mb-1">Stock history</h3>
                    <div class="text-body-medium text-medium-emphasis mb-4">
                        {{ historyDialog.item?.product_name }} — {{ historyDialog.item?.name }}
                    </div>

                    <div v-if="history.length" class="history-list gap-3">
                        <div v-for="entry in history" :key="entry.id" class="history-row px-4 py-3 border-sm border-warm border-opacity-100 rounded-lg">
                            <div class="d-flex align-center justify-space-between ga-2">
                                <v-chip
                                    size="small"
                                    variant="flat"
                                    class="stitch-pill"
                                    :class="entry.quantity_change >= 0 ? 'bg-surface-mint-subtle text-mint-deep' : 'bg-surface-rose-subtle text-rose-deep'"
                                >
                                    {{ entry.quantity_change >= 0 ? '+' : '' }}{{ entry.quantity_change }}
                                </v-chip>
                                <span class="text-body-small text-medium-emphasis">{{ new Date(entry.created_at).toLocaleString() }}</span>
                            </div>
                            <div class="font-weight-medium mt-1">{{ entry.reason }}</div>
                            <div class="text-body-small text-medium-emphasis mt-1">
                                by {{ entry.user?.name ?? 'Unknown' }}
                                <template v-if="entry.notes">— {{ entry.notes }}</template>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-body-medium text-medium-emphasis mb-4">
                        No recorded adjustments yet.
                    </p>
                </v-card-text>
                <v-card-actions class="justify-end pb-4 pe-4">
                    <v-btn variant="text" @click="historyDialog.show = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import http from '../../api/http';
import { formatMxn, formatUsd } from '../../utils/format';

const headers = [
    { title: 'Product', key: 'description', sortable: false },
    { title: 'Attributes', key: 'attributes', sortable: false },
    { title: 'Cost (USD)', key: 'cost_usd', sortable: false },
    { title: 'Price (MXN)', key: 'price_mxn', sortable: false },
    { title: 'Stock', key: 'stock_quantity', sortable: false },
    { title: '', key: 'actions', sortable: false, width: '190px' },
];

const reasonOptions = ['initial', 'purchase', 'sale', 'damaged', 'adjustment', 'return'];

const lowStockThreshold = 5;

const items = ref([]);
const loading = ref(false);
const search = ref('');
const total = ref(0);
const perPage = ref(10);
const activeFilter = ref('all');
let loadSequence = 0;

const adjustDialog = ref({ show: false, item: null });
const adjustErrorBag = ref({});
const adjusting = ref(false);
const adjustForm = reactive({ quantity_change: 0, reason: '', notes: '' });

const historyDialog = ref({ show: false, item: null });
const history = ref([]);

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

const projectedStock = computed(() => {
    if (!adjustDialog.value.item) return null;

    return adjustDialog.value.item.stock_quantity + Number(adjustForm.quantity_change ?? 0);
});

function stockClasses(quantity) {
    if (quantity === 0) return 'bg-surface-rose-subtle text-rose-deep';

    if (quantity <= lowStockThreshold) return 'bg-surface-cream-subtle text-status-warning';

    return 'bg-surface-mint-subtle text-mint-deep';
}

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
    adjustErrorBag.value = {};
    adjustForm.quantity_change = 0;
    adjustForm.reason = '';
    adjustForm.notes = '';
    adjustDialog.value = { show: true, item };
}

function adjustErrors(key) {
    return adjustErrorBag.value[key] ?? [];
}

async function performAdjust(item, quantityChange, reason, notes) {
    await http.post('/api/stock/adjustments', {
        variant_id: item.id,
        quantity_change: quantityChange,
        reason: reason ?? 'adjustment',
        notes: notes ?? null,
    });

    await load();
}

async function quickAdjust(item, delta) {
    await performAdjust(item, delta, 'adjustment', null);
}

async function submitAdjust() {
    adjusting.value = true;
    adjustErrorBag.value = {};

    try {
        await performAdjust(
            adjustDialog.value.item,
            adjustForm.quantity_change,
            adjustForm.reason,
            adjustForm.notes,
        );

        adjustDialog.value.show = false;
    } catch (error) {
        adjustErrorBag.value = error.data?.errors ?? {};
    } finally {
        adjusting.value = false;
    }
}

async function openHistory(item) {
    historyDialog.value = { show: true, item };
    history.value = [];

    const data = await http.get(`/api/stock/${item.id}/history?per_page=50`);

    history.value = data.data;
}

onMounted(load);
</script>

<style scoped>
.stepper-step {
    display: flex;
    align-items: flex-start;
    opacity: 0.5;
}

.stepper-step.active {
    opacity: 1;
}

.step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    font-size: 11px;
    font-weight: 700;
    background: rgb(var(--v-theme-primary-container));
    color: rgb(var(--v-theme-on-primary-container));
}

.preview-banner {
    background: rgb(var(--v-theme-canvas-tint));
}

.history-list {
    display: flex;
    flex-direction: column;
}

.history-row {
    background: rgb(var(--v-theme-surface-card));
}

.search-field {
    flex: 1 1 0%;
    min-width: 260px;
}
</style>
