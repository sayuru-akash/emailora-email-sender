<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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

const sections = [
    {
        title: 'Data Emailora processes',
        body: 'Emailora may store contact names, email addresses, phone numbers, company details, job titles, location fields, source, consent status, engagement timestamps, notes, list and tag membership, imported metadata, unsubscribe status, bounce status, complaint status, and campaign recipient history.',
    },
    {
        title: 'How the data is used',
        body: 'This information is used to manage contacts, build audiences, personalize campaign content, send email through configured providers, process unsubscribes, suppress blocked or invalid recipients, report campaign performance, and maintain an operational audit trail.',
    },
    {
        title: 'Imports',
        body: 'Uploaded contact files are used to preview, validate, map, and process contacts. Extra columns may be stored as contact metadata. Failed rows may be retained so operators can review processing results.',
    },
    {
        title: 'Campaign and provider events',
        body: 'Emailora stores campaign, recipient, message, delivery, open, click, failure, bounce, and complaint events where available. Delivery is handled through configured providers such as Resend or Brevo, and provider webhooks may update message and contact status.',
    },
    {
        title: 'Audit logs',
        body: 'Operational activity can include sign-ins, imports, contact changes, campaign actions, webhook processing, and administrative updates. Audit logs may include actor details, IP address, user agent, route, event category, severity, and redacted metadata.',
    },
    {
        title: 'Sensitive configuration',
        body: 'Provider credentials are read from environment configuration. Emailora is designed not to store provider API keys in application records. Sensitive provider payloads, tokens, signatures, headers, cookies, raw import rows, and personalized message bodies should not be stored in activity log properties.',
    },
    {
        title: 'Unsubscribe and suppression',
        body: 'Recipients can unsubscribe through signed unsubscribe links. Emailora records unsubscribe and suppression status so future campaigns can avoid sending to contacts who opted out, bounced, complained, or were blocked.',
    },
    {
        title: 'Retention',
        body: "Contact records, campaign history, imports, message events, suppressions, and audit logs are retained according to the workspace operator's retention policy, legal requirements, and operational needs.",
    },
];
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description" />
        <meta name="robots" :content="seo.robots" />
        <link rel="canonical" :href="seo.canonical" />
        <meta property="og:type" content="article" />
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

    <PublicLayout contained>
        <article>
            <p class="text-sm font-medium text-primary">Privacy Policy</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight">
                How Emailora handles campaign data.
            </h1>
            <p class="mt-5 text-base leading-7 text-muted-foreground">
                Emailora is an email campaign and contact management system.
                This policy explains how contact records, campaign activity,
                provider events, imports, user accounts, and operational logs
                are handled inside the platform.
            </p>
            <p class="mt-4 text-sm text-muted-foreground">
                Last updated: May 24, 2026
            </p>

            <div class="mt-10 space-y-8">
                <section
                    v-for="section in sections"
                    :key="section.title"
                    class="border-t border-border pt-8"
                >
                    <h2 class="text-xl font-semibold tracking-tight">
                        {{ section.title }}
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-muted-foreground">
                        {{ section.body }}
                    </p>
                </section>
            </div>
        </article>
    </PublicLayout>
</template>
