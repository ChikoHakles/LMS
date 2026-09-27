<script setup lang="ts">
import ChoiceAnswer from '@/components/quiz/ChoiceAnswer.vue';
import EssayAnswer from '@/components/quiz/EssayAnswer.vue';
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, Send } from 'lucide-vue-next';
import { computed } from 'vue';

type Question = {
    id: number;
    type: 'choice' | 'essay';
    prompt: string;
    points: number;
    choices: Array<{ id: number; label: string }>;
};
type Attempt = {
    id: number;
    status: 'awaiting_review' | 'completed';
    score: string | null;
    submitted_at: string;
    answers: Array<{ question_id: number; choice_id: number | null; response: string | null; score: string | null; comment: string | null }>;
};

const props = defineProps<{
    material: { id: number; type: 'quiz'; title: string; summary: string | null; published_at: string | null };
    questions: Question[];
    attempt: Attempt | null;
}>();
const page = usePage<SharedData & { flash: { status?: string } }>();
const form = useForm({
    answers: props.questions.map((question) => ({
        question_id: question.id,
        choice_id: null as number | null,
        response: '',
    })),
});
const errors = computed(() => form.errors as Record<string, string>);
const totalPoints = computed(() => props.questions.reduce((total, question) => total + question.points, 0));
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Materi belajar', href: route('student.materials.index') },
    { title: props.material.title, href: route('student.quizzes.show', props.material.id) },
];

function submitQuiz(): void {
    if (!window.confirm('Kirim jawaban kuis sekarang? Kuis hanya dapat dikirim satu kali.')) return;
    form.post(route('student.quizzes.submit', props.material.id), { preserveScroll: true });
}

function answerFor(questionId: number) {
    return props.attempt?.answers.find((answer) => answer.question_id === questionId);
}
</script>

<template>
    <Head :title="material.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
            <header>
                <Link
                    :href="route('student.materials.index')"
                    class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" /> Kembali ke materi
                </Link>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <RuangBadge variant="primary">Kuis</RuangBadge>
                    <span class="text-sm text-muted-foreground">{{ questions.length }} soal · {{ totalPoints }} poin</span>
                </div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ material.title }}</h1>
                <p v-if="material.summary" class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground">{{ material.summary }}</p>
            </header>

            <div
                v-if="page.props.flash?.status"
                role="status"
                aria-live="polite"
                class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success"
            >
                {{ page.props.flash.status }}
            </div>

            <RuangCard v-if="attempt" class="space-y-5 p-5 sm:p-6">
                <div class="flex flex-wrap items-center gap-3">
                    <RuangBadge :variant="attempt.status === 'completed' ? 'success' : 'muted'">
                        {{ attempt.status === 'completed' ? 'Selesai dinilai' : 'Menunggu penilaian esai' }}
                    </RuangBadge>
                    <p v-if="attempt.status === 'completed'" class="text-sm font-semibold">Nilai {{ attempt.score }} / {{ totalPoints }}</p>
                </div>
                <p v-if="attempt.status === 'awaiting_review'" class="text-sm leading-6 text-muted-foreground">
                    Jawaban tersimpan. Nilai akhir akan tersedia setelah tutor selesai menilai semua esai.
                </p>
                <div v-for="question in questions" :key="question.id" class="rounded-xl border border-border p-4">
                    <p class="font-medium leading-6">{{ question.prompt }}</p>
                    <p v-if="answerFor(question.id)?.response" class="mt-2 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                        {{ answerFor(question.id)?.response }}
                    </p>
                    <p v-else-if="answerFor(question.id)?.choice_id" class="mt-2 text-sm text-muted-foreground">Jawaban pilihan tersimpan.</p>
                    <div v-if="attempt.status === 'completed' && answerFor(question.id)?.score !== null" class="mt-3 text-sm">
                        <span class="font-semibold">{{ answerFor(question.id)?.score }} / {{ question.points }} poin</span>
                        <p v-if="answerFor(question.id)?.comment" class="mt-1 text-muted-foreground">{{ answerFor(question.id)?.comment }}</p>
                    </div>
                </div>
                <Link
                    :href="route('student.daily-rhythm')"
                    class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-medium hover:bg-muted"
                >
                    <Check class="size-4" aria-hidden="true" /> Kembali ke Ritme hari ini
                </Link>
            </RuangCard>

            <template v-else>
                <div v-if="errors.attempt" role="alert" class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    {{ errors.attempt }}
                </div>
                <div v-if="errors.answers" role="alert" class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    {{ errors.answers }}
                </div>
                <RuangCard v-for="(question, index) in questions" :key="question.id" class="space-y-4 p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-semibold">Soal {{ index + 1 }}</h2>
                        <RuangBadge variant="muted"
                            >{{ question.points }} poin · {{ question.type === 'choice' ? 'Pilihan ganda' : 'Esai' }}</RuangBadge
                        >
                    </div>
                    <p class="leading-7">{{ question.prompt }}</p>
                    <ChoiceAnswer
                        v-if="question.type === 'choice'"
                        v-model="form.answers[index].choice_id"
                        :question-id="question.id"
                        :choices="question.choices"
                        :error="errors[`answers.${index}.choice_id`]"
                    />
                    <EssayAnswer
                        v-else
                        v-model="form.answers[index].response"
                        :question-id="question.id"
                        :error="errors[`answers.${index}.response`]"
                    />
                </RuangCard>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-muted-foreground">Periksa semua jawaban sebelum mengirim. Pengiriman hanya tersedia satu kali.</p>
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground disabled:opacity-50"
                        @click="submitQuiz"
                    >
                        <Send class="size-4" aria-hidden="true" /> {{ form.processing ? 'Mengirim…' : 'Kirim kuis' }}
                    </button>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
