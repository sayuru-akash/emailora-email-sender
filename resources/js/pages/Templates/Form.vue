<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Check, Code2, Eye, FileText } from 'lucide-vue-next';
import { computed } from 'vue';
import PageHeader from '@/components/emailora/PageHeader.vue';

const props = defineProps<{ template?: any }>();

const defaultHtml =
    '<p>Hello {first_name},</p><p><a href="{unsubscribe_url}">Unsubscribe</a></p>';

const form = useForm({
    name: props.template?.name ?? '',
    category: props.template?.category ?? 'newsletter',
    subject: props.template?.subject ?? '',
    preheader: props.template?.preheader ?? '',
    html_body: props.template?.html_body ?? defaultHtml,
    text_body: props.template?.text_body ?? '',
    status: props.template?.status ?? 'active',
});

const isEditing = computed(() => Boolean(props.template?.id));
const pageTitle = computed(() =>
    isEditing.value ? 'Edit Template' : 'Create Template',
);
const previewHtml = computed(() => {
    const html = form.html_body || defaultHtml;

    if (/<base\s/i.test(html)) {
        return html;
    }

    if (/<head\b/i.test(html)) {
        return html.replace(/<head([^>]*)>/i, '<head$1><base target="_blank">');
    }

    return `<!doctype html><html><head><meta charset="utf-8"><base target="_blank"></head><body>${html}</body></html>`;
});
const htmlLength = computed(() => form.html_body.length.toLocaleString());
const textLength = computed(() => form.text_body.length.toLocaleString());
const hasRequiredContent = computed(
    () =>
        Boolean(form.name.trim()) &&
        Boolean(form.subject.trim()) &&
        (Boolean(form.html_body.trim()) || Boolean(form.text_body.trim())),
);
const saveLabel = computed(() =>
    isEditing.value ? 'Update template' : 'Create template',
);

function submit() {
    const options = { preserveScroll: true };

    if (props.template?.id) {
        form.put(`/templates/${props.template.id}`, options);

        return;
    }

    form.post('/templates', options);
}
</script>

