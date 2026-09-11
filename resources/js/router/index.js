import { createRouter, createWebHistory } from 'vue-router';
import { pinia } from '../store';
import { useAuthStore } from '../store/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../pages/DashboardPage.vue'),
            },
            {
                path: 'products',
                name: 'products.index',
                component: () => import('../pages/Products/ProductListPage.vue'),
            },
            {
                path: 'products/new',
                name: 'products.create',
                component: () => import('../pages/Products/ProductFormPage.vue'),
            },
            {
                path: 'products/:id',
                name: 'products.show',
                component: () => import('../pages/Products/ProductDetailPage.vue'),
            },
            {
                path: 'products/:id/edit',
                name: 'products.edit',
                component: () => import('../pages/Products/ProductFormPage.vue'),
            },
            {
                path: 'stock',
                name: 'stock.index',
                component: () => import('../pages/Stock/StockPage.vue'),
            },
            {
                path: 'orders',
                name: 'orders.index',
                component: () => import('../pages/Orders/OrdersListPage.vue'),
            },
            {
                path: 'orders/new',
                name: 'orders.create',
                component: () => import('../pages/Orders/OrderCreatePage.vue'),
            },
            {
                path: 'orders/:id',
                name: 'orders.show',
                component: () => import('../pages/Orders/OrderDetailPage.vue'),
            },
            {
                path: 'pricing',
                name: 'pricing.index',
                component: () => import('../pages/Pricing/PricingPage.vue'),
            },
            {
                path: 'settings',
                name: 'settings.index',
                component: () => import('../pages/Settings/SettingsPage.vue'),
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore(pinia);

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        if (!auth.checked) {
            try {
                await auth.loadCurrentUser();
            } catch {
                auth.clearSession();
            }
        }

        if (!auth.isAuthenticated) {
            return { name: 'login', query: { redirect: to.fullPath } };
        }
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
});

export default router;