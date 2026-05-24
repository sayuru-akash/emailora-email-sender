<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoMark from '@/components/AppLogoMark.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';

defineProps<{
    contained?: boolean;
}>();
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <a
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-md focus:bg-primary focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:text-primary-foreground"
            href="#main"
        >
            Skip to content
        </a>

        <header
            class="sticky top-0 z-40 border-b border-border/70 bg-background/85 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 lg:px-8"
            >
                <Link class="flex items-center gap-3" href="/">
                    <AppLogoMark class="size-9" icon-class="size-5" />
                    <span class="text-sm font-semibold tracking-tight"
                        >Emailora</span
                    >
                </Link>

                <nav
                    class="hidden items-center gap-7 text-sm text-muted-foreground md:flex"
                    aria-label="Primary"
                >
                    <a
                        class="transition hover:text-foreground"
                        href="/#workflow"
                        >Workflow</a
                    >
                    <a class="transition hover:text-foreground" href="/#control"
                        >Control</a
                    >
                    <a class="transition hover:text-foreground" href="/#trust"
                        >Trust</a
                    >
                    <Link
                        class="transition hover:text-foreground"
                        href="/privacy"
                        >Privacy</Link
                    >
                    <Link class="transition hover:text-foreground" href="/terms"
                        >Terms</Link
                    >
                </nav>

                <div class="flex items-center gap-2">
                    <ThemeToggle />
                    <Link
                        class="inline-flex h-9 items-center rounded-md border border-border bg-card px-3 text-sm font-medium transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        href="/login"
                    >
                        Log in
                    </Link>
                </div>
            </div>
        </header>

        <main
            id="main"
            :class="[
                contained ? 'mx-auto w-full max-w-4xl px-4 py-12 lg:px-8' : '',
            ]"
        >
            <slot />
        </main>

        <footer class="border-t border-border/70">
            <div
                class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between lg:px-8"
            >
                <p>Emailora by Codezela Technologies</p>
                <nav class="flex flex-wrap gap-4" aria-label="Legal">
                    <Link
                        class="transition hover:text-foreground"
                        href="/privacy"
                        >Privacy</Link
                    >
                    <Link class="transition hover:text-foreground" href="/terms"
                        >Terms</Link
                    >
                    <Link class="transition hover:text-foreground" href="/login"
                        >Log in</Link
                    >
                </nav>
            </div>
        </footer>
    </div>
</template>
