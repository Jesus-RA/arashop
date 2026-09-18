<template>
    <div>
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-5">
            <div>
                <div class="d-flex align-center ga-2 mb-1">
                    <span class="stitch-label">Laredo Hub</span>
                    <v-chip size="small" variant="flat" class="stitch-pill bg-info-lighten-5 text-info">
                        <span class="fx-dot me-1" style="background: rgb(var(--v-theme-success))" />
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
                        <div class="stitch-kpi-icon rounded-lg bg-primary-lighten-5">
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
                        <div class="stitch-kpi-icon rounded-lg bg-warning-lighten-5">
                            <v-icon icon="mdi-truck-delivery" color="warning" size="18" />
                        </div>
                    </div>
                    <div class="text-title-large font-weight-bold mt-2 text-warning">0 <span class="text-body-small text-medium-emphasis">Pending freight</span></div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Units in Portfolio</span>
                        <div class="stitch-kpi-icon rounded-lg bg-info-lighten-5">
                            <v-icon icon="mdi-warehouse" color="info" size="18" />
                        </div>
                    </div>
                    <div class="text-title-large font-weight-bold mt-2 tabular-nums">{{ onHandTotal }} <span class="text-body-small text-medium-emphasis">On hand</span></div>
                </v-card>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <v-card class="rounded-lg px-5 py-4">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-body-small text-medium-emphasis">Low-Stock Alerts</span>
                        <div class="stitch-kpi-icon rounded-lg bg-warning-lighten-5">
                            <v-icon icon="mdi-alert" color="error" size="18" />
                        </div>
                    </div>
                    <div class="text-title-large font-weight-bold mt-2 text-error">{{ lowStockCount }} <span class="text-body-small text-medium-emphasis">Below {{ lowStockThreshold }}</span></div>
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
                            :color="activeFilter === pill.value ? 'primary' : 'surface-variant'"
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
                        <v-chip size="small" variant="flat" class="stitch-pill bg-info-lighten-5 text-info">Live Adjust</v-chip>
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

                    <div class="preview-banner pa-4 mb-4 border-sm rounded-lg">
                        <div class="d-flex align-center justify-space-between">
                            <span class="text-body-small text-medium-emphasis">Current Stock</span>
                            <span class="text-title-large font-weight-bold tabular-nums">{{ adjustDialog.item?.stock_quantity }}</span>
                        </div>
                        <div class="d-flex align-end justify-space-between mt-2">
                            <div>
                                <div class="stitch-label mb-1">New Stock will be:</div>
                                <div class="d-flex align-baseline ga-2">
                                    <span class="text-headline-small font-weight-bold tabular-nums" :class="projectedStock < 0 ? 'text-error' : 'text-info'">
                                        {{ projectedStock }}
                                    </span>
                                    <span class="text-body-small text-medium-emphasis">units on-hand</span>
                                </div>
                            </div>
                            <div class="d-flex ga-2 align-center">
                                <v-chip size="small" variant="flat" class="stitch-pill" :class="adjustForm.quantity_change >= 0 ? 'bg-info-lighten-5 text-info' : 'bg-primary-lighten-5 text-primary'">
                                    {{ adjustForm.quantity_change >= 0 ? '+' : '' }}{{ adjustForm.quantity_change }} unit(s)
                                </v-chip>
                            </div>
                        </div>
                    </div>

                    <v-form ref="adjustFormRef" @submit.prevent="submitAdjust">
                        <v-text-field
                            v-model.number="adjustForm.quantity_change"
                            label="Quantity change"
                            type="number"
                            :rules="adjustQuantityRules"
                            class="mb-2"
                        />
                        <v-select
                            v-model="adjustForm.transaction_type"
                            :items="transactionTypeOptions"
                            label="Transaction type"
                            :rules="[required('Transaction type is required')]"
                            class="mb-2"
                        />
                        <v-combobox
                            v-if="adjustForm.transaction_type === 'MANUAL_ADJUSTMENT'"
                            v-model="adjustForm.reason"
                            :items="reasonOptions"
                            label="Reason"
                            :rules="[required('Reason is required')]"
                            class="mb-2"
                        />
                        <v-textarea
                            v-model.trim="adjustForm.notes"
                            label="Notes"
                            rows="2"
                        />

                        <v-alert
                            v-if="submitError"
                            type="error"
                            variant="tonal"
                            class="mt-2"
                        >
                            <ul class="mb-0 ps-4">
                                <li v-for="(message, i) in submitError" :key="i">{{ message }}</li>
                            </ul>
                        </v-alert>

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
                        <v-chip size="small" variant="flat" class="stitch-pill bg-warning-lighten-5 text-premium">Audit Trail</v-chip>
                    </div>
                    <h3 class="text-body-large font-weight-bold mb-1">Stock history</h3>
                    <div class="text-body-medium text-medium-emphasis mb-4">
                        {{ historyDialog.item?.product_name }} — {{ historyDialog.item?.name }}
                    </div>

                    <div v-if="history.length" class="history-list gap-3">
                        <div v-for="entry in history" :key="entry.id" class="history-row px-4 py-3 border-sm border-opacity-100 rounded-lg">
                            <div class="d-flex align-center justify-space-between ga-2">
                                <v-chip
                                    size="small"
                                    variant="flat"
                                    class="stitch-pill"
                                    :class="entry.quantity_change >= 0 ? 'bg-info-lighten-5 text-info' : 'bg-primary-lighten-5 text-primary'"
                                >
                                    {{ entry.quantity_change >= 0 ? '+' : '' }}{{ entry.quantity_change }}
                                </v-chip>
                                <span class="text-body-small text-medium-emphasis">{{ new Date(entry.created_at).toLocaleString() }}</span>
                            </div>
                            <div class="font-weight-medium mt-1">{{ entry.reason }}</div>
                            <div class="d-flex align-center justify-space-between ga-2 mt-1">
                                <v-chip size="x-small" variant="flat" class="stitch-pill bg-surface-light text-on-surface-variant">
                                    {{ transactionTypeLabel(entry.transaction_type) }}
                                </v-chip>
                                <span class="text-body-small text-medium-emphasis">
                                    by {{ entry.user?.name ?? 'Unknown' }}
                                    <template v-if="entry.notes">— {{ entry.notes }}</template>
                                </span>
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
import { numeric, required } from '../../utils/validation';

