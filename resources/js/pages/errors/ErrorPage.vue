<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Home, RefreshCw } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

const props = withDefaults(defineProps<{ status?: number }>(), {
    status: 500,
});

const page = usePage();

const details = computed(() => {
    const messages: Record<number, { title: string; description: string }> = {
        403: {
            title: 'Access denied',
            description:
                'Your account does not have permission to view this page.',
        },
        404: {
            title: 'Page not found',
            description:
                'The page may have moved, been removed, or never existed.',
        },
        405: {
            title: 'Action not available',
            description: 'That action is not supported for this page.',
        },
        419: {
            title: 'Session expired',
            description: 'Refresh the page to start a new secure session.',
        },
        429: {
            title: 'Too many requests',
            description: 'Please wait a moment before trying again.',
        },
        500: {
            title: 'Something went wrong',
            description:
                'We could not complete that request. Please try again.',
        },
        503: {
            title: 'Service temporarily unavailable',
            description:
                'Emailora is temporarily unavailable. Please try again shortly.',
        },
    };

    return messages[props.status] ?? messages[500];
});

const isAuthenticated = computed(() =>
    Boolean((page.props.auth as { user?: unknown } | undefined)?.user),
);
const destination = computed(() =>
    isAuthenticated.value ? '/dashboard' : '/',
);
const destinationLabel = computed(() =>
    isAuthenticated.value ? 'Go to dashboard' : 'Back to home',
);

function retry(): void {
    window.location.reload();
}
</script>

<template>
    <Head :title="details.title">
        <meta
            head-key="robots"
            name="robots"
            content="noindex, nofollow, noarchive"
        />
    </Head>

    <PublicLayout>
        <main
            class="mx-auto flex min-h-[calc(100svh-10rem)] max-w-2xl items-center px-4 py-12 sm:py-20"
        >
            <section class="w-full border-l-4 border-primary pl-6 sm:pl-8">
                <p class="text-sm font-semibold text-primary">
                    Error {{ status }}
                </p>
                <h1
                    class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl"
                >
                    {{ details.title }}
                </h1>
                <p
                    class="mt-4 max-w-xl text-base leading-7 text-muted-foreground"
                >
                    {{ details.description }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <button
                        v-if="status === 419 || status >= 500"
                        type="button"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        @click="retry"
                    >
                        <RefreshCw class="size-4" aria-hidden="true" />
                        Try again
                    </button>
                    <Link
                        :href="destination"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-border bg-card px-4 text-sm font-medium transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <Home
                            v-if="!isAuthenticated"
                            class="size-4"
                            aria-hidden="true"
                        />
                        <ArrowLeft v-else class="size-4" aria-hidden="true" />
                        {{ destinationLabel }}
                    </Link>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
