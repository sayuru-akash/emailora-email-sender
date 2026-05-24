<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import InputError from '@/components/InputError.vue';
const props = defineProps<{ userRecord?: any }>();
const isEditing = Boolean(props.userRecord?.id);
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
    <Head :title="isEditing ? 'Edit User' : 'Add User'" />
    <main class="mx-auto w-full max-w-3xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="isEditing ? 'Edit User' : 'Add User'"
            subtitle="Manage team access, role, and account status."
        >
            <template #actions>
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm"
                    href="/users"
                    >Cancel</Link
                >
            </template>
        </PageHeader>
        <form
            class="space-y-4 rounded-lg border bg-card p-5"
            @submit.prevent="submit"
        >
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Name</span>
                <input
                    v-model="form.name"
                    class="h-10 w-full rounded-md border px-3"
                />
                <InputError :message="form.errors.name" />
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Email</span>
                <input
                    v-model="form.email"
                    class="h-10 w-full rounded-md border px-3"
                    type="email"
                />
                <InputError :message="form.errors.email" />
            </label>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Role</span>
                    <select
                        v-model="form.role"
                        class="h-10 w-full rounded-md border px-3"
                    >
                        <option>owner</option>
                        <option>admin</option>
                        <option>manager</option>
                        <option>staff</option>
                        <option>viewer</option>
                    </select>
                    <InputError :message="form.errors.role" />
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Status</span>
                    <select
                        v-model="form.status"
                        class="h-10 w-full rounded-md border px-3"
                    >
                        <option>active</option>
                        <option>inactive</option>
                        <option>suspended</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </label>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Password</span>
                    <input
                        v-model="form.password"
                        type="password"
                        class="h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.password" />
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Confirm password</span>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        class="h-10 w-full rounded-md border px-3"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm"
                    href="/users"
                    >Cancel</Link
                >
                <button
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                    :disabled="form.processing"
                    type="submit"
                >
                    {{ form.processing ? 'Saving...' : 'Save user' }}
                </button>
            </div>
        </form>
    </main>
</template>
