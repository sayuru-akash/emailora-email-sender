<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatCard from '@/components/emailora/StatCard.vue';
const props = defineProps<{ campaign: any; breakdown?: any[] }>();
</script>
<template>
    <Head title="Campaign Report" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader :title="`${props.campaign.name} Report`">
            <template #actions>
                <Link
                    class="rounded-md border bg-card px-3 py-2 text-sm transition hover:bg-muted"
                    :href="`/campaigns/${props.campaign.id}`"
                    >Campaign</Link
                >
                <Link
                    class="rounded-md border bg-card px-3 py-2 text-sm transition hover:bg-muted"
                    :href="`/campaigns/${props.campaign.id}/recipients`"
                    >Recipients</Link
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground"
                    :href="`/reports/campaigns/${props.campaign.id}/export`"
                    >Export CSV</Link
                >
            </template>
        </PageHeader>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard
                label="Sent"
                :value="props.campaign.sent_count"
            /><StatCard
                label="Delivered"
                :value="props.campaign.delivered_count"
            /><StatCard
                label="Opened"
                :value="props.campaign.opened_count"
            /><StatCard
                label="Clicked"
                :value="props.campaign.clicked_count"
            /><StatCard
                label="Failed"
                :value="props.campaign.failed_count"
                tone="danger"
            />
        </div>
        <section class="mt-6 rounded-lg border bg-card p-5">
            <div
                v-for="item in props.breakdown ?? []"
                :key="item.status"
                class="flex justify-between border-b py-2 text-sm"
            >
                <span>{{ item.status }}</span
                ><span>{{ item.total }}</span>
            </div>
        </section>
    </main>
</template>
