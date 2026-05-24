<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, RefreshCw, Send } from 'lucide-vue-next';
import { computed } from 'vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';
import InputError from '@/components/InputError.vue';

type PreviewRow = {
    row_number: number;
    status: string;
    is_duplicate: boolean;
    errors: string[];
    warnings: string[];
    raw_data: Record<string, unknown>;
    mapped_data: Record<string, unknown>;
    email_normalized?: string | null;
};

const props = defineProps<{
    import: any;
    headers?: string[];
    mapping?: Record<string, string>;
    previewRows?: PreviewRow[];
    summary?: {
        total_rows: number;
        valid_rows: number;
        invalid_rows: number;
        duplicate_rows: number;
        warnings: string[];
    };
    fieldOptions?: Record<string, string>;
}>();

const form = useForm({
    mapping: { ...(props.mapping ?? {}) } as Record<string, string>,
});

const canConfirm = computed(() => Boolean(form.mapping.email));
const stats = computed(() => [
    { label: 'Rows', value: props.summary?.total_rows ?? 0 },
    { label: 'Valid', value: props.summary?.valid_rows ?? 0 },
    { label: 'Invalid', value: props.summary?.invalid_rows ?? 0 },
    { label: 'Duplicates', value: props.summary?.duplicate_rows ?? 0 },
]);

function refreshPreview() {
    form.post(`/imports/${props.import.id}/preview`, {
        preserveScroll: true,
    });
}

function confirmImport() {
    if (!canConfirm.value) {
        return;
    }

    router.post(
        `/imports/${props.import.id}/confirm`,
        {},
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Import Mapping" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            :title="props.import.file_name"
            subtitle="Review the detected columns and validation result before processing."
        >
            <template #actions>
                <Link
                    class="rounded-md border border-border px-3 py-2 text-sm hover:bg-accent"
                    href="/imports/create"
                >
                    New import
                </Link>
                <button
                    class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!canConfirm || form.processing"
                    type="button"
                    @click="confirmImport"
                >
                    <Send class="h-4 w-4" />
                    Confirm import
                </button>
            </template>
        </PageHeader>

        <div class="grid gap-4 md:grid-cols-4">
            <section
                v-for="item in stats"
                :key="item.label"
                class="rounded-lg border border-border bg-card p-4"
            >
                <p class="text-sm text-muted-foreground">{{ item.label }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ item.value }}</p>
            </section>
        </div>

        <div
            v-if="(props.summary?.warnings ?? []).length"
            class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
        >
            <p v-for="warning in props.summary?.warnings ?? []" :key="warning">
                {{ warning }}
            </p>
        </div>

        <section class="mt-6 rounded-lg border border-border bg-card p-5">
            <div
                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2 class="text-base font-semibold">Column mapping</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Only mapped contact fields are written. Unmapped file
                        columns stay in metadata.
                    </p>
                </div>
                <button
                    class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm hover:bg-accent disabled:opacity-60"
                    :disabled="form.processing"
                    type="button"
                    @click="refreshPreview"
                >
                    <RefreshCw class="h-4 w-4" />
                    Refresh preview
                </button>
            </div>
            <InputError class="mt-3" :message="form.errors['mapping.email']" />
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <label
                    v-for="(label, field) in props.fieldOptions ?? {}"
                    :key="field"
                    class="grid gap-1 text-sm"
                >
                    <span class="font-medium">{{ label }}</span>
                    <select
                        v-model="form.mapping[field]"
                        class="h-10 rounded-md border border-border bg-background px-3 text-sm"
                    >
                        <option value="">Do not import</option>
                        <option
                            v-for="header in props.headers ?? []"
                            :key="header"
                            :value="header"
                        >
                            {{ header }}
                        </option>
                    </select>
                </label>
            </div>
        </section>

        <section class="mt-6">
            <PageHeader
                title="Validation Preview"
                :subtitle="`${props.previewRows?.length ?? 0} preview rows shown`"
            />
            <TableShell min-width="1120px">
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Row</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Company</th>
                            <th class="px-4 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in props.previewRows ?? []"
                            :key="row.row_number"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ row.row_number }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <StatusBadge :status="row.status" />
                                    <span
                                        v-if="row.is_duplicate"
                                        class="rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700"
                                        >duplicate</span
                                    >
                                </div>
                            </td>
                            <td class="max-w-64 truncate px-4 py-3">
                                {{ row.email_normalized ?? '-' }}
                            </td>
                            <td class="max-w-64 truncate px-4 py-3">
                                {{
                                    row.mapped_data.full_name ||
                                    [
                                        row.mapped_data.first_name,
                                        row.mapped_data.last_name,
                                    ]
                                        .filter(Boolean)
                                        .join(' ') ||
                                    '-'
                                }}
                            </td>
                            <td class="max-w-52 truncate px-4 py-3">
                                {{ row.mapped_data.company ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    v-if="row.errors?.length"
                                    class="text-red-600"
                                >
                                    {{ row.errors.join(' ') }}
                                </div>
                                <div
                                    v-else-if="row.warnings?.length"
                                    class="text-amber-700"
                                >
                                    {{ row.warnings.join(' ') }}
                                </div>
                                <div
                                    v-else
                                    class="inline-flex items-center gap-1 text-emerald-700"
                                >
                                    <CheckCircle2 class="h-4 w-4" />
                                    Ready
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableShell>
        </section>
    </main>
</template>
