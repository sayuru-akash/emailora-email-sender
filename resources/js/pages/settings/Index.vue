<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
const testForm = useForm({
    to: '',
    provider: props.settings?.default_provider ?? 'auto',
});
const testResult = ref('');

function submitSettings() {
    form.transform((data) => ({
        ...data,
        fallback_provider: data.fallback_provider || null,
    })).put('/settings');
}

function sendTestEmail() {
    testResult.value = '';
    testForm.post('/settings/test-email', {
        preserveScroll: true,
        onSuccess: () => {
            testResult.value = 'Test request submitted.';
        },
        onError: () => {
            testResult.value = 'Check the test email fields and provider configuration.';
        },
    });
}
</script>
<template>
    <Head title="Settings" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Settings"
            subtitle="Company, provider, domains, and defaults"
        />
        <form
            class="space-y-4 rounded-lg border bg-card p-5"
            @submit.prevent="submitSettings"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">Company name</label>
                    <input
                        v-model="form.company_name"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.company_name" />
                </div>
                <div>
                    <label class="text-sm font-medium">Timezone</label>
                    <input
                        v-model="form.timezone"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.timezone" />
                </div>
                <div>
                    <label class="text-sm font-medium">From name</label>
                    <input
                        v-model="form.default_from_name"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.default_from_name" />
                </div>
                <div>
                    <label class="text-sm font-medium">From email</label>
                    <input
                        v-model="form.default_from_email"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.default_from_email" />
                </div>
                <div>
                    <label class="text-sm font-medium">Reply-to</label>
                    <input
                        v-model="form.default_reply_to"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.default_reply_to" />
                </div>
                <div>
                    <label class="text-sm font-medium">Default provider</label>
                    <select
                        v-model="form.default_provider"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    >
                        <option>resend</option>
                        <option>brevo</option>
                        <option>auto</option>
                    </select>
                    <InputError :message="form.errors.default_provider" />
                </div>
                <div>
                    <label class="text-sm font-medium">Fallback provider</label>
                    <select
                        v-model="form.fallback_provider"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    >
                        <option value="">None</option>
                        <option>resend</option>
                        <option>brevo</option>
                    </select>
                    <InputError :message="form.errors.fallback_provider" />
                </div>
                <div>
                    <label class="text-sm font-medium">Rate limit/minute</label>
                    <input
                        v-model="form.rate_limit_per_minute"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                        min="1"
                        max="100000"
                        type="number"
                    />
                    <InputError :message="form.errors.rate_limit_per_minute" />
                </div>
                <div>
                    <label class="text-sm font-medium">Chunk size</label>
                    <input
                        v-model="form.chunk_size"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                        min="1"
                        max="1000"
                        type="number"
                    />
                    <InputError :message="form.errors.chunk_size" />
                </div>
            </div>
            <div class="rounded-md bg-muted p-3 text-sm">
                Resend:
                {{
                    props.providerStatus?.resend?.configured
                        ? 'configured'
                        : 'missing key'
                }}
                · Brevo:
                {{
                    props.providerStatus?.brevo?.configured
                        ? 'configured'
                        : 'missing key'
                }}
            </div>
            <button
                class="rounded-md bg-primary px-3 py-2 text-sm text-white disabled:opacity-60"
                :disabled="form.processing"
            >
                Save
            </button>
        </form>

        <form
            class="mt-6 space-y-4 rounded-lg border bg-card p-5"
            @submit.prevent="sendTestEmail"
        >
            <div>
                <h2 class="font-medium">Test email provider</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">Recipient email</label>
                    <input
                        v-model="testForm.to"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="testForm.errors.to" />
                </div>
                <div>
                    <label class="text-sm font-medium">Provider</label>
                    <select
                        v-model="testForm.provider"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    >
                        <option>auto</option>
                        <option>resend</option>
                        <option>brevo</option>
                    </select>
                    <InputError :message="testForm.errors.provider" />
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button
                    class="rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                    :disabled="testForm.processing"
                >
                    {{ testForm.processing ? 'Sending...' : 'Send test' }}
                </button>
                <span
                    v-if="testResult"
                    class="text-sm text-muted-foreground"
                    >{{ testResult }}</span
                >
            </div>
        </form>
    </main>
</template>
