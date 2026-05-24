<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import TableShell from '@/components/emailora/TableShell.vue';

const props = defineProps<{ tags?: any; filters?: any }>();
const search = ref(props.filters?.search ?? '');

watch(search, (value) => {
    router.get(
        '/tags',
        { ...props.filters, search: value || undefined, page: undefined },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Tags" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Tags"
            :subtitle="`${props.tags?.meta?.total ?? 0} tags`"
        >
            <template #actions>
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    href="/tags/create"
                    >Create Tag</Link
                >
            </template>
        </PageHeader>
        <div
            class="mb-4 flex flex-col gap-2 rounded-lg border border-border bg-card p-3 md:flex-row"
        >
            <input
                v-model="search"
                class="h-9 rounded-md border border-border px-3 text-sm md:w-80"
                placeholder="Search tags"
            />
        </div>
        <TableShell min-width="680px">
            <table
                v-if="(props.tags?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Contacts</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="tag in props.tags.data" :key="tag.id">
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/tags/${tag.id}`">{{
                                tag.name
                            }}</Link>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ tag.contacts_count ?? 0 }} contacts
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <RowAction
                                    :href="`/tags/${tag.id}`"
                                    icon="view"
                                    label="Open"
                                />
                                <RowAction
                                    :href="`/tags/${tag.id}/edit`"
                                    icon="edit"
                                    label="Edit"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No tags found" />
            <template #footer>
                <Pagination :meta="props.tags?.meta" />
            </template>
        </TableShell>
    </main>
</template>
