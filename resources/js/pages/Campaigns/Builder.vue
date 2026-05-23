<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    ChevronRight,
    FileText,
    LayoutTemplate,
    Mail,
    Search,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import VariablePicker from '@/components/emailora/VariablePicker.vue';

type TemplateOption = {
    id: number;
    name: string;
    subject: string;
    preheader?: string | null;
    html_body?: string | null;
    text_body?: string | null;
};

type AudienceOption = {
    id: number;
    name: string;
    contacts_count?: number;
};

type ContactOption = {
    id: number;
    name: string;
    email: string;
    status: string;
    company?: string | null;
};

type AudienceEstimate = {
    count: number;
    sendable_count: number;
    suppressed_count: number;
};

type VariableDefinition = {
    key: string;
    token: string;
    label: string;
    group: string;
    description: string;
    sample?: string;
};

const props = defineProps<{
    campaign?: any;
    defaults?: any;
    templates?: TemplateOption[];
    lists?: AudienceOption[];
    tags?: AudienceOption[];
    selectedContacts?: ContactOption[];
    variableDefinitions?: VariableDefinition[];
}>();

const defaultHtml =
    '<p>Hello {first_name},</p><p><a href="{unsubscribe_url}">Unsubscribe</a></p>';
const filters = props.campaign?.target_filters ?? {};

const form = useForm({
    name: props.campaign?.name ?? '',
    provider: props.campaign?.provider ?? props.defaults?.provider ?? 'resend',
    from_name:
        props.campaign?.from_name ?? props.defaults?.from_name ?? 'Emailora',
    from_email: props.campaign?.from_email ?? props.defaults?.from_email ?? '',
    reply_to_email:
        props.campaign?.reply_to_email ?? props.defaults?.reply_to_email ?? '',
    subject: props.campaign?.subject ?? '',
    preheader: props.campaign?.preheader ?? '',
    html_body: props.campaign?.html_body ?? defaultHtml,
    text_body: props.campaign?.text_body ?? '',
    email_template_id: props.campaign?.email_template_id ?? null,
    target_type: props.campaign?.target_type ?? 'all_contacts',
    target_filters: props.campaign?.target_filters ?? {},
    status: props.campaign?.status === 'scheduled' ? 'scheduled' : 'draft',
    scheduled_at: props.campaign?.scheduled_at ?? '',
});

const steps = [
    { key: 'source', label: 'Source' },
    { key: 'content', label: 'Content' },
    { key: 'audience', label: 'Audience' },
    { key: 'review', label: 'Review' },
] as const;
type StepKey = (typeof steps)[number]['key'];

const activeStep = ref<StepKey>('source');
const contentMode = ref<'template' | 'manual'>(
    form.email_template_id ? 'template' : 'manual',
);
const selectedTemplateId = ref<number | null>(form.email_template_id);
const selectedListIds = ref<number[]>(filters.list_ids ?? []);
const selectedTagIds = ref<number[]>(filters.tag_ids ?? []);
const selectedContacts = ref<ContactOption[]>(props.selectedContacts ?? []);
const contactResults = ref<ContactOption[]>([]);
const contactSearch = ref('');
const contactSearchLoading = ref(false);
const estimate = ref<AudienceEstimate | null>(null);
const estimateLoading = ref(false);
const builderTop = ref<HTMLElement | null>(null);
const sendDialogOpen = ref(false);
const sendAction = ref<'send' | 'schedule'>('send');
const recipientMode = ref<'current_audience' | 'new_contacts'>(
    'current_audience',
);

type InsertField = 'subject' | 'preheader' | 'html_body' | 'text_body';

const activeInsertField = ref<InsertField>('html_body');
const subjectField = ref<HTMLInputElement | null>(null);
const preheaderField = ref<HTMLInputElement | null>(null);
const htmlBodyField = ref<HTMLTextAreaElement | null>(null);
const textBodyField = ref<HTMLTextAreaElement | null>(null);

let contactSearchTimer: ReturnType<typeof setTimeout> | null = null;
let estimateTimer: ReturnType<typeof setTimeout> | null = null;

