<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';

const props = defineProps<{ list: any; contacts?: any }>();
</script>

<template>
    <Head :title="props.list.name" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.list.name"
            :subtitle="props.list.description"
        >
            <template #actions>
                <Link class="rounded-md border px-3 py-2 text-sm" href="/lists"
                    >Back</Link
                >
                <a
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="`/lists/${props.list.id}/export`"
                    >Export</a
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    :href="`/lists/${props.list.id}/edit`"
                    >Edit</Link
                >
                <Link
                    class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive"
                    :href="`/lists/${props.list.id}`"
                    method="delete"
                    as="button"
                    @click="
                        !window.confirm('Delete this list?') &&
                            $event.preventDefault()
                    "
                    >Delete</Link
                >
            </template>
        </PageHeader>
        <TableShell min-width="820px">
            <table
                v-if="(props.contacts?.data ?? []).length"
                class="w-full text-sm"
            >
                <tbody class="divide-y">
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
            <EmptyState v-else title="No contacts found for this list" />
            <template #footer>
                <Pagination :meta="props.contacts?.meta" />
            </template>
        </TableShell>
    </main>
</template>
