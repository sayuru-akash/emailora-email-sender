<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

const props = defineProps<{ lists?: any }>();
const form = useForm({
    name: '',
    description: '',
    status: 'active',
    color: '#4f46e5',
});
</script>

<template>
    <Head title="Lists" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Lists"
            :subtitle="`${props.lists?.meta?.total ?? 0} lists`"
        />
        <form
            class="mb-4 flex flex-col gap-2 rounded-lg border bg-card p-3 md:flex-row"
            @submit.prevent="
                form.post('/lists', {
                    preserveScroll: true,
                    onSuccess: () => form.reset(),
                })
            "
        >
            <input
                v-model="form.name"
                class="h-9 rounded-md border px-3 text-sm md:w-72"
                placeholder="List name"
            />
            <input
                v-model="form.description"
                class="h-9 flex-1 rounded-md border px-3 text-sm"
                placeholder="Description"
            />
            <button class="rounded-md bg-primary px-3 py-2 text-sm text-white">
                Save
            </button>
        </form>
        <div class="overflow-hidden rounded-lg border bg-card">
            <table
                v-if="(props.lists?.data ?? []).length"
                class="w-full text-sm"
            >
                <tbody class="divide-y">
                    <tr v-for="list in props.lists.data" :key="list.id">
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/lists/${list.id}`">{{
                                list.name
                            }}</Link>
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="list.status" />
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ list.contacts_count ?? 0 }} contacts
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No lists found" />
            <Pagination :meta="props.lists?.meta" />
        </div>
    </main>
</template>