const templates = computed(() => props.templates ?? []);
const selectedTemplate = computed(
    () =>
        templates.value.find(
            (template) => template.id === selectedTemplateId.value,
        ) ?? null,
);
const activeStepIndex = computed(() =>
    steps.findIndex((step) => step.key === activeStep.value),
);
const selectedAudienceCount = computed(() => {
    if (form.target_type === 'list') {
        return selectedListIds.value.length;
    }

    if (form.target_type === 'tag') {
        return selectedTagIds.value.length;
    }

    if (form.target_type === 'manual_selection') {
        return selectedContacts.value.length;
    }

    return 0;
});
const contentReady = computed(
    () =>
        Boolean(form.subject.trim()) &&
        (Boolean(form.html_body.trim()) || Boolean(form.text_body.trim())) &&
        (contentMode.value === 'manual' || Boolean(selectedTemplate.value)),
);
const senderReady = computed(
    () =>
        Boolean(form.name.trim()) &&
        Boolean(form.from_name.trim()) &&
        Boolean(form.from_email.trim()),
);
const audienceReady = computed(() => {
    if (form.target_type === 'all_contacts') {
        return true;
    }

    return selectedAudienceCount.value > 0;
});
const canSave = computed(
    () => senderReady.value && contentReady.value && audienceReady.value,
);
const previewHtml = computed(() => {
    const html = withPreheader(
        renderSampleContent(form.html_body || defaultHtml),
        renderSampleContent(form.preheader || ''),
    );

    if (/<base\s/i.test(html)) {
        return html;
    }

    if (/<head\b/i.test(html)) {
        return html.replace(/<head([^>]*)>/i, '<head$1><base target="_blank">');
    }

    return `<!doctype html><html><head><meta charset="utf-8"><base target="_blank"></head><body>${html}</body></html>`;
});
const hasUnsubscribe = computed(() =>
    variablesIn(
        `${form.preheader} ${form.html_body} ${form.text_body}`,
    ).includes('unsubscribe_url'),
);
const audienceSummary = computed(() => {
    if (form.target_type === 'all_contacts') {
        return 'All active emailable contacts';
    }

    if (form.target_type === 'list') {
        return `${selectedListIds.value.length} list${selectedListIds.value.length === 1 ? '' : 's'} selected`;
    }

    if (form.target_type === 'tag') {
        return `${selectedTagIds.value.length} tag${selectedTagIds.value.length === 1 ? '' : 's'} selected`;
    }

    return `${selectedContacts.value.length} contact${selectedContacts.value.length === 1 ? '' : 's'} selected`;
});
const saveLabel = computed(() =>
    props.campaign?.id ? 'Update campaign' : 'Save campaign',
);
const campaignError = computed(
    () => (form.errors as Record<string, string | undefined>).campaign,
);
const activeFieldLabel = computed(() => {
    return {
        subject: 'subject',
        preheader: 'preheader',
        html_body: 'HTML body',
        text_body: 'plain text',
    }[activeInsertField.value];
});
const sampleSubject = computed(() =>
    renderSampleContent(form.subject || 'Subject'),
);
const samplePreheader = computed(() =>
    renderSampleContent(form.preheader || 'No preheader set'),
);

function fieldElement(field: InsertField) {
    return {
        subject: subjectField.value,
        preheader: preheaderField.value,
        html_body: htmlBodyField.value,
        text_body: textBodyField.value,
    }[field];
}

function insertVariable(token: string) {
    const field = activeInsertField.value;
    const element = fieldElement(field);
    const current = form[field] ?? '';
    const start = element?.selectionStart ?? current.length;
    const end = element?.selectionEnd ?? current.length;
    const prefix = current.slice(0, start);
    const suffix = current.slice(end);
    const spacerBefore = prefix && !/\s$/.test(prefix) ? ' ' : '';
    const spacerAfter = suffix && !/^\s/.test(suffix) ? ' ' : '';

    form[field] = `${prefix}${spacerBefore}${token}${spacerAfter}${suffix}`;

    requestAnimationFrame(() => {
        const nextPosition = start + spacerBefore.length + token.length;
        element?.focus();
        element?.setSelectionRange(nextPosition, nextPosition);
    });
}

function variablesIn(content: string) {
    const keys = new Set<string>();
    const regex = /\{\{\s*([a-zA-Z0-9_.-]+)\s*}}|\{\s*([a-zA-Z0-9_.-]+)\s*}/g;
    let match;

    while ((match = regex.exec(content)) !== null) {
        const key = match[1] || match[2];

        if (key) {
            keys.add(key);
        }
    }

    return [...keys];
}

