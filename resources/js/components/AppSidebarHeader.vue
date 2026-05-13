<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { RefreshCw } from 'lucide-vue-next';
import { ref } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const refreshing = ref(false);

function refreshPage() {
    refreshing.value = true;
    router.reload({
        onFinish: () => {
            refreshing.value = false;
        },
    });
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-border bg-white px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex flex-1 items-center justify-end gap-3">
            <GlobalSearch />
            <button
                type="button"
                class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-white text-muted-foreground hover:text-foreground"
                aria-label="Refresh"
                @click="refreshPage"
            >
                <RefreshCw :class="['size-4', refreshing ? 'animate-spin' : '']" />
            </button>
        </div>
    </header>
</template>
