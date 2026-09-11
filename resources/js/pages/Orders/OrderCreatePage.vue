<template>
    <div>
        <div class="d-flex align-center ga-3 mb-4">
            <v-btn icon="mdi-arrow-left" variant="outlined" width="42" height="42" :to="{ name: 'orders.index' }" />
            <div class="flex-1-1-auto">
                <div class="stitch-label mb-1">Sales Orders</div>
                <h1 class="text-headline-small font-weight-bold mb-0">New order</h1>
            </div>
            <v-spacer />
            <v-chip size="small" variant="flat" class="stitch-pill bg-surface-cream-subtle text-status-warning">
                <span class="fx-dot me-1" style="background: rgb(var(--v-theme-status-warning))" />
                Pending
            </v-chip>
        </div>

        <v-row>
            <v-col cols="12" lg="8">
                <v-card class="rounded-lg mb-4 pa-4">
                    <h2 class="text-body-large font-weight-bold mb-1">Add items</h2>
                    <p class="text-body-small text-medium-emphasis mb-4">
                        Search the live catalog. Prices are snapshotted into the order at checkout.
                    </p>

                    <v-autocomplete
                        v-model="pickerValue"
                        :items="pickerItems"
                        item-title="display"
                        item-value="id"
                        label="Search variants…"
                        prepend-inner-icon="mdi-magnify"
                        :loading="pickerLoading"
                        :disabled="saving"
                        clearable
                        hide-details
                        density="compact"
                        class="mb-4"
                        @update:model-value="onPick"
                    />

                    <div v-if="lines.length" class="lines">
                        <div
                            v-for="line in lines"
                            :key="line.id"
                            class="line pa-3 mb-2 border-sm border-warm rounded-lg"
                        >
                            <div class="d-flex align-center justify-space-between ga-3">
                                <div class="flex-1-1-auto">
                                    <div class="font-weight-medium">{{ line.product_name }} — {{ line.name }}</div>
                                    <div class="d-flex align-center ga-1 mt-1">
                                        <v-chip
                                            v-for="(value, key) in line.attributes ?? {}"
                                            :key="key"
                                            size="x-small"
                                            variant="flat"
                                            class="stitch-pill bg-canvas-tint text-on-surface-variant"
                                        >
                                            {{ key }}: {{ value }}
                                        </v-chip>
                                    </div>
                                    <div class="text-body-small text-medium-emphasis mt-1">
                                        {{ formatMxn(line.price_mxn) }} · {{ line.stock_quantity }} in stock
                                    </div>
                                </div>
                                <div class="d-flex align-center ga-2">
                                    <v-btn icon="mdi-minus" size="x-small" variant="tonal" :disabled="saving" @click="decrement(line)" />
                                    <span class="tabular-nums font-weight-semibold" style="min-width: 32px; text-align: center">
                                        {{ line.quantity }}
                                    </span>
                                    <v-btn icon="mdi-plus" size="x-small" variant="tonal" :disabled="saving || line.quantity >= line.stock_quantity" @click="increment(line)" />
                                    <span class="tabular-nums font-weight-semibold" style="min-width: 90px; text-align: right">
                                        {{ formatMxn(line.subtotal) }}
                                    </span>
                                    <v-btn icon="mdi-close" size="x-small" variant="text" color="error" :disabled="saving" @click="removeLine(line)" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-body-medium text-medium-emphasis mb-0">
                        No items yet — search for a variant above to add it.
                    </p>

                    <v-alert
                        v-if="formErrors.length"
                        type="error"
                        variant="tonal"
                        density="compact"
                        class="mt-4"
                    >
                        <p v-for="(error, i) in formErrors" :key="i" class="mb-0">
                            {{ error }}
                        </p>
                    </v-alert>
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <v-card class="rounded-lg pa-5 mb-4">
                    <div class="stitch-label mb-3">Order Summary</div>
                    <div class="d-flex align-center justify-space-between mb-2">
                        <span class="text-body-small text-medium-emphasis">Line items</span>
                        <span class="text-body-medium">{{ lines.length }}</span>
                    </div>
                    <div class="d-flex align-center justify-space-between mb-2">
                        <span class="text-body-small text-medium-emphasis">Unit count</span>
                        <span class="text-body-medium">{{ unitCount }}</span>
                    </div>
                    <v-divider class="my-3" />
                    <div class="d-flex align-center justify-space-between mb-4">
                        <span class="text-body-large font-weight-bold">Total (MXN)</span>
                        <span class="text-title-large font-weight-bold text-mint-deep tabular-nums">{{ formatMxn(total) }}</span>
                    </div>

                    <v-btn
                        block
                        color="primary"
                        prepend-icon="mdi-check"
                        :loading="saving"
                        :disabled="saving || lines.length === 0"
                        @click="submit"
                    >
                        Confirm order
                    </v-btn>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import http from '../../api/http';
