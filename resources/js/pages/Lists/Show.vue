<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/emailora/ConfirmDialog.vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';

const props = defineProps<{
    list: any;
    contacts?: any;
    availableContacts?: any[];
}>();
const deleteDialogOpen = ref(false);
const deleting = ref(false);
const selectedContactIds = ref<number[]>([]);
const addContactIds = ref<number[]>([]);
const membershipProcessing = ref(false);

function deleteList() {
    deleting.value = true;
    router.delete(`/lists/${props.list.id}`, {
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
        },
    });
}

function addContacts() {
    if (!addContactIds.value.length) {
        return;
    }

    membershipProcessing.value = true;
    router.post(
        `/lists/${props.list.id}/add-contacts`,
        { contact_ids: addContactIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                addContactIds.value = [];
            },
            onFinish: () => {
                membershipProcessing.value = false;
            },
        },
    );
}

function removeContacts() {
    if (!selectedContactIds.value.length) {
        return;
    }

    membershipProcessing.value = true;
    router.post(
        `/lists/${props.list.id}/remove-contacts`,
        { contact_ids: selectedContactIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedContactIds.value = [];
            },
            onFinish: () => {
                membershipProcessing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="props.list.name" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader :title="props.list.name" :subtitle="props.list.description">
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
                <button
                    class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive"
                    type="button"
                    @click="deleteDialogOpen = true"
                >
                    Delete
                </button>
            </template>
        </PageHeader>
        <section class="mb-5 rounded-lg border border-border bg-card p-4">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
            >
                <label class="grid flex-1 gap-1 text-sm">
                    <span class="font-medium">Add contacts</span>
                    <select
                        v-model="addContactIds"
                        multiple
                        class="min-h-28 rounded-md border border-border bg-background px-3 py-2"
                    >
                        <option
                            v-for="contact in props.availableContacts ?? []"
                            :key="contact.id"
                            :value="contact.id"
                        >
                            {{ contact.full_name || contact.email }} -
                            {{ contact.email }}
                        </option>
                    </select>
                </label>
                <button
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                    :disabled="!addContactIds.length || membershipProcessing"
                    type="button"
                    @click="addContacts"
                >
                    Add selected
                </button>
            </div>
        </section>
        <div
            v-if="selectedContactIds.length"
            class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-3 text-sm"
        >
            <span>{{ selectedContactIds.length }} selected</span>
            <button
                class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive disabled:opacity-60"
                :disabled="membershipProcessing"
                type="button"
                @click="removeContacts"
            >
                Remove from list
            </button>
        </div>
        <TableShell min-width="820px">
            <table
                v-if="(props.contacts?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="w-10 px-4 py-3"></th>
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
                        <td class="px-4 py-3">
                            <input
                                v-model="selectedContactIds"
                                type="checkbox"
                                :value="contact.id"
                            />
                        </td>
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
        <ConfirmDialog
            v-model="deleteDialogOpen"
            title="Delete list"
            description="Contacts will remain in the system, but this list and its membership will be removed."
            confirm-label="Delete list"
            destructive
            :processing="deleting"
            @confirm="deleteList"
        />
    </main>
</template>
