<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ imports?: any }>();
</script>
<template>
    <Head title="Imports" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Imports"
            ><template #actions
                ><Link
                    class="rounded-md bg-primary px-3 py-2 text-sm text-white"
                    href="/imports/create"
                    >Import Contacts</Link
                ></template
            ></PageHeader
        >
        <TableShell min-width="900px">
            <table
                v-if="(props.imports?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">File</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Processed</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="item in props.imports.data" :key="item.id">
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/imports/${item.id}`">{{
                                item.file_name
                            }}</Link>
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="item.status" />
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ item.processed_rows ?? 0 }} /
                            {{ item.total_rows ?? 0 }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ item.created_at ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No imports found" />
            <template #footer>
                <Pagination :meta="props.imports?.meta" />
            </template>
        </TableShell>
    </main>
</template>
