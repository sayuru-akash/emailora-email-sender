<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BarChart3, Download, TrendingUp } from 'lucide-vue-next';
import PageHeader from '@/components/emailora/PageHeader.vue';
import RowAction from '@/components/emailora/RowAction.vue';
import StatCard from '@/components/emailora/StatCard.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';

type Breakdown = Record<string, string | number>;
type TimelineRow = {
    date: string;
    total: number;
    sent: number;
    failed: number;
};
type CampaignRow = {
    id: number;
    name: string;
    subject: string;
    status: string;
    total_recipients: number;
    sent_count: number;
    delivery_rate?: number;
    open_rate?: number;
    click_rate?: number;
    failed_count: number;
    created_at?: string | null;
    scheduled_at?: string | null;
    completed_at?: string | null;
};

const props = defineProps<{
    filters?: { period?: string };
    stats?: Record<string, number>;
    rates?: Record<string, number>;
    contactsByStatus?: Breakdown[];
    contactsBySource?: Breakdown[];
    campaignsByStatus?: Breakdown[];
    messagesByStatus?: Breakdown[];
    eventsByType?: Breakdown[];
    messageTimeline?: TimelineRow[];
    topCampaigns?: CampaignRow[];
    recentCampaigns?: CampaignRow[];
}>();

const periodOptions = [
    { value: '7', label: '7 days' },
    { value: '30', label: '30 days' },
    { value: '90', label: '90 days' },
    { value: 'all', label: 'All time' },
];

