<script setup lang="ts">
import QuestionEditor from '@/components/quiz/QuestionEditor.vue';
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { QuizMaterialDraft, QuizQuestionDraft } from '@/types/quiz';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowLeft, ArrowUp, Plus, Save, Send, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ material: QuizMaterialDraft | null }>();
const page = usePage<SharedData & { flash: { status?: string } }>();
const cloneQuestions = (questions: QuizQuestionDraft[] | undefined): QuizQuestionDraft[] =>
    questions ? (JSON.parse(JSON.stringify(questions)) as QuizQuestionDraft[]) : [];
const form = useForm({
    title: props.material?.title ?? '',
    summary: props.material?.summary ?? '',
    questions: cloneQuestions(props.material?.questions),
});
const errors = computed(() => form.errors as Record<string, string>);
const isPublishing = ref(false);
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pustaka materi', href: route('tutor.materials.index') },
    { title: props.material ? 'Edit kuis' : 'Buat Materi: Kuis', href: page.url },
];

function newQuestion(): QuizQuestionDraft {
    return {
        type: 'choice',
        prompt: '',
        points: 1,
        choices: [
            { label: '', is_correct: true },
            { label: '', is_correct: false },
        ],
    };
}

function addQuestion(): void {
    if (form.questions.length >= 50) return;
    form.questions.push(newQuestion());
    form.clearErrors();
}

function removeQuestion(index: number): void {
    form.questions.splice(index, 1);
    form.clearErrors();
}

function moveQuestion(index: number, offset: -1 | 1): void {
    const nextIndex = index + offset;
    if (nextIndex < 0 || nextIndex >= form.questions.length) return;
    const [question] = form.questions.splice(index, 1);
    form.questions.splice(nextIndex, 0, question);
    form.clearErrors();
}

function saveDraft(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    };
    if (props.material) {
        form.put(route('tutor.materials.quiz.update', props.material.id), options);
    } else {
        form.post(route('tutor.materials.quiz.store'), options);
    }
}

function publishQuiz(): void {
    if (!props.material || form.isDirty) {
        saveDraft();
        return;
    }

    isPublishing.value = true;
    router.patch(
        route('tutor.materials.quiz.publish', props.material.id),
        {},
        {
            preserveScroll: true,
            onError: (serverErrors) => Object.assign(form.errors, serverErrors),
            onFinish: () => {
                isPublishing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="material ? 'Edit kuis' : 'Buat Materi: Kuis'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <Link
                        :href="route('tutor.materials.index')"
                        class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="size-4" aria-hidden="true" /> Kembali ke pustaka
                    </Link>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Penyusun kuis</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ material ? 'Edit kuis' : 'Buat Materi: Kuis' }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                        Campur soal pilihan ganda dan esai. Kunci hanya terlihat oleh tutor dan digunakan server saat menilai.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-secondary px-4 py-2.5 text-sm font-semibold text-secondary-foreground disabled:opacity-50"
                        @click="saveDraft"
                    >
                        <Save class="size-4" aria-hidden="true" /> Simpan draf
                    </button>
                    <button
                        v-if="material"
                        type="button"
                        :disabled="isPublishing || form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground disabled:opacity-50"
                        @click="publishQuiz"
                    >
                        <Send class="size-4" aria-hidden="true" /> Terbitkan
                    </button>
                </div>
            </header>

            <div
                v-if="page.props.flash?.status"
                role="status"
                aria-live="polite"
                class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success"
            >
                {{ page.props.flash.status }}
            </div>
            <div v-if="material" class="flex flex-wrap items-center gap-3">
                <RuangBadge :variant="material.status === 'published' ? 'success' : 'muted'">
                    {{ material.status === 'published' ? 'Terbit' : 'Draf' }}
                </RuangBadge>
                <span v-if="material.status === 'published'" class="text-sm text-muted-foreground">
                    Simpan perubahan untuk mengembalikan kuis ke status draf.
                </span>
            </div>

            <RuangCard class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="quiz-title" class="text-sm font-semibold">Judul kuis</label>
                        <input
                            id="quiz-title"
                            v-model="form.title"
                            maxlength="255"
                            required
                            :aria-invalid="Boolean(errors.title)"
                            class="mt-2 w-full rounded-lg border-input bg-background text-base focus:ring-ring"
                            placeholder="Contoh: Latihan pecahan"
                        />
                        <p v-if="errors.title" class="mt-1 text-sm text-destructive">{{ errors.title }}</p>
                    </div>
                    <div>
                        <label for="quiz-summary" class="text-sm font-semibold">Ringkasan</label>
                        <textarea
                            id="quiz-summary"
                            v-model="form.summary"
                            rows="2"
                            maxlength="1000"
                            :aria-invalid="Boolean(errors.summary)"
                            class="mt-2 w-full resize-y rounded-lg border-input bg-background text-sm leading-6 focus:ring-ring"
                            placeholder="Petunjuk singkat untuk siswa"
                        />
                        <p v-if="errors.summary" class="mt-1 text-sm text-destructive">{{ errors.summary }}</p>
                    </div>
                </div>
                <p v-if="errors.questions" role="alert" class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    {{ errors.questions }}
                </p>
            </RuangCard>

            <section aria-labelledby="questions-heading" class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="questions-heading" class="text-lg font-semibold">Susunan soal</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Urutan di sini menjadi urutan pengerjaan siswa.</p>
                    </div>
                    <button
                        type="button"
                        :disabled="form.questions.length >= 50"
                        class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-semibold hover:bg-muted disabled:opacity-50"
                        @click="addQuestion"
                    >
                        <Plus class="size-4" aria-hidden="true" /> Tambah soal
                    </button>
                </div>

                <div v-if="!form.questions.length" class="rounded-xl border border-dashed border-border bg-card px-5 py-10 text-center">
                    <p class="font-medium">Belum ada soal</p>
                    <p class="mt-1 text-sm text-muted-foreground">Tambahkan soal pilihan atau esai untuk mulai menyusun kuis.</p>
                </div>

                <RuangCard v-for="(question, index) in form.questions" :key="question.id ?? index" class="p-5 sm:p-6">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <RuangBadge variant="primary">Soal {{ index + 1 }} · {{ question.type === 'choice' ? 'Pilihan ganda' : 'Esai' }}</RuangBadge>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                :disabled="index === 0"
                                :aria-label="`Pindahkan soal ${index + 1} ke atas`"
                                class="rounded-md p-2 text-muted-foreground hover:bg-muted disabled:opacity-40"
                                @click="moveQuestion(index, -1)"
                            >
                                <ArrowUp class="size-4" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                :disabled="index === form.questions.length - 1"
                                :aria-label="`Pindahkan soal ${index + 1} ke bawah`"
                                class="rounded-md p-2 text-muted-foreground hover:bg-muted disabled:opacity-40"
                                @click="moveQuestion(index, 1)"
                            >
                                <ArrowDown class="size-4" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                :aria-label="`Hapus soal ${index + 1}`"
                                class="rounded-md p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                @click="removeQuestion(index)"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                    <QuestionEditor v-model:question="form.questions[index]" :position="index" :errors="errors" />
                </RuangCard>
            </section>
        </div>
    </AppLayout>
</template>
