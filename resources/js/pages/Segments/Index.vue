<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ segments?: any }>();
</script>
<template>
    <Head title="Segments" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Segments">
            <template #actions>
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    href="/segments/create"
                    >Create Segment</Link
                >
            </template>
        </PageHeader>
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
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="segment in props.segments?.data ?? []"
                        :key="segment.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link
                                class="text-primary"
                                :href="`/segments/${segment.id}`"
                                >{{ segment.name }}</Link
                            >
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="segment.status" />
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ segment.created_at ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <RowAction
                                    :href="`/segments/${segment.id}`"
                                    icon="view"
                                    label="View"
                                />
                                <RowAction
                                    :href="`/segments/${segment.id}/edit`"
                                    icon="edit"
                                    label="Edit"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState
                v-else
                title="No segments found"
                description="Create a saved audience segment for repeated campaign targeting."
            />
            <template #footer>
                <Pagination :meta="props.segments?.meta" />
            </template>
        </TableShell>
    </main>
</template>
