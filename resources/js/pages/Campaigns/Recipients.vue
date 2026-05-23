<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ campaign: any; recipients?: any }>();
</script>
<template>
    <Head title="Recipients" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader :title="`${props.campaign.name} Recipients`" />
        <TableShell min-width="900px">
            <table
                v-if="(props.recipients?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Provider Message</th>
                        <th class="px-4 py-3">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="r in props.recipients?.data ?? []" :key="r.id">
                        <td class="px-4 py-3 font-medium">
                            {{ r.email_normalized }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="r.status" />
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ r.provider_message_id ?? '-' }}
                        </td>
                        <td class="max-w-80 truncate px-4 py-3 text-red-600">
                            {{ r.error_message ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No recipients found" />
            <template #footer>
                <Pagination :meta="props.recipients?.meta" />
            </template>
        </TableShell>
    </main>
</template>
