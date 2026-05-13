<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';

const props = defineProps<{ contact?: any; lists?: any[]; tags?: any[] }>();
const form = useForm({
    first_name: props.contact?.first_name ?? '',
    last_name: props.contact?.last_name ?? '',
    full_name: props.contact?.full_name ?? '',
    email: props.contact?.email ?? '',
    company: props.contact?.company ?? '',
    source: props.contact?.source ?? 'manual',
    status: props.contact?.status ?? 'active',
    consent_status: props.contact?.consent_status ?? 'unknown',
    notes: props.contact?.notes ?? '',
    list_ids: (props.contact?.lists ?? []).map((item: any) => item.id),
    tag_ids: (props.contact?.tags ?? []).map((item: any) => item.id),
});

function submit() {
    if (props.contact?.id) {
        form.put(`/contacts/${props.contact.id}`);
    } else {
        form.post('/contacts');
    }
}
</script>

<template>
    <Head :title="props.contact ? 'Edit Contact' : 'Add Contact'" />
    <main class="mx-auto w-full max-w-4xl px-4 py-6 lg:px-8">
        <PageHeader :title="props.contact ? 'Edit Contact' : 'Add Contact'" subtitle="Identity, consent, lists, and tags" />
        <form class="rounded-lg border border-border bg-white p-5" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-2">
                <input v-model="form.first_name" class="h-10 rounded-md border px-3" placeholder="First name" />
                <input v-model="form.last_name" class="h-10 rounded-md border px-3" placeholder="Last name" />
                <input v-model="form.full_name" class="h-10 rounded-md border px-3 md:col-span-2" placeholder="Full name" />
                <input v-model="form.email" class="h-10 rounded-md border px-3" placeholder="Email" />
                <input v-model="form.company" class="h-10 rounded-md border px-3" placeholder="Company" />
                <input v-model="form.source" class="h-10 rounded-md border px-3" placeholder="Source" />
                <select v-model="form.status" class="h-10 rounded-md border px-3">
                    <option>active</option><option>inactive</option><option>unsubscribed</option><option>bounced</option><option>complained</option><option>blocked</option><option>invalid</option>
                </select>
                <select v-model="form.consent_status" class="h-10 rounded-md border px-3">
                    <option>unknown</option><option>opted_in</option><option>opted_out</option>
                </select>
                <textarea v-model="form.notes" class="min-h-28 rounded-md border px-3 py-2 md:col-span-2" placeholder="Notes" />
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <Link class="rounded-md border px-3 py-2 text-sm" href="/contacts">Cancel</Link>
                <button class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-white" :disabled="form.processing">Save</button>
            </div>
        </form>
    </main>
</template>
