<script setup lang="ts">
import MaterialCard from '@/components/materials/MaterialCard.vue';
import MaterialEmptyState from '@/components/materials/MaterialEmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

type Material = {
    id: number;
    type: 'article' | 'video' | 'quiz';
    title: string;
    summary: string | null;
    status: 'published';
    published_at: string | null;
};

defineProps<{
    materials: {
        data: Material[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Materi', href: route('student.materials.index') }];
</script>

<template>
    <Head title="Materi" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Kegiatan belajar</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Materi yang ditugaskan</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                    Materi yang telah diterbitkan dan ditugaskan untuk Anda akan tampil di sini.
                </p>
            </header>
            <div v-if="materials.data.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <MaterialCard v-for="material in materials.data" :key="material.id" :material="material" read-only show-student-action />
            </div>
            <MaterialEmptyState
                v-else
                title="Belum ada materi yang ditugaskan"
                description="Materi belajar yang tutor tugaskan akan muncul di sini."
            />
            <nav v-if="materials.last_page > 1" class="flex flex-wrap justify-end gap-2" aria-label="Halaman materi">
                <a
                    v-for="link in materials.links"
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
