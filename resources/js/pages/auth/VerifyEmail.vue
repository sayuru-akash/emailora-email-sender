<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Email verification',
        description:
            'Email verification is not required for this Emailora workspace.',
    },
});
</script>

<template>
    <Head title="Email verification" />
    <div class="space-y-4 text-center">
        <p class="text-sm text-muted-foreground">
            Continue to the operator dashboard.
        </p>
        <Link
            class="text-sm font-medium text-primary underline underline-offset-4"
            href="/dashboard"
            >Dashboard</Link
        >
    </div>
</template>
