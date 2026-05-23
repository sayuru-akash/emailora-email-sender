<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
const props = defineProps<{ token: string }>();
const page = usePage();
</script>
<template>
    <Head title="Unsubscribe" />
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <section class="w-full rounded-lg border bg-card p-6 text-center">
            <h1 class="text-xl font-semibold">Unsubscribe</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                {{
                    (page.props.flash as any)?.success ??
                    'Stop future marketing emails for this address.'
                }}
            </p>
            <button
                class="mt-5 rounded-md bg-primary px-3 py-2 text-sm text-white"
                @click="router.post(`/unsubscribe/${props.token}`)"
            >
                Confirm
            </button>
        </section>
    </main>
</template>
