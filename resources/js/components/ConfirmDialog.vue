<template>
    <v-dialog v-model="model" max-width="420" :persistent="persistent">
        <v-card>
            <v-card-text class="d-flex flex-column gr-2 text-center">
                <div class="d-flex align-center justify-center mx-auto rounded-circle delete-icon" :class="resolvedIconBg">
                    <slot name="icon">
                        <v-icon :icon="icon" :color="iconColor" size="28" />
                    </slot>
                </div>
                <slot name="title">
                    <h3 class="text-body-large font-weight-bold my-0">{{ title }}</h3>
                </slot>
                <slot name="text">
                    <p class="text-body-medium text-medium-emphasis my-0">{{ text }}</p>
                </slot>
            </v-card-text>
            <v-card-actions class="d-flex justify-space-between px-3">
                <slot name="actions">
                    <v-btn class="flex-grow-1 flex-shrink-0" variant="elevated" :color="cancelButtonColor" @click="close">
                        {{ cancelButtonText }}
                    </v-btn>
                    <v-btn
                        class="flex-grow-1 flex-shrink-0"
                        variant="elevated"
                        :color="confirmButtonColor"
                        :loading="loading"
                        :disabled="loading"
                        @click="onConfirm"
                    >
                        {{ confirmButtonText }}
                    </v-btn>
                </slot>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { computed } from 'vue';
import { iconBg } from '../utils/iconBg';

const props = defineProps({
    title: { type: String, default: '' },
    text: { type: String, default: '' },
    cancelButtonText: { type: String, default: 'Cancel' },
    confirmButtonText: { type: String, default: 'Confirm' },
    cancelButtonColor: { type: String, default: '' },
    confirmButtonColor: { type: String, default: 'primary' },
    icon: { type: String, default: '' },
    iconColor: { type: String, default: '' },
    iconBgColor: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    persistent: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm']);

const model = defineModel({ type: Boolean, default: false });

const resolvedIconBg = computed(() => props.iconBgColor || iconBg(props.iconColor));

function close() {
    model.value = false;
}

function onConfirm() {
    emit('confirm');
}
</script>

<style scoped>
.delete-icon {
    width: 48px;
    height: 48px;
}
</style>