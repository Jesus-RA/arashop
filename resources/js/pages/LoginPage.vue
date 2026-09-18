<template>
    <v-main class="d-flex align-center justify-center fill-height">
        <v-card width="420" elevation="8" class="pa-6">
            <v-card-title class="text-center text-headline-small font-weight-bold pb-4">
                <v-icon icon="mdi-shopping" class="me-2" />
                Arashop
            </v-card-title>

            <v-form ref="formRef" @submit.prevent="submit" class="d-flex flex-column gr-3">
                <v-text-field
                    v-model="form.email"
                    label="Email"
                    type="email"
                    autocomplete="email"
                    :rules="[required('Email is required'), email('Enter a valid email address')]"
                    prepend-inner-icon="mdi-email"
                />

                <v-text-field
                    v-model="form.password"
                    label="Password"
                    type="password"
                    autocomplete="current-password"
                    :rules="[required('Password is required')]"
                    prepend-inner-icon="mdi-lock"
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
import { email, required } from '../utils/validation';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = reactive({ email: '', password: '' });

const formRef = ref(null);
const loading = ref(false);
const alertMessage = ref('');

async function submit() {
    const { valid } = await formRef.value.validate();

    if (!valid) {
        return;
    }

    loading.value = true;
    alertMessage.value = '';

    try {
        await auth.login(form.email, form.password);

        router.push(route.query.redirect ?? '/');
    } catch (error) {
        alertMessage.value = error.data?.message
            ?? (error.status ? 'Log in failed.' : 'Unable to reach the server. Please try again.');
    } finally {
        loading.value = false;
    }
}
</script>
