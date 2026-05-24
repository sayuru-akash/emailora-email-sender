<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/emailora/ConfirmDialog.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatCard from '@/components/emailora/StatCard.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
const props = defineProps<{
    campaign: any;
    recipients?: any[];
    audience?: any;
    actions?: any;
}>();
const previewUrl = `/campaigns/${props.campaign.id}/preview`;
const processingAction = ref<string | null>(null);
const sendDialogOpen = ref(false);
const testDialogOpen = ref(false);
const confirmDialogOpen = ref(false);
const confirmState = ref({
    title: '',
    description: '',
    confirmLabel: 'Confirm',
    action: '',
    path: '',
    method: 'post' as 'post' | 'delete',
});
const recipientMode = ref<'current_audience' | 'new_contacts'>(
    'current_audience',
);
const testForm = useForm({
    to: '',
    provider: props.campaign.provider ?? 'auto',
});
const testFormErrors = testForm.errors as Record<string, string | undefined>;

function postAction(
    action: string,
    path: string,
    data: Record<string, string> = {},
) {
    processingAction.value = action;
    router.post(path, data, {
        preserveScroll: true,
        onFinish: () => {
            processingAction.value = null;
        },
    });
}

function requestConfirmation(
    action: string,
    path: string,
    title: string,
    description: string,
    confirmLabel: string,
    method: 'post' | 'delete' = 'post',
) {
    confirmState.value = {
        title,
        description,
        confirmLabel,
        action,
        path,
        method,
    };
    confirmDialogOpen.value = true;
}

function confirmAction() {
    processingAction.value = confirmState.value.action;
    const options = {
        preserveScroll: true,
        onFinish: () => {
            processingAction.value = null;
            confirmDialogOpen.value = false;
        },
    };

    if (confirmState.value.method === 'delete') {
        router.delete(confirmState.value.path, options);

        return;
    }

    router.post(confirmState.value.path, {}, options);
}

function confirmSendNow() {
    sendDialogOpen.value = false;
    postAction('send', `/campaigns/${props.campaign.id}/send`, {
        recipient_mode: recipientMode.value,
    });
}

