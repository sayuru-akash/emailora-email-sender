<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{
    campaign?: any;
    defaults?: any;
    templates?: any[];
    lists?: any[];
    tags?: any[];
}>();
const form = useForm({
    name: props.campaign?.name ?? '',
    provider: props.campaign?.provider ?? props.defaults?.provider ?? 'resend',
    from_name:
        props.campaign?.from_name ?? props.defaults?.from_name ?? 'Emailora',
    from_email: props.campaign?.from_email ?? props.defaults?.from_email ?? '',
    reply_to_email:
        props.campaign?.reply_to_email ?? props.defaults?.reply_to_email ?? '',
    subject: props.campaign?.subject ?? '',
    preheader: props.campaign?.preheader ?? '',
    html_body:
        props.campaign?.html_body ??
        '<p>Hello {first_name},</p><p><a href="{unsubscribe_url}">Unsubscribe</a></p>',
    text_body: props.campaign?.text_body ?? '',
    target_type: props.campaign?.target_type ?? 'all_contacts',
    target_filters: props.campaign?.target_filters ?? {},
    status: 'draft',
});
function save() {
    if (props.campaign?.id) {
        form.put(`/campaigns/${props.campaign.id}`);
    } else {
        form.post('/campaigns');
    }
}
</script>
<template>
    <Head title="Campaign Builder" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Campaign Builder"
            subtitle="Setup, content, audience, and review"
        />
        <form
            class="grid gap-6 lg:grid-cols-[1fr_420px]"
            @submit.prevent="save"
        >
            <section class="space-y-4 rounded-lg border bg-card p-5">
                <input
                    v-model="form.name"
                    class="h-10 w-full rounded-md border px-3"
                    placeholder="Campaign name"
                />
                <div class="grid gap-4 md:grid-cols-2">
                    <input
                        v-model="form.from_name"
                        class="h-10 rounded-md border px-3"
                        placeholder="From name"
                    /><input
                        v-model="form.from_email"
                        class="h-10 rounded-md border px-3"
                        placeholder="From email"
                    />
                </div>
                <input
                    v-model="form.subject"
                    class="h-10 w-full rounded-md border px-3"
                    placeholder="Subject"
                />
                <input
                    v-model="form.preheader"
                    class="h-10 w-full rounded-md border px-3"
                    placeholder="Preheader"
                />
                <select
                    v-model="form.target_type"
                    class="h-10 w-full rounded-md border px-3"
                >
                    <option>all_contacts</option>
                    <option>list</option>
                    <option>tag</option>
                    <option>manual_selection</option>
                </select>
                <textarea
                    v-model="form.html_body"
                    class="min-h-72 w-full rounded-md border px-3 py-2 font-mono text-sm"
                />
                <textarea
                    v-model="form.text_body"
                    class="min-h-32 w-full rounded-md border px-3 py-2 text-sm"
                    placeholder="Plain text fallback"
                />
                <button
                    class="rounded-md bg-primary px-3 py-2 text-sm text-white"
                >
                    Save Draft
                </button>
            </section>
            <aside class="rounded-lg border bg-card p-5">
                <h2 class="font-medium">Email Preview</h2>
                <div class="mt-4 rounded-lg border p-4">
                    <div class="font-medium">
                        {{ form.subject || 'Subject' }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ form.preheader }}
                    </div>
                    <iframe
                        class="mt-4 h-80 w-full rounded border"
                        :srcdoc="form.html_body"
                    ></iframe>
                </div>
            </aside>
        </form>
    </main>
</template>
