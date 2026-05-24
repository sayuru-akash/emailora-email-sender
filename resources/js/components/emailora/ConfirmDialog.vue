<script setup lang="ts">
defineProps<{
    modelValue: boolean;
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
    processing?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    confirm: [];
}>();

function close() {
    emit('update:modelValue', false);
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-6"
            role="dialog"
            aria-modal="true"
            @keydown.esc="close"
        >
            <button
                class="absolute inset-0 h-full w-full cursor-default"
                type="button"
                aria-label="Close dialog"
                @click="close"
            ></button>
            <section
                class="relative w-full max-w-md rounded-lg border border-border bg-card p-5 shadow-xl"
            >
                <h2 class="text-lg font-semibold text-foreground">
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mt-2 text-sm leading-6 text-muted-foreground"
                >
                    {{ description }}
                </p>
                <div
                    class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <button
                        class="rounded-md border border-border px-3 py-2 text-sm hover:bg-accent disabled:opacity-60"
                        type="button"
                        :disabled="processing"
                        @click="close"
                    >
                        {{ cancelLabel ?? 'Cancel' }}
                    </button>
                    <button
                        :class="[
                            'rounded-md px-3 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-60',
                            destructive
                                ? 'bg-destructive text-destructive-foreground'
                                : 'bg-primary text-primary-foreground',
                        ]"
                        type="button"
                        :disabled="processing"
                        @click="emit('confirm')"
                    >
                        {{
                            processing
                                ? 'Working...'
                                : (confirmLabel ?? 'Confirm')
                        }}
                    </button>
                </div>
            </section>
        </div>
    </Teleport>
</template>
