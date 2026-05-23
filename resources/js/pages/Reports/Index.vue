<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatCard from '@/components/emailora/StatCard.vue';
const props = defineProps<{
    stats?: Record<string, number>;
    contactsByStatus?: any[];
    campaignsByStatus?: any[];
}>();
</script>
<template>
    <Head title="Reports" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Reports" subtitle="Email and contact analytics" />
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Contacts"
                :value="props.stats?.contacts ?? 0"
            /><StatCard
                label="Active Contacts"
                :value="props.stats?.active_contacts ?? 0"
            /><StatCard
                label="Messages"
                :value="props.stats?.messages ?? 0"
            /><StatCard
                label="Campaigns"
                :value="props.stats?.campaigns ?? 0"
            />
        </div>
        <section class="mt-6 rounded-lg border bg-card p-5">
            <h2 class="font-medium">Status breakdown</h2>
            <div
                v-for="item in props.contactsByStatus ?? []"
                :key="item.status"
                class="mt-3 flex justify-between text-sm"
            >
                <span>{{ item.status }}</span
                ><span>{{ item.total }}</span>
            </div>
        </section>
    </main>
</template>
