<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
const props = defineProps<{ campaign: any; recipients?: any }>();
</script>
<template>
    <Head title="Recipients" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader :title="`${props.campaign.name} Recipients`" />
        <div class="rounded-lg border bg-card">
            <div
                v-for="r in props.recipients?.data ?? []"
                :key="r.id"
                class="flex justify-between border-b px-4 py-3 text-sm"
            >
                <span>{{ r.email_normalized }}</span
                ><StatusBadge :status="r.status" />
            </div>
            <Pagination :meta="props.recipients?.meta" />
        </div>
    </main>
</template>
