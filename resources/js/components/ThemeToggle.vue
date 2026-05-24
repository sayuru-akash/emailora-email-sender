<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();
const mounted = ref(false);

const isDark = computed(() => resolvedAppearance.value === 'dark');
const nextLabel = computed(() =>
    !mounted.value
        ? 'Toggle theme'
        : isDark.value
          ? 'Switch to light mode'
          : 'Switch to dark mode',
);

onMounted(() => {
    mounted.value = true;
});

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
        <Sun
            v-if="mounted && isDark"
            class="size-4"
            aria-hidden="true"
            focusable="false"
        />
        <Moon v-else class="size-4" aria-hidden="true" focusable="false" />
    </button>
</template>