const headers = [
    { title: 'Product', key: 'description', sortable: false },
    { title: 'Attributes', key: 'attributes', sortable: false },
    { title: 'Cost (USD)', key: 'cost_usd', sortable: false },
    { title: 'Price (MXN)', key: 'price_mxn', sortable: false },
    { title: 'Stock', key: 'stock_quantity', sortable: false },
    { title: '', key: 'actions', sortable: false, width: '190px' },
];

const reasonOptions = ['initial', 'purchase', 'sale', 'damaged', 'adjustment', 'return'];
const transactionTypeOptions = ['SALE', 'RESTOCK', 'MANUAL_ADJUSTMENT'];

const transactionTypeLabels = {
    SALE: 'Sale',
    RESTOCK: 'Restock',
    MANUAL_ADJUSTMENT: 'Manual adjustment',
    ORDER_CANCELLATION: 'Order cancellation',
};

const lowStockThreshold = 5;

const items = ref([]);
const loading = ref(false);
const search = ref('');
const total = ref(0);
const perPage = ref(10);
const activeFilter = ref('all');
let loadSequence = 0;

const adjustDialog = ref({ show: false, item: null });
const adjustFormRef = ref(null);
const adjusting = ref(false);
const submitError = ref(null);
const adjustForm = reactive({ quantity_change: 0, transaction_type: 'MANUAL_ADJUSTMENT', reason: '', notes: '' });

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
    if (quantity === 0) return 'bg-primary-lighten-5 text-primary';

    if (quantity <= lowStockThreshold) return 'bg-warning-lighten-5 text-warning';

    return 'bg-info-lighten-5 text-info';
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

function notOversell(value) {
    const current = adjustDialog.value.item?.stock_quantity ?? 0;
    const projected = current + Number(value ?? 0);

    return projected >= 0 || 'Stock cannot drop below zero';
}

const adjustQuantityRules = [
    required('Quantity change is required'),
    numeric('Must be a number'),
    notOversell,
];

function openAdjust(item) {
    submitError.value = null;
    adjustForm.quantity_change = 0;
    adjustForm.transaction_type = 'MANUAL_ADJUSTMENT';
    adjustForm.reason = '';
    adjustForm.notes = '';
    adjustFormRef.value?.resetValidation();
    adjustDialog.value = { show: true, item };
}

function transactionTypeLabel(type) {
    return transactionTypeLabels[type] ?? type;
}

async function performAdjust(item, quantityChange, transactionType, reason, notes) {
    await http.post('/api/inventory/adjust', {
        variant_id: item.id,
        quantity_change: quantityChange,
        transaction_type: transactionType,
        reason: reason ?? null,
        notes: notes ?? null,
    });

    await load();
}

async function quickAdjust(item, delta) {
    await performAdjust(item, delta, 'MANUAL_ADJUSTMENT', 'adjustment', null);
}

async function submitAdjust() {
    const { valid } = await adjustFormRef.value.validate();

    if (!valid) {
        return;
    }

    adjusting.value = true;
    submitError.value = null;

    try {
        await performAdjust(
            adjustDialog.value.item,
            adjustForm.quantity_change,
            adjustForm.transaction_type,
            adjustForm.reason,
            adjustForm.notes,
        );

        adjustDialog.value.show = false;
    } catch (error) {
        submitError.value = Object.values(error.data?.errors ?? {}).flat();
    } finally {
        adjusting.value = false;
    }
}

async function openHistory(item) {
    historyDialog.value = { show: true, item };
    history.value = [];

    const data = await http.get(`/api/variants/${item.id}/transactions?per_page=50`);

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
    background: rgb(var(--v-theme-primary));
    color: rgb(var(--v-theme-on-primary));
}

.preview-banner {
    background: rgb(var(--v-theme-surface-light));
}

.history-list {
    display: flex;
    flex-direction: column;
}

.history-row {
    background: rgb(var(--v-theme-surface));
}

.search-field {
    flex: 1 1 0%;
    min-width: 260px;
}
</style>
