<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
const props = defineProps<{ campaigns?: any }>();
</script>
<template>
    <Head title="Campaigns" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Campaigns" :subtitle="`${props.campaigns?.meta?.total ?? 0} campaigns`">
            <template #actions><Link class="rounded-md bg-primary px-3 py-2 text-sm text-white" href="/campaigns/builder">New Campaign</Link></template>
        </PageHeader>
        <div class="overflow-hidden rounded-lg border bg-white">
            <table v-if="(props.campaigns?.data ?? []).length" class="w-full min-w-[900px] text-sm">
                <tbody class="divide-y">
                    <tr v-for="campaign in props.campaigns.data" :key="campaign.id">
                        <td class="px-4 py-3 font-medium"><Link :href="`/campaigns/${campaign.id}`">{{ campaign.name }}</Link></td>
                        <td class="px-4 py-3"><StatusBadge :status="campaign.status" /></td>
                        <td class="px-4 py-3">{{ campaign.total_recipients }} recipients</td>
                        <td class="px-4 py-3">{{ campaign.sent_count }} sent</td>
                        <td class="px-4 py-3">{{ campaign.failed_count }} failed</td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No campaign activity yet" />
            <Pagination :meta="props.campaigns?.meta" />
        </div>
    </main>
</template>
