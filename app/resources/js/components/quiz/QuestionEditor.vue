<script setup lang="ts">
import LatexEditor from '@/components/common/LatexEditor.vue';
import ChoiceEditor from '@/components/quiz/ChoiceEditor.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import type { QuizQuestionDraft } from '@/types/quiz';
import { watch } from 'vue';

const question = defineModel<QuizQuestionDraft>('question', { required: true });
defineProps<{
    position: number;
    errors: Record<string, string>;
}>();

const questionTypeOptions = [
    { value: 'choice', label: 'Pilihan ganda' },
    { value: 'essay', label: 'Esai' },
];

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
                <label :for="`question-prompt-${position}`" class="mb-2 block text-sm font-semibold">Pertanyaan {{ position + 1 }}</label>
                <LatexEditor
                    :id="`question-prompt-${position}`"
                    v-model="question.prompt"
                    mode="full"
                    :min-rows="3"
                    placeholder="Tulis pertanyaan atau instruksi esai"
                    :error="errors[`questions.${position}.prompt`]"
                />
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-1">
                <div>
                    <label :for="`question-type-${position}`" class="mb-2 block text-sm font-semibold">Jenis</label>
                    <SearchableSelect
                        :id="`question-type-${position}`"
                        v-model="question.type"
                        :options="questionTypeOptions"
                        placeholder="Pilih jenis..."
                        aria-label="Jenis pertanyaan"
                    />
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
