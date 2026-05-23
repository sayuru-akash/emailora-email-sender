<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="rounded-lg border border-dashed border-border bg-card p-8 text-center"
    >
        <Inbox class="mx-auto size-8 text-muted-foreground" />
        <div class="mt-3 font-medium">{{ title }}</div>
        <div v-if="description" class="mt-1 text-sm text-muted-foreground">
            {{ description }}
        </div>
    </div>
</template>
