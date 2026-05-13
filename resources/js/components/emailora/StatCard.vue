<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    tone?: 'default' | 'success' | 'danger' | 'warning';
}>();
</script>

<template>
    <div class="rounded-lg border border-border bg-white p-4 shadow-sm">
        <div class="text-sm text-muted-foreground">{{ label }}</div>
        <div
            :class="[
                'mt-2 text-2xl font-semibold',
                tone === 'success' ? 'text-emerald-600' : '',
                tone === 'danger' ? 'text-red-600' : '',
                tone === 'warning' ? 'text-amber-600' : '',
            ]"
        >
            {{ value }}
        </div>
    </div>
</template>
