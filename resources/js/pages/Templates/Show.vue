<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/emailora/ConfirmDialog.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ template: any }>();
const previewUrl = `/templates/${props.template.id}/preview`;
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function deleteTemplate() {
    deleting.value = true;
    router.delete(`/templates/${props.template.id}`, {
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
        },
    });
}
</script>
<template>
    <Head :title="props.template.name" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.template.name"
            :subtitle="props.template.subject"
        >
            <template #actions>
                <a
                    class="rounded-md border bg-card px-3 py-2 text-sm text-foreground transition hover:bg-muted"
                    :href="previewUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    >Open Preview</a
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    :href="`/templates/${props.template.id}/edit`"
                    >Edit</Link
                >
                <Link
                    class="rounded-md border bg-card px-3 py-2 text-sm text-foreground transition hover:bg-muted"
                    :href="`/templates/${props.template.id}/duplicate`"
                    method="post"
                    as="button"
                    >Duplicate</Link
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
        <div class="rounded-lg border bg-card p-5">
            <div class="text-sm text-muted-foreground">
                {{ props.template.preheader }}
            </div>
            <iframe
                class="mt-4 h-[70vh] min-h-[640px] w-full rounded-md border bg-white"
                :src="previewUrl"
                title="Template email preview"
            ></iframe>
        </div>
        <ConfirmDialog
            v-model="deleteDialogOpen"
            title="Delete template"
            description="Campaigns that already copied this content are not changed, but this template will no longer be available for new campaigns."
            confirm-label="Delete template"
            destructive
            :processing="deleting"
            @confirm="deleteTemplate"
        />
    </main>
</template>
