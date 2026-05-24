<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

const sections = [
    {
        title: 'Authorized use',
        body: "Emailora is intended for authorized teams managing their own email contacts and campaigns. Users must only upload, store, segment, and send to contacts they are permitted to process under applicable law and their organization's policies.",
    },
    {
        title: 'Email compliance',
        body: 'Users are responsible for lawful consent, accurate sender information, required unsubscribe language, and compliant campaign content. Emailora provides unsubscribe, suppression, targeting, and audit features, but the workspace operator remains responsible for campaign use.',
    },
    {
        title: 'Prohibited use',
        body: 'Emailora may not be used to send spam, deceptive messages, phishing content, malware, unlawful promotions, or campaigns to purchased or unauthorized lists. Users may not bypass suppression, unsubscribe, bounce, complaint, or block controls.',
    },
    {
        title: 'Provider responsibility',
        body: 'Email delivery depends on configured third-party providers such as Resend or Brevo. Users are responsible for valid provider accounts, verified sender domains, API credentials, rate limits, and compliance with provider terms.',
    },
    {
        title: 'Imports and contact data',
        body: 'Users are responsible for the accuracy and legality of uploaded contact files. Emailora provides validation previews, duplicate handling, mapping, and row-level results, but users decide what data is imported and how it is used.',
    },
    {
        title: 'Account access',
        body: 'Accounts are created by an owner or administrator. Users are responsible for protecting login credentials, using assigned roles appropriately, and reporting unauthorized access or suspicious activity.',
    },
    {
        title: 'Operational availability',
        body: 'Campaign sending, scheduled sends, imports, recovery tasks, and reporting depend on queues, scheduler processes, database availability, and provider availability. Operators are responsible for running and monitoring the required infrastructure in production.',
    },
    {
        title: 'Limitations',
        body: 'Emailora does not guarantee inbox placement, provider acceptance, open tracking accuracy, click tracking accuracy, or uninterrupted third-party delivery. Reports reflect the data available from the application and configured providers.',
    },
];
</script>

<template>
    <Head>
        <title>Terms of Use</title>
        <meta
            name="description"
            content="Terms for using Emailora, an email campaign, contact management, import, reporting, and sending operations platform."
        />
        <link rel="canonical" href="/terms" />
    </Head>

    <PublicLayout contained>
        <article>
            <p class="text-sm font-medium text-primary">Terms of Use</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight">
                Responsible use of Emailora.
            </h1>
            <p class="mt-5 text-base leading-7 text-muted-foreground">
                These terms govern access to and use of Emailora, an email
                campaign, contact management, import, reporting, and sending
                operations platform.
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
