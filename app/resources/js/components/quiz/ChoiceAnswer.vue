<script setup lang="ts">
defineModel<number | null>({ required: true });
defineProps<{
    questionId: number;
    choices: Array<{ id: number; label: string }>;
    error?: string;
}>();
</script>

<template>
    <fieldset class="space-y-2">
        <legend class="sr-only">Pilih satu jawaban</legend>
        <label
            v-for="choice in choices"
            :key="choice.id"
            class="flex cursor-pointer items-start gap-3 rounded-lg border border-border px-4 py-3 text-sm hover:bg-muted/60"
        >
            <input
                v-model="$model"
                :name="`answer-${questionId}`"
                type="radio"
                :value="choice.id"
                required
                class="mt-0.5 border-input text-primary focus:ring-ring"
            />
            <span class="leading-6">{{ choice.label }}</span>
        </label>
        <p v-if="error" role="alert" class="text-sm text-destructive">{{ error }}</p>
    </fieldset>
</template>
