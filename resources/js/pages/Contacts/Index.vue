<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';

const props = defineProps<{
    contacts?: any;
    filters?: any;
    filterOptions?: any;
}>();
const search = ref(props.filters?.search ?? '');
const selectedIds = ref<number[]>([]);
const hasSelection = computed(() => selectedIds.value.length > 0);

watch(search, (value) => {
    router.get(
        '/contacts',
        { ...props.filters, search: value || undefined, page: undefined },
        { preserveState: true, replace: true },
    );
});

function applyBulkAction(action: string) {
    if (!hasSelection.value) {
        return;
    }

    if (
        action === 'delete' &&
        !window.confirm(`Delete ${selectedIds.value.length} selected contacts?`)
    ) {
        return;
    }

    router.post(
        '/contacts/bulk-action',
        { action, ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
        },
    );
}

function togglePageSelection(event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    selectedIds.value = checked
        ? (props.contacts?.data ?? []).map((contact: any) => contact.id)
        : [];
}
</script>

<template>
    <Head title="Contacts" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Contacts"
            :subtitle="`${props.contacts?.meta?.total ?? 0} contacts`"
        >
            <template #actions>
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm"
                    href="/imports/create"
                    >Import</Link
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    href="/contacts/create"
                    >Add Contact</Link
                >
                <a
                    class="rounded-md border border-border px-3 py-2 text-sm"
                    href="/contacts/export"
                    >Export</a
                >
            </template>
        </PageHeader>
        <div
            class="mb-4 flex flex-col gap-3 rounded-lg border border-border bg-card p-3 md:flex-row"
        >
            <input
                v-model="search"
                class="h-9 rounded-md border border-border px-3 text-sm md:w-80"
                placeholder="Search contacts"
            />
            <select
                class="h-9 rounded-md border border-border px-3 text-sm"
                @change="
                    router.get('/contacts', {
                        ...props.filters,
                        status:
                            ($event.target as HTMLSelectElement).value ||
                            undefined,
                        page: undefined,
                    })
                "
            >
                <option value="">All statuses</option>
                <option
                    v-for="status in props.filterOptions?.statuses ?? []"
                    :key="status"
                    :value="status"
                    :selected="props.filters?.status === status"
                >
                    {{ status }}
                </option>
            </select>
            <select
                class="h-9 rounded-md border border-border px-3 text-sm"
                @change="
                    router.get('/contacts', {
                        ...props.filters,
                        source:
                            ($event.target as HTMLSelectElement).value ||
                            undefined,
                        page: undefined,
                    })
                "
            >
                <option value="">All sources</option>
                <option
                    v-for="source in props.filterOptions?.sources ?? []"
                    :key="source"
                    :value="source"
                    :selected="props.filters?.source === source"
                >
                    {{ source }}
                </option>
            </select>
            <div class="flex flex-wrap gap-2 md:ml-auto">
                <button
                    class="rounded-md border border-border px-3 py-2 text-sm disabled:opacity-50"
                    :disabled="!hasSelection"
                    type="button"
                    @click="applyBulkAction('mark_inactive')"
                >
                    Mark inactive
                </button>
                <button
                    class="rounded-md border border-border px-3 py-2 text-sm disabled:opacity-50"
                    :disabled="!hasSelection"
                    type="button"
                    @click="applyBulkAction('unsubscribe')"
                >
                    Unsubscribe
                </button>
                <button
                    class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive disabled:opacity-50"
                    :disabled="!hasSelection"
                    type="button"
                    @click="applyBulkAction('delete')"
                >
                    Delete
                </button>
            </div>
        </div>
        <TableShell min-width="960px">
            <table
                v-if="(props.contacts?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" @change="togglePageSelection" />
                        </th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Last contacted</th>
                        <th class="px-4 py-3">Added</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="contact in props.contacts.data"
                        :key="contact.id"
                    >
                        <td class="px-4 py-3">
                            <input
                                v-model="selectedIds"
                                type="checkbox"
                                :value="contact.id"
                            />
                        </td>
                        <td class="max-w-56 truncate px-4 py-3 font-medium">
                            {{
                                contact.full_name ||
                                contact.first_name ||
                                contact.email
                            }}
                        </td>
                        <td class="max-w-64 truncate px-4 py-3">
                            {{ contact.email }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="contact.status" />
                        </td>
                        <td class="px-4 py-3">
                            {{ contact.source ?? 'manual' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ contact.last_contacted_at ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ contact.created_at }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <Link
                                    class="text-primary"
                                    :href="`/contacts/${contact.id}`"
                                    >View</Link
                                >
                                <Link
                                    class="text-muted-foreground hover:text-primary"
                                    :href="`/contacts/${contact.id}/edit`"
                                    >Edit</Link
                                >
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState
                v-else
                title="No contacts found"
                description="Change the filters to see more contacts."
            />
            <template #footer>
                <Pagination :meta="props.contacts?.meta" />
            </template>
        </TableShell>
    </main>
</template>
