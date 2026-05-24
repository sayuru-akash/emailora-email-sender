<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/emailora/ConfirmDialog.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

const props = defineProps<{ segment: any }>();
const deleteDialogOpen = ref(false);
const deleting = ref(false);
const filterEntries = computed(() =>
    Object.entries(props.segment.filters ?? {}),
);

function deleteSegment() {
    deleting.value = true;
    router.delete(`/segments/${props.segment.id}`, {
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
        },
    });
}
</script>

<template>
    <Head :title="props.segment.name" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.segment.name"
            :subtitle="props.segment.description"
        >
            <template #actions>
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm"
                    href="/segments"
                    >Back</Link
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    :href="`/segments/${props.segment.id}/edit`"
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
        <section class="rounded-lg border border-border bg-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold">Segment rules</h2>
                <StatusBadge :status="props.segment.status" />
            </div>
            <dl class="mt-4 divide-y divide-border text-sm">
                <div
                    v-for="[key, value] in filterEntries"
                    :key="key"
                    class="grid gap-2 py-3 md:grid-cols-[180px_1fr]"
                >
                    <dt class="font-medium text-muted-foreground">{{ key }}</dt>
                    <dd class="break-words">
                        <span
                            v-if="Array.isArray(value)"
                            class="flex flex-wrap gap-2"
                        >
                            <span
                                v-for="entry in value"
                                :key="String(entry)"
                                class="rounded-md bg-muted px-2 py-1 text-xs"
                                >{{ entry }}</span
                            >
                            <span
                                v-if="value.length === 0"
                                class="text-muted-foreground"
                                >None</span
                            >
                        </span>
                        <span v-else>{{ value ?? '-' }}</span>
                    </dd>
                </div>
            </dl>
        </section>
        <ConfirmDialog
            v-model="deleteDialogOpen"
            title="Delete segment"
            description="This removes the saved segment. Contacts and campaigns are not deleted."
            confirm-label="Delete segment"
            destructive
            :processing="deleting"
            @confirm="deleteSegment"
        />
    </main>
</template>
