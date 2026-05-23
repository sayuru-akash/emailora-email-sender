<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
const props = defineProps<{ userRecord?: any }>();
const form = useForm({
    name: props.userRecord?.name ?? '',
    email: props.userRecord?.email ?? '',
    role: props.userRecord?.role ?? 'staff',
    status: props.userRecord?.status ?? 'active',
    password: '',
    password_confirmation: '',
});
function submit() {
    if (props.userRecord?.id) {
        form.put(`/users/${props.userRecord.id}`);
    } else {
        form.post('/users');
    }
}
</script>
<template>
    <Head title="User" />
    <main class="mx-auto w-full max-w-3xl px-4 py-6 lg:px-8">
        <PageHeader title="User" />
        <form
            class="space-y-4 rounded-lg border bg-card p-5"
            @submit.prevent="submit"
        >
            <input
                v-model="form.name"
                class="h-10 w-full rounded-md border px-3"
                placeholder="Name"
            /><input
                v-model="form.email"
                class="h-10 w-full rounded-md border px-3"
                placeholder="Email"
            /><select
                v-model="form.role"
                class="h-10 w-full rounded-md border px-3"
            >
                <option>owner</option>
                <option>admin</option>
                <option>manager</option>
                <option>staff</option>
                <option>viewer</option></select
            ><select
                v-model="form.status"
                class="h-10 w-full rounded-md border px-3"
            >
                <option>active</option>
                <option>inactive</option>
                <option>suspended</option></select
            ><input
                v-model="form.password"
                type="password"
                class="h-10 w-full rounded-md border px-3"
                placeholder="Password"
            /><input
                v-model="form.password_confirmation"
                type="password"
                class="h-10 w-full rounded-md border px-3"
                placeholder="Confirm password"
            /><button
                class="rounded-md bg-primary px-3 py-2 text-sm text-white"
            >
                Save
            </button>
        </form>
    </main>
</template>
