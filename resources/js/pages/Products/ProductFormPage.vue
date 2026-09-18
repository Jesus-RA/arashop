<template>
    <div>
        <div class="d-flex align-center ga-3 mb-4">
            <v-btn icon="mdi-arrow-left" variant="outlined" width="42" height="42" :to="{ name: 'products.index' }" />
            <div>
                <div class="stitch-label mb-1">Amazon US Gateway</div>
                <h1 class="text-headline-small font-weight-bold mb-0">{{ isEdit ? 'Edit product' : 'New product' }}</h1>
            </div>
            <v-spacer />
            <v-chip size="small" variant="flat" class="stitch-pill bg-surface-light text-on-surface-variant">
                <span class="fx-dot me-1" style="background: rgb(var(--v-theme-warning))" />
                {{ isEdit ? 'Edit Mode' : 'Draft' }}
            </v-chip>
        </div>

        <v-form ref="formRef" @submit.prevent="save">
            <v-row>
                <v-col cols="12" md="4">
                    <v-card class="rounded-lg px-6 py-5 mb-4">
                        <div class="d-flex align-center ga-3 mb-4">
                            <div class="stitch-kpi-icon rounded-lg bg-primary-lighten-5">
                                <v-icon icon="mdi-package-variant" color="primary" size="18" />
                            </div>
                            <div>
                                <h2 class="text-body-large font-weight-bold mb-0">Product Details</h2>
                                <p class="text-body-small text-medium-emphasis mb-0">US sourcing fundamentals</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <v-img
                                v-if="form.image_path"
                                :src="form.image_path"
                                max-height="140"
                                contain
                                class="mx-auto mb-3 rounded-lg"
                            />
                            <v-file-upload
                                v-model="pickedImage"
                                accept="image/jpeg,image/png"
                                :multiple="false"
                                clearable
                                show-size
                                prepend-icon="mdi-image-outline"
                                divider-text="or"
                                browse-text="Browse"
                                @update:model-value="onPickedImage"
                            />
                            <div v-if="form.image_path" class="d-flex align-center justify-center ga-2 mt-2">
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
                        <v-card-title class="d-flex align-center justify-space-between ga-2 p-4">
                            <div class="d-flex align-center ga-3">
                                <div class="stitch-kpi-icon rounded-lg bg-info-lighten-5">
                                    <v-icon icon="mdi-tune" color="info" size="18" />
                                </div>
                                <div>
                                    <h2 class="text-body-large font-weight-bold my-0">Pricing &amp; Inventory Matrix</h2>
                                    <p class="text-body-small text-medium-emphasis my-0">Cost, resale and stock per variant</p>
                                </div>
                            </div>
                            <v-btn color="primary" variant="tonal" size="small" prepend-icon="mdi-plus" :disabled="!productId" @click="openAddVariant">
                                Add variant
                            </v-btn>
                        </v-card-title>

                        <v-card-text>
                            <v-alert
                                v-if="!productId"
                                type="warning"
                                variant="tonal"
                                density="compact"
                                class="mb-3"
                            >
                                Save the product first, then add its variants.
                            </v-alert>
                            <v-data-table-virtual
                                :items="form.variants"
                                :headers="variantColumns"
                                density="comfortable"
                                disable-sort
                                hide-no-data
                                no-data-text="No variants yet"
                                class="stitch-table"
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
                                        size="x-small"
                                        variant="flat"
                                        class="stitch-pill"
                                        :class="item.stock_quantity === 0 ? 'bg-primary-lighten-5 text-primary' : 'bg-info-lighten-5 text-info'"
                                    >
                                        {{ item.stock_quantity }}
                                    </v-chip>
                                </template>

                                <template #item.actions="{ item }">
                                    <div class="d-flex align-center gc-1">
                                        <v-btn
                                            icon="mdi-pencil-outline"
                                            size="small"
                                            variant="text"
                                            @click="openEditVariant(item)"
                                        />
                                        <v-btn
                                            icon="mdi-delete-outline"
                                            size="small"
                                            variant="text"
                                            color="error"
                                            @click="confirmDeleteVariant(item)"
                                        />
                                    </div>
                                </template>
                            </v-data-table-virtual>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <div class="form-actions mt-4 px-5 py-4 gap-3 border-sm rounded-lg">
                <div class="d-flex align-center ga-2">
                    <v-chip size="small" variant="flat" class="stitch-pill bg-info-lighten-5 text-info">{{ form.variants.length }} Variants</v-chip>
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

        <ProductVariantForm
            v-model="variantDialog"
            :product-id="productId"
            :variant="editingVariant"
            @saved="onSavedVariant"
        />

        <v-dialog v-model="deleteDialog" max-width="420">
            <v-card class="pa-2">
                <v-card-text class="text-center pt-6">
                    <div class="mx-auto mb-4 d-flex align-center justify-center rounded-circle bg-error-lighten-4 delete-icon">
                        <v-icon icon="mdi-delete-forever" color="error" size="28" />
                    </div>
                    <h3 class="text-body-large font-weight-bold">Delete variant</h3>
                    <p class="text-body-medium text-medium-emphasis mb-0 mt-2">
                        Remove "{{ deleteVariant?.name }}" from this product? This cannot be undone.
                    </p>
                </v-card-text>
                <v-card-actions class="justify-end pb-4 pe-4">
                    <v-btn variant="tonal" @click="deleteVariant = null">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" :disabled="deleting" @click="discardVariant">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ProductVariantForm from '../../components/ProductVariantForm.vue';
import http from '../../api/http';
import { useUiStore } from '../../store/ui';
import { formatMxn, formatUsd } from '../../utils/format';
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
const formRef = ref(null);
const deleting = ref(false);
const variantDialog = ref(false);
const editingVariant = ref(null);
const deleteVariant = ref(null);
const productId = ref(null);

const uploadingImage = ref(false);
const pickedImage = ref([]);

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

<style scoped>
.form-actions {
    position: sticky;
    bottom: 0;
    z-index: 4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    background: rgb(var(--v-theme-surface));
}

.delete-icon {
    width: 48px;
    height: 48px;
}
</style>
