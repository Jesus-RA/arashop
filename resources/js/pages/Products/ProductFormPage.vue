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
                            class="mb-2 required"
                            :rules="[required('Product name is required')]"
                        />
                        <v-select
                            v-model="form.status"
                            :items="statusOptions"
                            label="Status"
                            class="mb-2"
                        />
                        <v-textarea
                            v-model.trim="form.description"
                            label="Description"
                            rows="3"
                        />
                    </v-card>
                </v-col>

                <v-col cols="12" md="8">
                    <v-card class="rounded-lg">
                        <v-card-title class="d-flex align-center justify-space-between ga-2 p-4 border-b-sm border-warm">
                            <div class="d-flex align-center ga-3">
                                <div class="stitch-kpi-icon rounded-lg bg-surface-mint-subtle">
                                    <v-icon icon="mdi-variant" color="mint-deep" size="18" />
                                </div>
                                <div>
                                    <h2 class="text-body-large font-weight-bold mb-0">Pricing &amp; Inventory Matrix</h2>
                                    <p class="text-body-small text-medium-emphasis mb-0">Cost, resale and stock per variant</p>
                                </div>
                            </div>
                            <v-btn color="primary" variant="tonal" size="small" prepend-icon="mdi-plus" @click="addVariant">
                                Add variant
                            </v-btn>
                        </v-card-title>

                        <v-card-text>
                            <v-data-table-virtual
                                :items="form.variants"
                                :headers="variantColumns"
                                density="comfortable"
                                disable-sort
                                hide-no-data
                                no-data-text="No variants yet"
                                class="stitch-table"
                            >
                                <template #item.name="{ index }">
                                    <v-text-field
                                        v-model.trim="form.variants[index].name"
                                        label="Variant name"
                                        density="compact"
                                        hide-details="auto"
                                        :rules="[required('Variant name is required')]"
                                    />
                                </template>

                                <template #item.attributes="{ index }">
                                    <div
                                        v-for="(attribute, attrIndex) in form.variants[index].attributes"
                                        :key="attrIndex"
                                        class="d-flex ga-2 mb-1"
                                    >
                                        <v-text-field
                                            v-model.trim="attribute.key"
                                            label="Attribute"
                                            density="compact"
                                            hide-details="auto"
                                            style="max-width: 130px"
                                        />
                                        <v-text-field
                                            v-model.trim="attribute.value"
                                            label="Value"
                                            density="compact"
                                            hide-details="auto"
                                            style="max-width: 130px"
                                            @keydown.enter.prevent
                                        />
                                        <v-btn
                                            icon="mdi-close"
                                            size="x-small"
                                            variant="text"
                                            @click="form.variants[index].attributes.splice(attrIndex, 1)"
                                        />
                                    </div>
                                    <v-btn size="x-small" variant="text" prepend-icon="mdi-plus" @click="form.variants[index].attributes.push({ key: '', value: '' })">
                                        Attribute
                                    </v-btn>
                                </template>

                                <template #item.cost_usd="{ index }">
                                    <v-text-field
                                        v-model.number="form.variants[index].cost_usd"
                                        label="Cost USD"
                                        type="number"
                                        density="compact"
                                        hide-details="auto"
                                        style="max-width: 150px"
                                    />
                                </template>

                                <template #item.price_mxn="{ index }">
                                    <v-text-field
                                        v-model.number="form.variants[index].price_mxn"
                                        label="Price MXN"
                                        type="number"
                                        density="compact"
                                        hide-details="auto"
                                        style="max-width: 150px"
                                    />
                                </template>

                                <template #item.stock_quantity="{ index }">
                                    <v-text-field
                                        v-model.number="form.variants[index].stock_quantity"
                                        label="Qty"
                                        type="number"
                                        density="compact"
                                        hide-details="auto"
                                        style="max-width: 120px"
                                    />
                                </template>

                                <template #item.actions="{ index }">
                                    <v-btn
                                        icon="mdi-delete-outline"
                                        size="small"
                                        variant="text"
                                        color="error"
                                        :disabled="form.variants.length === 1"
                                        @click="form.variants.splice(index, 1)"
                                    />
                                </template>
                            </v-data-table-virtual>
                        </v-card-text>
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
import { useUiStore } from '../../store/ui';
import { required } from '../../utils/validation';

const route = useRoute();
const router = useRouter();
const uiStore = useUiStore();

const isEdit = computed(() => route.name === 'products.edit');

const statusOptions = [
    { title: 'Draft', value: 'draft' },
    { title: 'Active', value: 'active' },
    { title: 'Archived', value: 'archived' },
];

const saving = ref(false);

const form = reactive({
    name: '',
    description: '',
    status: 'draft',
    variants: [newVariant()],
});

function newVariant() {
    return {
        id: null,
        name: '',
        attributes: [],
        cost_usd: null,
        price_mxn: null,
        stock_quantity: 0,
    };
}

function addVariant() {
    form.variants.push(newVariant());
}

const variantColumns = computed(() => [
    { key: 'name', title: 'Name', sortable: false, minWidth: '170px' },
    { key: 'attributes', title: 'Attributes', sortable: false, width: '340px' },
    { key: 'cost_usd', title: 'Cost (USD)', sortable: false, width: '140px' },
    { key: 'price_mxn', title: 'Price (MXN)', sortable: false, width: '140px' },
    ...(!isEdit.value ? [{ key: 'stock_quantity', title: 'Qty', sortable: false, width: '120px' }] : []),
    { key: 'actions', title: '', sortable: false, width: '56px', align: 'end' },
]);

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
    form.status = data.status;
    form.variants = data.variants.map((variant) => ({
        id: variant.id,
        name: variant.name,
        attributes: Object.entries(variant.attributes ?? {}).map(([key, value]) => ({ key, value })),
        cost_usd: variant.cost_usd,
        price_mxn: variant.price_mxn,
        stock_quantity: variant.stock_quantity,
    }));
}

async function save() {
    saving.value = true;

    const payload = {
        name: form.name,
        description: form.description,
        status: form.status,
        variants: form.variants.map((variant) => ({
            ...(variant.id ? { id: variant.id } : {}),
            name: variant.name,
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

        uiStore.notify(isEdit.value ? 'Product updated' : 'Product created');
        router.push({ name: 'products.show', params: { id: result.data.id } });
    } catch (error) {
        const message = Object.values(error.data?.errors ?? {}).flat()[0] ?? 'Could not save the product.';
        uiStore.notify(message, { color: 'error', timeout: 5000 });
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
