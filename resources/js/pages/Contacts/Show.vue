<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/emailora/ConfirmDialog.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

const props = defineProps<{ contact: any; recentMessages?: any[] }>();
const deleteDialogOpen = ref(false);
const contactActionDialogOpen = ref(false);
const deleting = ref(false);
const processingAction = ref<string | null>(null);
const contactAction = ref({
    action: '',
    path: '',
    title: '',
    description: '',
    confirmLabel: '',
});

function deleteContact() {
    deleting.value = true;
    router.delete(`/contacts/${props.contact.id}`, {
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
        },
    });
}

function requestContactAction(
    action: string,
    path: string,
    title: string,
    description: string,
    confirmLabel: string,
) {
    contactAction.value = { action, path, title, description, confirmLabel };
    contactActionDialogOpen.value = true;
}

function confirmContactAction() {
    processingAction.value = contactAction.value.action;
    router.post(
        contactAction.value.path,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingAction.value = null;
                contactActionDialogOpen.value = false;
            },
        },
    );
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
                    class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground"
                    :href="`/contacts/${props.contact.id}/edit`"
                    >Edit</Link
                >
                <button
                    v-if="props.contact.status !== 'unsubscribed'"
                    class="rounded-md border px-3 py-2 text-sm"
                    type="button"
                    @click="
                        requestContactAction(
                            'unsubscribe',
                            `/contacts/${props.contact.id}/unsubscribe`,
                            'Unsubscribe contact',
                            'This contact will be suppressed from future campaign sends. Existing reporting remains unchanged.',
                            'Unsubscribe contact',
                        )
                    "
                >
                    {{
                        processingAction === 'unsubscribe'
                            ? 'Updating...'
                            : 'Unsubscribe'
                    }}
                </button>
                <button
                    v-if="props.contact.status !== 'blocked'"
                    class="rounded-md border px-3 py-2 text-sm"
                    type="button"
                    @click="
                        requestContactAction(
                            'block',
                            `/contacts/${props.contact.id}/block`,
                            'Block contact',
                            'This contact will be blocked from future sending and audience targeting.',
                            'Block contact',
                        )
                    "
                >
                    {{ processingAction === 'block' ? 'Blocking...' : 'Block' }}
                </button>
                <button
                    class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive"
                    type="button"
                    @click="deleteDialogOpen = true"
                >
                    Delete
                </button>
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
        <ConfirmDialog
            v-model="deleteDialogOpen"
            title="Delete contact"
            description="This removes the contact from audiences and future campaign targeting. Existing message history remains for reporting."
            confirm-label="Delete contact"
            destructive
            :processing="deleting"
            @confirm="deleteContact"
        />
        <ConfirmDialog
            v-model="contactActionDialogOpen"
            :title="contactAction.title"
            :description="contactAction.description"
            :confirm-label="contactAction.confirmLabel"
            destructive
            :processing="processingAction === contactAction.action"
            @confirm="confirmContactAction"
        />
    </main>
</template>
