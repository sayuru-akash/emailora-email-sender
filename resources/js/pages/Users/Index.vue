<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ users?: any }>();
</script>
<template>
    <Head title="Users" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Users"
            ><template #actions
                ><Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    href="/users/create"
                    >Add User</Link
                ></template
            ></PageHeader
        >
        <TableShell min-width="820px">
            <table
                v-if="(props.users?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="user in props.users?.data ?? []" :key="user.id">
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/users/${user.id}`">{{
                                user.name
                            }}</Link>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ user.email }}
                        </td>
                        <td class="px-4 py-3">{{ user.role }}</td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="user.status" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <RowAction
                                    :href="`/users/${user.id}`"
                                    icon="view"
                                    label="View"
                                />
                                <RowAction
                                    :href="`/users/${user.id}/edit`"
                                    icon="edit"
                                    label="Edit"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No users found" />
            <template #footer>
                <Pagination :meta="props.users?.meta" />
            </template>
        </TableShell>
    </main>
</template>
