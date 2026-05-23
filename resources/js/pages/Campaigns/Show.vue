<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatCard from '@/components/emailora/StatCard.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
const props = defineProps<{ campaign: any; recipients?: any[] }>();
</script>
<template>
    <Head :title="props.campaign.name" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.campaign.name"
            :subtitle="props.campaign.subject"
        >
            <template #actions>
                <button
                    v-if="props.campaign.status === 'draft'"
                    class="rounded-md bg-primary px-3 py-2 text-sm text-white"
                    @click="router.post(`/campaigns/${props.campaign.id}/send`)"
                >
                    Send Now
                </button>
                <button
                    v-if="
                        ['queued', 'preparing', 'sending'].includes(
                            props.campaign.status,
                        )
                    "
                    class="rounded-md border px-3 py-2 text-sm"
                    @click="
                        router.post(`/campaigns/${props.campaign.id}/pause`)
                    "
                >
                    Pause
                </button>
                <button
                    v-if="props.campaign.status === 'paused'"
                    class="rounded-md border px-3 py-2 text-sm"
                    @click="
                        router.post(`/campaigns/${props.campaign.id}/resume`)
                    "
                >
                    Resume
                </button>
                <Link
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="`/campaigns/${props.campaign.id}/report`"
                    >View Report</Link
                >
            </template>
        </PageHeader>
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <StatCard
                label="Recipients"
                :value="props.campaign.total_recipients"
            />
            <StatCard
                label="Sent"
                :value="props.campaign.sent_count"
                tone="success"
            />
            <StatCard
                label="Delivered"
                :value="props.campaign.delivered_count"
            />
            <StatCard label="Opened" :value="props.campaign.opened_count" />
            <StatCard label="Clicked" :value="props.campaign.clicked_count" />
            <StatCard
                label="Failed"
                :value="props.campaign.failed_count"
                tone="danger"
            />
        </div>
        <section class="rounded-lg border bg-card p-5">
            <StatusBadge :status="props.campaign.status" /><iframe
                class="mt-4 h-96 w-full rounded border"
                :srcdoc="props.campaign.html_body"
            ></iframe>
        </section>
    </main>
</template>
