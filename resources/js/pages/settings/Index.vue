<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ settings?: any; providerStatus?: any }>();
const form = useForm({
    company_name: props.settings?.company_name ?? 'Emailora',
    timezone: props.settings?.timezone ?? 'Asia/Colombo',
    default_from_name: props.settings?.default_from_name ?? 'Emailora',
    default_from_email: props.settings?.default_from_email ?? '',
    default_reply_to: props.settings?.default_reply_to ?? '',
    default_provider: props.settings?.default_provider ?? 'resend',
    fallback_provider: props.settings?.fallback_provider ?? '',
    rate_limit_per_minute: props.settings?.rate_limit_per_minute ?? 300,
    chunk_size: props.settings?.chunk_size ?? 50,
});
</script>
<template><Head title="Settings" /><main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8"><PageHeader title="Settings" subtitle="Company, provider, domains, and defaults" /><form class="space-y-4 rounded-lg border bg-white p-5" @submit.prevent="form.put('/settings')"><div class="grid gap-4 md:grid-cols-2"><input v-model="form.company_name" class="h-10 rounded-md border px-3" placeholder="Company name" /><input v-model="form.timezone" class="h-10 rounded-md border px-3" placeholder="Timezone" /><input v-model="form.default_from_name" class="h-10 rounded-md border px-3" placeholder="From name" /><input v-model="form.default_from_email" class="h-10 rounded-md border px-3" placeholder="From email" /><input v-model="form.default_reply_to" class="h-10 rounded-md border px-3" placeholder="Reply-to" /><select v-model="form.default_provider" class="h-10 rounded-md border px-3"><option>resend</option><option>brevo</option><option>auto</option></select></div><div class="rounded-md bg-muted p-3 text-sm">Resend: {{ props.providerStatus?.resend?.configured ? 'configured' : 'missing key' }} · Brevo: {{ props.providerStatus?.brevo?.configured ? 'configured' : 'missing key' }}</div><button class="rounded-md bg-primary px-3 py-2 text-sm text-white">Save</button></form></main></template>
