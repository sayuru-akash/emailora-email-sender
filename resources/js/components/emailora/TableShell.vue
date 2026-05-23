<script setup lang="ts">
withDefaults(
    defineProps<{
        minWidth?: string;
    }>(),
    {
        minWidth: '760px',
    },
);
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-border bg-card">
        <div class="w-full overflow-x-auto overscroll-x-contain">
            <div :style="{ minWidth }">
                <slot />
            </div>
        </div>
        <slot name="footer" />
    </div>
</template>
