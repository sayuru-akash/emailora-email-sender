<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
const props = defineProps<{ segments?: any }>();
</script>
<template><Head title="Segments" /><main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8"><PageHeader title="Segments" /><div class="rounded-lg border bg-white"><div v-for="segment in props.segments?.data ?? []" :key="segment.id" class="border-b px-4 py-3 text-sm">{{ segment.name }}</div><Pagination :meta="props.segments?.meta" /></div></main></template>
