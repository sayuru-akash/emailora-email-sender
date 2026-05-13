<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
const props = defineProps<{ users?: any }>();
</script>
<template><Head title="Users" /><main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8"><PageHeader title="Users"><template #actions><Link class="rounded-md bg-primary px-3 py-2 text-sm text-white" href="/users/create">Add User</Link></template></PageHeader><div class="rounded-lg border bg-white"><div v-for="user in props.users?.data ?? []" :key="user.id" class="flex justify-between border-b px-4 py-3 text-sm"><Link :href="`/users/${user.id}`" class="font-medium">{{ user.name }} <span class="text-muted-foreground">{{ user.email }}</span></Link><div class="flex gap-3"><span>{{ user.role }}</span><StatusBadge :status="user.status" /></div></div><Pagination :meta="props.users?.meta" /></div></main></template>
