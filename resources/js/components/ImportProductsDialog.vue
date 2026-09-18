<template>
    <v-dialog v-model="show" max-width="900">
        <v-card class="pa-2">
            <v-card-title class="text-h6 pb-0">Import Products from CSV</v-card-title>

            <v-card-text class="d-flex flex-column gr-3 pt-2 pb-4">
                <p class="text-body-medium text-medium-emphasis">
                    Upload a CSV file in the template format. Each row becomes a variant; rows sharing the
                    same product name are grouped into one product.
                    A preview runs automatically after a valid file is selected — review it before importing.
                </p>
                <v-file-input
                    v-model="file"
                    label="CSV file"
                    accept=".csv,.txt,text/csv"
                    prepend-icon="mdi-file-delimited-outline"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                    :rules="fileRules"
                />
                <v-alert
                    v-if="previewError"
                    type="error"
                    variant="tonal"
                    density="comfortable"
                    class="mt-4 mb-0"
                >
                    <ul class="mb-0 ps-4">
                        <li v-for="(message, i) in previewError" :key="i">{{ message }}</li>
                    </ul>
                </v-alert>
                <div class="mt-4">
                    <div class="d-flex flex-wrap align-center ga-3 mb-2">
                        <h4 class="text-body-medium font-weight-bold my-0">Preview</h4>
                        <span class="text-body-small text-medium-emphasis">
                            {{ previewProductsCount }} product{{ previewProductsCount === 1 ? '' : 's' }}
                            · {{ previewVariantsCount }} variant{{ previewVariantsCount === 1 ? '' : 's' }}
                        </span>
                        <v-chip
                            v-if="filePreview?.skipped"
                            size="small"
                            variant="flat"
                            class="text-body-small font-weight-semibold bg-warning-lighten-5 text-warning"
                        >
                            {{ filePreview.skipped }} row{{ filePreview.skipped === 1 ? '' : 's' }} skipped
                        </v-chip>
                    </div>
                    <div class="border rounded-lg overflow-hidden">
                        <v-table density="compact">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th>Status</th>
                                    <th class="text-right">Cost USD</th>
                                    <th class="text-right">Price MXN</th>
                                    <th class="text-right">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="previewing">
                                    <tr>
                                        <td colspan="6" class="text-center pa-4 text-medium-emphasis">
                                            <v-progress-circular indeterminate size="24" width="2" class="me-2" />
                                            Preparing preview…
                                        </td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <template v-for="product in filePreview?.products ?? []" :key="product.name">
                                        <tr v-for="(variant, i) in product.variants" :key="`${product.name}-${i}`">
                                            <td class="font-weight-medium">{{ product.name }}</td>
                                            <td>{{ variant.name }}</td>
                                            <td>
                                                <v-chip
                                                    size="x-small"
                                                    variant="flat"
                                                    class="text-body-small font-weight-semibold"
                                                    :class="statusClasses(product.status)"
                                                >
                                                    {{ statusLabel(product.status) }}
                                                </v-chip>
                                            </td>
                                            <td class="text-right tabular-nums">{{ formatUsd(variant.cost_usd) }}</td>
                                            <td class="text-right tabular-nums">{{ formatMxn(variant.price_mxn) }}</td>
                                            <td class="text-right">{{ variant.stock_quantity }}</td>
                                        </tr>
                                    </template>
                                    <tr v-if="!filePreview?.products?.length">
                                        <td colspan="6" class="text-center pa-4 text-body-medium text-medium-emphasis">
                                            Select a CSV file to preview its rows.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </v-table>
                    </div>
                    <v-alert
                        v-if="filePreview?.errors?.length"
                        type="warning"
                        variant="tonal"
                        density="comfortable"
                        class="mt-4 mb-0 text-body-medium"
                    >
                        <div class=" font-weight-semibold mb-1">
                            Skipped rows — review and correct these in your CSV before importing:
                        </div>
                        <ul class="mb-0 ps-4">
                            <li v-for="(message, i) in filePreview.errors" :key="i">{{ message }}</li>
                        </ul>
                    </v-alert>
                </div>
                <v-alert
                    v-if="importResult"
                    type="success"
                    variant="tonal"
                    density="comfortable"
                    class="mt-4 mb-0"
                >
                    {{ importResult.imported }} product{{ importResult.imported === 1 ? '' : 's' }} imported
                    ({{ importResult.variants }} variant{{ importResult.variants === 1 ? '' : 's' }}).
                    <span v-if="importResult.skipped" class="font-weight-medium">
                        {{ importResult.skipped }} row{{ importResult.skipped === 1 ? '' : 's' }} skipped.
                    </span>
                </v-alert>
                <v-alert v-if="importResult?.errors?.length" type="warning" variant="tonal" density="comfortable" class="mt-4 mb-0">
                    <ul class="mb-0 ps-4">
                        <li v-for="(message, i) in importResult.errors" :key="i">{{ message }}</li>
                    </ul>
                </v-alert>
            </v-card-text>

            <v-card-actions class="justify-space-between">
                <v-btn prepend-icon="mdi-download" @click="downloadTemplate">Download Template</v-btn>
                <div class="d-flex align-center ga-2">
                    <v-btn @click="close">Cancel</v-btn>

                    <v-btn
                        color="primary"
                        :loading="importing"
                        :disabled="!filePreview || importing"
                        @click="importProducts"
                    >
                        Import
                    </v-btn>
                </div>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import http from '../api/http';
