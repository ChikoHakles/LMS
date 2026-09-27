<script setup lang="ts">
import EssayReviewItem from '@/components/quiz/EssayReviewItem.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardCheck } from 'lucide-vue-next';

defineProps<{
    pendingAnswers: Array<{
        id: number;
        attempt_id: number;
        student: { name: string };
        material: { id: number; title: string };
        question: { id: number; prompt: string; points: number };
        response: string;
        submitted_at: string;
    }>;
}>();
const page = usePage<SharedData & { flash: { status?: string } }>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pustaka materi', href: route('tutor.materials.index') },
    { title: 'Penilaian esai', href: page.url },
];
</script>

<template>
    <Head title="Penilaian esai" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header>
                <Link
                    :href="route('tutor.materials.index')"
                    class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" /> Kembali ke pustaka
                </Link>
                <p class="mt-5 text-sm font-semibold uppercase tracking-[0.16em] text-primary">Penilaian kuis</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Antrean jawaban esai</h1>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">Jawaban siswa dari kuis Anda menunggu skor dan komentar.</p>
            </header>

            <div
                v-if="page.props.flash?.status"
                role="status"
                aria-live="polite"
                class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success"
            >
                {{ page.props.flash.status }}
            </div>

            <div v-if="pendingAnswers.length" class="space-y-4">
                <EssayReviewItem v-for="answer in pendingAnswers" :key="answer.id" :answer="answer" />
            </div>
            <RuangCard v-else class="px-5 py-12 text-center">
                <ClipboardCheck class="mx-auto size-9 text-success" aria-hidden="true" />
                <h2 class="mt-3 font-semibold">Antrean esai kosong</h2>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">Jawaban yang perlu dinilai akan muncul di sini.</p>
            </RuangCard>
        </div>
    </AppLayout>
</template>
