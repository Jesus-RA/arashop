<template>
    <div v-if="product">
        <div class="d-flex align-center mb-4">
            <v-btn icon="mdi-arrow-left" variant="text" :to="{ name: 'products.index' }" />
            <h1 class="text-h4">{{ product.name }}</h1>
            <div class="ms-4">
                <v-chip size="small" :color="statusColor(product.status)" label>
                    {{ product.status }}
                </v-chip>
            </div>
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
                <v-card>
                    <v-card-text class="text-center">
                        <v-avatar size="180" rounded="lg" class="mb-4">
                            <v-img v-if="product.image_path" :src="product.image_path" />
                            <v-icon v-else icon="mdi-package-variant-outline" size="64" />
                        </v-avatar>
                        <div class="text-subtitle-1 font-weight-bold">{{ product.name }}</div>
                        <div v-if="product.sku" class="text-body-2 text-medium-emphasis">
                            SKU {{ product.sku }}
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="8">
                <v-card class="mb-4">
                    <v-card-title>Variants</v-card-title>
                    <v-table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Attributes</th>
                                <th>Cost (USD)</th>
                                <th>Price (MXN)</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="variant in product.variants"
                                :key="variant.id"
                            >
                                <td class="font-weight-medium">{{ variant.name }}</td>
                                <td>{{ variant.sku ?? '-' }}</td>
                                <td>
                                    <v-chip
                                        v-for="(value, key) in variant.attributes"
                                        :key="key"
                                        size="x-small"
                                        variant="tonal"
                                        class="me-1"
                                    >
                                        {{ key }}: {{ value }}
                                    </v-chip>
                                </td>
                                <td>{{ formatUsd(variant.cost_usd) }}</td>
                                <td>{{ formatMxn(variant.price_mxn) }}</td>
                                <td>
                                    <v-chip
                                        size="small"
                                        :color="variant.stock_quantity === 0 ? 'error' : 'default'"
                                        label
                                    >
                                        {{ variant.stock_quantity }}
                                    </v-chip>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>

                <v-card v-if="product.description">
                    <v-card-title>Description</v-card-title>
                    <v-card-text>{{ product.description }}</v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-dialog v-model="deleteDialog" max-width="420">
            <v-card>
                <v-card-title>Delete product</v-card-title>
                <v-card-text>
                    Delete "{{ product.name }}" and all its variants? This cannot be undone.
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="destroy">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http from '../../api/http';
import { formatMxn, formatUsd } from '../../utils/format';

const route = useRoute();
const router = useRouter();

const product = ref(null);
const deleteDialog = ref(false);
const deleting = ref(false);

function statusColor(status) {
    return ({ active: 'success', draft: 'warning', archived: 'secondary' })[status] ?? 'default';
}

async function load() {
    const { data } = await http.get(`/api/products/${route.params.id}`);

    product.value = data;
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