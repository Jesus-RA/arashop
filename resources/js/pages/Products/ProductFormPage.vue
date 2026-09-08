<template>
    <div>
        <div class="d-flex align-center mb-4">
            <v-btn icon="mdi-arrow-left" variant="text" :to="{ name: 'products.index' }" />
            <h1 class="text-h4">{{ isEdit ? 'Edit product' : 'New product' }}</h1>
        </div>

        <v-form @submit.prevent="save">
            <v-card class="mb-4">
                <v-card-title>Details</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model.trim="form.name"
                                label="Name"
                                :error-messages="fieldErrors('name')"
                                required
                            />
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-text-field
                                v-model.trim="form.sku"
                                label="Product SKU"
                                :error-messages="fieldErrors('sku')"
                            />
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-select
                                v-model="form.status"
                                :items="statusOptions"
                                label="Status"
                                :error-messages="fieldErrors('status')"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model.trim="form.description"
                                label="Description"
                                rows="3"
                                :error-messages="fieldErrors('description')"
                            />
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>

            <v-card class="mb-4">
                <v-card-title class="d-flex align-center">
                    Variants
                    <v-spacer />
                    <v-btn color="primary" variant="tonal" size="small" prepend-icon="mdi-plus" @click="addVariant">
                        Add variant
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-alert v-if="fieldErrors('variants').length" type="error" variant="tonal" class="mb-3">
                        {{ fieldErrors('variants').join(' ') }}
                    </v-alert>

                    <v-table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Attributes</th>
                                <th>Cost (USD)</th>
                                <th>Price (MXN)</th>
                                <th v-if="!isEdit">Initial stock</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(variant, index) in form.variants" :key="index">
                                <td style="min-width: 160px">
                                    <v-text-field
                                        v-model.trim="variant.name"
                                        label="Variant name"
                                        density="compact"
                                        hide-details
                                        :error-messages="fieldErrors(`variants.${index}.name`)"
                                    />
                                </td>
                                <td style="min-width: 140px">
                                    <v-text-field
                                        v-model.trim="variant.sku"
                                        label="SKU"
                                        density="compact"
                                        hide-details
                                        :error-messages="fieldErrors(`variants.${index}.sku`)"
                                    />
                                </td>
                                <td style="min-width: 260px">
                                    <div
                                        v-for="(attribute, attrIndex) in variant.attributes"
                                        :key="attrIndex"
                                        class="d-flex ga-2 mb-1"
                                    >
                                        <v-text-field
                                            v-model.trim="attribute.key"
                                            label="Attribute"
                                            density="compact"
                                            hide-details
                                            style="max-width: 130px"
                                        />
                                        <v-text-field
                                            v-model.trim="attribute.value"
                                            label="Value"
                                            density="compact"
                                            hide-details
                                            style="max-width: 130px"
                                            @keydown.enter.prevent
                                        />
                                        <v-btn
                                            icon="mdi-close"
                                            size="x-small"
                                            variant="text"
                                            @click="variant.attributes.splice(attrIndex, 1)"
                                        />
                                    </div>
                                    <v-btn size="x-small" variant="text" prepend-icon="mdi-plus" @click="variant.attributes.push({ key: '', value: '' })">
                                        Attribute
                                    </v-btn>
                                </td>
                                <td style="min-width: 120px">
                                    <v-text-field
                                        v-model.number="variant.cost_usd"
                                        label="Cost USD"
                                        type="number"
                                        density="compact"
                                        hide-details
                                        :error-messages="fieldErrors(`variants.${index}.cost_usd`)"
                                    />
                                </td>
                                <td style="min-width: 120px">
                                    <v-text-field
                                        v-model.number="variant.price_mxn"
                                        label="Price MXN"
                                        type="number"
                                        density="compact"
                                        hide-details
                                        :error-messages="fieldErrors(`variants.${index}.price_mxn`)"
                                    />
                                </td>
                                <td v-if="!isEdit" style="min-width: 120px">
                                    <v-text-field
                                        v-model.number="variant.stock_quantity"
                                        label="Qty"
                                        type="number"
                                        density="compact"
                                        hide-details
                                        :error-messages="fieldErrors(`variants.${index}.stock_quantity`)"
                                    />
                                </td>
                                <td>
                                    <v-btn
                                        icon="mdi-delete-outline"
                                        size="small"
                                        variant="text"
                                        color="error"
                                        :disabled="form.variants.length === 1"
                                        @click="form.variants.splice(index, 1)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
            </v-card>

            <div class="d-flex justify-end ga-2">
                <v-btn variant="text" :to="{ name: 'products.index' }">Cancel</v-btn>
                <v-btn
                    type="submit"
                    color="primary"
                    :loading="saving"
                    :disabled="saving"
                >
                    {{ isEdit ? 'Save changes' : 'Create product' }}
                </v-btn>
            </div>
        </v-form>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http from '../../api/http';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => route.name === 'products.edit');

const statusOptions = [
    { title: 'Draft', value: 'draft' },
    { title: 'Active', value: 'active' },
    { title: 'Archived', value: 'archived' },
];

const saving = ref(false);
const errors = ref({});

const form = reactive({
    name: '',
    description: '',
    sku: '',
    status: 'draft',
    variants: [newVariant()],
});

function newVariant() {
    return {
        id: null,
        name: '',
        sku: '',
        attributes: [],
        cost_usd: null,
        price_mxn: null,
        stock_quantity: 0,
    };
}

function addVariant() {
    form.variants.push(newVariant());
}

function fieldErrors(key) {
    return errors.value[key] ?? [];
}

function attributesToObject(attributes) {
    return attributes
        .filter((attribute) => attribute.key.trim() !== '')
        .reduce((acc, attribute) => {
            acc[attribute.key.trim()] = attribute.value;
            return acc;
        }, {});
}

async function loadProduct() {
    const { data } = await http.get(`/api/products/${route.params.id}`);

    form.name = data.name;
    form.description = data.description ?? '';
    form.sku = data.sku ?? '';
    form.status = data.status;
    form.variants = data.variants.map((variant) => ({
        id: variant.id,
        name: variant.name,
        sku: variant.sku ?? '',
        attributes: Object.entries(variant.attributes ?? {}).map(([key, value]) => ({ key, value })),
        cost_usd: variant.cost_usd,
        price_mxn: variant.price_mxn,
        stock_quantity: variant.stock_quantity,
    }));
}

async function save() {
    saving.value = true;
    errors.value = {};

    const payload = {
        name: form.name,
        description: form.description,
        sku: form.sku,
        status: form.status,
        variants: form.variants.map((variant) => ({
            ...(variant.id ? { id: variant.id } : {}),
            name: variant.name,
            sku: variant.sku,
            attributes: attributesToObject(variant.attributes),
            cost_usd: variant.cost_usd,
            price_mxn: variant.price_mxn,
            ...(isEdit.value ? {} : { stock_quantity: variant.stock_quantity }),
        })),
    };

    try {
const result = isEdit.value
            ? await http.put(`/api/products/${route.params.id}`, payload)
            : await http.post('/api/products', payload);

        router.push({ name: 'products.show', params: { id: result.data.id } });
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    if (isEdit.value) {
        loadProduct();
    }
});
</script>