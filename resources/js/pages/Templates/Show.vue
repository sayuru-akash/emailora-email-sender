<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ template: any }>();
const previewUrl = `/templates/${props.template.id}/preview`;
</script>
<template>
    <Head :title="props.template.name" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.template.name"
            :subtitle="props.template.subject"
        >
            <template #actions>
                <a
                    class="rounded-md border bg-card px-3 py-2 text-sm text-foreground transition hover:bg-muted"
                    :href="previewUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    >Open Preview</a
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm text-white"
                    :href="`/templates/${props.template.id}/edit`"
                    >Edit</Link
                >
            </template>
        </PageHeader>
        <div class="rounded-lg border bg-card p-5">
            <div class="text-sm text-muted-foreground">
                {{ props.template.preheader }}
            </div>
            <iframe
                class="mt-4 h-[70vh] min-h-[640px] w-full rounded-md border bg-white"
                :src="previewUrl"
                title="Template email preview"
            ></iframe>
        </div>
    </main>
</template>
