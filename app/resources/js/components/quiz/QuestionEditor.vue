<script setup lang="ts">
import ChoiceEditor from '@/components/quiz/ChoiceEditor.vue';
import type { QuizQuestionDraft } from '@/types/quiz';
import { watch } from 'vue';

const question = defineModel<QuizQuestionDraft>('question', { required: true });
const props = defineProps<{
    position: number;
    errors: Record<string, string>;
}>();

watch(
    () => question.value.type,
    (type) => {
        question.value.choices =
            type === 'choice'
                ? [
                      { label: '', is_correct: true },
                      { label: '', is_correct: false },
                  ]
                : [];
    },
);
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_10rem]">
            <div>
                <label :for="`question-prompt-${position}`" class="text-sm font-semibold">Pertanyaan {{ position + 1 }}</label>
                <textarea
                    :id="`question-prompt-${position}`"
                    v-model="question.prompt"
                    rows="3"
                    maxlength="10000"
                    required
                    :aria-invalid="Boolean(errors[`questions.${position}.prompt`])"
                    class="mt-2 w-full resize-y rounded-lg border-input bg-background text-sm leading-6 focus:ring-ring"
                    placeholder="Tulis pertanyaan atau instruksi esai"
                />
                <p v-if="errors[`questions.${position}.prompt`]" class="mt-1 text-sm text-destructive">
                    {{ errors[`questions.${position}.prompt`] }}
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-1">
                <div>
                    <label :for="`question-type-${position}`" class="text-sm font-semibold">Jenis</label>
                    <select
                        :id="`question-type-${position}`"
                        v-model="question.type"
                        class="mt-2 w-full rounded-lg border-input bg-background text-sm focus:ring-ring"
                    >
                        <option value="choice">Pilihan ganda</option>
                        <option value="essay">Esai</option>
                    </select>
                </div>
                <div>
                    <label :for="`question-points-${position}`" class="text-sm font-semibold">Poin</label>
                    <input
                        :id="`question-points-${position}`"
                        v-model.number="question.points"
                        type="number"
                        min="0.01"
                        max="1000"
                        step="0.01"
                        required
                        :aria-invalid="Boolean(errors[`questions.${position}.points`])"
                        class="mt-2 w-full rounded-lg border-input bg-background text-sm focus:ring-ring"
                    />
                    <p v-if="errors[`questions.${position}.points`]" class="mt-1 text-sm text-destructive">
                        {{ errors[`questions.${position}.points`] }}
                    </p>
                </div>
            </div>
        </div>

        <ChoiceEditor v-if="question.type === 'choice'" v-model="question.choices" :question-position="position" :errors="errors" />
        <div v-else class="rounded-lg bg-secondary/50 p-4 text-sm leading-6 text-muted-foreground">
            Jawaban esai akan dinilai tutor. Tidak ada pilihan atau kunci otomatis.
        </div>
    </div>
</template>
