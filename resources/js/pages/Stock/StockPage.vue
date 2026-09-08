<template>
    <div>
        <div class="d-flex align-center mb-4">
            <h1 class="text-h4">Stock</h1>
        </div>

        <v-card>
            <v-card-text>
                <v-text-field
                    v-model.trim="search"
                    label="Search stock"
                    prepend-inner-icon="mdi-magnify"
                    clearable
                    hide-details
                    density="compact"
                    class="mb-2"
                    @update:model-value="load"
                />

                <v-data-table-server
                    :headers="headers"
                    :items="items"
                    :loading="loading"
                    :items-length="total"
                    :items-per-page="perPage"
                    :items-per-page-options="[10, 20, 50]"
                    item-key="id"
                    @update:options="onOptions"
                >
                    <template #item.description="{ item }">
                        <div class="font-weight-medium">{{ item.product_name }}</div>
                        <div class="text-body-2 text-medium-emphasis">{{ item.name }}</div>
                    </template>

                    <template #item.attributes="{ item }">
                        <v-chip
                            v-for="(value, key) in item.attributes"
                            :key="key"
                            size="x-small"
                            variant="tonal"
                            class="me-1"
                        >
                            {{ key }}: {{ value }}
                        </v-chip>
                    </template>

                    <template #item.stock_quantity="{ item }">
                        <v-chip
                            size="small"
                            :color="item.stock_quantity === 0 ? 'error' : item.stock_quantity < lowStockThreshold ? 'warning' : 'default'"
                            label
                        >
                            {{ item.stock_quantity }}
                        </v-chip>
                    </template>

                    <template #item.actions="{ item }">
                        <v-btn size="small" variant="text" prepend-icon="mdi-cog" @click="openAdjust(item)">
                            Adjust
                        </v-btn>
                        <v-btn size="small" variant="text" prepend-icon="mdi-history" @click="openHistory(item)">
                            History
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <v-dialog v-model="adjustDialog.show" max-width="480">
            <v-card>
                <v-card-title>Adjust stock</v-card-title>
                <v-card-text>
                    <div class="text-subtitle-1 font-weight-bold mb-1">
                        {{ adjustDialog.item?.product_name }} — {{ adjustDialog.item?.name }}
                    </div>
                    <div class="text-body-2 text-medium-emphasis mb-4">
                        Current stock:
                        <strong>{{ adjustDialog.item?.stock_quantity }}</strong>
                    </div>

                    <v-form @submit.prevent="submitAdjust">
                        <v-text-field
                            v-model.number="adjustForm.quantity_change"
                            label="Quantity change"
                            type="number"
                            required
                            :error-messages="adjustErrors('quantity_change')"
                        />
                        <v-combobox
                            v-model="adjustForm.reason"
                            :items="reasonOptions"
                            label="Reason"
                            required
                            :error-messages="adjustErrors('reason')"
                        />
                        <v-textarea
                            v-model.trim="adjustForm.notes"
                            label="Notes"
                            rows="2"
                            :error-messages="adjustErrors('notes')"
                        />

                        <v-alert
                            v-if="projectedStock !== null && projectedStock < 0"
                            type="error"
                            variant="tonal"
                            class="mt-2"
                        >
                            Stock would drop below zero.
                        </v-alert>
                        <v-alert
                            v-else-if="projectedStock !== null"
                            type="info"
                            variant="tonal"
                            class="mt-2"
                        >
                            New stock: {{ projectedStock }}
                        </v-alert>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
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

        <v-dialog v-model="historyDialog.show" max-width="560">
            <v-card>
                <v-card-title>
                    Stock history
                    <div v-if="historyDialog.item" class="text-body-2 text-medium-emphasis font-weight-regular">
                        {{ historyDialog.item.product_name }} — {{ historyDialog.item.name }}
                    </div>
                </v-card-title>
                <v-card-text>
                    <v-list v-if="history.length">
                        <v-list-item
                            v-for="entry in history"
                            :key="entry.id"
                            class="px-0"
                        >
                            <template #prepend>
                                <v-chip
                                    size="small"
                                    :color="entry.quantity_change >= 0 ? 'success' : 'error'"
                                    label
                                >
                                    {{ entry.quantity_change >= 0 ? '+' : '' }}{{ entry.quantity_change }}
                                </v-chip>
                            </template>
                            <v-list-item-title>
                                {{ entry.reason }}
                                <span class="text-medium-emphasis">· {{ entry.user?.name ?? 'Unknown' }}</span>
                            </v-list-item-title>
                            <v-list-item-subtitle>
                                {{ new Date(entry.created_at).toLocaleString() }}
                                <template v-if="entry.notes">— {{ entry.notes }}</template>
                            </v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                    <p v-else class="text-body-2 text-medium-emphasis mb-0">
                        No recorded adjustments yet.
                    </p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="historyDialog.show = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import http from '../../api/http';

const headers = [
    { title: 'Product', key: 'description', sortable: false },
    { title: 'SKU', key: 'sku' },
    { title: 'Attributes', key: 'attributes', sortable: false },
    { title: 'Cost (USD)', key: 'cost_usd', sortable: false },
    { title: 'Price (MXN)', key: 'price_mxn', sortable: false },
    { title: 'Stock', key: 'stock_quantity', sortable: false },
    { title: '', key: 'actions', sortable: false, width: '170px' },
];

const reasonOptions = ['initial', 'purchase', 'sale', 'damaged', 'adjustment', 'return'];

const lowStockThreshold = 5;

const items = ref([]);
const loading = ref(false);
const search = ref('');
const total = ref(0);
const perPage = ref(10);
let loadSequence = 0;

const adjustDialog = ref({ show: false, item: null });
const adjustErrorBag = ref({});
const adjusting = ref(false);
const adjustForm = reactive({ quantity_change: 0, reason: '', notes: '' });

const historyDialog = ref({ show: false, item: null });
const history = ref([]);

const projectedStock = computed(() => {
    if (!adjustDialog.value.item) return null;

    return adjustDialog.value.item.stock_quantity + Number(adjustForm.quantity_change ?? 0);
});

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

async function submitAdjust() {
    adjusting.value = true;
    adjustErrorBag.value = {};

    try {
        await http.post('/api/stock/adjustments', {
            variant_id: adjustDialog.value.item.id,
            quantity_change: adjustForm.quantity_change,
            reason: adjustForm.reason,
            notes: adjustForm.notes,
        });

        adjustDialog.value.show = false;
        await load();
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