<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');
const nextLabel = computed(() =>
    isDark.value ? 'Switch to light mode' : 'Switch to dark mode',
);

function toggleTheme() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <button
        type="button"
        class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-card text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        :aria-label="nextLabel"
        :title="nextLabel"
        @click="toggleTheme"
    >
        <Sun v-if="isDark" class="size-4" />
        <Moon v-else class="size-4" />
    </button>
</template>
