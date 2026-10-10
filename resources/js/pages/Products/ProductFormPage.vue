<template>
    <section class="d-flex flex-column gr-3">
        <div>
            <v-btn prepend-icon="mdi-arrow-left" variant="text" color="primary" :to="{ name: 'products.index' }">
                Go Back
            </v-btn>
        </div>
        <div class="d-flex align-center ga-3">
            <h1 class="text-headline-small font-weight-bold my-0">{{ isEdit ? 'Edit product' : 'New product' }}</h1>
        </div>

        <v-form ref="formRef" @submit.prevent="save" class="d-flex flex-column gr-5">
            <v-row>
                <v-col cols="12" md="4">
                    <v-card class="d-flex flex-column gr-3 rounded-lg py-3">
                        <v-card-title class="d-flex align-center ga-3">
                            <div class="stitch-kpi-icon rounded-lg bg-secondary-lighten-2">
                                <v-icon icon="mdi-package-variant" color="primary" size="18" />
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold my-0">Product Details</h2>
                                <p class="text-body-small text-medium-emphasis my-0">US sourcing fundamentals</p>
                            </div>
                        </v-card-title>

                        <v-card-text class="d-flex flex-column gr-3 pb-0">
                            <v-text-field
                                v-model.trim="form.name"
                                label="Name"
                                class=" required"
                                :rules="[required('Product name is required')]"
                            />
                            <v-select
                                v-model="form.status"
                                :items="statusOptions"
                                label="Status"
                                class=""
                            />
                            <v-textarea
                                v-model.trim="form.description"
                                label="Description"
                                rows="3"
                            />
                            <v-img
                                v-if="form.image_path"
                                :src="form.image_path"
                                max-height="140"
                                contain
                                class="mx-auto rounded-lg"
                            />

                            <v-file-upload
                                v-model="pickedImage"
                                title="Drop product image here or click to choose locally"
                                accept="image/jpeg,image/png"
                                :multiple="false"
                                density="compact"
                                clearable
                                show-size
                                divider-text="or"
                                browse-text="Browse"
                                hide-details=auto
                                @update:model-value="onPickedImage"
                            />
                            <div v-if="form.image_path" class="d-flex align-center justify-center ga-2">
                                <span class="text-body-small text-medium-emphasis">JPG or PNG, up to 4 MB</span>
                                <v-btn
                                    size="small"
                                    variant="text"
                                    color="error"
                                    prepend-icon="mdi-close"
                                    :disabled="uploadingImage"
                                    @click="removeImage"
                                >
                                    Remove image
                                </v-btn>
                            </div>

                            <div class="d-flex align-center justify-end gc-3">
                                <v-btn
                                    variant="text"
                                    :to="{ name: 'products.index' }"
                                >
                                    Cancel
                                </v-btn>
                                <v-btn
                                    type="submit"
                                    color="primary"
                                    :loading="saving"
                                    :disabled="saving"
                                >
                                    {{ isEdit ? 'Save changes' : 'Create product' }}
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" md="8">
                    <v-alert v-if="!isEdit" density="compact" type="info" class="mb-2" variant="tonal">Save the product to start adding variants</v-alert>

                    <v-card class="rounded-lg pt-3 position-relative" :disabled="!isEdit">
                        <v-card-title class="d-flex align-center justify-space-between ga-2 p-4">
                            <div class="d-flex align-center ga-3">
                                <div class="stitch-kpi-icon rounded-lg bg-secondary-lighten-2">
                                    <v-icon icon="mdi-tune" color="primary" size="18" />
                                </div>
                                <div>
                                    <h2 class="text-body-large font-weight-bold my-0">Variants</h2>
                                    <p class="text-body-small text-medium-emphasis my-0">Cost, selling price and stock per variant</p>
                                </div>
                            </div>
                            <v-btn color="primary" variant="tonal" size="small" prepend-icon="mdi-plus" :disabled="!productId" @click="openAddVariant">
                                Add variant
                            </v-btn>
                        </v-card-title>

                        <v-card-text class="py-0">
                            <v-data-table-server
                                :items="form.variants"
                                :items-length="form.variants.length"
                                :headers="variantColumns"
                                :loading="variantsLoading"
                                density="compact"
                                disable-sort
                            >
                                <template #item.name="{ item }">
                                    <span class="font-weight-medium">{{ item.name }}</span>
                                </template>

                                <template #item.attributes="{ item }">
                                    <template v-if="item.attributes.length">
                                        <v-chip
                                            v-for="(attribute, i) in item.attributes"
                                            :key="i"
                                            size="x-small"
                                            variant="flat"
                                            class="stitch-pill bg-surface-light text-on-surface-variant me-1 mb-1"
                                        >
                                            {{ attribute.key }}: {{ attribute.value }}
                                        </v-chip>
                                    </template>
                                    <span v-else class="text-body-small text-medium-emphasis">—</span>
                                </template>

                                <template #item.cost_usd="{ item }">
                                    <span class="tabular-nums">{{ formatUsd(item.cost_usd) }}</span>
                                </template>

                                <template #item.price_mxn="{ item }">
                                    <span class="tabular-nums font-weight-semibold">{{ formatMxn(item.price_mxn) }}</span>
                                </template>

                                <template #item.stock_quantity="{ item }">
                                    <v-chip
                                        size="small"
                                        variant="flat"
                                        class="stitch-pill"
                                        :class="stockClasses(item.stock_quantity)"
                                    >
                                        {{ item.stock_quantity }} units
                                    </v-chip>
                                </template>

                                <template #no-data>
                                    <div class="d-flex flex-column gr-3 align-center py-3">
                                        <p class="my-0">Add stock to you product through your variants</p>
                                        <v-btn color="primary" variant="tonal" size="small" prepend-icon="mdi-plus" :disabled="!productId" @click="openAddVariant">
                                            Add variant
                                        </v-btn>
                                    </div>
                                </template>

                                <template #item.actions="{ item }">
                                    <div class="d-flex align-center gc-1">
                                        <v-tooltip location="top">
                                            <template #activator="{ props }">
                                                <v-btn
                                                    v-bind="props"
                                                    icon="mdi-pencil-outline"
                                                    size="small"
                                                    variant="text"
                                                    @click="openEditVariant(item)"
                                                />
                                            </template>
                                            <span>Edit</span>
                                        </v-tooltip>
                                        <v-tooltip location="top">
                                            <template #activator="{ props }">
                                                <v-btn
                                                    v-bind="props"
                                                    icon="mdi-delete-outline"
                                                    size="small"
                                                    variant="text"
                                                    color="error"
                                                    @click="confirmDeleteVariant(item)"
                                                />
                                            </template>
                                            <span>Delete</span>
                                        </v-tooltip>
                                    </div>
                                </template>
                            </v-data-table-server>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-form>

        <ProductVariantForm
            v-model="variantDialog"
            :product-id="productId"
            :variant="editingVariant"
            @saved="onSavedVariant"
        />

        <ConfirmDialog
            v-model="deleteDialog"
            title="Delete variant"
            icon="mdi-delete-forever"
            icon-color="red"
            cancel-button-text="Cancel"
            confirm-button-text="Delete"
            confirm-button-color="red"
            :loading="deleting"
            @confirm="discardVariant"
        >
            <template #text>
                <p class="text-body-medium text-medium-emphasis my-0">
                    Remove "{{ deleteVariant?.name }}" from this product? This cannot be undone.
                </p>
            </template>
        </ConfirmDialog>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ProductVariantForm from '../../components/ProductVariantForm.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import http from '../../api/http';
