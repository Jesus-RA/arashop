<template>
    <v-navigation-drawer
        v-model="drawer"
        app
        color="surface-container-lowest"
        width="256"
    >
        <aside class="d-flex flex-column h-100 py-3" aria-label="Primary navigation">
            <div class="stitch-label px-3 ms-1 mb-1">Workspace</div>
            <nav class="pa-2">
                <router-link
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="nav-link d-flex align-center text-decoration-none text-body-medium font-weight-medium pa-3 gc-3 rounded-lg"
                    :class="{ 'nav-link--active': isActive(item) }"
                    @click="onNavClick"
                >
                    <v-icon :icon="item.icon" size="20" />
                    <span>{{ item.title }}</span>
                </router-link>
            </nav>

            <v-spacer />

            <div class="exchange-stream ma-3 pa-4 border-sm border-warm rounded-lg">
                <div class="d-flex align-center justify-space-between">
                    <span class="text-label-medium text-uppercase text-medium-emphasis mb-0">Exchange Stream</span>
                    <span class="fx-dot rounded-circle"></span>
                </div>
                <div class="exchange-stream__value mt-1">
                    {{ fxRate.toFixed(2) }}
                    <small>MXN</small>
                </div>
                <div class="text-body-small text-medium-emphasis mt-1">Updated just now</div>
            </div>
        </aside>
    </v-navigation-drawer>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useDisplay } from 'vuetify';

const drawer = defineModel({ type: Boolean, required: true });

defineProps({
    fxRate: { type: Number, required: true },
});

const route = useRoute();
const { mdAndDown } = useDisplay();

const navItems = [
    { title: 'Dashboard', icon: 'mdi-view-dashboard', to: '/', exact: true },
    { title: 'Products', icon: 'mdi-package-variant', to: '/products', exact: false },
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

function onNavClick() {
    // Close the drawer on small screens so the target page is not hidden behind it.
    if (mdAndDown.value) {
        drawer.value = false;
    }
}
</script>


<style scoped>
.v-navigation-drawer--left {
    border-right: none;
}
</style>
