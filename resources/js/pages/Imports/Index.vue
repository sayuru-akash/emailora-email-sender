<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
const props = defineProps<{ imports?: any }>();
</script>
<template><Head title="Imports" /><main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8"><PageHeader title="Imports"><template #actions><Link class="rounded-md bg-primary px-3 py-2 text-sm text-white" href="/imports/create">Import Contacts</Link></template></PageHeader><div class="rounded-lg border bg-white"><div v-if="(props.imports?.data ?? []).length"><Link v-for="item in props.imports.data" :key="item.id" :href="`/imports/${item.id}`" class="flex justify-between border-b px-4 py-3 text-sm"><span>{{ item.file_name }}</span><StatusBadge :status="item.status" /></Link></div><EmptyState v-else title="No imports found" /><Pagination :meta="props.imports?.meta" /></div></main></template>
