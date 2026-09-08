<template>
    <v-btn
        variant="text"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleTheme"
    >
        <v-icon :icon="isDark ? 'mdi-white-balance-sunny' : 'mdi-weather-night'" />
    </v-btn>
</template>

<script setup>
import { computed } from 'vue';
import { useTheme } from 'vuetify';

const theme = useTheme();

const isDark = computed(() => theme.global.name.value === 'dark');

function toggleTheme() {
    const next = isDark.value ? 'light' : 'dark';

    theme.change(next);
    localStorage.setItem('arashop.theme', next);
}
</script>