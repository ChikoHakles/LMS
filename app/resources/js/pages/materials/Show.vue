<script setup lang="ts">
import ArticleRenderer, { type ArticleBlock } from '@/components/materials/ArticleRenderer.vue';
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, PencilLine } from 'lucide-vue-next';

const props = defineProps<{
    material: {
        id: number;
        type: 'article';
        title: string;
        summary: string | null;
        status?: 'draft' | 'published';
        published_at: string | null;
        blocks: ArticleBlock[] | null;
    };
    canEdit: boolean;
    assignmentId?: number | null;
    completed?: boolean;
}>();
const completion = useForm({});

function markComplete(): void {
    if (!props.assignmentId || props.completed) return;
    completion.put(route('student.daily-plan-materials.complete', props.assignmentId), { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pustaka materi', href: props.canEdit ? route('tutor.materials.index') : route('student.materials.index') },
    { title: 'Baca artikel', href: route(props.canEdit ? 'tutor.materials.show' : 'student.materials.show', props.material.id) },
];
</script>

<template>
    <Head :title="material.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="route(canEdit ? 'tutor.materials.index' : 'student.materials.index')"
                    class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                    ><ArrowLeft class="size-4" aria-hidden="true" /> {{ canEdit ? 'Kembali ke pustaka' : 'Kembali ke materi' }}</Link
                >
                <div class="flex items-center gap-3">
                    <RuangBadge v-if="canEdit" :variant="material.status === 'published' ? 'success' : 'muted'">{{
                        material.status === 'published' ? 'Terbit' : 'Draf'
                    }}</RuangBadge>
                    <Link
                        v-if="canEdit"
                        :href="route('tutor.materials.edit', material.id)"
                        class="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium hover:bg-muted"
                        ><PencilLine class="size-4" aria-hidden="true" /> Edit</Link
                    >
                </div>
            </header>
            <RuangCard class="p-6 sm:p-10">
                <header class="mx-auto mb-8 max-w-3xl border-b border-border pb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Artikel</p>
                    <h1 class="mt-3 text-3xl font-bold leading-tight tracking-tight sm:text-4xl">{{ material.title }}</h1>
                    <p v-if="material.summary" class="mt-4 text-lg leading-7 text-muted-foreground">{{ material.summary }}</p>
                </header>
                <ArticleRenderer :blocks="material.blocks" />
            </RuangCard>
            <div v-if="!canEdit && assignmentId" class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    :disabled="completed || completion.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground disabled:opacity-60"
                    @click="markComplete"
                >
                    <Check class="size-4" aria-hidden="true" /> {{ completed ? 'Sudah selesai' : 'Tandai selesai' }}
                </button>
                <span v-if="completion.errors.assignment" role="alert" class="text-sm text-destructive">{{ completion.errors.assignment }}</span>
            </div>
        </div>
    </AppLayout>
</template>
