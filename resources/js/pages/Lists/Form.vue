<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';

const props = defineProps<{ list?: any }>();
const form = useForm({
    name: props.list?.name ?? '',
    description: props.list?.description ?? '',
    status: props.list?.status ?? 'active',
    color: props.list?.color ?? '#4f46e5',
});

function submit() {
    if (props.list?.id) {
        form.put(`/lists/${props.list.id}`);

        return;
    }

    form.post('/lists');
}
</script>

<template>
    <Head :title="props.list ? 'Edit List' : 'Create List'" />
    <main class="mx-auto w-full max-w-3xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.list ? 'Edit List' : 'Create List'"
            subtitle="Group contacts for campaign targeting"
        >
            <template #actions>
                <Link class="rounded-md border px-3 py-2 text-sm" href="/lists"
                    >Back</Link
                >
            </template>
        </PageHeader>

        <form
            class="space-y-5 rounded-lg border border-border bg-card p-5"
            @submit.prevent="submit"
        >
            <div>
                <label class="text-sm font-medium" for="list-name">Name</label>
                <input
                    id="list-name"
                    v-model="form.name"
                    class="mt-1 h-10 w-full rounded-md border border-border bg-background px-3 text-sm"
                    autocomplete="off"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <label class="text-sm font-medium" for="list-description"
                    >Description</label
                >
                <textarea
                    id="list-description"
                    v-model="form.description"
                    class="mt-1 min-h-28 w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium" for="list-status"
                        >Status</label
                    >
                    <select
                        id="list-status"
                        v-model="form.status"
                        class="mt-1 h-10 w-full rounded-md border border-border bg-background px-3 text-sm"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>
                <div>
                    <label class="text-sm font-medium" for="list-color"
                        >Color</label
                    >
                    <input
                        id="list-color"
                        v-model="form.color"
                        class="mt-1 h-10 w-full rounded-md border border-border bg-background px-3 text-sm"
                    />
                    <InputError :message="form.errors.color" />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Link class="rounded-md border px-3 py-2 text-sm" href="/lists"
                    >Cancel</Link
                >
                <button
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving...' : 'Save list' }}
                </button>
            </div>
        </form>
    </main>
</template>
