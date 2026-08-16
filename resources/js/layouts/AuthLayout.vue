<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <Head>
        <meta
            head-key="robots"
            name="robots"
            content="noindex, nofollow, noarchive"
        />
    </Head>

    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>
