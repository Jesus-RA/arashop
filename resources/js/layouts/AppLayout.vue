<template>
    <v-app-bar color="surface" elevation="0" class="">
        <div class="d-flex align-center w-100 px-4">
            <v-btn
                :icon="drawer ? 'mdi-backburger' : 'mdi-menu'"
                variant="text"
                size="small"
                aria-label="Toggle sidebar"
                class="me-2"
                @click="drawer = !drawer"
            />

            <router-link to="/" class="d-flex align-center ga-3 text-decoration-none">
                <svg
                    class="flex-shrink-0"
                    width="32"
                    height="32"
                    viewBox="0 0 120 120"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <defs>
                        <linearGradient id="stitch-rose" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#FBAFAF" />
                            <stop offset="100%" stop-color="#E27D8C" />
                        </linearGradient>
                        <linearGradient id="stitch-mint" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#99E1E5" />
                            <stop offset="100%" stop-color="#4DB6AC" />
                        </linearGradient>
                    </defs>
                    <rect width="120" height="120" rx="28" fill="url(#stitch-rose)" />
                    <path d="M34 86L60 32L86 86H70L60 62L50 86H34Z" fill="#FFFFFF" />
                    <path d="M48 68H72" stroke="#F3E8CB" stroke-width="5" stroke-linecap="round" />
                    <circle cx="88" cy="34" r="12" fill="url(#stitch-mint)" />
                    <path d="M83 34L87 38L93 30" stroke="#004D40" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="d-flex flex-column">
                    <span class="text-body-large font-weight-bold lh-1">Arashop</span>
                    <span class="text-body-small text-medium-emphasis lh-1 mt-1">US → MX Arbitrage</span>
                </span>
            </router-link>

            <v-spacer />

            <v-chip
                variant="flat"
                class="bg-pale-teal-lighten-3 text-pale-teal-darken-4 text-body-medium font-weight-medium hidden-sm-and-down border-sm border-pale-teal border-opacity-100"
            >
                <div class="d-flex align-center gc-1">
                    <span class="fx-dot bg-pale-teal-darken-1"></span>
                    <span>1 USD = {{ fxRate.toFixed(2) }} MXN</span>
                </div>
            </v-chip>

            <ThemeToggle />

            <v-menu location="bottom end">
                <template #activator="{ props }">
                    <v-btn v-bind="props" variant="text" class="ms-1">
                        <v-avatar size="32" color="primary" class="me-2">
                            <v-icon icon="mdi-account" size="18" />
                        </v-avatar>
                        <span class="hidden-sm-and-down">{{ auth.user?.name }}</span>
                    </v-btn>
                </template>

                <v-list>
                    <v-list-item prepend-icon="mdi-logout" title="Log out" @click="logout" />
                </v-list>
            </v-menu>
        </div>
    </v-app-bar>

    <AppSidebar v-model="drawer" :fx-rate="fxRate" />

    <v-main class="stitch-canvas">
        <v-container fluid class="pa-6">
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
import { useRouter } from 'vue-router';
import { onMounted, ref } from 'vue';
import { useAuthStore } from '../store/auth';
import { useUiStore } from '../store/ui';
import AppSidebar from '../components/AppSidebar.vue';
import ThemeToggle from '../components/ThemeToggle.vue';
import http from '../api/http';

const auth = useAuthStore();
const uiStore = useUiStore();
const router = useRouter();
const drawer = ref(true);
const fxRate = ref(18.0);

async function logout() {
    await auth.logout();

    router.push({ name: 'login' });
}

onMounted(async () => {
    if (!auth.checked) {
        try {
            await auth.loadCurrentUser();
        } catch {
            auth.clearSession();
        }
    }

    try {
        const settings = await http.get('/api/settings');
        fxRate.value = Number(settings['fx.usd_to_mxn']) || 18.0;
    } catch {
        // keep the fallback rate when settings are unavailable
    }
});
</script>
