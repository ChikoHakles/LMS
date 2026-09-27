<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, ClipboardList, FileText, PencilLine, Play } from 'lucide-vue-next';

const props = defineProps<{
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
</script>

<template>
    <RuangCard class="flex h-full flex-col p-5 transition-shadow hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
            <RuangBadge variant="soft"
                ><component :is="icons[material.type]" class="mr-1 inline size-3.5" aria-hidden="true" />{{ labels[material.type] }}</RuangBadge
            >
            <RuangBadge :variant="material.status === 'published' ? 'success' : 'muted'">{{
                material.status === 'published' ? 'Terbit' : 'Draf'
            }}</RuangBadge>
        </div>
        <h2 class="mt-4 text-lg font-semibold leading-6 text-card-foreground">{{ material.title }}</h2>
        <p class="mt-2 line-clamp-3 min-h-[4.5rem] text-sm leading-6 text-muted-foreground">{{ material.summary || 'Belum ada ringkasan.' }}</p>
        <p v-if="readOnly && material.owner" class="mt-3 text-xs text-muted-foreground">Oleh {{ material.owner.name }}</p>
        <div class="mt-auto flex flex-wrap gap-2 pt-5">
            <Link
                v-if="!readOnly && (material.type === 'article' || material.type === 'video')"
                :href="route(material.type === 'article' ? 'tutor.materials.edit' : 'tutor.materials.video.edit', material.id)"
                class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm font-medium text-foreground hover:bg-muted"
            >
                <PencilLine class="size-4" aria-hidden="true" /> Edit
            </Link>
            <Link
                v-if="!readOnly && material.type === 'article'"
                :href="route('tutor.materials.show', material.id)"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
            >
                <ArrowUpRight class="size-4" aria-hidden="true" /> Pratinjau
            </Link>
            <Link
                v-if="showStudentAction && (material.type === 'article' || material.type === 'video')"
                :href="route('student.materials.show', material.id)"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
            >
                <ArrowUpRight class="size-4" aria-hidden="true" /> {{ material.type === 'article' ? 'Baca artikel' : 'Tonton video' }}
            </Link>
        </div>
    </RuangCard>
</template>
