<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Emailora" />
    <main class="flex min-h-screen items-center justify-center bg-[#fafafa] px-4">
        <section class="w-full max-w-md rounded-lg border border-border bg-white p-6 text-center shadow-sm">
            <div class="text-2xl font-semibold">Emailora</div>
            <p class="mt-2 text-sm text-muted-foreground">Authenticated email operations console.</p>
            <Link class="mt-5 inline-flex rounded-md bg-primary px-3 py-2 text-sm font-medium text-white" href="/login">
                Log in
            </Link>
        </section>
    </main>
</template>
