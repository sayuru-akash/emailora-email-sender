<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
const props = defineProps<{ tag: any; contacts?: any }>();
</script>
<template>
    <Head :title="props.tag.name" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader :title="props.tag.name" :subtitle="props.tag.description" />
        <div class="rounded-lg border bg-white">
            <div v-for="contact in props.contacts?.data ?? []" :key="contact.id" class="border-b px-4 py-3 text-sm">{{ contact.full_name || contact.email }} · {{ contact.email }}</div>
            <Pagination :meta="props.contacts?.meta" />
        </div>
    </main>
</template>
