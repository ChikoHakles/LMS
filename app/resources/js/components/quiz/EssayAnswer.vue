<script setup lang="ts">
defineModel<string>({ required: true });
defineProps<{ questionId: number; error?: string }>();
</script>

<template>
    <div>
        <label :for="`essay-answer-${questionId}`" class="text-sm font-semibold">Jawaban esai</label>
        <textarea
            :id="`essay-answer-${questionId}`"
            v-model="$model"
            rows="5"
            maxlength="10000"
            required
            :aria-invalid="Boolean(error)"
            class="mt-2 w-full resize-y rounded-lg border-input bg-background text-sm leading-6 focus:ring-ring"
            placeholder="Tulis jawaban dengan jelas"
        />
        <p v-if="error" role="alert" class="mt-1 text-sm text-destructive">{{ error }}</p>
    </div>
</template>
