<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/emailora/ConfirmDialog.vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ imports?: any }>();
const deleteDialogOpen = ref(false);
const deleting = ref(false);
const selectedImport = ref<any | null>(null);

function openDeleteDialog(item: any) {
    selectedImport.value = item;
    deleteDialogOpen.value = true;
}

function deleteImport() {
    if (!selectedImport.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/imports/${selectedImport.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            selectedImport.value = null;
        },
    });
}
</script>
<template>
    <Head title="Imports" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Imports"
            ><template #actions
                ><Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
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
                        <th class="px-4 py-3 text-right">Actions</th>
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
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <RowAction
                                    v-if="
                                        ['uploaded', 'mapped'].includes(
                                            item.status,
                                        )
                                    "
                                    :href="`/imports/${item.id}/mapping`"
                                    icon="continue"
                                    label="Continue"
                                />
                                <RowAction
                                    :href="`/imports/${item.id}`"
                                    icon="view"
                                    label="View"
                                />
                                <RowAction
                                    destructive
                                    icon="delete"
                                    label="Delete"
                                    @click="openDeleteDialog(item)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No imports found" />
            <template #footer>
                <Pagination :meta="props.imports?.meta" />
            </template>
        </TableShell>
        <ConfirmDialog
            v-model="deleteDialogOpen"
            title="Delete import"
            :description="`This removes ${selectedImport?.file_name ?? 'the import'} and its uploaded file/results. Contacts already created by this import are not deleted.`"
            confirm-label="Delete import"
            destructive
            :processing="deleting"
            @confirm="deleteImport"
        />
    </main>
</template>
