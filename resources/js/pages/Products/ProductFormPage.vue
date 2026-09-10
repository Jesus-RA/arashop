<template>
    <div>
        <div class="d-flex align-center ga-3 mb-4">
            <v-btn icon="mdi-arrow-left" variant="outlined" width="42" height="42" :to="{ name: 'products.index' }" />
            <div>
                <div class="stitch-label mb-1">Amazon US Gateway</div>
                <h1 class="text-headline-small font-weight-bold mb-0">{{ isEdit ? 'Edit product' : 'New product' }}</h1>
            </div>
            <v-spacer />
            <v-chip size="small" variant="flat" class="stitch-pill bg-canvas-tint text-on-surface-variant">
                <span class="fx-dot me-1" style="background: rgb(var(--v-theme-status-warning))" />
                {{ isEdit ? 'Edit Mode' : 'Draft' }}
            </v-chip>
        </div>

        <v-form @submit.prevent="save">
            <v-row>
                <v-col cols="12" md="4">
                    <v-card class="rounded-lg px-6 py-5 mb-4">
                        <div class="d-flex align-center ga-3 mb-4">
                            <div class="stitch-kpi-icon rounded-lg bg-surface-rose-subtle">
                                <v-icon icon="mdi-package-variant" color="primary" size="18" />
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold mb-0">Product Details</h2>
                                <p class="text-body-small text-medium-emphasis mb-0">US sourcing fundamentals</p>
                            </div>
                        </div>

                        <div class="image-drop pa-5 mb-4 border-sm border-dashed border-outline border-opacity-100 rounded-lg">
                            <div class="image-drop-inner">
                                <div class="chip-icon mx-auto mb-2 rounded-lg" style="width: 44px; height: 44px">
                                    <v-icon icon="mdi-image-outline" color="on-surface-variant" size="22" />
                                </div>
                                <div class="text-body-medium font-weight-medium">Product image</div>
                                <div class="text-body-small text-medium-emphasis">Upload from Amazon listing</div>
                            </div>
                        </div>

                        <v-text-field
                            v-model.trim="form.name"
                            label="Name"
                            :error-messages="fieldErrors('name')"
                            required
                            class="mb-2"
                        />
                        <v-text-field
                            v-model.trim="form.sku"
                            label="Product SKU"
                            :error-messages="fieldErrors('sku')"
                            class="mb-2"
                        />
                        <v-select
                            v-model="form.status"
                            :items="statusOptions"
                            label="Status"
                            :error-messages="fieldErrors('status')"
                            class="mb-2"
                        />
                        <v-textarea
                            v-model.trim="form.description"
                            label="Description"
                            rows="3"
                            :error-messages="fieldErrors('description')"
                        />
                    </v-card>
                </v-col>

                <v-col cols="12" md="8">
                    <v-card class="stitch-table-card rounded-lg">
                        <div class="d-flex align-center justify-space-between ga-2 p-4 border-b-sm border-warm">
                            <div class="d-flex align-center ga-3">
                                <div class="stitch-kpi-icon rounded-lg bg-surface-mint-subtle">
                                    <v-icon icon="mdi-variant" color="mint-deep" size="18" />
                                </div>
                                <div>
                                    <h2 class="text-body-large font-weight-bold mb-0">Pricing &amp; Inventory Matrix</h2>
                                    <p class="text-body-small text-medium-emphasis mb-0">Cost, resale and stock per SKU variant</p>
                                </div>
                            </div>
                            <v-btn color="primary" variant="tonal" size="small" prepend-icon="mdi-plus" @click="addVariant">
                                Add variant
                            </v-btn>
                        </div>

                        <v-alert v-if="fieldErrors('variants').length" type="error" variant="tonal" class="mx-4 mt-4">
                            {{ fieldErrors('variants').join(' ') }}
                        </v-alert>

                        <div class="table-scroll px-4 pb-4">
                            <table class="stitch-table w-100">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">SKU</th>
                                        <th class="px-4 py-3">Attributes</th>
                                        <th class="px-4 py-3">Cost (USD)</th>
                                        <th class="px-4 py-3">Price (MXN)</th>
                                        <th class="px-4 py-3" v-if="!isEdit">Qty</th>
                                        <th class="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(variant, index) in form.variants" :key="index">
                                        <td class="px-4 py-3" style="min-width: 160px">
                                            <v-text-field
                                                v-model.trim="variant.name"
                                                label="Variant name"
                                                density="compact"
                                                hide-details
                                                :error-messages="fieldErrors(`variants.${index}.name`)"
                                            />
                                        </td>
                                        <td class="px-4 py-3" style="min-width: 140px">
                                            <v-text-field
                                                v-model.trim="variant.sku"
                                                label="SKU"
                                                density="compact"
                                                hide-details
                                                :error-messages="fieldErrors(`variants.${index}.sku`)"
                                            />
                                        </td>
                                        <td class="px-4 py-3" style="min-width: 260px">
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
                                        <td class="px-4 py-3" style="min-width: 120px">
                                            <v-text-field
                                                v-model.number="variant.cost_usd"
                                                label="Cost USD"
                                                type="number"
                                                density="compact"
                                                hide-details
                                                :error-messages="fieldErrors(`variants.${index}.cost_usd`)"
                                            />
                                        </td>
                                        <td class="px-4 py-3" style="min-width: 120px">
                                            <v-text-field
                                                v-model.number="variant.price_mxn"
                                                label="Price MXN"
                                                type="number"
                                                density="compact"
                                                hide-details
                                                :error-messages="fieldErrors(`variants.${index}.price_mxn`)"
                                            />
                                        </td>
                                        <td class="px-4 py-3" v-if="!isEdit" style="min-width: 120px">
                                            <v-text-field
                                                v-model.number="variant.stock_quantity"
                                                label="Qty"
                                                type="number"
                                                density="compact"
                                                hide-details
                                                :error-messages="fieldErrors(`variants.${index}.stock_quantity`)"
                                            />
                                        </td>
                                        <td class="px-4 py-3">
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
                            </table>
                        </div>
                    </v-card>
                </v-col>
            </v-row>

            <div class="form-actions mt-4 px-5 py-4 gap-3 border-sm border-warm rounded-lg">
                <div class="d-flex align-center ga-2">
                    <v-chip size="small" variant="flat" class="stitch-pill bg-surface-mint-subtle text-mint-deep">{{ form.variants.length }} Variants</v-chip>
                    <span class="text-body-small text-medium-emphasis">Estimated margin will be calculated on save</span>
                </div>
                <div class="d-flex align-center ga-2">
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

<style scoped>
.table-scroll {
    overflow-x: auto;
}

.stitch-table-card {
    overflow: hidden;
}

.image-drop {
    background: rgb(var(--v-theme-canvas-tint));
}

.form-actions {
    position: sticky;
    bottom: 0;
    z-index: 4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    background: rgb(var(--v-theme-surface-card));
}
</style>
