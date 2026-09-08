<template>
    <div>
        <div class="d-flex align-center mb-4">
            <h1 class="text-h4">Products</h1>
            <v-spacer />
            <v-btn color="primary" prepend-icon="mdi-plus" :to="{ name: 'products.create' }">
                New product
            </v-btn>
        </div>

        <v-card>
            <v-card-text>
                <v-text-field
                    v-model.trim="search"
                    label="Search products"
                    prepend-inner-icon="mdi-magnify"
                    clearable
                    hide-details
                    density="compact"
                    class="mb-2"
                    @update:model-value="load"
                />

                <v-data-table-server
                    :headers="headers"
                    :items="products"
                    :loading="loading"
                    :items-length="total"
                    :items-per-page="perPage"
                    :items-per-page-options="[10, 20, 50]"
                    item-key="id"
                    @update:options="onOptions"
                >
                    <template #item.image_path="{ item }">
                        <v-avatar size="42" rounded>
                            <v-img
                                v-if="item.image_path"
                                :src="item.image_path"
                            />
                            <v-icon v-else icon="mdi-package-variant-outline" />
                        </v-avatar>
                    </template>

                    <template #item.name="{ item }">
                        <div class="font-weight-medium">{{ item.name }}</div>
                    </template>

                    <template #item.status="{ item }">
                        <v-chip size="small" :color="statusColor(item.status)" label>
                            {{ item.status }}
                        </v-chip>
                    </template>

                    <template #item.stock="{ item }">
                        <span :class="{ 'text-danger': item.total_stock === 0 }">
                            {{ item.total_stock }}
                        </span>
                    </template>

                    <template #item.actions="{ item }">
                        <v-btn icon="mdi-eye-outline" size="small" variant="text" :to="{ name: 'products.show', params: { id: item.id } }" />
                        <v-btn icon="mdi-pencil-outline" size="small" variant="text" :to="{ name: 'products.edit', params: { id: item.id } }" />
                        <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click="confirmDelete(item)" />
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <v-dialog v-model="deleteDialog.show" max-width="420">
            <v-card>
                <v-card-title>Delete product</v-card-title>
                <v-card-text>
                    Delete "{{ deleteDialog.product?.name }}"? This cannot be undone.
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog.show = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="destroy">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import http from '../../api/http';

const headers = [
    { title: '', key: 'image_path', sortable: false, width: '56px' },
    { title: 'Name', key: 'name' },
    { title: 'SKU', key: 'sku' },
    { title: 'Status', key: 'status', sortable: false },
    { title: 'Variants', key: 'variants_count', sortable: false },
    { title: 'Stock', key: 'stock', sortable: false },
    { title: '', key: 'actions', sortable: false, width: '120px' },
];

const products = ref([]);
const loading = ref(false);
const search = ref('');
const total = ref(0);
const perPage = ref(10);

const deleting = ref(false);
const deleteDialog = ref({ show: false, product: null });

function statusColor(status) {
    return ({ active: 'success', draft: 'warning', archived: 'secondary' })[status] ?? 'default';
}

async function load() {
    loading.value = true;

    try {
        const data = await http.get(
            `/api/products?per_page=${perPage.value}&search=${encodeURIComponent(search.value ?? '')}`,
        );

        products.value = data.data;
        total.value = data.meta?.total ?? 0;
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

async function destroy() {
    deleting.value = true;

    try {
        await http.delete(`/api/products/${deleteDialog.value.product.id}`);
        deleteDialog.value.show = false;
        await load();
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>