<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

const props = defineProps<{ contact: any; recentMessages?: any[] }>();
</script>

<template>
    <Head :title="props.contact.display_name ?? props.contact.email" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader :title="props.contact.display_name ?? props.contact.email" :subtitle="props.contact.email">
            <template #actions><Link class="rounded-md bg-primary px-3 py-2 text-sm text-white" :href="`/contacts/${props.contact.id}/edit`">Edit</Link></template>
        </PageHeader>
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-lg border border-border bg-white p-4">
                <StatusBadge :status="props.contact.status" />
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-muted-foreground">Company</dt><dd>{{ props.contact.company ?? '-' }}</dd></div>
                    <div><dt class="text-muted-foreground">Consent</dt><dd>{{ props.contact.consent_status }}</dd></div>
                    <div><dt class="text-muted-foreground">Source</dt><dd>{{ props.contact.source ?? 'manual' }}</dd></div>
                </dl>
            </section>
            <section class="rounded-lg border border-border bg-white p-4 lg:col-span-2">
                <h2 class="font-medium">Recent messages</h2>
                <div v-for="message in props.recentMessages ?? []" :key="message.id" class="mt-3 flex justify-between border-t pt-3 text-sm">
                    <span>{{ message.subject }}</span><StatusBadge :status="message.status" />
                </div>
                <p v-if="!(props.recentMessages ?? []).length" class="mt-3 text-sm text-muted-foreground">No messages yet</p>
            </section>
        </div>
    </main>
</template>
