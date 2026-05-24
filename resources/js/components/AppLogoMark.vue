<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

type Props = {
    class?: HTMLAttributes['class'];
    iconClass?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <div
        :class="[
            'flex items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm',
            $props.class,
        ]"
    >
        <AppLogoIcon
            :class="['text-primary-foreground', iconClass]"
            aria-hidden="true"
            focusable="false"
        />
    </div>
</template>