function sendTestEmail() {
    testForm.post(`/campaigns/${props.campaign.id}/send-test`, {
        preserveScroll: true,
        onSuccess: () => {
            testDialogOpen.value = false;
            testForm.reset('to');
        },
    });
}
</script>
<template>
    <Head :title="props.campaign.name" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.campaign.name"
            :subtitle="props.campaign.subject"
        >
            <template #actions>
                <Link
                    v-if="props.actions?.canEdit"
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="`/campaigns/${props.campaign.id}/edit`"
                    >Edit</Link
                >
                <button
                    v-if="props.actions?.canSend"
                    class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="Boolean(processingAction)"
                    @click="sendDialogOpen = true"
                >
                    {{
                        processingAction === 'send' ? 'Sending...' : 'Send Now'
                    }}
                </button>
                <button
                    v-if="props.actions?.canPause"
                    class="rounded-md border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="Boolean(processingAction)"
                    @click="
                        postAction(
                            'pause',
                            `/campaigns/${props.campaign.id}/pause`,
                        )
                    "
                >
                    {{ processingAction === 'pause' ? 'Pausing...' : 'Pause' }}
                </button>
                <button
                    v-if="props.actions?.canResume"
                    class="rounded-md border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="Boolean(processingAction)"
                    @click="
                        postAction(
                            'resume',
                            `/campaigns/${props.campaign.id}/resume`,
                        )
                    "
                >
                    {{
                        processingAction === 'resume' ? 'Resuming...' : 'Resume'
                    }}
                </button>
                <button
                    v-if="props.actions?.canCancel"
                    class="rounded-md border px-3 py-2 text-sm text-destructive disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="Boolean(processingAction)"
                    @click="
                        requestConfirmation(
                            'cancel',
                            `/campaigns/${props.campaign.id}/cancel`,
                            'Cancel campaign',
                            'This stops any remaining campaign work. Already sent messages and reports are kept.',
                            'Cancel campaign',
                        )
                    "
                >
                    {{
                        processingAction === 'cancel'
                            ? 'Cancelling...'
                            : 'Cancel'
                    }}
                </button>
                <button
                    v-if="props.actions?.canResendFailed"
                    class="rounded-md border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="Boolean(processingAction)"
                    @click="
                        requestConfirmation(
                            'resend',
                            `/campaigns/${props.campaign.id}/resend-failed`,
                            'Retry failed recipients',
                            'All failed recipients for this campaign will be queued again. Successful recipients are not changed.',
                            'Queue retries',
                        )
                    "
                >
                    {{
                        processingAction === 'resend'
                            ? 'Queueing...'
                            : 'Resend Failed'
                    }}
                </button>
                <button
                    class="rounded-md border px-3 py-2 text-sm"
                    type="button"
                    @click="testDialogOpen = true"
                >
                    Send Test
                </button>
                <Link
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="`/campaigns/${props.campaign.id}/duplicate`"
                    method="post"
                    as="button"
                    >Duplicate</Link
                >
                <Link
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="`/campaigns/${props.campaign.id}/recipients`"
                    >Recipients</Link
                >
                <Link
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="previewUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    >Open Preview</Link
                >
                <Link
                    class="rounded-md border px-3 py-2 text-sm"
                    :href="`/campaigns/${props.campaign.id}/report`"
                    >View Report</Link
                >
                <button
                    v-if="props.actions?.canDelete"
                    class="rounded-md border px-3 py-2 text-sm text-destructive disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="Boolean(processingAction)"
                    @click="
                        requestConfirmation(
                            'delete',
                            `/campaigns/${props.campaign.id}`,
                            'Delete campaign draft',
                            'This removes the draft campaign. Sent campaign records cannot be deleted from this action.',
                            'Delete campaign',
                            'delete',
                        )
                    "
                >
                    {{
                        processingAction === 'delete' ? 'Deleting...' : 'Delete'
                    }}
                </button>
            </template>
        </PageHeader>
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <StatCard
                :label="
                    props.audience?.prepared_count > 0
                        ? 'Prepared'
                        : 'Target Audience'
                "
                :value="
                    props.audience?.display_count ??
                    props.campaign.total_recipients
                "
            />
            <StatCard
                label="Sent"
                :value="props.campaign.sent_count"
                tone="success"
            />
            <StatCard
                label="Delivered"
                :value="props.campaign.delivered_count"
            />
            <StatCard label="Opened" :value="props.campaign.opened_count" />
            <StatCard label="Clicked" :value="props.campaign.clicked_count" />
            <StatCard
                label="Failed"
                :value="props.campaign.failed_count"
                tone="danger"
            />
        </div>
        <section class="rounded-lg border bg-card p-5">
            <StatusBadge :status="props.campaign.status" />
            <iframe
                class="mt-4 h-[70vh] min-h-[640px] w-full rounded border bg-white"
                :src="previewUrl"
                title="Campaign email preview"
            ></iframe>
        </section>

        <div
            v-if="sendDialogOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/35 px-4"
            role="dialog"
            aria-modal="true"
        >
            <section
                class="w-full max-w-lg rounded-lg border bg-card p-5 shadow-xl"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Send campaign</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Choose how recipients should be prepared before this
                            campaign is queued.
                        </p>
                    </div>
                    <button
                        class="rounded-md border px-2 py-1 text-sm"
                        type="button"
                        @click="sendDialogOpen = false"
                    >
                        Close
                    </button>
                </div>

                <div class="mt-5 space-y-3">
                    <label
                        class="block cursor-pointer rounded-lg border p-4 transition hover:bg-muted/60"
                        :class="
                            recipientMode === 'current_audience'
                                ? 'border-primary bg-primary/5'
                                : ''
                        "
                    >
                        <input
                            v-model="recipientMode"
                            class="sr-only"
                            type="radio"
                            value="current_audience"
                        />
                        <span class="font-medium"
                            >Send to current audience</span
                        >
                        <span class="mt-1 block text-sm text-muted-foreground">
                            Rebuild recipients from the latest audience filters
                            and send to all matching contacts.
                        </span>
                    </label>
                    <label
                        class="block cursor-pointer rounded-lg border p-4 transition hover:bg-muted/60"
                        :class="
                            recipientMode === 'new_contacts'
                                ? 'border-primary bg-primary/5'
                                : ''
                        "
                    >
                        <input
                            v-model="recipientMode"
                            class="sr-only"
                            type="radio"
                            value="new_contacts"
                        />
                        <span class="font-medium">Only add new contacts</span>
                        <span class="mt-1 block text-sm text-muted-foreground">
                            Keep any existing prepared recipients and add only
                            contacts that are newly in the current audience.
                        </span>
                    </label>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        class="rounded-md border px-3 py-2 text-sm"
                        type="button"
                        @click="sendDialogOpen = false"
                    >
                        Cancel
                    </button>
                    <button
                        class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground"
                        type="button"
                        @click="confirmSendNow"
                    >
                        Queue send
                    </button>
                </div>
            </section>
        </div>
        <div
            v-if="testDialogOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/35 px-4"
            role="dialog"
            aria-modal="true"
        >
            <form
                class="w-full max-w-lg rounded-lg border bg-card p-5 shadow-xl"
                @submit.prevent="sendTestEmail"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Send test email</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Send this campaign to one address with sample
                            personalization before queueing recipients.
                        </p>
                    </div>
                    <button
                        class="rounded-md border px-2 py-1 text-sm"
                        type="button"
                        @click="testDialogOpen = false"
                    >
                        Close
                    </button>
                </div>

                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Recipient email</span>
                        <input
                            v-model="testForm.to"
                            class="h-10 rounded-md border bg-background px-3"
                            placeholder="you@example.com"
                            type="email"
                        />
                        <InputError :message="testForm.errors.to" />
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Provider</span>
                        <select
                            v-model="testForm.provider"
                            class="h-10 rounded-md border bg-background px-3"
                        >
                            <option value="auto">auto</option>
                            <option value="resend">resend</option>
                            <option value="brevo">brevo</option>
                        </select>
                        <InputError :message="testForm.errors.provider" />
                    </label>
                    <InputError :message="testFormErrors.campaign" />
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        class="rounded-md border px-3 py-2 text-sm"
                        type="button"
                        @click="testDialogOpen = false"
                    >
                        Cancel
                    </button>
                    <button
                        class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground disabled:opacity-60"
                        :disabled="testForm.processing"
                        type="submit"
                    >
                        {{ testForm.processing ? 'Sending...' : 'Send test' }}
                    </button>
                </div>
            </form>
        </div>
        <ConfirmDialog
            v-model="confirmDialogOpen"
            :title="confirmState.title"
            :description="confirmState.description"
            :confirm-label="confirmState.confirmLabel"
            :destructive="['cancel', 'delete'].includes(confirmState.action)"
            :processing="processingAction === confirmState.action"
            @confirm="confirmAction"
        />
    </main>
</template>
