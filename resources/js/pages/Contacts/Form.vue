<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';

const props = defineProps<{ contact?: any; lists?: any[]; tags?: any[] }>();
const form = useForm({
    first_name: props.contact?.first_name ?? '',
    last_name: props.contact?.last_name ?? '',
    full_name: props.contact?.full_name ?? '',
    email: props.contact?.email ?? '',
    phone: props.contact?.phone ?? '',
    company: props.contact?.company ?? '',
    job_title: props.contact?.job_title ?? '',
    country: props.contact?.country ?? '',
    district: props.contact?.district ?? '',
    city: props.contact?.city ?? '',
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
        <PageHeader
            :title="props.contact ? 'Edit Contact' : 'Add Contact'"
            subtitle="Identity, consent, lists, and tags"
        />
        <form
            class="rounded-lg border border-border bg-card p-5"
            @submit.prevent="submit"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium" for="first-name"
                        >First name</label
                    >
                    <input
                        id="first-name"
                        v-model="form.first_name"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.first_name" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="last-name"
                        >Last name</label
                    >
                    <input
                        id="last-name"
                        v-model="form.last_name"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.last_name" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium" for="full-name"
                        >Full name</label
                    >
                    <input
                        id="full-name"
                        v-model="form.full_name"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.full_name" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="email">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="phone">Phone</label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.phone" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="company"
                        >Company</label
                    >
                    <input
                        id="company"
                        v-model="form.company"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.company" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="job-title"
                        >Job title</label
                    >
                    <input
                        id="job-title"
                        v-model="form.job_title"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.job_title" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="country"
                        >Country</label
                    >
                    <input
                        id="country"
                        v-model="form.country"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.country" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="district"
                        >District</label
                    >
                    <input
                        id="district"
                        v-model="form.district"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.district" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="city">City</label>
                    <input
                        id="city"
                        v-model="form.city"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.city" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="source"
                        >Source</label
                    >
                    <input
                        id="source"
                        v-model="form.source"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.source" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="status"
                        >Status</label
                    >
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    >
                        <option>active</option>
                        <option>inactive</option>
                        <option>unsubscribed</option>
                        <option>bounced</option>
                        <option>complained</option>
                        <option>blocked</option>
                        <option>invalid</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="consent-status"
                        >Consent</label
                    >
                    <select
                        id="consent-status"
                        v-model="form.consent_status"
                        class="mt-1 h-10 w-full rounded-md border px-3"
                    >
                        <option>unknown</option>
                        <option>opted_in</option>
                        <option>opted_out</option>
                    </select>
                    <InputError :message="form.errors.consent_status" />
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium" for="notes">Notes</label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        class="mt-1 min-h-28 w-full rounded-md border px-3 py-2"
                    />
                    <InputError :message="form.errors.notes" />
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <section class="rounded-md border border-border p-3">
                    <h2 class="text-sm font-medium">Lists</h2>
                    <div class="mt-3 max-h-52 space-y-2 overflow-y-auto pr-1">
                        <label
                            v-for="list in props.lists ?? []"
                            :key="list.id"
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                v-model="form.list_ids"
                                type="checkbox"
                                :value="list.id"
                            />
                            <span>{{ list.name }}</span>
                        </label>
                        <p
                            v-if="!(props.lists ?? []).length"
                            class="text-sm text-muted-foreground"
                        >
                            No active lists yet
                        </p>
                    </div>
                    <InputError :message="form.errors.list_ids" />
                </section>
                <section class="rounded-md border border-border p-3">
                    <h2 class="text-sm font-medium">Tags</h2>
                    <div class="mt-3 max-h-52 space-y-2 overflow-y-auto pr-1">
                        <label
                            v-for="tag in props.tags ?? []"
                            :key="tag.id"
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                v-model="form.tag_ids"
                                type="checkbox"
                                :value="tag.id"
                            />
                            <span>{{ tag.name }}</span>
                        </label>
                        <p
                            v-if="!(props.tags ?? []).length"
                            class="text-sm text-muted-foreground"
                        >
                            No tags yet
                        </p>
                    </div>
                    <InputError :message="form.errors.tag_ids" />
                </section>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <Link
                    class="rounded-md border px-3 py-2 text-sm"
                    href="/contacts"
                    >Cancel</Link
                >
                <button
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-white"
                    :disabled="form.processing"
                >
                    Save
                </button>
            </div>
        </form>
    </main>
</template>
