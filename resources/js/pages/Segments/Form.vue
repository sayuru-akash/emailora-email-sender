<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    segment?: any;
    defaultFilters?: Record<string, unknown>;
}>();
const isEditing = computed(() => Boolean(props.segment?.id));
const form = useForm({
    name: props.segment?.name ?? '',
    description: props.segment?.description ?? '',
    status: props.segment?.status ?? 'active',
    filters: JSON.stringify(props.defaultFilters ?? {}, null, 2),
});

function submit() {
    let parsedFilters: Record<string, unknown>;

    try {
        parsedFilters = JSON.parse(form.filters || '{}');
    } catch {
        form.setError('filters', 'Filters must be valid JSON.');

        return;
    }

    const payload = {
        name: form.name,
        description: form.description,
        status: form.status,
        filters: parsedFilters,
    };

    if (isEditing.value) {
        form.transform(() => payload).put(`/segments/${props.segment.id}`);

        return;
    }

    form.transform(() => payload).post('/segments');
}
</script>

<template>
    <Head :title="isEditing ? 'Edit Segment' : 'Create Segment'" />
    <main class="mx-auto w-full max-w-4xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="isEditing ? 'Edit Segment' : 'Create Segment'"
            subtitle="Saved audience filters for campaign targeting."
        >
            <template #actions>
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm"
                    href="/segments"
                    >Cancel</Link
                >
            </template>
        </PageHeader>
        <form
            class="space-y-5 rounded-lg border border-border bg-card p-5"
            @submit.prevent="submit"
        >
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Name</span>
                <input
                    v-model="form.name"
                    class="h-10 rounded-md border border-border bg-background px-3"
                />
                <InputError :message="form.errors.name" />
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Description</span>
                <textarea
                    v-model="form.description"
                    class="min-h-24 rounded-md border border-border bg-background px-3 py-2"
                ></textarea>
                <InputError :message="form.errors.description" />
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Status</span>
                <select
                    v-model="form.status"
                    class="h-10 rounded-md border border-border bg-background px-3"
                >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <InputError :message="form.errors.status" />
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Filters JSON</span>
                <textarea
                    v-model="form.filters"
                    class="min-h-72 rounded-md border border-border bg-background px-3 py-2 font-mono text-xs leading-5"
                ></textarea>
                <InputError :message="form.errors.filters" />
            </label>
            <div class="flex justify-end gap-2">
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm"
                    href="/segments"
                    >Cancel</Link
                >
                <button
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                    :disabled="form.processing"
                    type="submit"
                >
                    {{ form.processing ? 'Saving...' : 'Save segment' }}
                </button>
            </div>
        </form>
    </main>
</template>
