<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ templates?: any }>();
</script>
<template>
    <Head title="Email Templates" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Email Templates"
            :subtitle="`${props.templates?.meta?.total ?? 0} templates`"
        >
            <template #actions>
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    href="/templates/create"
                    >Create Template</Link
                >
            </template>
        </PageHeader>
        <TableShell min-width="880px">
            <table
                v-if="(props.templates?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Template</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="template in props.templates.data"
                        :key="template.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/templates/${template.id}`">{{
                                template.name
                            }}</Link>
                        </td>
                        <td class="px-4 py-3">{{ template.subject }}</td>
                        <td class="px-4 py-3">
                            {{ template.category ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="template.status" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <RowAction
                                    :href="`/templates/${template.id}`"
                                    icon="view"
                                    label="View"
                                />
                                <RowAction
                                    :href="`/templates/${template.id}/edit`"
                                    icon="edit"
                                    label="Edit"
                                />
                                <RowAction
                                    :href="`/templates/${template.id}/duplicate`"
                                    icon="duplicate"
                                    label="Duplicate"
                                    method="post"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No templates found" />
            <template #footer>
                <Pagination :meta="props.templates?.meta" />
            </template>
        </TableShell>
    </main>
</template>
