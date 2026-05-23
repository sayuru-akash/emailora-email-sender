<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ tag: any; contacts?: any }>();

function deleteTag() {
    if (confirm('Delete this tag?')) {
        router.delete(`/tags/${props.tag.id}`);
    }
}
</script>
<template>
    <Head :title="props.tag.name" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader :title="props.tag.name" :subtitle="props.tag.description">
            <template #actions>
                <Link class="rounded-md border px-3 py-2 text-sm" href="/tags"
                    >Back</Link
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    :href="`/tags/${props.tag.id}/edit`"
                    >Edit</Link
                >
                <button
                    class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive"
                    type="button"
                    @click="deleteTag"
                >
                    Delete
                </button>
                >
            </template>
        </PageHeader>
        <TableShell min-width="820px">
            <table
                v-if="(props.contacts?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="contact in props.contacts?.data ?? []"
                        :key="contact.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/contacts/${contact.id}`">{{
                                contact.full_name || contact.email
                            }}</Link>
                        </td>
                        <td class="px-4 py-3">{{ contact.email }}</td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="contact.status" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No contacts found for this tag" />
            <template #footer>
                <Pagination :meta="props.contacts?.meta" />
            </template>
        </TableShell>
    </main>
</template>