import { useUiStore } from '../../store/ui';
import { formatMxn, formatUsd } from '../../utils/format';
import { required } from '../../utils/validation';
import { stockClasses } from '../../utils/stock';

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
const formRef = ref(null);
const deleting = ref(false);
const variantDialog = ref(false);
const editingVariant = ref(null);
const deleteVariant = ref(null);
const productId = ref(null);

const uploadingImage = ref(false);
const pickedImage = ref([]);
const variantsLoading = ref(false);

const deleteDialog = computed({
    get: () => deleteVariant.value !== null,
    set: (value) => {
        if (!value) {
            deleteVariant.value = null;
        }
    },
});

const form = reactive({
    name: '',
    description: '',
    status: 'draft',
    image_path: '',
    variants: [],
});

function onPickedImage(files) {
    const file = files?.[0];

    pickedImage.value = [];

    if (!file) {
        return;
    }

    console.info('Product image picked:', { name: file.name, type: file.type, size: file.size });

    uploadImage(file);
}

function removeImage() {
    form.image_path = '';
}

async function uploadImage(file) {
    const isAllowed = ['image/jpeg', 'image/png'].includes(file.type)
        || /\.(jpe?g|png)$/i.test(file.name);

    if (!isAllowed) {
        uiStore.notify('Only JPG or PNG images are allowed.', { color: 'error', timeout: 5000 });

        return;
    }

    const body = new FormData();
    body.append('image', file);

    uploadingImage.value = true;

    try {
        const data = await http.post('/api/products/images', body);

        form.image_path = data.image_path;
    } catch (error) {
        console.error('Product image upload failed:', error.status, error.data);

        const message = Object.values(error.data?.errors ?? {}).flat()[0]
            ?? (error.status ? `Could not upload the image. (HTTP ${error.status})` : 'Could not upload the image. Check your connection.')
            ;
        uiStore.notify(message, { color: 'error', timeout: 5000 });
    } finally {
        uploadingImage.value = false;
    }
}

