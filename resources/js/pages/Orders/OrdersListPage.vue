<template>
    <div>
        <div class="d-flex flex-wrap align-end justify-space-between ga-3 mb-5">
            <div>
                <div class="d-flex align-center ga-2 mb-1">
                    <span class="stitch-label">Sales Orders</span>
                    <v-chip size="small" variant="flat" class="text-body-small font-weight-semibold bg-surface-mint-subtle text-mint-deep">MXN Checkout</v-chip>
                </div>
                <h1 class="text-headline-small font-weight-bold mb-0">Orders</h1>
                <p class="text-body-medium text-medium-emphasis mb-0 mt-1">
                    Sales captured against live stock, with immutable price snapshots.
                </p>
            </div>
            <div class="d-flex align-center ga-2">
                <v-btn color="primary" prepend-icon="mdi-plus" :to="{ name: 'orders.create' }">
                    New order
                </v-btn>
            </div>
        </div>

        <v-row class="mb-4">
            <v-col cols="12" sm="6" lg="3">
                <KpiCard title="Total Orders" icon="mdi-receipt-text-outline" icon-color="primary">
                    {{ total }} Orders
                </KpiCard>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <KpiCard title="Awaiting Payment" icon="mdi-clock-outline" icon-color="status-warning">
                    <span class="text-status-warning">{{ pendingCount }} Pending</span>
                </KpiCard>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <KpiCard title="Paid" icon="mdi-check-circle-outline" icon-color="mint-deep">
                    <span class="text-mint-deep">{{ paidCount }} Paid</span>
                </KpiCard>
            </v-col>
            <v-col cols="12" sm="6" lg="3">
                <KpiCard title="Page Revenue" icon="mdi-cash-multiple" icon-color="primary">
                    <span class="text-mint-deep">{{ formatMxn(pageRevenue) }}</span>
                </KpiCard>
            </v-col>
        </v-row>

        <v-card class="rounded-lg pa-3 mb-4">
            <div class="d-flex flex-wrap align-center justify-space-between ga-3">
                <div class="d-flex flex-wrap align-center ga-3 flex-1">
                    <v-text-field
                        v-model.trim="search"
                        label="Search orders"
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
                    Showing {{ currentStart }}-{{ currentEnd }} of {{ total }} orders
                </span>
            </div>
        </v-card>

        <v-card class="overflow-hidden">
            <v-data-table-server
                :headers="headers"
                :items="filteredOrders"
                :loading="loading"
                :items-length="total"
                :items-per-page="perPage"
                :items-per-page-options="[10, 20, 50]"
                item-key="id"
                density="comfortable"
                @update:options="onOptions"
                @click:row="openOrder"
            >
                <template #item.order_number="{ item }">
                    <span class="font-weight-semibold">{{ item.order_number }}</span>
                </template>

                <template #item.status="{ item }">
                    <v-chip
                        size="small"
                        variant="flat"
                        class="text-body-small font-weight-semibold"
                        :class="orderStatusClasses(item.status)"
                    >
                        <div class="d-flex align-center gc-2">
                            <span
                                class="fx-dot rounded-circle"
                                :style="{ background: orderStatusDotColor(item.status) }"
                            />
                            {{ orderStatusLabel(item.status) }}
                        </div>
                    </v-chip>
                </template>

                <template #item.items="{ item }">
                    {{ item.items.length }} item{{ item.items.length === 1 ? '' : 's' }}
                </template>

                <template #item.total_amount="{ item }">
                    <span class="tabular-nums font-weight-semibold">{{ formatMxn(item.total_amount) }}</span>
                </template>

                <template #item.user="{ item }">
                    {{ item.user?.name ?? 'System' }}
                </template>

                <template #item.created_at="{ item }">
                    <span class="text-body-medium text-medium-emphasis">{{ formatDate(item.created_at) }}</span>
                </template>

                <template #item.actions="{ item }">
                    <v-btn size="small" variant="tonal" color="primary" :to="{ name: 'orders.show', params: { id: item.id } }">
                        View
                    </v-btn>
                </template>
            </v-data-table-server>
        </v-card>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import KpiCard from '../../components/KpiCard.vue';
import http from '../../api/http';
import { formatDate, formatMxn } from '../../utils/format';
import { orderStatusClasses, orderStatusDotColor, orderStatusLabel } from '../../utils/orderStatus';

const router = useRouter();

const headers = [
    { title: 'Order', key: 'order_number', sortable: false },
    { title: 'Status', key: 'status', sortable: false },
    { title: 'Items', key: 'items', sortable: false },
    { title: 'Created by', key: 'user', sortable: false },
    { title: 'Total (MXN)', key: 'total_amount', sortable: false },
    { title: 'Date', key: 'created_at', sortable: false },
    { title: '', key: 'actions', sortable: false, width: '90px' },
];

const orders = ref([]);
const loading = ref(false);
const search = ref('');
const total = ref(0);
const perPage = ref(10);
const activeFilter = ref('all');

const filterPills = [
    { label: 'All', value: 'all' },
    { label: 'Pending', value: 'pending' },
    { label: 'Paid', value: 'paid' },
    { label: 'Cancelled', value: 'cancelled' },
];

const filteredOrders = computed(() => {
    if (activeFilter.value === 'all') {
        return orders.value;
    }

    return orders.value.filter((order) => order.status === activeFilter.value);
});

const pendingCount = computed(() => orders.value.filter((order) => order.status === 'pending').length);
const paidCount = computed(() => orders.value.filter((order) => order.status === 'paid').length);
const pageRevenue = computed(() =>
    orders.value.reduce((sum, order) => sum + (Number(order.total_amount) || 0), 0),
);

const currentStart = computed(() => (total.value === 0 ? 0 : (perPage.value * 1) - perPage.value + 1));
const currentEnd = computed(() => Math.min(perPage.value, total.value));

function openOrder(_event, { item }) {
    router.push({ name: 'orders.show', params: { id: item.id } });
}

async function load() {
    loading.value = true;

    try {
        const data = await http.get(
            `/api/orders?per_page=${perPage.value}&search=${encodeURIComponent(search.value ?? '')}`,
        );

        orders.value = data.data;
        total.value = data.meta?.total ?? 0;
    } finally {
        loading.value = false;
    }
}

function onOptions({ page, itemsPerPage }) {
    perPage.value = itemsPerPage;
    load();
}

onMounted(load);
</script>

<style scoped>
.search-field {
    flex: 1 1 0%;
    min-width: 260px;
}
</style>