function renderSampleContent(content: string) {
    const values = new Map(
        (props.variableDefinitions ?? []).map((definition) => [
            definition.key,
            definition.sample ?? '',
        ]),
    );

    return content.replace(
        /\{\{\s*([a-zA-Z0-9_.-]+)\s*}}|\{\s*([a-zA-Z0-9_.-]+)\s*}/g,
        (_match, bladeKey: string | undefined, legacyKey: string | undefined) =>
            values.get(bladeKey || legacyKey || '') ?? '',
    );
}

function escapeHtml(value: string) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function withPreheader(html: string, preheader: string) {
    const cleanPreheader = preheader.trim();

    if (!cleanPreheader) {
        return html;
    }

    const node = `<div style="display:none!important;max-height:0;max-width:0;opacity:0;overflow:hidden;color:transparent;line-height:1px;mso-hide:all;">${escapeHtml(cleanPreheader)}</div>`;

    if (/<body\b[^>]*>/i.test(html)) {
        return html.replace(/(<body\b[^>]*>)/i, `$1${node}`);
    }

    return `${node}${html}`;
}

function stepClasses(step: StepKey) {
    const index = steps.findIndex((item) => item.key === step);
    const isActive = activeStep.value === step;
    const isComplete = index < activeStepIndex.value;

    return [
        'flex items-center gap-2 rounded-md px-3 py-2 text-sm transition',
        isActive
            ? 'bg-primary text-primary-foreground'
            : isComplete
              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
              : 'bg-muted text-muted-foreground hover:text-foreground',
    ];
}

function goToStep(step: StepKey) {
    activeStep.value = step;
    scrollActiveStepIntoView();
}

function nextStep() {
    const next = steps[activeStepIndex.value + 1];

    if (next) {
        activeStep.value = next.key;
        scrollActiveStepIntoView();
    }
}

function previousStep() {
    const previous = steps[activeStepIndex.value - 1];

    if (previous) {
        activeStep.value = previous.key;
        scrollActiveStepIntoView();
    }
}

function scrollActiveStepIntoView() {
    nextTick(() => {
        builderTop.value?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    });
}

function applyTemplate(template: TemplateOption) {
    contentMode.value = 'template';
    selectedTemplateId.value = template.id;
    form.email_template_id = template.id;
    form.subject = template.subject ?? '';
    form.preheader = template.preheader ?? '';
    form.html_body = template.html_body ?? defaultHtml;
    form.text_body = template.text_body ?? '';
}

function switchToManual() {
    contentMode.value = 'manual';
    selectedTemplateId.value = null;
    form.email_template_id = null;

    if (!form.subject.trim()) {
        form.html_body = form.html_body || defaultHtml;
    }
}

function toggleSelected(list: number[], id: number) {
    const index = list.indexOf(id);

    if (index >= 0) {
        list.splice(index, 1);

        return;
    }

    list.push(id);
}

function setAudienceType(type: string) {
    form.target_type = type;
    syncAudienceFilters();
}

function isSelectedContact(contact: ContactOption) {
    return selectedContacts.value.some((item) => item.id === contact.id);
}

function addContact(contact: ContactOption) {
    if (!isSelectedContact(contact)) {
        selectedContacts.value.push(contact);
    }
}

function removeContact(contact: ContactOption) {
    selectedContacts.value = selectedContacts.value.filter(
        (item) => item.id !== contact.id,
    );
}

function syncAudienceFilters() {
    if (form.target_type === 'list') {
        form.target_filters = { list_ids: [...selectedListIds.value] };

        return;
    }

    if (form.target_type === 'tag') {
        form.target_filters = { tag_ids: [...selectedTagIds.value] };

        return;
    }

    if (form.target_type === 'manual_selection') {
        form.target_filters = {
            contact_ids: selectedContacts.value.map((contact) => contact.id),
        };

        return;
    }

    form.target_filters = {};
}

function csrfHeaders(): Record<string, string> {
    if (typeof document === 'undefined') {
        return {};
    }

    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {};
}

async function searchContacts() {
    const search = contactSearch.value.trim();

    if (search.length < 2) {
        contactResults.value = [];

        return;
    }

    contactSearchLoading.value = true;

    try {
        const response = await fetch(
            `/campaigns/audience/contacts?search=${encodeURIComponent(search)}`,
            { headers: { Accept: 'application/json' } },
        );
        const payload = await response.json();
        contactResults.value = payload.contacts ?? [];
    } finally {
        contactSearchLoading.value = false;
    }
}

function queueContactSearch() {
    if (contactSearchTimer) {
        clearTimeout(contactSearchTimer);
    }

    contactSearchTimer = setTimeout(searchContacts, 250);
}

