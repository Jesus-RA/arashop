<template>
    <v-dialog v-model="show" max-width="520">
        <v-card class="pa-2">
            <v-card-title class="text-h6 pb-2">{{ isEditing ? 'Edit variant' : 'Add variant' }}</v-card-title>

            <v-card-text class="py-2">
                <v-alert
                    v-if="saveError"
                    type="error"
                    variant="tonal"
                    class="mb-4"
                >
                    <ul class="mb-0 ps-4">
                        <li v-for="(message, i) in saveError" :key="i">{{ message }}</li>
                    </ul>
                </v-alert>

                <v-form ref="formRef" class="d-flex flex-column gr-3">
                    <v-text-field
                        v-model.trim="draft.name"
                        label="Variant name"
                        variant="outlined"
                        class="mb-2 required"
                        :rules="[required('Variant name is required')]"
                    />

                    <v-row>
                        <v-col cols="6">
                            <v-text-field
                                v-model.number="draft.cost_usd"
                                label="Cost USD"
                                type="number"
                                variant="outlined"
                                :rules="[numeric('Must be a number'), min(0, 'Must be 0 or more')]"
                            />
                        </v-col>
                        <v-col cols="6">
                            <v-text-field
                                v-model.number="draft.price_mxn"
                                label="Price MXN"
                                type="number"
                                variant="outlined"
                                :rules="[numeric('Must be a number'), min(0, 'Must be 0 or more')]"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                v-model.number="draft.stock_quantity"
                                :label="isEditing ? 'Stock quantity' : 'Initial stock'"
                                type="number"
                                variant="outlined"
                                :rules="[numeric('Must be a number'), min(0, 'Must be 0 or more')]"
                            />
                        </v-col>
                    </v-row>

                    <div class="mb-2">
                        <div class="text-body-small text-medium-emphasis mb-1">Attributes</div>
                        <div v-for="(attribute, index) in draft.attributes" :key="index" class="d-flex ga-2 mb-1">
                            <v-text-field
                                v-model.trim="attribute.key"
                                label="Attribute"
                                variant="outlined"
                                hide-details="auto"
                                style="max-width: 170px"
                            />
                            <v-text-field
                                v-model.trim="attribute.value"
                                label="Value"
                                variant="outlined"
                                hide-details="auto"
                                style="max-width: 170px"
                                @keydown.enter.prevent
                            />
                            <v-btn
                                icon="mdi-close"
                                size="small"
                                variant="text"
                                @click="draft.attributes.splice(index, 1)"
                            />
                        </div>
                        <v-btn
                            size="small"
                            variant="text"
                            prepend-icon="mdi-plus"
                            class="pl-0"
                            @click="draft.attributes.push({ key: '', value: '' })"
                        >
                            Add attribute
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>

            <v-card-actions class="justify-end">
                <v-btn variant="tonal" @click="show = false">Cancel</v-btn>
                <v-btn
                    color="primary"
                    :loading="saving"
                    :disabled="saving"
                    @click="save"
                >
                    Save variant
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import http from '../api/http';
import { min, numeric, required } from '../utils/validation';

const props = defineProps({
    productId: { type: Number, required: true },
    variant: { type: Object, default: null },
});

const emit = defineEmits(['saved']);
const show = defineModel({ type: Boolean, default: false });

const formRef = ref(null);
const saving = ref(false);
const saveError = ref(null);

const draft = reactive({
    id: null,
    name: '',
    attributes: [],
    cost_usd: null,
    price_mxn: null,
    stock_quantity: 0,
});

const isEditing = computed(() => props.variant !== null);

watch(show, (open) => {
    if (open) {
        const source = props.variant;

        draft.id = source?.id ?? null;
        draft.name = source?.name ?? '';
        draft.attributes = (source?.attributes ?? []).map((attribute) => ({
            key: attribute.key,
            value: attribute.value,
        }));
        draft.cost_usd = source?.cost_usd ?? null;
        draft.price_mxn = source?.price_mxn ?? null;
        draft.stock_quantity = source?.stock_quantity ?? 0;
        saveError.value = null;
    }
});

function normalizeMoney(value) {
    return value === '' ? null : value;
}

function attributesToObject(attributes) {
    return attributes
        .filter((attribute) => attribute.key.trim() !== '')
        .reduce((acc, attribute) => {
            acc[attribute.key.trim()] = attribute.value;

            return acc;
        }, {});
}

async function save() {
    const { valid } = await formRef.value.validate();

    if (!valid) {
        return;
    }

    saving.value = true;
    saveError.value = null;

    const payload = {
        name: draft.name,
        attributes: attributesToObject(draft.attributes),
        cost_usd: normalizeMoney(draft.cost_usd),
        price_mxn: normalizeMoney(draft.price_mxn),
        stock_quantity: draft.stock_quantity,
    };

    try {
        const { data } = isEditing.value
            ? await http.put(`/api/products/${props.productId}/variants/${draft.id}`, payload)
            : await http.post(`/api/products/${props.productId}/variants`, payload);

        emit('saved', data);
        show.value = false;
    } catch (error) {
        saveError.value = Object.values(error.data?.errors ?? {}).flat() ?? ['Could not save the variant.'];
    } finally {
        saving.value = false;
    }
}
</script>
