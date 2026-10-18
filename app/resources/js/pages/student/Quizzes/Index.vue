<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, ClipboardCheck } from 'lucide-vue-next';

type Quiz = {
    id: number;
    title: string;
    summary: string | null;
    className: string;
    date: string;
    questionCount: number;
    totalPoints: number;
    available: boolean;
    status: 'awaiting_review' | 'completed' | null;
    score: string | null;
    href: string;
};

defineProps<{
    quizzes: {
        data: Quiz[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        last_page: number;
    };
    filteredMaterial: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Beranda', href: route('dashboard') },
    { title: 'Kuis & latihan', href: route('student.quizzes.index') },
];

function statusLabel(quiz: Quiz): string {
    if (quiz.status === 'completed') return 'Selesai dinilai';
    if (quiz.status === 'awaiting_review') return 'Menunggu penilaian';
    return quiz.available ? 'Belum dikerjakan' : 'Belum dapat dikerjakan';
}
</script>

<template>
    <Head title="Kuis & latihan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Kegiatan belajar</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Kuis & latihan</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                        Kerjakan kuis yang ditugaskan tutor, lalu lihat nilai dan umpan balik di sini.
                    </p>
                </div>
                <Link
                    v-if="filteredMaterial"
                    :href="route('student.quizzes.index')"
                    class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                >
                    Lihat semua kuis
                </Link>
            </header>

            <div v-if="quizzes.data.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <RuangCard v-for="quiz in quizzes.data" :key="quiz.id" class="flex h-full flex-col p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <RuangBadge :variant="quiz.status === 'completed' ? 'success' : 'muted'">{{ statusLabel(quiz) }}</RuangBadge>
                        <ClipboardCheck class="size-5 text-primary" aria-hidden="true" />
                    </div>
                    <h2 class="mt-4 text-lg font-semibold leading-6">{{ quiz.title }}</h2>
                    <p v-if="quiz.summary" class="mt-2 line-clamp-2 text-sm leading-6 text-muted-foreground">{{ quiz.summary }}</p>
                    <p class="mt-3 text-sm text-muted-foreground">{{ quiz.className }} · {{ quiz.date }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ quiz.questionCount }} soal · {{ quiz.totalPoints }} poin</p>
                    <p v-if="quiz.status === 'completed'" class="mt-4 font-semibold text-success">Nilai {{ quiz.score }} / {{ quiz.totalPoints }}</p>
                    <p v-else-if="quiz.status === 'awaiting_review'" class="mt-4 text-sm text-muted-foreground">
                        Jawaban sudah dikirim. Nilai akhir tersedia setelah tutor menilai esai.
                    </p>
                    <Link
                        :href="quiz.href"
                        class="mt-auto inline-flex w-fit items-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-semibold hover:bg-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    >
                        {{ quiz.status ? 'Lihat hasil' : quiz.available ? 'Kerjakan kuis' : 'Lihat detail' }}
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </RuangCard>
            </div>
            <RuangCard v-else class="p-8 text-center">
                <h2 class="font-semibold">Belum ada kuis yang ditugaskan</h2>
                <p class="mt-2 text-sm text-muted-foreground">Kuis akan muncul setelah tutor menerbitkan dan menugaskannya ke kelasmu.</p>
            </RuangCard>

            <nav v-if="quizzes.last_page > 1" class="flex flex-wrap justify-end gap-2" aria-label="Halaman kuis">
                <a
                    v-for="link in quizzes.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :aria-current="link.active ? 'page' : undefined"
                    :aria-disabled="!link.url"
                    :class="[
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-background text-foreground hover:bg-muted',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    class="rounded-md border px-3 py-1.5 text-sm"
                    v-html="link.label"
                />
            </nav>
        </div>
    </AppLayout>
</template>
