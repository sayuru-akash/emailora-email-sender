<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ segment: any }>();
</script>
<template>
    <Head :title="props.segment.name" />
    <main class="mx-auto w-full max-w-4xl px-4 py-6 lg:px-8">
        <PageHeader :title="props.segment.name" />
        <pre class="rounded-lg border bg-card p-5 text-sm">{{
            props.segment.filters
        }}</pre>
    </main>
</template>
