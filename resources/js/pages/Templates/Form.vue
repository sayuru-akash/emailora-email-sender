<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ template?: any }>();
const form = useForm({
    name: props.template?.name ?? '',
    category: props.template?.category ?? 'newsletter',
    subject: props.template?.subject ?? '',
    preheader: props.template?.preheader ?? '',
    html_body:
        props.template?.html_body ??
        '<p>Hello {first_name},</p><p><a href="{unsubscribe_url}">Unsubscribe</a></p>',
    text_body: props.template?.text_body ?? '',
    status: props.template?.status ?? 'active',
});
function submit() {
    if (props.template?.id) {
        form.put(`/templates/${props.template.id}`);
    } else {
        form.post('/templates');
    }
}
</script>
<template>
    <Head :title="props.template ? 'Edit Template' : 'Create Template'" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.template ? 'Edit Template' : 'Create Template'"
            subtitle="Subject, preview text, HTML, and plain text"
        />
        <form
            class="space-y-4 rounded-lg border bg-card p-5"
            @submit.prevent="submit"
        >
            <input
                v-model="form.name"
                class="h-10 w-full rounded-md border px-3"
                placeholder="Template name"
            />
            <div class="grid gap-4 md:grid-cols-2">
                <input
                    v-model="form.category"
                    class="h-10 rounded-md border px-3"
                    placeholder="Category"
                />
                <select
                    v-model="form.status"
                    class="h-10 rounded-md border px-3"
                >
                    <option>active</option>
                    <option>inactive</option>
                </select>
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
            <textarea
                v-model="form.html_body"
                class="min-h-64 w-full rounded-md border px-3 py-2 font-mono text-sm"
            />
            <textarea
                v-model="form.text_body"
                class="min-h-32 w-full rounded-md border px-3 py-2 text-sm"
                placeholder="Plain text fallback"
            />
            <div class="flex justify-end gap-2">
                <Link
                    class="rounded-md border px-3 py-2 text-sm"
                    href="/templates"
                    >Cancel</Link
                ><button
                    class="rounded-md bg-primary px-3 py-2 text-sm text-white"
                >
                    Save
                </button>
            </div>
        </form>
    </main>
</template>