async function refreshEstimate() {
    estimateLoading.value = true;

    try {
        const response = await fetch('/campaigns/audience/estimate', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...csrfHeaders(),
            },
            body: JSON.stringify({
                target_type: form.target_type,
                target_filters: form.target_filters,
            }),
        });

        if (response.ok) {
            estimate.value = await response.json();
        }
    } finally {
        estimateLoading.value = false;
    }
}

function queueEstimateRefresh() {
    if (estimateTimer) {
        clearTimeout(estimateTimer);
    }

    estimateTimer = setTimeout(refreshEstimate, 250);
}

function save() {
    syncAudienceFilters();

    const options = {
        preserveScroll: true,
    };

    if (props.campaign?.id) {
        form.put(`/campaigns/${props.campaign.id}`, options);

        return;
    }

    form.post('/campaigns', options);
}

function openSendDialog(action: 'send' | 'schedule') {
    sendAction.value = action;
    sendDialogOpen.value = true;
}

function confirmSend() {
    if (!props.campaign?.id) {
        return;
    }

    syncAudienceFilters();

    const scheduledAt =
        sendAction.value === 'schedule' ? form.scheduled_at : null;

    form.put(`/campaigns/${props.campaign.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            sendDialogOpen.value = false;
            router.post(
                `/campaigns/${props.campaign?.id}/send`,
                {
                    scheduled_at: scheduledAt,
                    recipient_mode: recipientMode.value,
                },
                { preserveScroll: true },
            );
        },
    });
}

watch(
    [selectedListIds, selectedTagIds, selectedContacts],
    () => {
        syncAudienceFilters();
        queueEstimateRefresh();
    },
    { deep: true },
);

watch(
    () => form.target_type,
    () => {
        syncAudienceFilters();
        queueEstimateRefresh();
    },
);

watch(contactSearch, queueContactSearch);

syncAudienceFilters();
refreshEstimate();
</script>

<template>
    <Head :title="props.campaign?.id ? 'Edit Campaign' : 'New Campaign'" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.campaign?.id ? 'Edit Campaign' : 'New Campaign'"
            subtitle="Create the message, choose the audience, and confirm the send setup."
        >
            <template #actions>
                <Link
                    class="rounded-md border bg-card px-3 py-2 text-sm text-foreground transition hover:bg-muted"
                    href="/campaigns"
                >
                    Campaigns
                </Link>
            </template>
        </PageHeader>

        <form class="space-y-5" @submit.prevent="save">
            <nav
                ref="builderTop"
                class="grid gap-2 rounded-lg border bg-card p-2 sm:grid-cols-4"
                aria-label="Campaign builder steps"
            >
                <button
                    v-for="(step, index) in steps"
                    :key="step.key"
                    type="button"
                    :class="stepClasses(step.key)"
                    @click="goToStep(step.key)"
                >
                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-background/80 text-xs text-foreground"
                    >
                        <Check
                            v-if="index < activeStepIndex"
                            class="h-3.5 w-3.5"
                        />
                        <span v-else>{{ index + 1 }}</span>
                    </span>
                    <span>{{ step.label }}</span>
                </button>
            </nav>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
                <section class="min-w-0 rounded-lg border bg-card p-5">
                    <div v-if="activeStep === 'source'" class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold">
                                Start from a template or write manually
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Pick a saved template for approved copy and
                                images, or choose manual mode for a new email.
                            </p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <button
                                type="button"
                                :class="[
                                    'rounded-lg border p-4 text-left transition',
                                    contentMode === 'template'
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                        : 'hover:bg-muted/60',
                                ]"
                                @click="contentMode = 'template'"
                            >
                                <LayoutTemplate
                                    class="mb-3 h-5 w-5 text-primary"
                                />
                                <div class="font-medium">Use a template</div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Best for reusable branded campaigns with
                                    existing images and copy.
                                </p>
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-lg border p-4 text-left transition',
                                    contentMode === 'manual'
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                        : 'hover:bg-muted/60',
                                ]"
                                @click="switchToManual"
                            >
                                <FileText class="mb-3 h-5 w-5 text-primary" />
                                <div class="font-medium">Write manually</div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Best for one-off messages where the subject
                                    and HTML are edited directly.
                                </p>
                            </button>
                        </div>

                        <div
                            v-if="contentMode === 'template'"
                            class="space-y-3"
                        >
                            <div
                                class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between"
                            >
                                <div>
                                    <h3 class="font-medium">Select template</h3>
                                    <p class="text-sm text-muted-foreground">
                                        Choosing a template fills the subject,
                                        preheader, HTML, and text body.
                                    </p>
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    {{ templates.length }} available
                                </span>
                            </div>
                            <div class="grid gap-3 lg:grid-cols-2">
                                <button
                                    v-for="template in templates"
                                    :key="template.id"
                                    type="button"
                                    :class="[
                                        'rounded-lg border p-4 text-left transition hover:bg-muted/60',
                                        selectedTemplateId === template.id
                                            ? 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                            : '',
                                    ]"
                                    @click="applyTemplate(template)"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <div class="truncate font-medium">
                                                {{ template.name }}
                                            </div>
                                            <p
                                                class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                                            >
                                                {{ template.subject }}
                                            </p>
                                        </div>
                                        <Check
                                            v-if="
                                                selectedTemplateId ===
                                                template.id
                                            "
                                            class="h-5 w-5 shrink-0 text-primary"
                                        />
                                    </div>
                                </button>
                            </div>
                            <p
                                v-if="form.errors.email_template_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.email_template_id }}
                            </p>
                        </div>
                    </div>

                    <div v-else-if="activeStep === 'content'" class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold">
                                Sender and message
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Keep the sender details clear and verify the
                                preview before saving.
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium"
                                    >Campaign name</span
                                >
                                <input
                                    v-model="form.name"
                                    class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    placeholder="May CCA unpaid reminder"
                                />
                                <span
                                    v-if="form.errors.name"
                                    class="text-sm text-destructive"
                                    >{{ form.errors.name }}</span
                                >
                            </label>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium"
                                    >Provider</span
                                >
                                <select
                                    v-model="form.provider"
                                    class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="auto">Auto</option>
                                    <option value="brevo">Brevo</option>
                                    <option value="resend">Resend</option>
                                </select>
                            </label>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium"
                                    >From name</span
                                >
                                <input
                                    v-model="form.from_name"
                                    class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    placeholder="Codezela Technologies"
                                />
                                <span
                                    v-if="form.errors.from_name"
                                    class="text-sm text-destructive"
                                    >{{ form.errors.from_name }}</span
                                >
                            </label>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium"
                                    >From email</span
                                >
                                <input
                                    v-model="form.from_email"
                                    class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    placeholder="team@codezela.com"
                                />
                                <span
                                    v-if="form.errors.from_email"
                                    class="text-sm text-destructive"
                                    >{{ form.errors.from_email }}</span
                                >
                            </label>
                            <label class="space-y-1.5 md:col-span-2">
                                <span class="text-sm font-medium"
                                    >Reply-to email</span
                                >
                                <input
                                    v-model="form.reply_to_email"
                                    class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    placeholder="Optional"
                                />
                                <span
                                    v-if="form.errors.reply_to_email"
                                    class="text-sm text-destructive"
                                    >{{ form.errors.reply_to_email }}</span
                                >
                            </label>
                        </div>

                        <div class="grid gap-4">
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium">Subject</span>
                                <input
                                    ref="subjectField"
                                    v-model="form.subject"
                                    class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    placeholder="Your payment reminder"
                                    @focus="activeInsertField = 'subject'"
                                />
                                <span
                                    v-if="form.errors.subject"
                                    class="text-sm text-destructive"
                                    >{{ form.errors.subject }}</span
                                >
                            </label>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium"
                                    >Preheader</span
                                >
                                <input
                                    ref="preheaderField"
                                    v-model="form.preheader"
                                    class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    placeholder="Short preview text shown in inboxes"
                                    @focus="activeInsertField = 'preheader'"
                                />
                            </label>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium"
                                    >HTML body</span
                                >
                                <textarea
                                    ref="htmlBodyField"
                                    v-model="form.html_body"
                                    class="min-h-[360px] w-full rounded-md border bg-background px-3 py-2 font-mono text-sm"
                                    spellcheck="false"
                                    @focus="activeInsertField = 'html_body'"
                                />
                                <span
                                    v-if="form.errors.html_body"
                                    class="text-sm text-destructive"
                                    >{{ form.errors.html_body }}</span
                                >
                            </label>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium"
                                    >Plain text fallback</span
                                >
                                <textarea
                                    ref="textBodyField"
                                    v-model="form.text_body"
                                    class="min-h-32 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    placeholder="Optional fallback for clients that do not render HTML"
                                    @focus="activeInsertField = 'text_body'"
                                />
                                <span
                                    v-if="form.errors.text_body"
                                    class="text-sm text-destructive"
                                    >{{ form.errors.text_body }}</span
                                >
                            </label>
                        </div>
                    </div>

                    <div
                        v-else-if="activeStep === 'audience'"
                        class="space-y-5"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">
                                Choose audience
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Use all contacts, narrow by list or tag, or add
                                individual contacts manually.
                            </p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <button
                                type="button"
                                :class="[
                                    'rounded-lg border p-4 text-left transition hover:bg-muted/60',
                                    form.target_type === 'all_contacts'
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                        : '',
                                ]"
                                @click="setAudienceType('all_contacts')"
                            >
                                <Users class="mb-3 h-5 w-5 text-primary" />
                                <div class="font-medium">All contacts</div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Send to every active contact that is not
                                    suppressed.
                                </p>
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-lg border p-4 text-left transition hover:bg-muted/60',
                                    form.target_type === 'manual_selection'
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                        : '',
                                ]"
                                @click="setAudienceType('manual_selection')"
                            >
                                <Search class="mb-3 h-5 w-5 text-primary" />
                                <div class="font-medium">
                                    Pick contacts manually
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Search and add exactly the people this
                                    campaign should reach.
                                </p>
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-lg border p-4 text-left transition hover:bg-muted/60',
                                    form.target_type === 'list'
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                        : '',
                                ]"
                                @click="setAudienceType('list')"
                            >
                                <div class="font-medium">Use lists</div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Combine one or more named lists.
                                </p>
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-lg border p-4 text-left transition hover:bg-muted/60',
                                    form.target_type === 'tag'
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                        : '',
                                ]"
                                @click="setAudienceType('tag')"
                            >
                                <div class="font-medium">Use tags</div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Target paid, unpaid, course, or custom tag
                                    groups.
                                </p>
                            </button>
                        </div>

                        <div
                            v-if="form.target_type === 'list'"
                            class="space-y-3"
                        >
                            <h3 class="font-medium">Lists</h3>
                            <div class="grid gap-2 md:grid-cols-2">
                                <button
                                    v-for="list in props.lists ?? []"
                                    :key="list.id"
                                    type="button"
                                    :class="[
                                        'flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-left text-sm transition hover:bg-muted/60',
                                        selectedListIds.includes(list.id)
                                            ? 'border-primary bg-primary/5'
                                            : '',
                                    ]"
                                    @click="
                                        toggleSelected(selectedListIds, list.id)
                                    "
                                >
                                    <span class="min-w-0">
                                        <span
                                            class="block truncate font-medium"
                                        >
                                            {{ list.name }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ list.contacts_count ?? 0 }}
                                            contacts
                                        </span>
                                    </span>
                                    <Check
                                        v-if="selectedListIds.includes(list.id)"
                                        class="h-4 w-4 shrink-0 text-primary"
                                    />
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="form.target_type === 'tag'"
                            class="space-y-3"
                        >
                            <h3 class="font-medium">Tags</h3>
                            <div class="grid gap-2 md:grid-cols-2">
                                <button
                                    v-for="tag in props.tags ?? []"
                                    :key="tag.id"
                                    type="button"
                                    :class="[
                                        'flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-left text-sm transition hover:bg-muted/60',
                                        selectedTagIds.includes(tag.id)
                                            ? 'border-primary bg-primary/5'
                                            : '',
                                    ]"
                                    @click="
                                        toggleSelected(selectedTagIds, tag.id)
                                    "
                                >
                                    <span class="min-w-0">
                                        <span
                                            class="block truncate font-medium"
                                        >
                                            {{ tag.name }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ tag.contacts_count ?? 0 }}
                                            contacts
                                        </span>
                                    </span>
                                    <Check
                                        v-if="selectedTagIds.includes(tag.id)"
                                        class="h-4 w-4 shrink-0 text-primary"
                                    />
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="form.target_type === 'manual_selection'"
                            class="space-y-4"
                        >
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium"
                                    >Search contacts</span
                                >
                                <div class="relative">
                                    <Search
                                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                                    />
                                    <input
                                        v-model="contactSearch"
                                        class="h-10 w-full rounded-md border bg-background pr-3 pl-9 text-sm"
                                        placeholder="Search name, email, or company"
                                    />
                                </div>
                            </label>

                            <div
                                v-if="contactSearchLoading"
                                class="rounded-md border bg-muted/40 px-3 py-2 text-sm text-muted-foreground"
                            >
                                Searching contacts...
                            </div>
                            <div
                                v-else-if="contactResults.length"
                                class="max-h-72 overflow-y-auto rounded-md border"
                            >
                                <button
                                    v-for="contact in contactResults"
                                    :key="contact.id"
                                    type="button"
                                    class="flex w-full items-center justify-between gap-3 border-b px-3 py-2 text-left text-sm last:border-b-0 hover:bg-muted/60"
                                    @click="addContact(contact)"
                                >
                                    <span class="min-w-0">
                                        <span
                                            class="block truncate font-medium"
                                        >
                                            {{ contact.name }}
                                        </span>
                                        <span
                                            class="block truncate text-xs text-muted-foreground"
                                        >
                                            {{ contact.email }}
                                        </span>
                                    </span>
                                    <span
                                        class="rounded-md border px-2 py-1 text-xs"
                                    >
                                        {{
                                            isSelectedContact(contact)
                                                ? 'Added'
                                                : 'Add'
                                        }}
                                    </span>
                                </button>
                            </div>
                            <div
                                v-else-if="contactSearch.trim().length >= 2"
                                class="rounded-md border bg-muted/40 px-3 py-2 text-sm text-muted-foreground"
                            >
                                No matching emailable contacts.
                            </div>

                            <div
                                class="min-h-24 rounded-md border bg-background p-3"
                            >
                                <div
                                    class="mb-2 flex items-center justify-between gap-3"
                                >
                                    <h3 class="text-sm font-medium">
                                        Selected contacts
                                    </h3>
                                    <span class="text-xs text-muted-foreground"
                                        >{{
                                            selectedContacts.length
                                        }}
                                        selected</span
                                    >
                                </div>
                                <div
                                    v-if="selectedContacts.length"
                                    class="flex flex-wrap gap-2"
                                >
                                    <button
                                        v-for="contact in selectedContacts"
                                        :key="contact.id"
                                        type="button"
                                        class="inline-flex max-w-full items-center gap-2 rounded-md border bg-card px-2.5 py-1.5 text-sm"
                                        @click="removeContact(contact)"
                                    >
                                        <span class="truncate">{{
                                            contact.email
                                        }}</span>
                                        <X class="h-3.5 w-3.5 shrink-0" />
                                    </button>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">
                                    Search above and add contacts to build this
                                    audience.
                                </p>
                            </div>
                        </div>

                        <p
                            v-if="form.errors.target_type"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.target_type }}
                        </p>
                        <p
                            v-if="!audienceReady"
                            class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            Select at least one audience item before saving.
                        </p>
                    </div>

                    <div v-else class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold">
                                Review campaign
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Confirm the content, sender, and audience before
                                saving the draft.
                            </p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="rounded-lg border p-4">
                                <div class="text-xs text-muted-foreground">
                                    Source
                                </div>
                                <div class="mt-1 font-medium">
                                    {{
                                        selectedTemplate?.name ??
                                        (contentMode === 'manual'
                                            ? 'Manual email'
                                            : 'No template selected')
                                    }}
                                </div>
                            </div>
                            <div class="rounded-lg border p-4">
                                <div class="text-xs text-muted-foreground">
                                    Audience
                                </div>
                                <div class="mt-1 font-medium">
                                    {{ audienceSummary }}
                                </div>
                            </div>
                            <div class="rounded-lg border p-4">
                                <div class="text-xs text-muted-foreground">
                                    Sendable estimate
                                </div>
                                <div class="mt-1 font-medium">
                                    {{
                                        estimateLoading
                                            ? 'Checking...'
                                            : (estimate?.sendable_count ?? 0)
                                    }}
                                </div>
                            </div>
                            <div class="rounded-lg border p-4">
                                <div class="text-xs text-muted-foreground">
                                    Provider
                                </div>
                                <div class="mt-1 font-medium">
                                    {{ form.provider || 'Auto' }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!hasUnsubscribe"
                            class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            This message does not include
                            <code v-pre>{{ unsubscribe_url }}</code
                            >. Sending will be blocked until the unsubscribe URL
                            token is present.
                        </div>
                        <div
                            v-if="campaignError"
                            class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                        >
                            {{ campaignError }}
                        </div>
                        <label
                            v-if="props.campaign?.id"
                            class="block space-y-1.5"
                        >
                            <span class="text-sm font-medium"
                                >Schedule send</span
                            >
                            <input
                                v-model="form.scheduled_at"
                                class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                type="datetime-local"
                            />
                            <span class="text-xs text-muted-foreground">
                                Leave blank to send immediately from this
                                campaign.
                            </span>
                        </label>
                    </div>
                </section>

                <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start">
                    <section class="rounded-lg border bg-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold">Campaign summary</h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ audienceSummary }}
                                </p>
                            </div>
                            <Mail class="h-5 w-5 text-primary" />
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-md border p-3">
                                <dt class="text-muted-foreground">Sendable</dt>
                                <dd class="mt-1 text-lg font-semibold">
                                    {{
                                        estimateLoading
                                            ? '...'
                                            : (estimate?.sendable_count ?? 0)
                                    }}
                                </dd>
                            </div>
                            <div class="rounded-md border p-3">
                                <dt class="text-muted-foreground">
                                    Suppressed
                                </dt>
                                <dd class="mt-1 text-lg font-semibold">
                                    {{
                                        estimateLoading
                                            ? '...'
                                            : (estimate?.suppressed_count ?? 0)
                                    }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4 space-y-2 text-sm">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-muted-foreground"
                                    >Sender</span
                                >
                                <span class="truncate font-medium">{{
                                    form.from_name || 'Not set'
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-muted-foreground"
                                    >Subject</span
                                >
                                <span class="truncate font-medium">{{
                                    form.subject || 'Not set'
                                }}</span>
                            </div>
                        </div>
                    </section>

                    <VariablePicker
                        :variables="props.variableDefinitions"
                        :active-field-label="activeFieldLabel"
                        @insert="insertVariable"
                    />

                    <section class="rounded-lg border bg-card p-5">
                        <div
                            class="mb-3 flex items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <h2 class="font-semibold">Live preview</h2>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ samplePreheader }}
                                </p>
                            </div>
                        </div>
                        <div class="rounded-md border bg-background p-3">
                            <div class="truncate text-sm font-medium">
                                {{ sampleSubject }}
                            </div>
                            <iframe
                                class="mt-3 h-[520px] w-full rounded border bg-white"
                                :srcdoc="previewHtml"
                                sandbox="allow-popups allow-popups-to-escape-sandbox"
                                title="Campaign email preview"
                            ></iframe>
                        </div>
                    </section>
                </aside>
            </div>

            <div
                class="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-sm text-muted-foreground">
                    <span v-if="canSave">Ready to save as a draft.</span>
                    <span v-else>
                        Complete the sender, content, and audience details to
                        save.
                    </span>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button
                        v-if="activeStepIndex > 0"
                        type="button"
                        class="rounded-md border px-3 py-2 text-sm transition hover:bg-muted"
                        @click="previousStep"
                    >
                        Back
                    </button>
                    <button
                        v-if="activeStepIndex < steps.length - 1"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm transition hover:bg-muted"
                        @click="nextStep"
                    >
                        Continue
                        <ChevronRight class="h-4 w-4" />
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing || !canSave"
                    >
                        <Check class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : saveLabel }}
                    </button>
                    <button
                        v-if="props.campaign?.id && activeStep === 'review'"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="
                            form.processing || !canSave || !form.scheduled_at
                        "
                        @click="openSendDialog('schedule')"
                    >
                        Schedule
                    </button>
                    <button
                        v-if="props.campaign?.id && activeStep === 'review'"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing || !canSave"
                        @click="openSendDialog('send')"
                    >
                        Send now
                    </button>
                </div>
            </div>

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
                            <h2 class="text-lg font-semibold">
                                {{
                                    sendAction === 'schedule'
                                        ? 'Schedule campaign'
                                        : 'Send campaign'
                                }}
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                The latest edits will be saved first, then
                                recipients will be prepared from your selected
                                audience.
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
                            <span
                                class="mt-1 block text-sm text-muted-foreground"
                            >
                                Rebuild recipients from the current filters and
                                send to every matching emailable contact.
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
                            <span class="font-medium"
                                >Only add new contacts</span
                            >
                            <span
                                class="mt-1 block text-sm text-muted-foreground"
                            >
                                Keep already prepared recipients and add only
                                contacts newly matching this audience.
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
                            class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="form.processing"
                            @click="confirmSend"
                        >
                            {{
                                sendAction === 'schedule'
                                    ? 'Save and schedule'
                                    : 'Save and queue'
                            }}
                        </button>
                    </div>
                </section>
            </div>
        </form>
    </main>
</template>
