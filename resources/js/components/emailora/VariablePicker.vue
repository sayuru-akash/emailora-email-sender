<script setup lang="ts">
import { Braces } from 'lucide-vue-next';
import { computed } from 'vue';

type VariableDefinition = {
    key: string;
    token: string;
    label: string;
    group: string;
    description: string;
};

const props = defineProps<{
    variables?: VariableDefinition[];
    activeFieldLabel?: string;
}>();

const emit = defineEmits<{
    insert: [token: string];
}>();

const groupedVariables = computed(() => {
    return (props.variables ?? []).reduce(
        (groups, variable) => {
            groups[variable.group] ??= [];
            groups[variable.group].push(variable);

            return groups;
        },
        {} as Record<string, VariableDefinition[]>,
    );
});
</script>

<template>
    <section class="rounded-lg border bg-card p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold">Variables</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Click a token to insert into
                    {{ activeFieldLabel || 'the active field' }}.
                </p>
            </div>
            <Braces class="h-5 w-5 text-primary" />
        </div>

        <div class="mt-4 space-y-4">
            <div
                v-for="(items, group) in groupedVariables"
                :key="group"
                class="space-y-2"
            >
                <h3 class="text-xs font-medium text-muted-foreground uppercase">
                    {{ group }}
                </h3>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="variable in items"
                        :key="variable.key"
                        type="button"
                        class="rounded-md border bg-background px-2.5 py-1.5 text-left text-xs transition hover:border-primary hover:bg-primary/5"
                        :title="variable.description"
                        @click="emit('insert', variable.token)"
                    >
                        <span class="block font-medium">{{
                            variable.label
                        }}</span>
                        <code class="text-muted-foreground">{{
                            variable.token
                        }}</code>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
