<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{
    campaign: any;
    recipients?: any;
    mode?: 'target_audience' | 'prepared_recipients';
}>();
const processingAction = ref<string | null>(null);
const statuses = [
    { label: 'All', value: '' },
    { label: 'Pending', value: 'pending' },
    { label: 'Queued', value: 'queued' },
    { label: 'Sent', value: 'sent' },
    { label: 'Delivered', value: 'delivered' },
    { label: 'Opened', value: 'opened' },
    { label: 'Clicked', value: 'clicked' },
    { label: 'Failed', value: 'failed' },
    { label: 'Skipped', value: 'skipped' },
];

function currentStatus() {
    return new URLSearchParams(window.location.search).get('status') ?? '';
}

function filterStatus(event: Event) {
    const value = (event.target as HTMLSelectElement).value;

    router.get(
        `/campaigns/${props.campaign.id}/recipients`,
        { status: value || undefined },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function resendFailed() {
    if (!window.confirm('Retry all failed recipients?')) {
        return;
    }

    processingAction.value = 'resend-all';
    router.post(
        `/campaigns/${props.campaign.id}/resend-failed`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingAction.value = null;
            },
        },
    );
}

function retryRecipient(id: number) {
    processingAction.value = `retry-${id}`;
    router.post(
        `/campaigns/${props.campaign.id}/recipients/${id}/resend`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingAction.value = null;
            },
        },
    );
}
</script>
<template>
    <Head title="Recipients" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader :title="`${props.campaign.name} Recipients`" />
        <div
            v-if="props.mode === 'target_audience'"
            class="mb-4 rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm"
        >
            This draft has not prepared campaign recipients yet. The table below
            shows the current target audience that will be prepared when you
            send or schedule the campaign.
        </div>
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <Link
                class="rounded-md border bg-card px-3 py-2 text-sm transition hover:bg-muted"
                :href="`/campaigns/${props.campaign.id}`"
            >
                Campaign
            </Link>
            <button
                v-if="props.campaign.failed_count > 0"
                class="rounded-md border bg-card px-3 py-2 text-sm transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="Boolean(processingAction)"
                @click="resendFailed"
            >
                {{
                    processingAction === 'resend-all'
                        ? 'Queueing...'
                        : 'Resend Failed'
                }}
            </button>
            <label
                v-if="props.mode !== 'target_audience'"
                class="ml-auto flex items-center gap-2 text-sm"
            >
                <span class="text-muted-foreground">Status</span>
                <select
                    class="h-9 rounded-md border bg-background px-2"
                    :value="currentStatus()"
                    @change="filterStatus"
                >
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>
            </label>
        </div>
        <TableShell min-width="900px">
            <table
                v-if="(props.recipients?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Provider Message</th>
                        <th class="px-4 py-3">Error</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="r in props.recipients?.data ?? []" :key="r.id">
                        <td class="px-4 py-3 font-medium">
                            {{ r.email_normalized }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ r.contact?.full_name ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="r.status" />
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ r.provider_message_id ?? '-' }}
                        </td>
                        <td class="max-w-80 truncate px-4 py-3 text-red-600">
                            {{ r.error_message ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="r.status === 'failed'"
                                class="rounded-md border px-2.5 py-1.5 text-xs transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="Boolean(processingAction)"
                                @click="retryRecipient(r.id)"
                            >
                                {{
                                    processingAction === `retry-${r.id}`
                                        ? 'Queueing...'
                                        : 'Retry'
                                }}
                            </button>
                            <span v-else class="text-xs text-muted-foreground"
                                >-</span
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No recipients found" />
            <template #footer>
                <Pagination :meta="props.recipients?.meta" />
            </template>
        </TableShell>
    </main>
</template>
