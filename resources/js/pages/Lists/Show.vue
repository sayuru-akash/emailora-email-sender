<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

const props = defineProps<{ list: any; contacts?: any }>();
</script>

<template>
    <Head :title="props.list.name" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader :title="props.list.name" :subtitle="props.list.description" />
        <div class="overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <tbody class="divide-y">
                    <tr v-for="contact in props.contacts?.data ?? []" :key="contact.id">
                        <td class="px-4 py-3 font-medium">{{ contact.full_name || contact.email }}</td>
                        <td class="px-4 py-3">{{ contact.email }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="contact.status" /></td>
                    </tr>
                </tbody>
            </table>
            <Pagination :meta="props.contacts?.meta" />
        </div>
    </main>
</template>
