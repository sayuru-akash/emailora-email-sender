<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Profile settings', href: '/settings/profile' }],
    },
});

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user ?? {});
const form = useForm({
    name: user.value.name ?? '',
    email: user.value.email ?? '',
});
</script>

<template>
    <Head title="Profile settings" />
    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile information"
            description="Update your name and email address"
        />
        <form
            class="space-y-6"
            @submit.prevent="form.patch('/settings/profile')"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    required
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>
            <Button
                :disabled="form.processing"
                data-test="update-profile-button"
                >Save</Button
            >
        </form>
    </div>
</template>
