<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    meta?: {
        current_page?: number;
        last_page?: number;
        total?: number;
        from?: number | null;
        to?: number | null;
    };
}>();
</script>

<template>
    <div v-if="(props.meta?.last_page ?? 1) > 1" class="flex items-center justify-between border-t border-border px-4 py-3 text-sm">
        <span class="text-muted-foreground">{{ meta?.from ?? 0 }}-{{ meta?.to ?? 0 }} of {{ meta?.total ?? 0 }}</span>
        <div class="flex gap-2">
            <Link
                :href="`?page=${Math.max(1, (meta?.current_page ?? 1) - 1)}`"
                class="rounded-md border border-border px-3 py-1.5"
            >
                Previous
            </Link>
            <Link
                :href="`?page=${Math.min(meta?.last_page ?? 1, (meta?.current_page ?? 1) + 1)}`"
                class="rounded-md border border-border px-3 py-1.5"
            >
                Next
            </Link>
        </div>
    </div>
</template>
