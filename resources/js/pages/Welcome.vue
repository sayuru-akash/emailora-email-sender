<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    Clock3,
    DatabaseZap,
    FileCheck2,
    MailCheck,
    Send,
    ShieldCheck,
    Sparkles,
    Tags,
    Users,
} from 'lucide-vue-next';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

type SeoMeta = {
    title: string;
    fullTitle: string;
    description: string;
    canonical: string;
    image: string;
    imageAlt: string;
    imageWidth: number;
    imageHeight: number;
    siteName: string;
    robots: string;
    locale: string;
};

defineProps<{
    seo: SeoMeta;
}>();

const capabilities = [
    {
        title: 'Clean contact records',
        body: 'Profiles, consent status, lists, tags, segments, notes, and imported metadata stay connected.',
        icon: Users,
    },
    {
        title: 'Preview every import',
        body: 'Map CSV, TXT, or XLSX files, inspect rows, handle duplicates, then commit with confidence.',
        icon: FileCheck2,
    },
    {
        title: 'Personalized campaigns',
        body: 'Use templates, variables, previews, unsubscribe links, and audience estimates before sending.',
        icon: Sparkles,
    },
    {
        title: 'Operational sending',
        body: 'Queue delivery, watch progress, pause, resume, retry failed recipients, and recover active work.',
        icon: Send,
    },
];

const workflow = [
    'Import and validate contacts',
    'Segment the audience',
    'Build the email',
    'Send, recover, and report',
];