import { useUiStore } from '../../store/ui';
import { formatMxn } from '../../utils/format';

const router = useRouter();
const uiStore = useUiStore();

const lines = ref([]);
const saving = ref(false);
const formErrors = ref([]);

const pickerValue = ref(null);
const pickerItems = ref([]);
const pickerLoading = ref(false);

const unitCount = computed(() => lines.value.reduce((sum, line) => sum + line.quantity, 0));
const total = computed(() => lines.value.reduce((sum, line) => sum + (Number(line.subtotal) || 0), 0));

function itemForLine(line) {
    return {
        id: line.id,
        product_name: line.product_name,
        name: line.name,
        attributes: line.attributes,
        price_mxn: Number(line.price_mxn) || 0,
        stock_quantity: Number(line.stock_quantity) || 0,
        quantity: line.quantity ?? 1,
    };
}

function onPick(variantId) {
    if (!variantId) {
        return;
    }

    const source = pickerItems.value.find((item) => item.id === variantId);

    pickerValue.value = null;

    if (!source) {
        return;
    }

    const existing = lines.value.find((line) => line.id === source.id);

    if (existing) {
        increment(existing);

        return;
    }

    formErrors.value = [];

    const line = itemForLine(source);
    line.quantity = 1;
    line.subtotal = line.price_mxn;
    lines.value.push(line);
}

function increment(line) {
    formErrors.value = [];

    if (line.quantity >= line.stock_quantity) {
        return;
    }

    line.quantity += 1;
    line.subtotal = line.price_mxn * line.quantity;
}

function decrement(line) {
    formErrors.value = [];

    if (line.quantity <= 1) {
        removeLine(line);

        return;
    }

    line.quantity -= 1;
    line.subtotal = line.price_mxn * line.quantity;
}

function removeLine(line) {
    formErrors.value = [];

    lines.value = lines.value.filter((candidate) => candidate.id !== line.id);
}

async function loadVariants() {
    pickerLoading.value = true;

    try {
        const data = await http.get('/api/stock?per_page=100');

        pickerItems.value = data.data
            .filter((variant) => variant.stock_quantity > 0)
            .map((variant) => ({
                ...variant,
                display: `${variant.name} — ${variant.product_name}`,
            }));
    } finally {
        pickerLoading.value = false;
    }
}

async function submit() {
    saving.value = true;
    formErrors.value = [];

    try {
        const response = await http.post('/api/orders', {
            items: lines.value.map((line) => ({
                variant_id: line.id,
                quantity: line.quantity,
            })),
        });

        const { data } = response;

        uiStore.notify(`Order ${data.order_number} placed`);
        router.replace({ name: 'orders.show', params: { id: data.id } });
    } catch (error) {
        const errors = error.data?.errors ?? {};

        formErrors.value = Object.values(errors).flat();

        if (formErrors.value.length === 0) {
            formErrors.value = ['Could not place the order. Check stock and try again.'];
        }
    } finally {
        saving.value = false;
    }
}

onMounted(loadVariants);
</script>

<style scoped>
.flex-1-1-auto {
    flex: 1 1 auto;
}

.line {
    background: rgb(var(--v-theme-surface-card));
}
</style>