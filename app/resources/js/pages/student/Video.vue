<script setup lang="ts">
import StudyTimer from '@/components/rhythm/StudyTimer.vue';
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import type { StudyTimerContext } from '@/composables/useStudyTimer';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, Play } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    material: {
        id: number;
        type: 'video';
        title: string;
        summary: string | null;
        published_at: string | null;
        embedUrl: string;
        completed?: boolean;
        assignmentId?: number | null;
    };
    timerContext?: StudyTimerContext | null;
}>();
const completion = useForm({});

const safeEmbedUrl = computed(() => {
    const match = props.material.embedUrl.match(/^https:\/\/www\.youtube-nocookie\.com\/embed\/([A-Za-z0-9_-]{11})$/);
    return match ? `https://www.youtube-nocookie.com/embed/${match[1]}` : null;
});
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Materi belajar', href: route('student.materials.index') },
    { title: 'Tonton video', href: route('student.materials.show', props.material.id) },
];

function markComplete(): void {
    if (!props.material.assignmentId || props.material.completed) return;
    completion.put(route('student.daily-plan-materials.complete', props.material.assignmentId), { preserveScroll: true });
}
</script>

<template>
    <Head :title="material.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
            <header>
                <Link
                    :href="route('student.materials.index')"
                    class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" /> Kembali ke materi
                </Link>
                <p class="mt-5 text-sm font-semibold uppercase tracking-[0.16em] text-primary">Materi video</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ material.title }}</h1>
                <p v-if="material.summary" class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground">{{ material.summary }}</p>
            </header>
            <StudyTimer v-if="timerContext" :key="timerContext.assignmentId" :context="timerContext" />

            <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <RuangCard class="overflow-hidden p-4 sm:p-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <RuangBadge variant="primary"><Play class="mr-1 inline size-3.5" aria-hidden="true" /> Video YouTube</RuangBadge>
                        <span v-if="material.published_at" class="text-xs text-muted-foreground">Materi terbit</span>
                    </div>
                    <div v-if="safeEmbedUrl" class="overflow-hidden rounded-xl bg-black">
                        <iframe
                            :src="safeEmbedUrl"
                            :title="material.title"
                            class="aspect-video w-full"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        />
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-border bg-muted/40 px-5 py-10 text-center">
                        <p class="font-semibold">Video tidak dapat ditampilkan</p>
                        <p class="mt-1 text-sm text-muted-foreground">Tautan video tidak valid.</p>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-border pt-5">
                        <button
                            type="button"
                            :disabled="!material.assignmentId || material.completed || completion.processing"
                            :aria-pressed="material.completed ? 'true' : 'false'"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                            @click="markComplete"
                        >
                            <Check class="size-4" aria-hidden="true" /> {{ material.completed ? 'Sudah selesai' : 'Tandai selesai' }}
                        </button>
                        <Link
                            :href="route('student.daily-rhythm')"
                            class="inline-flex items-center rounded-lg border border-border px-4 py-2.5 text-sm font-medium hover:bg-muted"
                        >
                            Buka Ritme hari ini
                        </Link>
                    </div>
                    <p v-if="completion.errors.assignment" role="alert" class="mt-3 text-sm text-destructive">{{ completion.errors.assignment }}</p>
                    <p v-if="!material.completed" class="mt-3 text-xs leading-5 text-muted-foreground">
                        Status penyelesaian mengikuti checklist materi yang ditugaskan.
                    </p>
                </RuangCard>

                <RuangCard class="p-5">
                    <h2 class="font-semibold">Tentang video ini</h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        {{ material.summary || 'Video dipilih oleh tutor sebagai bagian dari kegiatan belajar.' }}
                    </p>
                    <div class="mt-5 rounded-lg bg-secondary/50 p-4">
                        <p class="text-sm font-semibold">Saat belajar</p>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            Tonton video, lalu kembali ke Ritme hari ini untuk melanjutkan langkah belajar berikutnya.
                        </p>
                    </div>
                </RuangCard>
            </div>
        </div>
    </AppLayout>
</template>