<template>
    <Head :title="pageTitle" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="pageTitle"
            subtitle="Edit reusable subject, inbox preview text, HTML, and plain text fallback."
        >
            <template #actions>
                <Link
                    v-if="props.template?.id"
                    class="rounded-md border bg-card px-3 py-2 text-sm text-foreground transition hover:bg-muted"
                    :href="`/templates/${props.template.id}`"
                >
                    View Template
                </Link>
                <Link
                    class="rounded-md border bg-card px-3 py-2 text-sm text-foreground transition hover:bg-muted"
                    href="/templates"
                >
                    Templates
                </Link>
            </template>
        </PageHeader>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_440px]">
                <section
                    class="min-w-0 space-y-5 rounded-lg border bg-card p-5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">
                                Template details
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Keep the metadata clean so campaigns can pick
                                the right template quickly.
                            </p>
                        </div>
                        <span
                            class="rounded-md border px-2.5 py-1 text-xs font-medium capitalize"
                        >
                            {{ form.status }}
                        </span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-1.5 md:col-span-2">
                            <span class="text-sm font-medium"
                                >Template name</span
                            >
                            <input
                                v-model="form.name"
                                class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                placeholder="CCA payment reminder"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                                >{{ form.errors.name }}</span
                            >
                        </label>

                        <label class="space-y-1.5">
                            <span class="text-sm font-medium">Category</span>
                            <input
                                v-model="form.category"
                                class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                placeholder="newsletter"
                            />
                            <span
                                v-if="form.errors.category"
                                class="text-sm text-destructive"
                                >{{ form.errors.category }}</span
                            >
                        </label>

                        <label class="space-y-1.5">
                            <span class="text-sm font-medium">Status</span>
                            <select
                                v-model="form.status"
                                class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <span
                                v-if="form.errors.status"
                                class="text-sm text-destructive"
                                >{{ form.errors.status }}</span
                            >
                        </label>

                        <label class="space-y-1.5 md:col-span-2">
                            <span class="text-sm font-medium">Subject</span>
                            <input
                                v-model="form.subject"
                                class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                placeholder="Your payment reminder"
                            />
                            <span
                                v-if="form.errors.subject"
                                class="text-sm text-destructive"
                                >{{ form.errors.subject }}</span
                            >
                        </label>

                        <label class="space-y-1.5 md:col-span-2">
                            <span class="text-sm font-medium">Preheader</span>
                            <textarea
                                v-model="form.preheader"
                                class="min-h-24 w-full resize-y rounded-md border bg-background px-3 py-2 text-sm"
                                maxlength="180"
                                placeholder="Inbox preview text"
                            />
                            <div
                                class="flex items-center justify-between gap-3 text-xs text-muted-foreground"
                            >
                                <span
                                    v-if="form.errors.preheader"
                                    class="text-destructive"
                                    >{{ form.errors.preheader }}</span
                                >
                                <span v-else>
                                    Short text shown after the subject in
                                    inboxes.
                                </span>
                                <span
                                    >{{ form.preheader?.length ?? 0 }}/180</span
                                >
                            </div>
                        </label>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold">
                                    Email content
                                </h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Edit HTML and plain text side by side with
                                    the preview.
                                </p>
                            </div>
                            <div
                                class="flex gap-2 text-xs text-muted-foreground"
                            >
                                <span class="rounded-md border px-2 py-1"
                                    >HTML {{ htmlLength }}</span
                                >
                                <span class="rounded-md border px-2 py-1"
                                    >Text {{ textLength }}</span
                                >
                            </div>
                        </div>

                        <label class="space-y-1.5">
                            <span
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <Code2 class="h-4 w-4 text-primary" />
                                HTML body
                            </span>
                            <textarea
                                v-model="form.html_body"
                                class="min-h-[520px] w-full resize-y rounded-md border bg-background px-3 py-2 font-mono text-sm leading-6"
                                spellcheck="false"
                            />
                            <span
                                v-if="form.errors.html_body"
                                class="text-sm text-destructive"
                                >{{ form.errors.html_body }}</span
                            >
                        </label>

                        <label class="space-y-1.5">
                            <span
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <FileText class="h-4 w-4 text-primary" />
                                Plain text fallback
                            </span>
                            <textarea
                                v-model="form.text_body"
                                class="min-h-40 w-full resize-y rounded-md border bg-background px-3 py-2 text-sm leading-6"
                                placeholder="Optional fallback for clients that do not render HTML"
                            />
                            <span
                                v-if="form.errors.text_body"
                                class="text-sm text-destructive"
                                >{{ form.errors.text_body }}</span
                            >
                        </label>
                    </div>
                </section>

                <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start">
                    <section class="rounded-lg border bg-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="font-semibold">Preview</h2>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ form.preheader || 'No preheader set' }}
                                </p>
                            </div>
                            <Eye class="h-5 w-5 text-primary" />
                        </div>

                        <div class="mt-4 rounded-md border bg-background p-3">
                            <div class="truncate text-sm font-medium">
                                {{ form.subject || 'Subject' }}
                            </div>
                            <iframe
                                class="mt-3 h-[640px] w-full rounded border bg-white"
                                :srcdoc="previewHtml"
                                sandbox="allow-popups allow-popups-to-escape-sandbox"
                                title="Template email preview"
                            ></iframe>
                        </div>
                    </section>

                    <section class="rounded-lg border bg-card p-5">
                        <h2 class="font-semibold">Save checks</h2>
                        <div class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <Check
                                    :class="[
                                        'h-4 w-4',
                                        form.name.trim()
                                            ? 'text-emerald-600'
                                            : 'text-muted-foreground',
                                    ]"
                                />
                                <span>Template name set</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Check
                                    :class="[
                                        'h-4 w-4',
                                        form.subject.trim()
                                            ? 'text-emerald-600'
                                            : 'text-muted-foreground',
                                    ]"
                                />
                                <span>Subject set</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Check
                                    :class="[
                                        'h-4 w-4',
                                        form.html_body.trim() ||
                                        form.text_body.trim()
                                            ? 'text-emerald-600'
                                            : 'text-muted-foreground',
                                    ]"
                                />
                                <span>HTML or text body set</span>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>

            <div
                class="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-sm text-muted-foreground">
                    <span v-if="hasRequiredContent">
                        Ready to save this template.
                    </span>
                    <span v-else>
                        Complete the required name, subject, and body fields to
                        save.
                    </span>
                </div>
                <div class="flex justify-end gap-2">
                    <Link
                        class="rounded-md border px-3 py-2 text-sm transition hover:bg-muted"
                        :href="
                            props.template?.id
                                ? `/templates/${props.template.id}`
                                : '/templates'
                        "
                    >
                        Cancel
                    </Link>
                    <button
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing || !hasRequiredContent"
                    >
                        <Check class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : saveLabel }}
                    </button>
                </div>
            </div>
        </form>
    </main>
</template>
