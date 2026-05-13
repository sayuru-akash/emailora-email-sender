<script setup lang="ts">
defineProps<{
    status?: string | null;
}>();
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium',
            ['active', 'sent', 'delivered', 'completed'].includes(status ?? '') ? 'bg-emerald-50 text-emerald-700' : '',
            ['failed', 'bounced', 'complained', 'blocked', 'cancelled'].includes(status ?? '') ? 'bg-red-50 text-red-700' : '',
            ['queued', 'preparing', 'sending', 'scheduled', 'paused'].includes(status ?? '') ? 'bg-amber-50 text-amber-700' : '',
            !['active', 'sent', 'delivered', 'completed', 'failed', 'bounced', 'complained', 'blocked', 'cancelled', 'queued', 'preparing', 'sending', 'scheduled', 'paused'].includes(status ?? '') ? 'bg-gray-100 text-gray-700' : '',
        ]"
    >
        {{ status ?? 'unknown' }}
    </span>
</template>
