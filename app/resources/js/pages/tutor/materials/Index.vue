<script setup lang="ts">
import MaterialCard from '@/components/materials/MaterialCard.vue';
import MaterialEmptyState from '@/components/materials/MaterialEmptyState.vue';
import MaterialFilters from '@/components/materials/MaterialFilters.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

type Material = {
    id: number;
    type: 'article' | 'video' | 'quiz';
    title: string;
    summary: string | null;
    status: 'draft' | 'published';
    published_at: string | null;
    owner?: { id: number; name: string };
};

const props = defineProps<{
    materials: {
        data: Material[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { q?: string; type?: string | null; status?: string | null };
    types: string[];
    readOnly?: boolean;
}>();

const page = usePage<SharedData & { flash: { status?: string } }>();
const heading = props.readOnly ? 'Pustaka materi' : 'Pustaka materi';
const breadcrumbs: BreadcrumbItem[] = [{ title: heading, href: page.url }];
</script>

<template>
    <Head :title="heading" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Konten belajar</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ heading }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">Temukan artikel, video, dan kuis untuk kegiatan belajar.</p>
                </div>
                <div v-if="!readOnly" class="flex flex-wrap gap-2">
                    <Link
                        :href="route('tutor.materials.article.create')"
                        class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-semibold hover:bg-muted"
                    >
                        <Plus class="size-4" aria-hidden="true" /> Buat artikel
                    </Link>
                    <Link
                        :href="route('tutor.materials.video.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
                    >
                        <Plus class="size-4" aria-hidden="true" /> Buat video
                    </Link>
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

            <MaterialFilters :filters="filters" :show-status="!readOnly" :route-name="readOnly ? 'admin.materials.index' : 'tutor.materials.index'" />

            <div v-if="materials.data.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <MaterialCard v-for="material in materials.data" :key="material.id" :material="material" :read-only="readOnly" />
            </div>
            <MaterialEmptyState
                v-else
                :title="filters.q || filters.type || filters.status ? 'Materi tidak ditemukan' : 'Belum ada materi'"
                :description="
                    filters.q || filters.type || filters.status
                        ? 'Coba ubah kata kunci atau filter yang dipilih.'
                        : readOnly
                          ? 'Materi yang dibagikan akan muncul di sini.'
                          : 'Artikel, video, dan kuis yang Anda buat akan tersimpan di pustaka ini.'
                "
                :create-href="readOnly ? null : route('tutor.materials.article.create')"
            />

            <RuangCard v-if="materials.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <p class="text-sm text-muted-foreground">
                    {{ materials.total }} materi · Halaman {{ materials.current_page }} dari {{ materials.last_page }}
                </p>
                <nav class="flex flex-wrap gap-2" aria-label="Halaman pustaka materi">
                    <Link
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
            </RuangCard>
        </div>
    </AppLayout>
</template>
