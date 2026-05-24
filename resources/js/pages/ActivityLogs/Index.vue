<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/emailora/EmptyState.vue';
import PageHeader from '@/components/emailora/PageHeader.vue';
import Pagination from '@/components/emailora/Pagination.vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';
import TableShell from '@/components/emailora/TableShell.vue';

type FilterOptions = {
    categories?: string[];
    events?: string[];
    severities?: string[];
    users?: Array<{ id: number; name: string; email: string }>;
};

const props = defineProps<{
    activities?: any;
    filters?: Record<string, string | number | null>;
    filterOptions?: FilterOptions;
}>();

const search = ref((props.filters?.search as string) ?? '');
let searchTimer: number | undefined;

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    Object.entries(props.filters ?? {}).forEach(([key, value]) => {
        if (value) {
            params.set(key, String(value));
        }
    });

    return `/activity-logs/export${params.toString() ? `?${params.toString()}` : ''}`;
});

watch(search, (value) => {
    if (searchTimer) {
        window.clearTimeout(searchTimer);
    }

    searchTimer = window.setTimeout(() => {
        applyFilters({ search: value || undefined, page: undefined });
    }, 250);
});

function applyFilters(updates: Record<string, string | number | undefined>) {
    router.get(
        '/activity-logs',
        { ...props.filters, ...updates },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function formatProperties(value: unknown) {
    if (!value || typeof value !== 'object') {
        return '{}';
    }

    return JSON.stringify(value, null, 2);
}
</script>

<template>
    <Head title="Activity Logs" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-8">
        <PageHeader
            title="Activity Logs"
            :subtitle="`${props.activities?.meta?.total ?? 0} audit events`"
        >
            <template #actions>
                <a
                    class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm hover:bg-accent"
                    :href="exportUrl"
                >
                    <Download class="h-4 w-4" />
                    Export
                </a>
            </template>
        </PageHeader>

        <section
            class="mb-4 grid gap-3 rounded-lg border border-border bg-card p-3 lg:grid-cols-[minmax(220px,1fr)_repeat(4,minmax(150px,190px))]"
        >
            <label class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="search"
                    class="h-10 w-full rounded-md border border-border bg-background pr-3 pl-9 text-sm"
                    placeholder="Search event, subject, path"
                />
            </label>
            <select
                class="h-10 rounded-md border border-border bg-background px-3 text-sm"
                :value="props.filters?.category ?? ''"
                @change="
                    applyFilters({
                        category:
                            ($event.target as HTMLSelectElement).value ||
                            undefined,
                        page: undefined,
                    })
                "
            >
                <option value="">All categories</option>
                <option
                    v-for="category in props.filterOptions?.categories ?? []"
                    :key="category"
                    :value="category"
                >
                    {{ category }}
                </option>
            </select>
            <select
                class="h-10 rounded-md border border-border bg-background px-3 text-sm"
                :value="props.filters?.event ?? ''"
                @change="
                    applyFilters({
                        event:
                            ($event.target as HTMLSelectElement).value ||
                            undefined,
                        page: undefined,
                    })
                "
            >
                <option value="">All events</option>
                <option
                    v-for="event in props.filterOptions?.events ?? []"
                    :key="event"
                    :value="event"
                >
                    {{ event }}
                </option>
            </select>
            <select
                class="h-10 rounded-md border border-border bg-background px-3 text-sm"
                :value="props.filters?.severity ?? ''"
                @change="
                    applyFilters({
                        severity:
                            ($event.target as HTMLSelectElement).value ||
                            undefined,
                        page: undefined,
                    })
                "
            >
                <option value="">All severities</option>
                <option
                    v-for="severity in props.filterOptions?.severities ?? []"
                    :key="severity"
                    :value="severity"
                >
                    {{ severity }}
                </option>
            </select>
            <select
                class="h-10 rounded-md border border-border bg-background px-3 text-sm"
                :value="props.filters?.user_id ?? ''"
                @change="
                    applyFilters({
                        user_id:
                            ($event.target as HTMLSelectElement).value ||
                            undefined,
                        page: undefined,
                    })
                "
            >
                <option value="">All users</option>
                <option
                    v-for="user in props.filterOptions?.users ?? []"
                    :key="user.id"
                    :value="user.id"
                >
                    {{ user.name }}
                </option>
            </select>
        </section>

        <TableShell min-width="1180px">
            <table
                v-if="(props.activities?.data ?? []).length"
                class="w-full text-sm"
            >
                <thead
                    class="bg-muted text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Path</th>
                        <th class="px-4 py-3">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="item in props.activities?.data ?? []"
                        :key="item.id"
                    >
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ item.occurred_at ?? item.created_at }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="item.severity" />
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ item.event }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ item.category }}
                            </div>
                        </td>
                        <td class="max-w-48 truncate px-4 py-3">
                            {{ item.user?.name ?? item.user_name ?? 'System' }}
                        </td>
                        <td class="max-w-56 truncate px-4 py-3">
                            {{ item.subject_name ?? '-' }}
                        </td>
                        <td
                            class="max-w-56 truncate px-4 py-3 text-muted-foreground"
                        >
                            {{ item.method ?? '' }} {{ item.url ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <details class="max-w-96">
                                <summary class="cursor-pointer text-primary">
                                    {{ item.description ?? 'View details' }}
                                </summary>
                                <pre
                                    class="mt-2 max-h-64 overflow-auto rounded-md bg-muted p-3 text-xs leading-5 text-muted-foreground"
                                    >{{
                                        formatProperties(item.properties)
                                    }}</pre
                                >
                            </details>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState
                v-else
                title="No activity logs found"
                description="Change filters or perform an action to create audit events."
            />
            <template #footer>
                <Pagination :meta="props.activities?.meta" />
            </template>
        </TableShell>
    </main>
</template>
