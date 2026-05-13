<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ userRecord: any }>();
</script>
<template><Head :title="props.userRecord.name" /><main class="mx-auto w-full max-w-3xl px-4 py-6 lg:px-8"><PageHeader :title="props.userRecord.name" :subtitle="props.userRecord.email"><template #actions><Link class="rounded-md bg-primary px-3 py-2 text-sm text-white" :href="`/users/${props.userRecord.id}/edit`">Edit</Link></template></PageHeader><section class="rounded-lg border bg-white p-5 text-sm">{{ props.userRecord.role }} · {{ props.userRecord.status }}</section></main></template>
