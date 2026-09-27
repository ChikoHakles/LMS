<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import { useForm } from '@inertiajs/vue3';
import { Check, Send } from 'lucide-vue-next';

const props = defineProps<{
    answer: {
        id: number;
        student: { name: string };
        material: { id: number; title: string };
        question: { id: number; prompt: string; points: number };
        response: string;
        submitted_at: string;
    };
}>();

const form = useForm({ score: 0, comment: '' });

function submitReview(): void {
    form.patch(route('tutor.essays.grade', props.answer.id), { preserveScroll: true });
}
</script>

<template>
    <RuangCard class="space-y-5 p-5 sm:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold">{{ answer.student.name }}</p>
                <p class="mt-1 text-sm text-muted-foreground">{{ answer.material.title }} · dikirim {{ answer.submitted_at }}</p>
            </div>
            <RuangBadge variant="muted">Menunggu penilaian</RuangBadge>
        </header>

        <section class="space-y-3 rounded-xl bg-secondary/40 p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Pertanyaan · maksimal {{ answer.question.points }} poin
                </p>
                <h2 class="mt-1 font-semibold leading-6">{{ answer.question.prompt }}</h2>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Jawaban siswa</p>
                <p class="mt-2 whitespace-pre-wrap text-sm leading-6">{{ answer.response }}</p>
            </div>
        </section>

        <form class="grid gap-4 sm:grid-cols-[10rem_minmax(0,1fr)]" @submit.prevent="submitReview">
            <div>
                <label :for="`essay-score-${answer.id}`" class="text-sm font-semibold">Nilai</label>
                <input
                    :id="`essay-score-${answer.id}`"
                    v-model.number="form.score"
                    type="number"
                    min="0"
                    :max="answer.question.points"
                    step="0.01"
                    required
                    :aria-invalid="Boolean(form.errors.score)"
                    class="mt-2 w-full rounded-lg border-input bg-background text-sm focus:ring-ring"
                />
                <p v-if="form.errors.score" role="alert" class="mt-1 text-sm text-destructive">{{ form.errors.score }}</p>
            </div>
            <div>
                <label :for="`essay-comment-${answer.id}`" class="text-sm font-semibold">Komentar tutor</label>
                <textarea
                    :id="`essay-comment-${answer.id}`"
                    v-model="form.comment"
                    rows="3"
                    maxlength="2000"
                    :aria-invalid="Boolean(form.errors.comment)"
                    class="mt-2 w-full resize-y rounded-lg border-input bg-background text-sm leading-6 focus:ring-ring"
                    placeholder="Berikan catatan untuk siswa (opsional)"
                />
                <p v-if="form.errors.comment" role="alert" class="mt-1 text-sm text-destructive">{{ form.errors.comment }}</p>
            </div>
            <div class="sm:col-span-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground disabled:opacity-50"
                >
                    <Check v-if="!form.processing" class="size-4" aria-hidden="true" />
                    <Send v-else class="size-4" aria-hidden="true" />
                    {{ form.processing ? 'Menyimpan…' : 'Simpan penilaian' }}
                </button>
            </div>
        </form>
    </RuangCard>
</template>
