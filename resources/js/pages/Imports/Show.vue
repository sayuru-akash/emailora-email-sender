<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ import: any; failedRows?: any }>();
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
        <section class="mt-6">
            <PageHeader
                title="Failed Rows"
                :subtitle="`${props.failedRows?.meta?.total ?? 0} rows need attention`"
            />
            <TableShell min-width="980px">
                <table
                    v-if="(props.failedRows?.data ?? []).length"
                    class="w-full text-sm"
                >
                    <thead
                        class="bg-muted text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Row</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Error</th>
                            <th class="px-4 py-3">Raw Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in props.failedRows?.data ?? []"
                            :key="row.id"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ row.row_number }}
                            </td>
                            <td class="px-4 py-3">
                                {{ row.email_normalized ?? '-' }}
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
                <EmptyState v-else title="No failed rows" />
                <template #footer>
                    <Pagination
                        page-name="failed_page"
                        :meta="props.failedRows?.meta"
                    />
                </template>
            </TableShell>
        </section>
    </main>
</template>
