<script setup lang="ts">
import { computed } from 'vue';
import StatusBadge from '@/components/emailora/StatusBadge.vue';

type Campaign = {
    status: string;
    total_recipients?: number;
    pending_count?: number;
    queued_count?: number;
    sent_count?: number;
    delivered_count?: number;
    opened_count?: number;
    clicked_count?: number;
    failed_count?: number;
    bounced_count?: number;
    complained_count?: number;
    skipped_count?: number;
};

type Audience = {
    prepared_count?: number;
    sendable_count?: number;
    display_count?: number;
};

const props = withDefaults(
    defineProps<{
        campaign: Campaign;
        audience?: Audience;
        compact?: boolean;
        live?: boolean;
        lastUpdated?: string;
    }>(),
    {
        audience: undefined,
        compact: false,
        live: false,
        lastUpdated: '',
    },
);

const activeStatuses = ['queued', 'preparing', 'sending', 'paused'];
const isActive = computed(() => activeStatuses.includes(props.campaign.status));
const total = computed(() =>
    Math.max(
        Number(props.campaign.total_recipients ?? 0),
        Number(props.audience?.prepared_count ?? 0),
        Number(props.audience?.display_count ?? 0),
        0,
    ),
);
const accepted = computed(
    () =>
        Number(props.campaign.sent_count ?? 0) +
        Number(props.campaign.delivered_count ?? 0) +
        Number(props.campaign.opened_count ?? 0) +
        Number(props.campaign.clicked_count ?? 0),
);
const failed = computed(
    () =>
        Number(props.campaign.failed_count ?? 0) +
        Number(props.campaign.bounced_count ?? 0) +
        Number(props.campaign.complained_count ?? 0),
);
const skipped = computed(() => Number(props.campaign.skipped_count ?? 0));
const queued = computed(() => Number(props.campaign.queued_count ?? 0));
const completed = computed(() => accepted.value + failed.value + skipped.value);
const progress = computed(() => {
    if (total.value < 1) {
        return props.campaign.status === 'preparing' ? 5 : 0;
    }

    return Math.min(100, Math.round((completed.value / total.value) * 100));
});
const remaining = computed(() => Math.max(0, total.value - completed.value));
const acceptedWidth = computed(() =>
    total.value < 1 ? 0 : Math.round((accepted.value / total.value) * 100),
);
const failedWidth = computed(() =>
    total.value < 1 ? 0 : Math.round((failed.value / total.value) * 100),
);
const skippedWidth = computed(() =>
    total.value < 1 ? 0 : Math.round((skipped.value / total.value) * 100),
);
const headline = computed(() => {
    if (props.campaign.status === 'preparing') {
        return 'Preparing recipients';
    }

    if (props.campaign.status === 'sending') {
        return 'Sending campaign';
    }

    if (props.campaign.status === 'queued') {
        return 'Queued for sending';
    }

    if (props.campaign.status === 'paused') {
        return 'Paused';
    }

    if (props.campaign.status === 'completed') {
        return 'Completed';
    }

    if (props.campaign.status === 'failed') {
        return 'Failed';
    }

    return 'Campaign status';
});
</script>

<template>
    <div v-if="compact" class="min-w-0">
        <div class="flex items-center justify-between gap-3 text-xs">
            <span class="truncate text-muted-foreground">
                <span class="font-medium text-foreground tabular-nums">
                    {{ accepted }}
                </span>
                / {{ total || 0 }} sent
            </span>
            <span
                v-if="failed"
                class="shrink-0 font-medium text-destructive tabular-nums"
            >
                {{ failed }} failed
            </span>
            <span
                v-else-if="isActive"
                class="inline-flex shrink-0 items-center gap-1.5 text-primary"
            >
                <span class="size-1.5 animate-pulse rounded-full bg-primary" />
                Live
            </span>
        </div>
        <div
            class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-muted"
            :aria-label="`${progress}% complete`"
            role="progressbar"
            :aria-valuenow="progress"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div
                class="h-full bg-emerald-500"
                :style="{ width: `${acceptedWidth}%` }"
            />
            <div
                class="h-full bg-destructive"
                :style="{ width: `${failedWidth}%` }"
            />
            <div
                class="h-full bg-amber-500"
                :style="{ width: `${skippedWidth}%` }"
            />
        </div>
    </div>

    <section v-else class="rounded-lg border border-border/70 bg-card/70 p-4">
        <div
            class="flex flex-col gap-3"
            :class="'md:flex-row md:items-start md:justify-between'"
        >
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge :status="campaign.status" />
                    <span class="text-sm font-medium">{{ headline }}</span>
                    <span
                        v-if="live && isActive"
                        class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-2 py-0.5 text-xs text-primary"
                    >
                        <span
                            class="size-1.5 animate-pulse rounded-full bg-primary"
                        ></span>
                        Live updates
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        total
                            ? `${completed} of ${total} recipients finished`
                            : 'Waiting for recipient preparation to start'
                    }}
                    <span v-if="lastUpdated"> · Checked {{ lastUpdated }}</span>
                </p>
            </div>
            <div class="text-2xl font-semibold tabular-nums">
                {{ progress }}%
            </div>
        </div>

        <div
            class="mt-3 flex h-1.5 overflow-hidden rounded-full bg-muted"
            :aria-label="`${progress}% complete`"
            role="progressbar"
            :aria-valuenow="progress"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div
                class="h-full bg-emerald-500"
                :style="{ width: `${acceptedWidth}%` }"
            ></div>
            <div
                class="h-full bg-destructive"
                :style="{ width: `${failedWidth}%` }"
            ></div>
            <div
                class="h-full bg-amber-500"
                :style="{ width: `${skippedWidth}%` }"
            ></div>
        </div>

        <div
            class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground"
        >
            <span class="rounded-full bg-muted px-2 py-1"
                >Remaining {{ remaining }}</span
            >
            <span class="rounded-full bg-muted px-2 py-1"
                >Queued / sending {{ queued }}</span
            >
            <span
                class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"
                >Sent {{ accepted }}</span
            >
            <span
                v-if="failed"
                class="rounded-full bg-red-50 px-2 py-1 text-red-700 dark:bg-red-950/40 dark:text-red-300"
                >Failed {{ failed }}</span
            >
            <span
                v-if="skipped"
                class="rounded-full bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                >Skipped {{ skipped }}</span
            >
        </div>
    </section>
</template>
