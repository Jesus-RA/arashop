<template>
    <Header />

    <v-main class="stitch-canvas">
        <v-container fluid>
            <router-view />
        </v-container>
    </v-main>

    <v-snackbar
        v-model="uiStore.visible"
        :color="uiStore.color"
        :timeout="uiStore.timeout"
        location="top right"
    >
        {{ uiStore.message }}
        <template #actions>
            <v-btn variant="text" @click="uiStore.visible = false">Close</v-btn>
        </template>
    </v-snackbar>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import { useUiStore } from '../store/ui';
import Header from '../components/Header.vue';

const auth = useAuthStore();
const uiStore = useUiStore();

onMounted(async () => {
    if (!auth.checked) {
        try {
            await auth.loadCurrentUser();
        } catch {
            auth.clearSession();
        }
    }
});
</script>
