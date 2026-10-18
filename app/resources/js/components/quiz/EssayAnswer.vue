<script setup lang="ts">
import LatexEditor from '@/components/common/LatexEditor.vue';

const model = defineModel<string>({ required: true });
defineProps<{ questionId: number; error?: string }>();
</script>

<template>
    <div>
        <label :for="`essay-answer-${questionId}`" class="mb-2 block text-sm font-semibold">Jawaban esai</label>
        <LatexEditor
            :id="`essay-answer-${questionId}`"
            v-model="model"
            mode="compact"
            :min-rows="4"
            placeholder="Tulis jawaban dengan jelas (mendukung LaTeX $...$ dan formula)"
            :error="error"
        />
    </div>
</template>