function openAddVariant() {
    editingVariant.value = null;
    variantDialog.value = true;
}

function openEditVariant(variant) {
    editingVariant.value = variant;
    variantDialog.value = true;
}

function onSavedVariant(data) {
    editingVariant.value = null;

    const index = form.variants.findIndex((variant) => variant.id === data.id);

    const normalized = {
        id: data.id,
        name: data.name,
        attributes: Object.entries(data.attributes ?? {}).map(([key, value]) => ({ key, value })),
        cost_usd: data.cost_usd,
        price_mxn: data.price_mxn,
        stock_quantity: data.stock_quantity,
    };

    if (index !== -1) {
        form.variants.splice(index, 1, normalized);

        return;
    }

    form.variants.push(normalized);
}

function confirmDeleteVariant(variant) {
    deleteVariant.value = variant;
}

async function discardVariant() {
    const target = deleteVariant.value;

    if (!target) {
        return;
    }

    deleting.value = true;

    try {
        await http.delete(`/api/products/${productId.value}/variants/${target.id}`);

        const index = form.variants.findIndex((variant) => variant.id === target.id);

        if (index !== -1) {
            form.variants.splice(index, 1);
        }

        uiStore.notify('Variant deleted');
    } catch (error) {
        const message = Object.values(error.data?.errors ?? {}).flat()[0] ?? 'Could not delete the variant.';
        uiStore.notify(message, { color: 'error', timeout: 5000 });
    } finally {
        deleting.value = false;
        deleteVariant.value = null;
    }
}

const variantColumns = computed(() => [
    { key: 'name', title: 'Name', sortable: false, minWidth: '170px' },
    { key: 'attributes', title: 'Attributes', sortable: false, width: '340px' },
    { key: 'cost_usd', title: 'Cost (USD)', sortable: false, width: '140px' },
    { key: 'price_mxn', title: 'Price (MXN)', sortable: false, width: '140px' },
    { key: 'stock_quantity', title: 'Qty', sortable: false, width: '120px' },
    { key: 'actions', title: 'Actions', sortable: false, width: '96px', align: 'center' },
]);

async function loadProduct() {
    variantsLoading.value = true;

    try {
        const { data } = await http.get(`/api/products/${route.params.id}`);

        productId.value = data.id;
        form.name = data.name;
        form.description = data.description ?? '';
        form.status = data.status;
        form.image_path = data.image_path ?? '';
        form.variants = data.variants.map((variant) => ({
            id: variant.id,
            name: variant.name,
            attributes: Object.entries(variant.attributes ?? {}).map(([key, value]) => ({ key, value })),
            cost_usd: variant.cost_usd,
            price_mxn: variant.price_mxn,
            stock_quantity: variant.stock_quantity,
        }));
    } finally {
        variantsLoading.value = false;
    }
}

async function save() {
    const { valid } = await formRef.value.validate();

    if (!valid) {
        return;
    }

    saving.value = true;

    const payload = {
        name: form.name,
        description: form.description,
        status: form.status,
        image_path: form.image_path,
    };

    try {
        if (productId.value) {
            await http.put(`/api/products/${productId.value}`, payload);

            uiStore.notify('Product updated');
        } else {
            const { data } = await http.post('/api/products', payload);

            productId.value = data.id;
            router.replace({ name: 'products.edit', params: { id: data.id } });

            uiStore.notify('Product created — you can now add variants');
        }
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
