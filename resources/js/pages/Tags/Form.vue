<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';

const props = defineProps<{ tag?: any }>();
const form = useForm({
    name: props.tag?.name ?? '',
    description: props.tag?.description ?? '',
    color: props.tag?.color ?? '#4f46e5',
});

function submit() {
    if (props.tag?.id) {
        form.put(`/tags/${props.tag.id}`);

        return;
    }

    form.post('/tags');
}
</script>

<template>
    <Head :title="props.tag ? 'Edit Tag' : 'Create Tag'" />
    <main class="mx-auto w-full max-w-3xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.tag ? 'Edit Tag' : 'Create Tag'"
            subtitle="Label contacts for targeting and reporting"
        >
            <template #actions>
                <Link class="rounded-md border px-3 py-2 text-sm" href="/tags"
                    >Back</Link
                >
            </template>
        </PageHeader>

        <form
            class="space-y-5 rounded-lg border border-border bg-card p-5"
            @submit.prevent="submit"
        >
            <div>
                <label class="text-sm font-medium" for="tag-name">Name</label>
                <input
                    id="tag-name"
                    v-model="form.name"
                    class="mt-1 h-10 w-full rounded-md border border-border bg-background px-3 text-sm"
                    autocomplete="off"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <label class="text-sm font-medium" for="tag-description"
                    >Description</label
                >
                <textarea
                    id="tag-description"
                    v-model="form.description"
                    class="mt-1 min-h-28 w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div>
                <label class="text-sm font-medium" for="tag-color">Color</label>
                <input
                    id="tag-color"
                    v-model="form.color"
                    class="mt-1 h-10 w-full rounded-md border border-border bg-background px-3 text-sm"
                />
                <InputError :message="form.errors.color" />
            </div>

            <div class="flex justify-end gap-2">
                <Link class="rounded-md border px-3 py-2 text-sm" href="/tags"
                    >Cancel</Link
                >
                <button
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving...' : 'Save tag' }}
                </button>
            </div>
        </form>
    </main>
</template>
