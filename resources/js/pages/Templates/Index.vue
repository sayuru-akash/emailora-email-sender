<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
const props = defineProps<{ templates?: any }>();
</script>
<template>
    <Head title="Email Templates" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Email Templates"
            :subtitle="`${props.templates?.meta?.total ?? 0} templates`"
        >
            <template #actions
                ><Link
                    class="rounded-md bg-primary px-3 py-2 text-sm text-white"
                    href="/templates/create"
                    >Create Template</Link
                ></template
            >
        </PageHeader>
        <div class="overflow-hidden rounded-lg border bg-card">
            <table
                v-if="(props.templates?.data ?? []).length"
                class="w-full text-sm"
            >
                <tbody class="divide-y">
                    <tr
                        v-for="template in props.templates.data"
                        :key="template.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link :href="`/templates/${template.id}`">{{
                                template.name
                            }}</Link>
                        </td>
                        <td class="px-4 py-3">{{ template.subject }}</td>
                        <td class="px-4 py-3">
                            {{ template.category ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="template.status" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="No templates found" />
            <Pagination :meta="props.templates?.meta" />
        </div>
    </main>
</template>
