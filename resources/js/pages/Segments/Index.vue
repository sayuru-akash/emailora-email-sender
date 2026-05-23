<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ segments?: any }>();
</script>
<template>
    <Head title="Segments" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Segments" />
        <TableShell min-width="760px">
            <table
                v-if="(props.segments?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Segment</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="segment in props.segments?.data ?? []"
                        :key="segment.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ segment.name }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="segment.status" />
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ segment.created_at ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No segments found" />
            <template #footer>
                <Pagination :meta="props.segments?.meta" />
            </template>
        </TableShell>
    </main>
</template>
