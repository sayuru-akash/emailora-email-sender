<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';

const props = defineProps<{
    import: any;
    rows?: any;
    filters?: any;
    statusOptions?: string[];
}>();

const activeStatuses = ['queued', 'processing'];
const shouldPoll = computed(() => activeStatuses.includes(props.import.status));
let pollId: number | undefined;

function applyStatus(event: Event) {
    router.get(
        `/imports/${props.import.id}`,
        {
            ...props.filters,
            status: (event.target as HTMLSelectElement).value || undefined,
            page: undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

onMounted(() => {
    if (!shouldPoll.value) {
        return;
    }

    pollId = window.setInterval(() => {
        router.reload({
            only: ['import', 'rows'],
        });
    }, 3500);
});

onUnmounted(() => {
    if (pollId) {
        window.clearInterval(pollId);
    }
});
</script>

<template>
    <Head :title="props.import.file_name" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.import.file_name"
            :subtitle="`${props.import.processed_rows} of ${props.import.total_rows} rows processed`"
        >
            <template #actions>
                <a
                    v-if="props.import.failed_rows > 0"
                    class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm hover:bg-accent"
                    :href="`/imports/${props.import.id}/download-failed`"
                >
                    <Download class="h-4 w-4" />
                    Failed rows
                </a>
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm hover:bg-accent"
                    href="/imports"
                >
                    All imports
                </Link>
            </template>
        </PageHeader>

        <section class="rounded-lg border border-border bg-card p-5">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex items-center gap-3">
                    <StatusBadge :status="props.import.status" />
                    <span class="text-sm text-muted-foreground">{{
                        props.import.file_type?.toUpperCase() ?? 'FILE'
                    }}</span>
                </div>
                <p v-if="shouldPoll" class="text-sm text-muted-foreground">
                    Refreshing progress...
                </p>
            </div>
            <div class="mt-4 h-2 rounded bg-muted">
                <div
                    class="h-2 rounded bg-primary transition-all"
                    :style="{ width: `${props.import.progress_percent ?? 0}%` }"
                ></div>
            </div>
            <p
                v-if="props.import.failure_message"
                class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700"
            >
                {{ props.import.failure_message }}
            </p>
        </section>

        <div class="mt-5 grid gap-4 md:grid-cols-5">
            <section class="rounded-lg border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Total</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ props.import.total_rows }}
                </p>
            </section>
            <section class="rounded-lg border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Processed</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ props.import.processed_rows }}
                </p>
            </section>
            <section class="rounded-lg border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Created or updated</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ props.import.successful_rows }}
                </p>
            </section>
            <section class="rounded-lg border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Duplicates</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ props.import.duplicate_rows }}
                </p>
            </section>
            <section class="rounded-lg border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Failed</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ props.import.failed_rows }}
                </p>
            </section>
        </div>

        <section class="mt-6">
            <div
                class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
                <PageHeader
                    class="mb-0"
                    title="Import Rows"
                    :subtitle="`${props.rows?.meta?.total ?? 0} rows`"
                />
                <select
                    class="h-10 rounded-md border border-border bg-background px-3 text-sm"
                    :value="props.filters?.status ?? ''"
                    @change="applyStatus"
                >
                    <option value="">All statuses</option>
                    <option
                        v-for="status in props.statusOptions ?? []"
                        :key="status"
                        :value="status"
                    >
                        {{ status }}
                    </option>
                </select>
            </div>
            <TableShell min-width="1040px">
                <table
                    v-if="(props.rows?.data ?? []).length"
                    class="w-full text-sm"
                >
                    <thead
                        class="bg-muted text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Row</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Error</th>
                            <th class="px-4 py-3">Raw Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="row in props.rows?.data ?? []" :key="row.id">
                            <td class="px-4 py-3 font-medium">
                                {{ row.row_number }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="row.status" />
                            </td>
                            <td class="max-w-64 truncate px-4 py-3">
                                {{ row.email_normalized ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ row.contact_id ?? '-' }}
                            </td>
                            <td
                                class="max-w-80 truncate px-4 py-3 text-red-600"
                            >
                                {{ row.error_message ?? '-' }}
                            </td>
                            <td
                                class="max-w-96 truncate px-4 py-3 text-muted-foreground"
                            >
                                {{ JSON.stringify(row.raw_data ?? {}) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <EmptyState v-else title="No import rows found" />
                <template #footer>
                    <Pagination :meta="props.rows?.meta" />
                </template>
            </TableShell>
        </section>
    </main>
</template>
