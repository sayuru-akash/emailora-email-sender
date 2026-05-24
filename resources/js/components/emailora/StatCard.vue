<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    tone?: 'default' | 'success' | 'danger' | 'warning';
}>();
</script>

<template>
    <div
        class="rounded-lg border border-border/70 bg-card/80 p-4 transition-colors hover:bg-muted/30"
    >
        <div class="text-xs font-medium tracking-wide text-muted-foreground">
            {{ label }}
        </div>
        <div
            :class="[
                'mt-2 text-2xl font-semibold text-foreground tabular-nums',
                tone === 'success' ? 'text-emerald-600' : '',
                tone === 'danger' ? 'text-red-600' : '',
                tone === 'warning' ? 'text-amber-600' : '',
            ]"
        >
            {{ value }}
        </div>
    </div>
</template>
