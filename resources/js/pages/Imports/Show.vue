<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
const props = defineProps<{ import: any; failedRows?: any[] }>();
</script>
<template>
    <Head :title="props.import.file_name" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader :title="props.import.file_name" />
        <section class="rounded-lg border bg-card p-5">
            <StatusBadge :status="props.import.status" />
            <div class="mt-4 h-2 rounded bg-muted">
                <div
                    class="h-2 rounded bg-primary"
                    :style="{ width: `${props.import.progress_percent ?? 0}%` }"
                ></div>
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ props.import.processed_rows }} /
                {{ props.import.total_rows }} rows
            </p>
        </section>
    </main>
</template>