function changePeriod(event: Event) {
    router.get(
        '/reports',
        { period: (event.target as HTMLSelectElement).value },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function valueOf(item: Breakdown, key: string) {
    return String(item[key] ?? 'unknown');
}

function totalOf(item: Breakdown) {
    return Number(item.total ?? 0);
}

function maxTimelineValue() {
    return Math.max(
        ...(props.messageTimeline ?? []).map((row) => row.total),
        1,
    );
}

function barWidth(value: number, total: number) {
    if (total < 1) {
        return '0%';
    }

    return `${Math.max(4, Math.round((value / total) * 100))}%`;
}
</script>

<template>
    <Head title="Reports" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Reports"
            subtitle="Campaign, delivery, contact, and provider performance"
        >
            <template #actions>
                <select
                    class="h-10 rounded-md border border-border bg-background px-3 text-sm"
                    :value="props.filters?.period ?? '30'"
                    @change="changePeriod"
                >
                    <option
                        v-for="option in periodOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Campaigns" :value="props.stats?.campaigns ?? 0" />
            <StatCard label="Messages" :value="props.stats?.messages ?? 0" />
            <StatCard
                label="Active contacts"
                :value="props.stats?.active_contacts ?? 0"
            />
            <StatCard
                label="Unsubscribed"
                :value="props.stats?.unsubscribed_contacts ?? 0"
                tone="warning"
            />
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Delivery rate"
                :value="`${props.rates?.delivery ?? 0}%`"
            />
            <StatCard label="Open rate" :value="`${props.rates?.open ?? 0}%`" />
            <StatCard
                label="Click rate"
                :value="`${props.rates?.click ?? 0}%`"
            />
            <StatCard
                label="Failure rate"
                :value="`${props.rates?.failure ?? 0}%`"
                tone="danger"
            />
        </div>

        <section class="mt-6 rounded-lg border border-border bg-card p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">Message volume</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Daily sent and failed message activity.
                    </p>
                </div>
                <BarChart3 class="h-5 w-5 text-muted-foreground" />
            </div>
            <div v-if="(props.messageTimeline ?? []).length" class="space-y-3">
                <div
                    v-for="row in props.messageTimeline ?? []"
                    :key="row.date"
                    class="grid gap-2 md:grid-cols-[120px_1fr_80px] md:items-center"
                >
                    <span class="text-sm text-muted-foreground">{{
                        row.date
                    }}</span>
                    <div class="h-8 overflow-hidden rounded-md bg-muted">
                        <div
                            class="flex h-full"
                            :style="{
                                width: barWidth(row.total, maxTimelineValue()),
                            }"
                        >
                            <div
                                class="h-full bg-primary"
                                :style="{
                                    width: barWidth(row.sent, row.total),
                                }"
                            ></div>
                            <div
                                class="h-full bg-destructive"
                                :style="{
                                    width: barWidth(row.failed, row.total),
                                }"
                            ></div>
                        </div>
                    </div>
                    <span class="text-right text-sm font-medium">{{
                        row.total
                    }}</span>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                No message activity for this period.
            </p>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-border bg-card p-5">
                <h2 class="text-base font-semibold">Message status</h2>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="item in props.messagesByStatus ?? []"
                        :key="valueOf(item, 'status')"
                        class="flex items-center justify-between gap-4 text-sm"
                    >
                        <StatusBadge :status="valueOf(item, 'status')" />
                        <span class="font-medium">{{ totalOf(item) }}</span>
                    </div>
                    <p
                        v-if="!(props.messagesByStatus ?? []).length"
                        class="text-sm text-muted-foreground"
                    >
                        No messages yet.
                    </p>
                </div>
            </section>

            <section class="rounded-lg border border-border bg-card p-5">
                <h2 class="text-base font-semibold">Provider events</h2>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="item in props.eventsByType ?? []"
                        :key="valueOf(item, 'event_type')"
                        class="flex items-center justify-between gap-4 text-sm"
                    >
                        <span class="truncate">{{
                            valueOf(item, 'event_type')
                        }}</span>
                        <span class="font-medium">{{ totalOf(item) }}</span>
                    </div>
                    <p
                        v-if="!(props.eventsByType ?? []).length"
                        class="text-sm text-muted-foreground"
                    >
                        No provider events for this period.
                    </p>
                </div>
            </section>

            <section class="rounded-lg border border-border bg-card p-5">
                <h2 class="text-base font-semibold">Campaign status</h2>
                <div class="mt-4 space-y-3">
                    <div
                        v-for="item in props.campaignsByStatus ?? []"
                        :key="valueOf(item, 'status')"
                        class="flex items-center justify-between gap-4 text-sm"
                    >
                        <StatusBadge :status="valueOf(item, 'status')" />
                        <span class="font-medium">{{ totalOf(item) }}</span>
                    </div>
                    <p
                        v-if="!(props.campaignsByStatus ?? []).length"
                        class="text-sm text-muted-foreground"
                    >
                        No campaigns for this period.
                    </p>
                </div>
            </section>

            <section class="rounded-lg border border-border bg-card p-5">
                <h2 class="text-base font-semibold">Contacts</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground">
                            By status
                        </h3>
                        <div class="mt-3 space-y-2">
                            <div
                                v-for="item in props.contactsByStatus ?? []"
                                :key="valueOf(item, 'status')"
                                class="flex items-center justify-between gap-4 text-sm"
                            >
                                <StatusBadge
                                    :status="valueOf(item, 'status')"
                                />
                                <span class="font-medium">{{
                                    totalOf(item)
                                }}</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground">
                            By source
                        </h3>
                        <div class="mt-3 space-y-2">
                            <div
                                v-for="item in props.contactsBySource ?? []"
                                :key="valueOf(item, 'source')"
                                class="flex items-center justify-between gap-4 text-sm"
                            >
                                <span class="truncate">{{
                                    valueOf(item, 'source')
                                }}</span>
                                <span class="font-medium">{{
                                    totalOf(item)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section class="mt-6">
            <PageHeader
                title="Top Campaigns"
                subtitle="Ranked by sent volume in the selected period"
            >
                <template #actions>
                    <TrendingUp class="h-5 w-5 text-muted-foreground" />
                </template>
            </PageHeader>
            <TableShell min-width="1040px">
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Campaign</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Recipients</th>
                            <th class="px-4 py-3">Sent</th>
                            <th class="px-4 py-3">Delivery</th>
                            <th class="px-4 py-3">Open</th>
                            <th class="px-4 py-3">Click</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="campaign in props.topCampaigns ?? []"
                            :key="campaign.id"
                        >
                            <td class="max-w-80 px-4 py-3">
                                <div class="truncate font-medium">
                                    {{ campaign.name }}
                                </div>
                                <div
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ campaign.subject }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="campaign.status" />
                            </td>
                            <td class="px-4 py-3">
                                {{ campaign.total_recipients }}
                            </td>
                            <td class="px-4 py-3">{{ campaign.sent_count }}</td>
                            <td class="px-4 py-3">
                                {{ campaign.delivery_rate }}%
                            </td>
                            <td class="px-4 py-3">{{ campaign.open_rate }}%</td>
                            <td class="px-4 py-3">
                                {{ campaign.click_rate }}%
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <RowAction
                                        :href="`/reports/campaigns/${campaign.id}`"
                                        icon="report"
                                        label="Report"
                                    />
                                    <a
                                        class="inline-flex h-8 items-center gap-1.5 rounded-md border border-border bg-background px-2.5 text-xs font-medium text-foreground transition hover:border-primary/40 hover:bg-primary/10 hover:text-primary"
                                        :href="`/reports/campaigns/${campaign.id}/export`"
                                    >
                                        <Download class="size-3.5" />
                                        CSV
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableShell>
        </section>

        <section class="mt-6">
            <PageHeader
                title="Recent Campaigns"
                subtitle="Latest campaign outcomes and current work"
            />
            <TableShell min-width="980px">
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Campaign</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3">Recipients</th>
                            <th class="px-4 py-3">Sent</th>
                            <th class="px-4 py-3">Failed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="campaign in props.recentCampaigns ?? []"
                            :key="campaign.id"
                        >
                            <td class="max-w-96 px-4 py-3">
                                <Link
                                    class="truncate font-medium text-primary"
                                    :href="`/campaigns/${campaign.id}`"
                                    >{{ campaign.name }}</Link
                                >
                                <div
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ campaign.subject }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="campaign.status" />
                            </td>
                            <td
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ campaign.created_at }}
                            </td>
                            <td class="px-4 py-3">
                                {{ campaign.total_recipients }}
                            </td>
                            <td class="px-4 py-3">{{ campaign.sent_count }}</td>
                            <td class="px-4 py-3">
                                {{ campaign.failed_count }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableShell>
        </section>
    </main>
</template>
