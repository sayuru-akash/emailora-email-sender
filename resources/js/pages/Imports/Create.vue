<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Download, FileSpreadsheet, UploadCloud } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import InputError from '@/components/InputError.vue';

type AudienceItem = { id: number; name: string };
type DuplicateOption = { value: string; label: string; description: string };

const props = defineProps<{
    lists?: AudienceItem[];
    tags?: AudienceItem[];
    duplicateOptions?: DuplicateOption[];
}>();

const dragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const form = useForm({
    file: null as File | null,
    duplicate_handling: 'skip',
    list_ids: [] as number[],
    tag_ids: [] as number[],
});

const selectedFileSize = computed(() => {
    if (!form.file) {
        return null;
    }

    return `${(form.file.size / 1024 / 1024).toFixed(2)} MB`;
});

function chooseFile(file?: File | null) {
    form.file = file ?? null;
    form.clearErrors('file');
}

function dropFile(event: DragEvent) {
    dragging.value = false;
    chooseFile(event.dataTransfer?.files?.[0]);
}

function submit() {
    form.post('/imports/upload', {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Import Contacts" />
    <main class="mx-auto w-full max-w-6xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Import Contacts"
            subtitle="Upload contacts with a validation preview before anything is written."
        >
            <template #actions>
                <a
                    class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm hover:bg-accent"
                    href="/imports/sample/csv"
                >
                    <Download class="h-4 w-4" />
                    CSV sample
                </a>
                <a
                    class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm hover:bg-accent"
                    href="/imports/sample/xlsx"
                >
                    <Download class="h-4 w-4" />
                    XLSX sample
                </a>
            </template>
        </PageHeader>

        <form
            class="grid gap-5 lg:grid-cols-[minmax(0,1.25fr)_minmax(320px,.75fr)]"
            @submit.prevent="submit"
        >
            <section class="rounded-lg border border-border bg-card p-5">
                <div
                    :class="[
                        'flex min-h-64 cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed p-6 text-center transition-colors',
                        dragging
                            ? 'border-primary bg-primary/5'
                            : 'border-border bg-background hover:border-primary/50',
                    ]"
                    role="button"
                    tabindex="0"
                    @click="fileInput?.click()"
                    @keydown.enter.prevent="fileInput?.click()"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dropFile"
                >
                    <input
                        ref="fileInput"
                        class="sr-only"
                        type="file"
                        accept=".csv,.txt,.xlsx"
                        @input="
                            chooseFile(
                                ($event.target as HTMLInputElement).files?.[0],
                            )
                        "
                    />
                    <div class="rounded-md bg-primary/10 p-3 text-primary">
                        <UploadCloud class="h-7 w-7" />
                    </div>
                    <h2 class="mt-4 text-lg font-semibold">
                        Select a CSV or XLSX file
                    </h2>
                    <p class="mt-2 max-w-md text-sm text-muted-foreground">
                        Use the sample file columns where possible. Extra
                        columns are preserved as contact metadata.
                    </p>
                    <div
                        v-if="form.file"
                        class="mt-5 flex max-w-full items-center gap-3 rounded-md border border-border bg-card px-4 py-3 text-left"
                    >
                        <FileSpreadsheet
                            class="h-5 w-5 shrink-0 text-primary"
                        />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ form.file.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ selectedFileSize }}
                            </p>
                        </div>
                    </div>
                </div>
                <InputError class="mt-3" :message="form.errors.file" />
            </section>

            <aside class="space-y-5">
                <section class="rounded-lg border border-border bg-card p-5">
                    <h2 class="text-base font-semibold">Duplicate handling</h2>
                    <div class="mt-3 space-y-2">
                        <label
                            v-for="option in props.duplicateOptions ?? []"
                            :key="option.value"
                            :class="[
                                'block cursor-pointer rounded-md border p-3 transition-colors',
                                form.duplicate_handling === option.value
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border hover:bg-accent',
                            ]"
                        >
                            <input
                                v-model="form.duplicate_handling"
                                class="sr-only"
                                name="duplicate_handling"
                                type="radio"
                                :value="option.value"
                            />
                            <span class="text-sm font-medium">{{
                                option.label
                            }}</span>
                            <span
                                class="mt-1 block text-xs leading-5 text-muted-foreground"
                                >{{ option.description }}</span
                            >
                        </label>
                    </div>
                    <InputError
                        class="mt-3"
                        :message="form.errors.duplicate_handling"
                    />
                </section>

                <section class="rounded-lg border border-border bg-card p-5">
                    <h2 class="text-base font-semibold">Apply lists</h2>
                    <div class="mt-3 grid max-h-44 gap-2 overflow-y-auto pr-1">
                        <label
                            v-for="list in props.lists ?? []"
                            :key="list.id"
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                v-model="form.list_ids"
                                type="checkbox"
                                :value="list.id"
                            />
                            <span class="truncate">{{ list.name }}</span>
                        </label>
                        <p
                            v-if="!(props.lists ?? []).length"
                            class="text-sm text-muted-foreground"
                        >
                            No active lists yet.
                        </p>
                    </div>
                </section>

                <section class="rounded-lg border border-border bg-card p-5">
                    <h2 class="text-base font-semibold">Apply tags</h2>
                    <div class="mt-3 grid max-h-44 gap-2 overflow-y-auto pr-1">
                        <label
                            v-for="tag in props.tags ?? []"
                            :key="tag.id"
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                v-model="form.tag_ids"
                                type="checkbox"
                                :value="tag.id"
                            />
                            <span class="truncate">{{ tag.name }}</span>
                        </label>
                        <p
                            v-if="!(props.tags ?? []).length"
                            class="text-sm text-muted-foreground"
                        >
                            No tags yet.
                        </p>
                    </div>
                </section>

                <button
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="form.processing || !form.file"
                    type="submit"
                >
                    <UploadCloud class="h-4 w-4" />
                    Upload and preview
                </button>
                <Link
                    class="block text-center text-sm text-muted-foreground hover:text-primary"
                    href="/imports"
                >
                    Back to imports
                </Link>
            </aside>
        </form>
    </main>
</template>