const trustItems = [
    'Unsubscribe links are required before campaigns send.',
    'Suppressed, bounced, complained, and blocked contacts are tracked.',
    'Unresolved personalization variables are blocked before delivery.',
    'Provider keys stay in environment configuration, not database records.',
];
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description" />
        <meta name="robots" :content="seo.robots" />
        <link rel="canonical" :href="seo.canonical" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" :content="seo.siteName" />
        <meta property="og:locale" :content="seo.locale" />
        <meta property="og:title" :content="seo.fullTitle" />
        <meta property="og:description" :content="seo.description" />
        <meta property="og:url" :content="seo.canonical" />
        <meta property="og:image" :content="seo.image" />
        <meta property="og:image:alt" :content="seo.imageAlt" />
        <meta property="og:image:width" :content="String(seo.imageWidth)" />
        <meta property="og:image:height" :content="String(seo.imageHeight)" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seo.fullTitle" />
        <meta name="twitter:description" :content="seo.description" />
        <meta name="twitter:image" :content="seo.image" />
        <meta name="twitter:image:alt" :content="seo.imageAlt" />
    </Head>

    <PublicLayout>
        <section
            class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-14 sm:py-18 lg:grid-cols-[minmax(0,1fr)_520px] lg:px-8 lg:py-24"
        >
            <div class="flex min-w-0 flex-col justify-center">
                <h1
                    class="max-w-3xl text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                >
                    Run email campaigns with cleaner data and fewer delivery
                    surprises.
                </h1>
                <p
                    class="mt-6 max-w-2xl text-base leading-7 text-muted-foreground sm:text-lg"
                >
                    Emailora brings contacts, imports, templates, audience
                    targeting, provider settings, campaign queues, unsubscribe
                    handling, and delivery reporting into one operations
                    console.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <Link
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-medium text-primary-foreground transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        href="/login"
                    >
                        Log in
                        <ArrowRight
                            class="size-4"
                            aria-hidden="true"
                            focusable="false"
                        />
                    </Link>
                    <a
                        class="inline-flex h-11 items-center justify-center rounded-md border border-border bg-card px-5 text-sm font-medium transition hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        href="#workflow"
                    >
                        View platform overview
                    </a>
                </div>
                <p
                    class="mt-8 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    Built for campaign operators managing real lists, queued
                    sends, provider events, and compliance-sensitive contact
                    data.
                </p>
            </div>

            <div
                class="relative min-h-[480px] overflow-hidden rounded-lg border border-border bg-card p-4 shadow-sm"
                aria-label="Emailora campaign operations preview"
            >
                <div
                    class="absolute inset-x-0 top-0 h-24 bg-linear-to-b from-primary/10 to-transparent"
                ></div>
                <div
                    class="relative rounded-lg border border-border bg-background"
                >
                    <div
                        class="flex items-center justify-between border-b border-border px-4 py-3"
                    >
                        <div>
                            <div class="text-sm font-medium">
                                May CCB Reminder
                            </div>
                            <div class="text-xs text-muted-foreground">
                                129 recipients ready
                            </div>
                        </div>
                        <span
                            class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                        >
                            completed
                        </span>
                    </div>
                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-lg border border-border p-3">
                                <div class="text-xs text-muted-foreground">
                                    Sent
                                </div>
                                <div class="mt-1 text-2xl font-semibold">
                                    129
                                </div>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <div class="text-xs text-muted-foreground">
                                    Failed
                                </div>
                                <div class="mt-1 text-2xl font-semibold">0</div>
                            </div>
                            <div class="rounded-lg border border-border p-3">
                                <div class="text-xs text-muted-foreground">
                                    Queue
                                </div>
                                <div class="mt-1 text-2xl font-semibold">0</div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-border p-4">
                            <div
                                class="mb-3 flex items-center justify-between text-sm"
                            >
                                <span class="font-medium"
                                    >Campaign progress</span
                                >
                                <span class="text-muted-foreground">100%</span>
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full w-full rounded-full bg-emerald-500"
                                ></div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-border p-4">
                                <div
                                    class="mb-3 flex items-center gap-2 text-sm font-medium"
                                >
                                    <Tags
                                        class="size-4 text-primary"
                                        aria-hidden="true"
                                        focusable="false"
                                    />
                                    Audience
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div
                                        class="flex items-center justify-between rounded-md bg-muted px-3 py-2"
                                    >
                                        <span>CCA unpaid</span>
                                        <span>45</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between rounded-md bg-muted px-3 py-2"
                                    >
                                        <span>CCB unpaid</span>
                                        <span>129</span>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-lg border border-border p-4">
                                <div
                                    class="mb-3 flex items-center gap-2 text-sm font-medium"
                                >
                                    <MailCheck
                                        class="size-4 text-primary"
                                        aria-hidden="true"
                                        focusable="false"
                                    />
                                    Provider
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div
                                        class="flex items-center gap-2 rounded-md bg-muted px-3 py-2"
                                    >
                                        <CheckCircle2
                                            class="size-3.5 text-emerald-600"
                                            aria-hidden="true"
                                            focusable="false"
                                        />
                                        Brevo accepted
                                    </div>
                                    <div
                                        class="flex items-center gap-2 rounded-md bg-muted px-3 py-2"
                                    >
                                        <Clock3
                                            class="size-3.5 text-primary"
                                            aria-hidden="true"
                                            focusable="false"
                                        />
                                        Queue monitored
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="workflow" class="border-y border-border bg-muted/30">
            <div
                class="mx-auto grid w-full max-w-7xl gap-4 px-4 py-10 sm:grid-cols-4 lg:px-8"
            >
                <div
                    v-for="(item, index) in workflow"
                    :key="item"
                    class="rounded-lg border border-border bg-card p-4"
                >
                    <div class="text-xs text-muted-foreground">
                        0{{ index + 1 }}
                    </div>
                    <div class="mt-3 text-sm font-medium">{{ item }}</div>
                </div>
            </div>
        </section>

        <section
            id="control"
            class="mx-auto w-full max-w-7xl px-4 py-16 lg:px-8"
        >
            <div class="max-w-2xl">
                <h2 class="text-3xl font-semibold tracking-tight">
                    Everything needed before and after send.
                </h2>
                <p class="mt-4 text-muted-foreground">
                    Keep campaign operations close to the data, queues, and
                    provider events that decide whether a send is ready.
                </p>
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="item in capabilities"
                    :key="item.title"
                    class="rounded-lg border border-border bg-card p-5 transition hover:bg-muted/30"
                >
                    <component
                        :is="item.icon"
                        class="size-5 text-primary"
                        aria-hidden="true"
                        focusable="false"
                    />
                    <h3 class="mt-5 text-base font-semibold">
                        {{ item.title }}
                    </h3>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        {{ item.body }}
                    </p>
                </article>
            </div>
        </section>

        <section id="trust" class="border-t border-border">
            <div
                class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 lg:grid-cols-[0.8fr_1fr] lg:px-8"
            >
                <div>
                    <ShieldCheck
                        class="size-8 text-primary"
                        aria-hidden="true"
                        focusable="false"
                    />
                    <h2 class="mt-5 text-3xl font-semibold tracking-tight">
                        Consent and recovery are part of the workflow.
                    </h2>
                    <p class="mt-4 text-muted-foreground">
                        Emailora treats sending as an operational system, not a
                        one-click blast.
                    </p>
                </div>
                <div class="grid gap-3">
                    <div
                        v-for="item in trustItems"
                        :key="item"
                        class="flex gap-3 rounded-lg border border-border bg-card p-4 text-sm leading-6"
                    >
                        <CheckCircle2
                            class="mt-0.5 size-4 shrink-0 text-emerald-600"
                            aria-hidden="true"
                            focusable="false"
                        />
                        <span>{{ item }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 py-16 lg:px-8">
            <div
                class="rounded-lg border border-border bg-card p-6 sm:p-8 lg:flex lg:items-center lg:justify-between"
            >
                <div class="max-w-2xl">
                    <DatabaseZap
                        class="size-6 text-primary"
                        aria-hidden="true"
                        focusable="false"
                    />
                    <h2 class="mt-5 text-3xl font-semibold tracking-tight">
                        Bring campaign work back under control.
                    </h2>
                    <p class="mt-3 text-muted-foreground">
                        Manage the list, prepare the audience, send the
                        campaign, and verify the result from one Emailora
                        workspace.
                    </p>
                </div>
                <Link
                    class="mt-6 inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-medium text-primary-foreground transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none lg:mt-0"
                    href="/login"
                >
                    Log in to Emailora
                    <ArrowRight
                        class="size-4"
                        aria-hidden="true"
                        focusable="false"
                    />
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
