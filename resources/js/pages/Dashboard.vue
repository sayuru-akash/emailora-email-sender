<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatCard from '@/components/emailora/StatCard.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

const props = defineProps<{
    stats?: Record<string, number>;
    recentCampaigns?: any[];
    recentImports?: any[];
    contactsByStatus?: any[];
}>();
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
                        class="flex items-center justify-between px-4 py-3 text-sm hover:bg-muted"
                    >
                        <span class="min-w-0">
                            <span class="block truncate font-medium">{{
                                campaign.name
                            }}</span>
                            <span class="text-muted-foreground"
                                >{{ campaign.sent_count }} sent /
                                {{ campaign.failed_count }} failed</span
                            >
                        </span>
                        <StatusBadge :status="campaign.status" />
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
                    class="flex items-center justify-between px-4 py-3 text-sm hover:bg-muted"
                >
                    <span class="truncate">{{ item.file_name }}</span>
                    <span class="text-muted-foreground"
                        >{{ item.processed_rows }} / {{ item.total_rows }}</span
                    >
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
