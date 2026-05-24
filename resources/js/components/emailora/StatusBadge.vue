<script setup lang="ts">
defineProps<{
    status?: string | null;
}>();
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize',
            ['active', 'sent', 'delivered', 'completed'].includes(status ?? '')
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300'
                : '',
            [
                'failed',
                'bounced',
                'complained',
                'blocked',
                'cancelled',
            ].includes(status ?? '')
                ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300'
                : '',
            ['queued', 'preparing', 'sending', 'scheduled', 'paused'].includes(
                status ?? '',
            )
                ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300'
                : '',
            ![
                'active',
                'sent',
                'delivered',
                'completed',
                'failed',
                'bounced',
                'complained',
                'blocked',
                'cancelled',
                'queued',
                'preparing',
                'sending',
                'scheduled',
                'paused',
            ].includes(status ?? '')
                ? 'border-border bg-muted text-muted-foreground'
                : '',
        ]"
    >
        {{ status ?? 'unknown' }}
    </span>
</template>
