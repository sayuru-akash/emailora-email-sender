<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ template: any }>();
</script>
<template>
    <Head :title="props.template.name" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.template.name"
            :subtitle="props.template.subject"
        >
            <template #actions
                ><Link
                    class="rounded-md bg-primary px-3 py-2 text-sm text-white"
                    :href="`/templates/${props.template.id}/edit`"
                    >Edit</Link
                ></template
            >
        </PageHeader>
        <div class="rounded-lg border bg-card p-5">
            <div class="text-sm text-muted-foreground">
                {{ props.template.preheader }}
            </div>
            <iframe
                class="mt-4 h-96 w-full rounded-md border"
                :srcdoc="props.template.html_body"
            ></iframe>
        </div>
    </main>
</template>
