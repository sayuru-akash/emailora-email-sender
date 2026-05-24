<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import CampaignProgress from '@/components/emailora/CampaignProgress.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatCard from '@/components/emailora/StatCard.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

const props = defineProps<{
    stats?: Record<string, number>;
    recentCampaigns?: any[];
    recentImports?: any[];
    contactsByStatus?: any[];
}>();
const activeCampaignStatuses = ['queued', 'preparing', 'sending'];
const activeImportStatuses = ['uploaded', 'mapped', 'processing'];
const hasLiveWork = computed(
    () =>
        (props.recentCampaigns ?? []).some((campaign) =>
            activeCampaignStatuses.includes(campaign.status),
        ) ||
        (props.recentImports ?? []).some((item) =>
            activeImportStatuses.includes(item.status),
        ),
);
const lastUpdated = ref('now');
let refreshTimer: ReturnType<typeof setInterval> | null = null;

function refreshDashboard() {
    if (!hasLiveWork.value) {
        return;
    }

    router.reload({
        only: ['stats', 'recentCampaigns', 'recentImports'],
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
    refreshDashboard();
    refreshTimer = setInterval(refreshDashboard, 2000);
});

onBeforeUnmount(() => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
    }
});
</script>

<template>
    <Head title="Dashboard" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Dashboard" subtitle="Email operations overview">
            <template #actions>
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                    href="/campaigns/builder"
                >
                    New Campaign
                </Link>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <StatCard
                label="Total Contacts"
                :value="props.stats?.total_contacts ?? 0"
            />
            <StatCard
                label="Active Contacts"
                :value="props.stats?.active_contacts ?? 0"
                tone="success"
            />
            <StatCard
                label="Sent This Month"
                :value="props.stats?.sent_this_month ?? 0"
            />
            <StatCard
                label="Failed/Bounced"
                :value="props.stats?.failed_bounced ?? 0"
                tone="danger"
            />
            <StatCard
                label="Active Campaigns"
                :value="props.stats?.active_campaigns ?? 0"
                tone="warning"
            />
            <StatCard
                label="Scheduled"
                :value="props.stats?.scheduled_campaigns ?? 0"
            />
        </div>
        <div
            v-if="hasLiveWork"
            class="mt-4 flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
        >
            <span class="size-1.5 animate-pulse rounded-full bg-primary"></span>
            <span>Live campaign/import updates refresh every 2 seconds.</span>
            <span v-if="lastUpdated">Checked {{ lastUpdated }}</span>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <section
                class="rounded-lg border border-border bg-card lg:col-span-2"
            >
                <div class="border-b border-border px-4 py-3 font-medium">
                    Recent campaigns
                </div>
                <div class="divide-y divide-border">
                    <Link
                        v-for="campaign in props.recentCampaigns ?? []"
                        :key="campaign.id"
                        :href="`/campaigns/${campaign.id}`"
                        class="grid min-h-24 grid-cols-[minmax(0,1fr)_104px] gap-x-4 gap-y-3 px-4 py-3 text-sm transition hover:bg-muted/50"
                    >
                        <div class="min-w-0 self-start">
                            <span
                                class="block truncate leading-5 font-medium"
                                >{{ campaign.name }}</span
                            >
                            <span
                                class="mt-0.5 block h-4 truncate text-xs leading-4 text-muted-foreground"
                            >
                                {{ campaign.subject || 'No subject' }}
                            </span>
                        </div>
                        <div class="flex justify-end self-start">
                            <StatusBadge :status="campaign.status" />
                        </div>
                        <CampaignProgress
                            class="col-span-2"
                            :campaign="campaign"
                            compact
                            :live="
                                activeCampaignStatuses.includes(campaign.status)
                            "
                            :last-updated="
                                activeCampaignStatuses.includes(campaign.status)
                                    ? lastUpdated
                                    : ''
                            "
                        />
                    </Link>
                    <div
                        v-if="!(props.recentCampaigns ?? []).length"
                        class="px-4 py-8 text-sm text-muted-foreground"
                    >
                        No campaign activity yet
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-border bg-card">
                <div class="border-b border-border px-4 py-3 font-medium">
                    Contacts by status
                </div>
                <div class="space-y-3 p-4">
                    <div
                        v-for="item in props.contactsByStatus ?? []"
                        :key="item.status"
                        class="flex items-center justify-between text-sm"
                    >
                        <StatusBadge :status="item.status" />
                        <span class="font-medium">{{ item.total }}</span>
                    </div>
                    <div
                        v-if="!(props.contactsByStatus ?? []).length"
                        class="text-sm text-muted-foreground"
                    >
                        No contacts found
                    </div>
                </div>
            </section>
        </div>

        <section class="mt-6 rounded-lg border border-border bg-card">
            <div class="border-b border-border px-4 py-3 font-medium">
                Recent imports
            </div>
            <div class="divide-y divide-border">
                <Link
                    v-for="item in props.recentImports ?? []"
                    :key="item.id"
                    :href="`/imports/${item.id}`"
                    class="flex items-center justify-between gap-4 px-4 py-3 text-sm hover:bg-muted"
                >
                    <span class="min-w-0">
                        <span class="block truncate">{{ item.file_name }}</span>
                        <span class="text-xs text-muted-foreground">
                            {{ item.processed_rows }} / {{ item.total_rows }}
                            rows
                        </span>
                    </span>
                    <StatusBadge :status="item.status" />
                </Link>
                <div
                    v-if="!(props.recentImports ?? []).length"
                    class="px-4 py-8 text-sm text-muted-foreground"
                >
                    No imports yet
                </div>
            </div>
        </section>
    </main>
</template>
