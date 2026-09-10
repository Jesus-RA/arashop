<template>
    <v-main class="d-flex align-center justify-center fill-height">
        <v-card width="420" elevation="8" class="pa-6">
            <v-card-title class="text-center text-headline-small font-weight-bold pb-4">
                <v-icon icon="mdi-shopping" class="me-2" />
                Arashop
            </v-card-title>

            <v-form @submit.prevent="submit">
                <v-text-field
                    v-model="form.email"
                    label="Email"
                    type="email"
                    autocomplete="email"
                    :error-messages="fieldErrors.email"
                    prepend-inner-icon="mdi-email"
                    required
                />

                <v-text-field
                    v-model="form.password"
                    label="Password"
                    type="password"
                    autocomplete="current-password"
                    :error-messages="fieldErrors.password"
                    prepend-inner-icon="mdi-lock"
                    required
                />

                <v-alert v-if="alertMessage" type="error" variant="tonal" class="mb-4">
                    {{ alertMessage }}
                </v-alert>

                <v-btn
                    type="submit"
                    color="primary"
                    block
                    size="large"
                    :loading="loading"
                    :disabled="loading"
                >
                    Log in
                </v-btn>
            </v-form>
        </v-card>
    </v-main>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = reactive({ email: '', password: '' });

const loading = ref(false);
const alertMessage = ref('');
const fieldErrors = reactive({ email: [], password: [] });

async function submit() {
    loading.value = true;
    alertMessage.value = '';
    fieldErrors.email = [];
    fieldErrors.password = [];

    try {
        await auth.login(form.email, form.password);

        router.push(route.query.redirect ?? '/');
    } catch (error) {
        const errors = error.data?.errors;

        if (errors) {
            fieldErrors.email = errors.email ?? [];
            fieldErrors.password = errors.password ?? [];
            alertMessage.value = error.data.message ?? 'Log in failed.';
        } else {
            alertMessage.value = 'Unable to reach the server. Please try again.';
        }
    } finally {
        loading.value = false;
    }
}
</script>