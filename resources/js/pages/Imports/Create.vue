<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const form = useForm({ file: null as File | null, duplicate_handling: 'skip', list_ids: [], tag_ids: [] });
</script>
<template><Head title="Import Contacts" /><main class="mx-auto w-full max-w-3xl px-4 py-6 lg:px-8"><PageHeader title="Import Contacts" subtitle="CSV/XLSX upload with duplicate handling" /><form class="space-y-4 rounded-lg border bg-white p-5" @submit.prevent="form.post('/imports/upload')"><input type="file" accept=".csv,.txt,.xlsx" @input="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null" /><select v-model="form.duplicate_handling" class="h-10 w-full rounded-md border px-3"><option value="skip">skip</option><option value="update">update</option><option value="add_to_list_tag">add to list/tag only</option><option value="upsert">create or update</option></select><button class="rounded-md bg-primary px-3 py-2 text-sm text-white">Upload</button></form></main></template>
