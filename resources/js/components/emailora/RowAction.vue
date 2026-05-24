<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Copy,
    Eye,
    FileText,
    Pencil,
    PlayCircle,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        href?: string;
        label: string;
        icon?:
            | 'continue'
            | 'delete'
            | 'duplicate'
            | 'edit'
            | 'report'
            | 'recipients'
            | 'view';
        method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
        destructive?: boolean;
        disabled?: boolean;
    }>(),
    {
        href: undefined,
        icon: 'view',
        method: 'get',
        destructive: false,
        disabled: false,
    },
);

defineEmits<{ click: [] }>();

const icons = {
    continue: PlayCircle,
    delete: Trash2,
    duplicate: Copy,
    edit: Pencil,
    report: FileText,
    recipients: Users,
    view: Eye,
};

const iconComponent = computed(() =>
    props.label.toLowerCase().includes('add') ? UserPlus : icons[props.icon],
);

const classes = computed(() => [
    'inline-flex h-8 items-center gap-1.5 rounded-md border px-2.5 text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-60',
    props.destructive
        ? 'border-destructive/30 bg-background text-destructive hover:bg-destructive/10'
        : 'border-border bg-background text-foreground hover:border-primary/40 hover:bg-primary/10 hover:text-primary',
]);
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :method="method"
        :as="method === 'get' ? undefined : 'button'"
        :class="classes"
        :disabled="disabled"
    >
        <component :is="iconComponent" class="size-3.5" />
        <span>{{ label }}</span>
    </Link>
    <button
        v-else
        type="button"
        :class="classes"
        :disabled="disabled"
        @click="$emit('click')"
    >
        <component :is="iconComponent" class="size-3.5" />
        <span>{{ label }}</span>
    </button>
</template>
