<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/emailora/ConfirmDialog.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ userRecord: any }>();
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function deleteUser() {
    deleting.value = true;
    router.delete(`/users/${props.userRecord.id}`, {
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
        },
    });
}
</script>
<template>
    <Head :title="props.userRecord.name" />
    <main class="mx-auto w-full max-w-3xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.userRecord.name"
            :subtitle="props.userRecord.email"
            ><template #actions>
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm"
                    href="/users"
                    >Back</Link
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    :href="`/users/${props.userRecord.id}/edit`"
                    >Edit</Link
                >
                <button
                    class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive"
                    type="button"
                    @click="deleteDialogOpen = true"
                >
                    Delete
                </button>
            </template></PageHeader
        >
        <section class="rounded-lg border bg-card p-5 text-sm">
            {{ props.userRecord.role }} · {{ props.userRecord.status }}
        </section>
        <ConfirmDialog
            v-model="deleteDialogOpen"
            title="Delete user"
            :description="`This removes ${props.userRecord.email} from the system. Their historical audit records remain.`"
            confirm-label="Delete user"
            destructive
            :processing="deleting"
            @confirm="deleteUser"
        />
    </main>
</template>
