<template>
    <v-app-bar color="surface" elevation="0">
        <div class="d-flex align-center gc-4 w-100 px-4">
            <v-menu location="bottom start">
                <template #activator="{ props }">
                    <v-btn
                        v-bind="props"
                        icon="mdi-menu"
                        variant="text"
                        size="small"
                        aria-label="Open navigation menu"
                        class="hidden-lg-and-up me-2"
                    />
                </template>

                <v-list class="py-0">
                    <v-list-item
                        v-for="item in navItems"
                        :key="item.to"
                        :to="item.to"
                        :prepend-icon="item.icon"
                        :title="item.title"
                        :active="isActive(item)"
                        class="text-body-medium font-weight-medium"
                    />
                </v-list>
            </v-menu>

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

            <nav class="d-flex align-center gc-3 hidden-md-and-down" aria-label="Primary navigation">
                <v-btn
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    :active="isActive(item)"
                    :variant="isActive(item) ? 'flat' : 'text'"
                    :color="isActive(item) ? 'primary' : undefined"
                    :prepend-icon="item.icon"
                    rounded="lg"
                    ripple="false"
                    class="text-none text-body-medium font-weight-medium px-3"
                >
                    {{ item.title }}
                </v-btn>
            </nav>

            <v-spacer />

            <v-chip
                variant="flat"
                class="bg-pale-teal-lighten-3 text-pale-teal-darken-4 text-body-medium font-weight-medium hidden-sm-and-down border-sm border-pale-teal border-opacity-100"
            >
                <div class="d-flex align-center gc-1">
                    <span class="rounded-circle bg-pale-teal-darken-1" style="width: 6px; height: 6px" />
                    <span>1 USD = {{ settingsStore.fxUsdToMxn.toFixed(2) }} MXN</span>
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
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import { useSettingsStore } from '../store/settings';
import ThemeToggle from './ThemeToggle.vue';

const auth = useAuthStore();
const settingsStore = useSettingsStore();
const router = useRouter();
const route = useRoute();

onMounted(settingsStore.load);

const navItems = [
    { title: 'Dashboard', icon: 'mdi-view-dashboard', to: '/', exact: true },
    { title: 'Products', icon: 'mdi-package-variant', to: '/products', exact: false },
    { title: 'Orders', icon: 'mdi-cart', to: '/orders', exact: false },
    { title: 'Stock', icon: 'mdi-warehouse', to: '/stock' },
    { title: 'Pricing', icon: 'mdi-calculator', to: '/pricing' },
    { title: 'Settings', icon: 'mdi-cog', to: '/settings' },
];

function isActive(item) {
    if (item.exact) {
        return route.path === item.to;
    }

    return route.path === item.to || route.path.startsWith(`${item.to}/`);
}

async function logout() {
    await auth.logout();

    router.push({ name: 'login' });
}
</script>

<style scoped>
nav .v-btn {
    transition: none;
}
</style>

