<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
const props = defineProps<{ activities?: any }>();
</script>
<template><Head title="Activity Logs" /><main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8"><PageHeader title="Activity Logs" subtitle="Provider and system events" /><div class="rounded-lg border bg-white"><div v-for="item in props.activities?.data ?? []" :key="item.id" class="border-b px-4 py-3 text-sm"><div class="font-medium">{{ item.event_type ?? 'event' }}</div><div class="text-muted-foreground">{{ item.provider }} · {{ item.email_normalized ?? '-' }}</div></div><Pagination :meta="props.activities?.meta" /></div></main></template>
