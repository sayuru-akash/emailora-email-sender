<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    pageName?: string;
    meta?: {
        current_page?: number;
        last_page?: number;
        per_page?: number;
        total?: number;
        from?: number | null;
        to?: number | null;
    };
}>();

const page = usePage();
const perPageOptions = [10, 25, 50, 100];

const currentPage = computed(() => props.meta?.current_page ?? 1);
const lastPage = computed(() => props.meta?.last_page ?? 1);
const total = computed(() => props.meta?.total ?? 0);
const currentPerPage = computed(() => props.meta?.per_page ?? 25);
const canGoPrevious = computed(() => currentPage.value > 1);
const canGoNext = computed(() => currentPage.value < lastPage.value);
const pageName = computed(() => props.pageName ?? 'page');

function hrefFor(updates: Record<string, string | number | null | undefined>) {
    const [path, query = ''] = (page.url || '').split('?');
    const params = new URLSearchParams(query);

    Object.entries(updates).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            params.delete(key);

            return;
        }

        params.set(key, String(value));
    });

    const nextQuery = params.toString();

    return nextQuery ? `${path}?${nextQuery}` : path;
}

function goToPerPage(event: Event) {
    const value = (event.target as HTMLSelectElement).value;

    router.get(
        hrefFor({ per_page: value, [pageName.value]: null }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}
</script>

<template>
    <div
        v-if="total > 0"
        class="flex flex-col gap-3 border-t border-border px-4 py-3 text-sm md:flex-row md:items-center md:justify-between"
    >
        <div class="flex flex-wrap items-center gap-3 text-muted-foreground">
            <span>
                {{ meta?.from ?? 0 }}-{{ meta?.to ?? 0 }} of
                {{ meta?.total ?? 0 }}
            </span>
            <label class="flex items-center gap-2">
                <span>Rows</span>
                <select
                    class="h-8 rounded-md border border-border bg-background px-2 text-foreground"
                    :value="currentPerPage"
                    @change="goToPerPage"
                >
                    <option
                        v-for="option in perPageOptions"
                        :key="option"
                        :value="option"
                    >
                        {{ option }}
                    </option>
                </select>
            </label>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-muted-foreground"
                >Page {{ currentPage }} of {{ lastPage }}</span
            >
            <Link
                v-if="canGoPrevious"
                :href="hrefFor({ [pageName]: currentPage - 1 })"
                class="rounded-md border border-border px-3 py-1.5 transition-colors hover:bg-accent hover:text-accent-foreground"
                preserve-scroll
            >
                Previous
            </Link>
            <button
                v-else
                class="cursor-not-allowed rounded-md border border-border px-3 py-1.5 text-muted-foreground opacity-60"
                type="button"
                disabled
            >
                Previous
            </button>
            <Link
                v-if="canGoNext"
                :href="hrefFor({ [pageName]: currentPage + 1 })"
                class="rounded-md border border-border px-3 py-1.5 transition-colors hover:bg-accent hover:text-accent-foreground"
                preserve-scroll
            >
                Next
            </Link>
            <button
                v-else
                class="cursor-not-allowed rounded-md border border-border px-3 py-1.5 text-muted-foreground opacity-60"
                type="button"
                disabled
            >
                Next
            </button>
        </div>
    </div>
</template>
