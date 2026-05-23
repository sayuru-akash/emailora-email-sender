<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

const props = defineProps<{ contact: any; recentMessages?: any[] }>();

function deleteContact() {
    if (confirm('Delete this contact?')) {
        router.delete(`/contacts/${props.contact.id}`);
    }
}
</script>

<template>
    <Head :title="props.contact.display_name ?? props.contact.email" />
    <main class="mx-auto w-full max-w-5xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.contact.display_name ?? props.contact.email"
            :subtitle="props.contact.email"
        >
            <template #actions>
                <Link
                    class="rounded-md border px-3 py-2 text-sm"
                    href="/contacts"
                    >Back</Link
                >
                <Link
                    class="rounded-md bg-primary px-3 py-2 text-sm text-white"
                    :href="`/contacts/${props.contact.id}/edit`"
                    >Edit</Link
                >
                <Link
                    v-if="props.contact.status !== 'unsubscribed'"
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="`/contacts/${props.contact.id}/unsubscribe`"
                    method="post"
                    as="button"
                    preserve-scroll
                    >Unsubscribe</Link
                >
                <Link
                    v-if="props.contact.status !== 'blocked'"
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="`/contacts/${props.contact.id}/block`"
                    method="post"
                    as="button"
                    preserve-scroll
                    >Block</Link
                >
                <button
                    class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive"
                    type="button"
                    @click="deleteContact"
                >
                    Delete
                </button>
                >
            </template>
        </PageHeader>
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-lg border border-border bg-card p-4">
                <StatusBadge :status="props.contact.status" />
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Company</dt>
                        <dd>{{ props.contact.company ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Consent</dt>
                        <dd>{{ props.contact.consent_status }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Source</dt>
                        <dd>{{ props.contact.source ?? 'manual' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd>{{ props.contact.phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Location</dt>
                        <dd>
                            {{
                                [
                                    props.contact.city,
                                    props.contact.district,
                                    props.contact.country,
                                ]
                                    .filter(Boolean)
                                    .join(', ') || '-'
                            }}
                        </dd>
                    </div>
                </dl>
            </section>
            <section
                class="rounded-lg border border-border bg-card p-4 lg:col-span-2"
            >
                <h2 class="font-medium">Recent messages</h2>
                <div
                    v-for="message in props.recentMessages ?? []"
                    :key="message.id"
                    class="mt-3 flex justify-between border-t pt-3 text-sm"
                >
                    <span>{{ message.subject }}</span
                    ><StatusBadge :status="message.status" />
                </div>
                <p
                    v-if="!(props.recentMessages ?? []).length"
                    class="mt-3 text-sm text-muted-foreground"
                >
                    No messages yet
                </p>
            </section>
            <section
                class="rounded-lg border border-border bg-card p-4 lg:col-span-3"
            >
                <h2 class="font-medium">Audience membership</h2>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="text-sm text-muted-foreground">Lists</h3>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link
                                v-for="list in props.contact.lists ?? []"
                                :key="list.id"
                                class="rounded-full border px-2.5 py-1 text-xs"
                                :href="`/lists/${list.id}`"
                                >{{ list.name }}</Link
                            >
                            <span
                                v-if="!(props.contact.lists ?? []).length"
                                class="text-sm text-muted-foreground"
                                >No lists</span
                            >
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm text-muted-foreground">Tags</h3>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link
                                v-for="tag in props.contact.tags ?? []"
                                :key="tag.id"
                                class="rounded-full border px-2.5 py-1 text-xs"
                                :href="`/tags/${tag.id}`"
                                >{{ tag.name }}</Link
                            >
                            <span
                                v-if="!(props.contact.tags ?? []).length"
                                class="text-sm text-muted-foreground"
                                >No tags</span
                            >
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
