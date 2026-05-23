<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        fill="none"
        v-bind="$attrs"
    >
        <path
            d="M7.5 12.5A4.5 4.5 0 0 1 12 8h16a4.5 4.5 0 0 1 4.5 4.5v12A4.5 4.5 0 0 1 28 29H12a4.5 4.5 0 0 1-4.5-4.5v-12Z"
            stroke="currentColor"
            stroke-width="3"
        />
        <path
            d="m9.5 12 10.1 8.2a2 2 0 0 0 2.5 0L32 12"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="3"
        />
        <path
            d="M24 25h8m0 0-3-3m3 3-3 3"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="3"
        />
    </svg>
</template>
