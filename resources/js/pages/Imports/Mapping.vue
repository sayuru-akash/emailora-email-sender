<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ import: any }>();
</script>
<template><Head title="Import Mapping" /><main class="mx-auto w-full max-w-4xl px-4 py-6 lg:px-8"><PageHeader title="Import Mapping" :subtitle="props.import.file_name" /><section class="rounded-lg border bg-white p-5"><p class="text-sm text-muted-foreground">Map the email column, then confirm processing.</p><button class="mt-4 rounded-md bg-primary px-3 py-2 text-sm text-white" @click="router.post(`/imports/${props.import.id}/confirm`)">Confirm import</button></section></main></template>