import { formatMxn, formatUsd } from '../utils/format';
import { statusClasses, statusLabel } from '../utils/productStatus';

const emit = defineEmits(['imported']);
const show = defineModel({ type: Boolean, default: false });

const file = ref(null);
const importing = ref(false);
const importResult = ref(null);
const previewing = ref(false);
const filePreview = ref(null);
const previewError = ref(null);

const fileRules = [
    (value) => !value || /\.(csv|txt)$/i.test(value.name) || 'Choose a .csv or .txt file',
    (value) => !value || value.size <= 2 * 1024 * 1024 || 'File must be 2 MB or smaller',
];

const TEMPLATE_HEADERS = ['name', 'description', 'status', 'variant name', 'attributes', 'cost usd', 'price mxn', 'stock quantity'];

const previewProductsCount = computed(() => filePreview.value?.products?.length ?? 0);
const previewVariantsCount = computed(() =>
    (filePreview.value?.products ?? []).reduce((sum, product) => sum + product.variants.length, 0),
);

watch(file, () => {
    filePreview.value = null;
    previewError.value = null;

    if (file.value) {
        previewImport();
    }
});

watch(show, (open) => {
    if (open) {
        reset();
    }
});

function reset() {
    file.value = null;
    importResult.value = null;
    previewing.value = false;
    filePreview.value = null;
    previewError.value = null;
    importing.value = false;
}

function close() {
    show.value = false;
    file.value = null;
}

function buildFormData() {
    const formData = new FormData();
    formData.append('file', file.value);

    return formData;
}

function downloadTemplate() {
    const exampleRow = [
        'Ergonomic Wireless Mouse',
        '2.4G wireless mouse with silent clicks',
        'active',
        'Ergonomic Wireless Mouse - Black',
        'Color:Black;Size:Standard',
        '12.50',
        '299.00',
        '25',
    ];

    const csv = `\uFEFF${[TEMPLATE_HEADERS.join(','), exampleRow.join(',')].join('\n')}`;
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');

    link.href = url;
    link.download = 'arashop-product-import-template.csv';
    link.click();
    URL.revokeObjectURL(url);
}

async function previewImport() {
    if (!file.value) {
        return;
    }

    previewing.value = true;
    filePreview.value = null;
    previewError.value = null;

    try {
        filePreview.value = await http.post('/api/products/import/preview', buildFormData());
    } catch (error) {
        const messages = error.data?.errors?.file ?? ['The file could not be previewed.'];

        previewError.value = Array.isArray(messages) ? messages : [messages];
    } finally {
        previewing.value = false;
    }
}

async function importProducts() {
    if (!file.value || !filePreview.value || importing.value) {
        return;
    }

    importing.value = true;
    importResult.value = null;

    try {
        importResult.value = await http.post('/api/products/import', buildFormData());
        filePreview.value = null;
        emit('imported', {
            imported: importResult.value.imported,
            variants: importResult.value.variants,
            skipped: importResult.value.skipped,
        });
        close();
    } catch (error) {
        importResult.value = null;
        emit('failed', error.data?.errors?.file?.[0] ?? 'The import could not be completed.');
    } finally {
        importing.value = false;
    }
}
</script>
