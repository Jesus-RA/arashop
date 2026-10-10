<template>
    <v-card class="d-flex align-center justify-space-between px-3 py-4">
        <div class="d-flex flex-column justify-space-between">
            <span class="text-body-small text-medium-emphasis">{{ title }}</span>
            <div class="text-headline-medium font-weight-semibold mt-2">
                <slot />
            </div>
        </div>
        <div class="d-flex align-center rounded-lg pa-4" :class="resolvedIconBg">
            <v-icon :icon="icon" :color="iconColor" size="18" />
        </div>
    </v-card>
</template>

<script setup>
const iconBgMap = {
    primary: 'bg-secondary-lighten-2',
    'success': 'bg-green-lighten-5',
    'info': 'bg-info-lighten-5',
    'warning': 'bg-amber-lighten-5',
};

const props = defineProps({
    title: { type: String, required: true },
    icon: { type: String, required: true },
    iconColor: { type: String, required: true },
    iconBg: { type: String, default: '' },
});

const resolvedIconBg = props.iconBg || iconBgMap[props.iconColor] || '';
</script>
