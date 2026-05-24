<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ campaigns?: any; filters?: any }>();
const statuses = [
    '',
    'draft',
    'scheduled',
    'queued',
    'preparing',
    'sending',
    'paused',
    'completed',
    'failed',
    'cancelled',
];

function applyFilters(updates: Record<string, string>) {
    router.get(
        '/campaigns',
        {
            search: props.filters?.search ?? '',
            status: props.filters?.status ?? '',
            provider: props.filters?.provider ?? '',
            ...updates,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}
</script>
<template>
    <Head title="Campaigns" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Campaigns"
            :subtitle="`${props.campaigns?.meta?.total ?? 0} campaigns`"
        >
            <template #actions
                ><Link
                    class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground"
                    href="/campaigns/builder"
                    >New Campaign</Link
                ></template
            >
        </PageHeader>
        <section
            class="mb-4 grid gap-3 rounded-lg border bg-card p-4 md:grid-cols-[minmax(0,1fr)_180px_180px]"
        >
            <input
                class="h-10 rounded-md border bg-background px-3 text-sm"
                :value="props.filters?.search ?? ''"
                placeholder="Search campaigns"
                @change="
                    applyFilters({
                        search: ($event.target as HTMLInputElement).value,
                    })
                "
            />
            <select
                class="h-10 rounded-md border bg-background px-3 text-sm capitalize"
                :value="props.filters?.status ?? ''"
                @change="
                    applyFilters({
                        status: ($event.target as HTMLSelectElement).value,
                    })
                "
            >
                <option
                    v-for="status in statuses"
                    :key="status || 'all'"
                    :value="status"
                >
                    {{ status || 'all statuses' }}
                </option>
            </select>
            <select
                class="h-10 rounded-md border bg-background px-3 text-sm capitalize"
                :value="props.filters?.provider ?? ''"
                @change="
                    applyFilters({
                        provider: ($event.target as HTMLSelectElement).value,
                    })
                "
            >
                <option value="">all providers</option>
                <option value="auto">auto</option>
                <option value="resend">resend</option>
                <option value="brevo">brevo</option>
            </select>
        </section>
        <TableShell min-width="900px">
            <table
                v-if="(props.campaigns?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Campaign</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Recipients</th>
                        <th class="px-4 py-3">Sent</th>
                        <th class="px-4 py-3">Failed</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="campaign in props.campaigns.data"
                        :key="campaign.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/campaigns/${campaign.id}`">{{
                                campaign.name
                            }}</Link>
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="campaign.status" />
                        </td>
                        <td class="px-4 py-3">
                            {{
                                campaign.display_recipient_count ??
                                campaign.total_recipients
                            }}
                            recipients
                        </td>
                        <td class="px-4 py-3">
                            {{ campaign.sent_count }} sent
                        </td>
                        <td class="px-4 py-3">
                            {{ campaign.failed_count }} failed
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <RowAction
                                    :href="`/campaigns/${campaign.id}`"
                                    icon="view"
                                    label="Open"
                                />
                                <RowAction
                                    v-if="
                                        ['draft', 'scheduled'].includes(
                                            campaign.status,
                                        )
                                    "
                                    :href="`/campaigns/${campaign.id}/edit`"
                                    icon="edit"
                                    label="Edit"
                                />
                                <RowAction
                                    :href="`/campaigns/${campaign.id}/duplicate`"
                                    icon="duplicate"
                                    label="Duplicate"
                                    method="post"
                                />
                                <RowAction
                                    :href="`/campaigns/${campaign.id}/recipients`"
                                    icon="recipients"
                                    label="Recipients"
                                />
                                <RowAction
                                    :href="`/campaigns/${campaign.id}/report`"
                                    icon="report"
                                    label="Report"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No campaign activity yet" />
            <template #footer>
                <Pagination :meta="props.campaigns?.meta" />
            </template>
        </TableShell>
    </main>
</template>
