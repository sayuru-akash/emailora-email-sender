<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';

const props = defineProps<{ tags?: any }>();
const form = useForm({ name: '', description: '', color: '#4f46e5' });
</script>

<template>
    <Head title="Tags" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader title="Tags" :subtitle="`${props.tags?.meta?.total ?? 0} tags`" />
        <form class="mb-4 flex flex-col gap-2 rounded-lg border bg-white p-3 md:flex-row" @submit.prevent="form.post('/tags', { preserveScroll: true, onSuccess: () => form.reset() })">
            <input v-model="form.name" class="h-9 rounded-md border px-3 text-sm md:w-72" placeholder="Tag name" />
            <input v-model="form.description" class="h-9 flex-1 rounded-md border px-3 text-sm" placeholder="Description" />
            <button class="rounded-md bg-primary px-3 py-2 text-sm text-white">Save</button>
        </form>
        <div class="overflow-hidden rounded-lg border bg-white">
            <table v-if="(props.tags?.data ?? []).length" class="w-full text-sm">
                <tbody class="divide-y">
                    <tr v-for="tag in props.tags.data" :key="tag.id">
                        <td class="px-4 py-3 font-medium"><Link :href="`/tags/${tag.id}`">{{ tag.name }}</Link></td>
                        <td class="px-4 py-3 text-muted-foreground">{{ tag.contacts_count ?? 0 }} contacts</td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No tags found" />
            <Pagination :meta="props.tags?.meta" />
        </div>
    </main>
</template>
