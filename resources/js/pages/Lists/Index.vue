<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';

const props = defineProps<{ lists?: any; filters?: any }>();
const search = ref(props.filters?.search ?? '');

watch(search, (value) => {
    router.get(
        '/lists',
        { ...props.filters, search: value || undefined, page: undefined },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Lists" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Lists"
            :subtitle="`${props.lists?.meta?.total ?? 0} lists`"
        >
            <template #actions>
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    href="/lists/create"
                    >Create List</Link
                >
            </template>
        </PageHeader>
        <div
            class="mb-4 flex flex-col gap-2 rounded-lg border border-border bg-card p-3 md:flex-row"
        >
            <input
                v-model="search"
                class="h-9 rounded-md border border-border px-3 text-sm md:w-80"
                placeholder="Search lists"
            />
        </div>
        <TableShell min-width="760px">
            <table
                v-if="(props.lists?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Contacts</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="list in props.lists.data" :key="list.id">
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/lists/${list.id}`">{{
                                list.name
                            }}</Link>
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="list.status" />
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ list.contacts_count ?? 0 }} contacts
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <RowAction
                                    :href="`/lists/${list.id}`"
                                    icon="view"
                                    label="Open"
                                />
                                <RowAction
                                    :href="`/lists/${list.id}/edit`"
                                    icon="edit"
                                    label="Edit"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No lists found" />
            <template #footer>
                <Pagination :meta="props.lists?.meta" />
            </template>
        </TableShell>
    </main>
</template>
