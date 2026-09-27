<script setup lang="ts">
import type { QuizChoiceDraft } from '@/types/quiz';
import { Plus, Trash2 } from 'lucide-vue-next';

const choices = defineModel<QuizChoiceDraft[]>({ required: true });
const props = defineProps<{
    questionPosition: number;
    errors: Record<string, string>;
}>();

function chooseKey(index: number): void {
    choices.value = choices.value.map((choice, choiceIndex) => ({ ...choice, is_correct: choiceIndex === index }));
}

function addChoice(): void {
    if (choices.value.length >= 10) return;
    choices.value.push({ label: '', is_correct: false });
}

function removeChoice(index: number): void {
    if (choices.value.length <= 2) return;
    const removedKey = choices.value[index]?.is_correct;
    choices.value.splice(index, 1);
    if (removedKey && choices.value.length) chooseKey(0);
}
</script>

<template>
    <fieldset class="space-y-3">
        <legend class="text-sm font-semibold">Pilihan dan kunci jawaban</legend>
        <p class="text-xs leading-5 text-muted-foreground">Pilih satu radio sebagai kunci. Sedikitnya dua opsi harus memiliki teks berbeda.</p>
        <div v-for="(choice, index) in choices" :key="index" class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-start gap-3">
            <input
                :name="`answer-key-${questionPosition}`"
                type="radio"
                :checked="choice.is_correct"
                :aria-label="`Jadikan pilihan ${index + 1} sebagai kunci`"
                class="mt-3 border-input text-primary focus:ring-ring"
                @change="chooseKey(index)"
            />
            <div>
                <label class="sr-only" :for="`choice-${questionPosition}-${index}`">Teks pilihan {{ index + 1 }}</label>
                <input
                    :id="`choice-${questionPosition}-${index}`"
                    v-model="choice.label"
                    :aria-invalid="Boolean(errors[`questions.${questionPosition}.choices.${index}.label`])"
                    maxlength="2000"
                    class="w-full rounded-lg border-input bg-background text-sm focus:ring-ring"
                    :placeholder="`Teks pilihan ${index + 1}`"
                />
                <p v-if="errors[`questions.${questionPosition}.choices.${index}.label`]" class="mt-1 text-sm text-destructive">
                    {{ errors[`questions.${questionPosition}.choices.${index}.label`] }}
                </p>
            </div>
            <button
                type="button"
                :disabled="choices.length <= 2"
                :aria-label="`Hapus pilihan ${index + 1}`"
                class="rounded-md p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive disabled:cursor-not-allowed disabled:opacity-40"
                @click="removeChoice(index)"
            >
                <Trash2 class="size-4" aria-hidden="true" />
            </button>
        </div>
        <p v-if="errors[`questions.${questionPosition}.choices`]" role="alert" class="text-sm text-destructive">
            {{ errors[`questions.${questionPosition}.choices`] }}
        </p>
        <button
            type="button"
            :disabled="choices.length >= 10"
            class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm font-medium hover:bg-muted disabled:opacity-50"
            @click="addChoice"
        >
            <Plus class="size-4" aria-hidden="true" /> Tambah pilihan
        </button>
    </fieldset>
</template>
