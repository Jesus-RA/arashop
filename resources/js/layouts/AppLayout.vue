<template>
    <v-app-bar app color="primary" dark>
        <v-app-bar-title>
            <v-icon icon="mdi-shopping" class="me-2" />
            Arashop
        </v-app-bar-title>

        <v-spacer />

        <ThemeToggle />

        <v-menu location="bottom end">
            <template #activator="{ props }">
                <v-btn v-bind="props" variant="text">
                    <v-icon icon="mdi-account" class="me-2" />
                    {{ auth.user?.name }}
                </v-btn>
            </template>

            <v-list>
                <v-list-item prepend-icon="mdi-logout" title="Log out" @click="logout" />
            </v-list>
        </v-menu>
    </v-app-bar>

    <v-navigation-drawer app permanent>
        <v-list density="comfortable" nav>
            <v-list-item
                v-for="item in navItems"
                :key="item.to"
                :prepend-icon="item.icon"
                :title="item.title"
                :to="item.to"
                :exact="item.exact"
                color="primary"
            />
        </v-list>
    </v-navigation-drawer>

    <v-main>
        <v-container fluid class="pa-6">
            <router-view />
        </v-container>
    </v-main>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import ThemeToggle from '../components/ThemeToggle.vue';

const auth = useAuthStore();
const router = useRouter();

const navItems = [
    { title: 'Dashboard', icon: 'mdi-view-dashboard', to: '/', exact: true },
    { title: 'Products', icon: 'mdi-package-variant', to: '/products' },
    { title: 'Stock', icon: 'mdi-warehouse', to: '/stock' },
    { title: 'Pricing', icon: 'mdi-calculator', to: '/pricing' },
    { title: 'Settings', icon: 'mdi-cog', to: '/settings' },
];

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
});
</script>

const l = new Set([
    '0G6A4575',
    '0G6A4581',
    '0G6A4625',
    '0G6A4638',
    '0G6A4562',
    '0G6A4624',
    '0G6A4563',
    '0G6A4643',
    '0G6A4527',
    '0G6A4524',
    '0G6A4584',
    '0G6A4509',
    '⁠0G6A4475',
    '⁠⁠0G6A4498',
    '⁠⁠0G6A4616',
    '⁠⁠0G6A4629',
])

