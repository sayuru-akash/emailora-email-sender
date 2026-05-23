<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ activities?: any }>();
</script>
<template>
    <Head title="Activity Logs" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Activity Logs"
            subtitle="Provider and system events"
        />
        <TableShell min-width="900px">
            <table
                v-if="(props.activities?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="item in props.activities?.data ?? []"
                        :key="item.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ item.event_type ?? 'event' }}
                        </td>
                        <td class="px-4 py-3">{{ item.provider ?? '-' }}</td>
                        <td class="px-4 py-3">
                            {{ item.email_normalized ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ item.created_at ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No activity logs found" />
            <template #footer>
                <Pagination :meta="props.activities?.meta" />
            </template>
        </TableShell>
    </main>
</template>
