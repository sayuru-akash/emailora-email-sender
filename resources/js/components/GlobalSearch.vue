<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

type SearchItem = {
    id: string;
    title: string;
    subtitle: string;
    badge: string;
    url: string;
};

type SearchGroup = {
    label: string;
    items: SearchItem[];
};

const query = ref('');
const groups = ref<SearchGroup[]>([]);
const open = ref(false);
const loading = ref(false);
let controller: AbortController | null = null;
let timer: number | null = null;

watch(query, (value) => {
    if (timer) {
        window.clearTimeout(timer);
    }

    if (controller) {
        controller.abort();
    }

    if (value.trim().length < 2) {
        groups.value = [];
        open.value = false;

        return;
    }

    timer = window.setTimeout(async () => {
        controller = new AbortController();
        loading.value = true;

        try {
            const response = await fetch(
                `/global-search?query=${encodeURIComponent(value)}`,
                {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                },
            );
            const payload = await response.json();
            groups.value = payload.groups ?? [];
            open.value = true;
        } finally {
            loading.value = false;
        }
    }, 220);
});

function clearSearch() {
    query.value = '';
    groups.value = [];
    open.value = false;
}
</script>

<template>
    <div class="relative hidden w-full max-w-md md:block">
        <Search
            class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
        />
        <input
            v-model="query"
            class="h-9 w-full rounded-md border border-border bg-card px-9 text-sm ring-primary/20 outline-none focus:ring-2"
            placeholder="Search contacts, campaigns, templates"
            @focus="open = groups.length > 0"
            @keydown.esc="clearSearch"
        />
        <button
            v-if="query"
            class="absolute top-2 right-2 rounded p-1 text-muted-foreground hover:text-foreground"
            type="button"
            aria-label="Clear search"
            @click="clearSearch"
        >
            <X class="size-4" />
        </button>

        <div
            v-if="open"
            class="absolute top-11 right-0 left-0 z-50 max-h-96 overflow-auto rounded-lg border border-border bg-card p-2 shadow-lg"
        >
            <div v-if="loading" class="px-3 py-2 text-sm text-muted-foreground">
                Searching
            </div>
            <template v-for="group in groups" :key="group.label">
                <div v-if="group.items.length" class="py-1">
                    <div
                        class="px-3 py-1 text-xs font-medium text-muted-foreground uppercase"
                    >
                        {{ group.label }}
                    </div>
                    <Link
                        v-for="item in group.items"
                        :key="item.id"
                        :href="item.url"
                        class="flex items-center justify-between rounded-md px-3 py-2 text-sm hover:bg-muted"
                        @click="clearSearch"
                    >
                        <span class="min-w-0">
                            <span class="block truncate font-medium">{{
                                item.title
                            }}</span>
                            <span
                                class="block truncate text-xs text-muted-foreground"
                                >{{ item.subtitle }}</span
                            >
                        </span>
                        <span
                            class="ml-3 rounded bg-sidebar-accent px-2 py-0.5 text-xs text-sidebar-accent-foreground"
                            >{{ item.badge }}</span
                        >
                    </Link>
                </div>
            </template>
            <div
                v-if="!loading && groups.every((group) => !group.items.length)"
                class="px-3 py-2 text-sm text-muted-foreground"
            >
                No results
            </div>
        </div>
    </div>
</template>
