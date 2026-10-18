<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, ClipboardList, FileText, PencilLine, Play } from 'lucide-vue-next';

defineProps<{
    material: {
        id: number;
        type: 'article' | 'video' | 'quiz';
        title: string;
        summary: string | null;
        status: 'draft' | 'published';
        published_at: string | null;
        owner?: { id: number; name: string };
    };
    readOnly?: boolean;
    showStudentAction?: boolean;
}>();

const labels = { article: 'Artikel', video: 'Video', quiz: 'Kuis' };
const icons = { article: FileText, video: Play, quiz: ClipboardList };

const categoryStyles = {
    article: {
        border: 'border-[#24EA9B]/50 hover:border-[#24EA9B]',
        badge: 'bg-[#E6FBF2] text-[#0D7A4D] border border-[#24EA9B]/50 dark:bg-[#064E3B]/50 dark:text-[#6EE7B7]',
        btn: 'bg-primary text-primary-foreground hover:bg-primary/90 font-semibold',
    },
    video: {
        border: 'border-[#FFDF18]/60 hover:border-[#FFDF18]',
        badge: 'bg-[#FEF9C3] text-[#713F12] border border-[#FFDF18]/60 dark:bg-[#713F12]/50 dark:text-[#FEF08A]',
        btn: 'bg-primary text-primary-foreground hover:bg-primary/90 font-semibold',
    },
    quiz: {
        border: 'border-[#06B6D4]/50 hover:border-[#06B6D4]',
        badge: 'bg-[#E0FAFF] text-[#0E7490] border border-[#06B6D4]/50 dark:bg-[#164E63]/50 dark:text-[#7DD3FC]',
        btn: 'bg-primary text-primary-foreground hover:bg-primary/90 font-semibold',
    },
};
</script>

<template>
    <RuangCard class="hover-lift flex h-full flex-col p-5 transition-all" :class="categoryStyles[material.type].border">
        <div class="flex items-start justify-between gap-3">
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" :class="categoryStyles[material.type].badge">
                <component :is="icons[material.type]" class="mr-1 inline size-3.5" aria-hidden="true" />
                {{ labels[material.type] }}
            </span>
            <RuangBadge :variant="material.status === 'published' ? 'success' : 'muted'">{{
                material.status === 'published' ? 'Terbit' : 'Draf'
            }}</RuangBadge>
        </div>
        <h2 class="mt-4 text-lg font-semibold leading-6 text-card-foreground">{{ material.title }}</h2>
        <p class="mt-2 line-clamp-3 min-h-[4.5rem] text-sm leading-6 text-muted-foreground">{{ material.summary || 'Belum ada ringkasan.' }}</p>
        <p v-if="readOnly && material.owner" class="mt-3 text-xs text-muted-foreground">Oleh {{ material.owner.name }}</p>
        <div class="mt-auto flex flex-wrap gap-2 pt-5">
            <Link
                v-if="!readOnly"
                :href="
                    route(
                        material.type === 'article'
                            ? 'tutor.materials.edit'
                            : material.type === 'video'
                              ? 'tutor.materials.video.edit'
                              : 'tutor.materials.quiz.edit',
                        { material: material.id },
                    )
                "
                class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm font-medium text-foreground hover:bg-muted"
            >
                <PencilLine class="size-4" aria-hidden="true" /> Edit
            </Link>
            <Link
                v-if="!readOnly && material.type === 'article'"
                :href="route('tutor.materials.show', { material: material.id })"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
            >
                <ArrowUpRight class="size-4" aria-hidden="true" /> Pratinjau
            </Link>
            <Link
                v-if="showStudentAction"
                :href="
                    material.type === 'quiz'
                        ? route('student.quizzes.index', { material: material.id })
                        : route('student.materials.show', { material: material.id })
                "
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
            >
                <ArrowUpRight class="size-4" aria-hidden="true" />
                {{ material.type === 'article' ? 'Baca artikel' : material.type === 'video' ? 'Tonton video' : 'Kerjakan kuis' }}
            </Link>
        </div>
    </RuangCard>
</template>
