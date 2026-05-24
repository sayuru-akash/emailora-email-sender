<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import CampaignProgress from '@/components/emailora/CampaignProgress.vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
const props = defineProps<{ campaigns?: any; filters?: any }>();
const activeStatuses = ['queued', 'preparing', 'sending'];
const lastUpdated = ref('now');
const hasActiveCampaigns = computed(() =>
    (props.campaigns?.data ?? []).some((campaign: any) =>
        activeStatuses.includes(campaign.status),
    ),
);
let refreshTimer: ReturnType<typeof setInterval> | null = null;
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

function refreshCampaigns() {
    if (!hasActiveCampaigns.value) {
        return;
    }

    router.reload({
        only: ['campaigns'],
        onSuccess: () => {
            lastUpdated.value = new Date().toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },
    });
}

onMounted(() => {
    refreshCampaigns();
    refreshTimer = setInterval(refreshCampaigns, 2000);
});

onBeforeUnmount(() => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
    }
});
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
        <div
            v-if="hasActiveCampaigns"
            class="mb-4 flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
        >
            <span class="size-1.5 animate-pulse rounded-full bg-primary"></span>
            <span>Live campaign updates refresh every 2 seconds.</span>
            <span v-if="lastUpdated">Checked {{ lastUpdated }}</span>
        </div>
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
                        <th class="px-4 py-3">Progress</th>
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
                            <CampaignProgress
                                :campaign="campaign"
                                compact
                                :live="activeStatuses.includes(campaign.status)"
                                :last-updated="
                                    activeStatuses.includes(campaign.status)
                                        ? lastUpdated
                                        : ''
                                "
                            />
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
