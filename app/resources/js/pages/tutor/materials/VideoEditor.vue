<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Eye, Play, Save, Send } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type VideoMaterial = {
    id: number;
    type: 'video';
    title: string;
    summary: string | null;
    status: 'draft' | 'published';
    published_at: string | null;
    youtube_video_id: string;
};

const props = defineProps<{ material: VideoMaterial | null }>();
const page = usePage<SharedData & { flash: { status?: string } }>();
const form = useForm({
    title: props.material?.title ?? '',
    summary: props.material?.summary ?? '',
    youtube_url: props.material?.youtube_video_id ? `https://www.youtube.com/watch?v=${props.material.youtube_video_id}` : '',
});
const isPublishing = ref(false);
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pustaka materi', href: route('tutor.materials.index') },
    { title: props.material ? 'Edit video' : 'Buat Materi: Video', href: page.url },
];

function extractYouTubeId(rawUrl: string): string | null {
    const authority = rawUrl.trim().match(/^https:\/\/([^/?#]*)/i)?.[1] ?? '';
    if (authority.includes('@')) return null;

    let url: URL;
    try {
        url = new URL(rawUrl.trim());
    } catch {
        return null;
    }

    if (url.protocol !== 'https:' || url.username || url.password || url.port) return null;

    const host = url.hostname.toLowerCase().replace(/\.$/, '');
    const youtubeHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com'];
    let id: string | null = null;
    if (youtubeHosts.includes(host) && url.pathname === '/watch') {
        const values = url.searchParams.getAll('v');
        id = values[values.length - 1] ?? null;
    } else if (youtubeHosts.includes(host)) {
        id = url.pathname.match(/^\/shorts\/([A-Za-z0-9_-]{11})\/?$/)?.[1] ?? null;
    } else if (host === 'youtu.be') {
        id = url.pathname.match(/^\/([A-Za-z0-9_-]{11})\/?$/)?.[1] ?? null;
    }

    return id && /^[A-Za-z0-9_-]{11}$/.test(id) ? id : null;
}

const previewEmbedUrl = computed(() => {
    const id = extractYouTubeId(form.youtube_url);
    return id ? `https://www.youtube-nocookie.com/embed/${id}` : null;
});

function saveDraft(): void {
    if (props.material) {
        form.put(route('tutor.materials.video.update', props.material.id), { preserveScroll: true });
    } else {
        form.post(route('tutor.materials.video.store'), { preserveScroll: true });
    }
}

function publishVideo(): void {
    if (!props.material || form.isDirty) {
        saveDraft();
        return;
    }

    isPublishing.value = true;
    router.patch(
        route('tutor.materials.video.publish', props.material.id),
        {},
        {
            preserveScroll: true,
            onError: (errors) => Object.assign(form.errors, errors),
            onFinish: () => {
                isPublishing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="material ? 'Edit video' : 'Buat Materi: Video'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <Link
                        :href="route('tutor.materials.index')"
                        class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                        ><ArrowLeft class="size-4" aria-hidden="true" /> Kembali ke pustaka</Link
                    >
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Penyunting video</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ material ? 'Edit video' : 'Buat Materi: Video' }}</h1>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Sematkan video YouTube menggunakan tautannya. Video baru disimpan sebagai draf.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="saveDraft"
                        class="inline-flex items-center gap-2 rounded-lg bg-secondary px-4 py-2.5 text-sm font-semibold text-secondary-foreground disabled:opacity-50"
                    >
                        <Save class="size-4" aria-hidden="true" /> Simpan draf
                    </button>
                    <button
                        v-if="material"
                        type="button"
                        :disabled="isPublishing || form.processing"
                        @click="publishVideo"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground disabled:opacity-50"
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
                <RuangBadge :variant="material.status === 'published' ? 'success' : 'muted'">{{
                    material.status === 'published' ? 'Terbit' : 'Draf'
                }}</RuangBadge>
                <span v-if="material.status === 'published'" class="text-sm text-muted-foreground"
                    >Menyimpan perubahan akan mengembalikan video ke status draf.</span
                >
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,0.9fr)]">
                <RuangCard class="h-fit space-y-5 p-5 sm:p-6">
                    <div>
                        <label for="video-title" class="text-sm font-semibold">Judul video</label>
                        <input
                            id="video-title"
                            v-model="form.title"
                            maxlength="255"
                            required
                            class="mt-2 w-full rounded-lg border-input bg-background text-base focus:ring-ring"
                            placeholder="Contoh: Memahami grafik fungsi kuadrat"
                        />
                        <p v-if="form.errors.title" class="mt-1 text-sm text-destructive">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label for="video-summary" class="text-sm font-semibold">Ringkasan</label>
                        <textarea
                            id="video-summary"
                            v-model="form.summary"
                            maxlength="1000"
                            rows="4"
                            class="mt-2 w-full resize-y rounded-lg border-input bg-background text-sm leading-6 focus:ring-ring"
                            placeholder="Jelaskan pokok bahasan dan hal yang perlu diperhatikan murid."
                        />
                        <p v-if="form.errors.summary" class="mt-1 text-sm text-destructive">{{ form.errors.summary }}</p>
                    </div>

                    <div>
                        <label for="youtube-url" class="text-sm font-semibold">Tautan YouTube</label>
                        <input
                            id="youtube-url"
                            v-model="form.youtube_url"
                            type="url"
                            maxlength="2048"
                            required
                            aria-describedby="youtube-help"
                            :aria-invalid="Boolean(form.errors.youtube_url)"
                            class="mt-2 w-full rounded-lg border-input bg-background text-sm focus:ring-ring"
                            placeholder="https://www.youtube.com/watch?v=..."
                        />
                        <p id="youtube-help" class="mt-2 text-xs leading-5 text-muted-foreground">
                            Gunakan tautan youtube.com/watch, youtu.be, atau Shorts. Tempel tautan langsung; HTML iframe tidak diperlukan.
                        </p>
                        <p v-if="form.errors.youtube_url" role="alert" class="mt-1 text-sm text-destructive">{{ form.errors.youtube_url }}</p>
                    </div>
                </RuangCard>

                <RuangCard class="h-fit p-5 sm:p-6">
                    <div class="flex items-center gap-2">
                        <Eye class="size-4 text-primary" aria-hidden="true" />
                        <h2 class="font-semibold">Pratinjau video</h2>
                    </div>
                    <div v-if="previewEmbedUrl" class="mt-4 overflow-hidden rounded-xl border border-border bg-black">
                        <iframe
                            :src="previewEmbedUrl"
                            :title="form.title || 'Pratinjau video YouTube'"
                            class="aspect-video w-full"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        />
                    </div>
                    <div
                        v-else
                        class="mt-4 flex aspect-video flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/40 px-6 text-center"
                    >
                        <Play class="size-8 text-muted-foreground" aria-hidden="true" />
                        <p class="mt-3 font-medium">Tautan video belum valid</p>
                        <p class="mt-1 max-w-sm text-sm leading-6 text-muted-foreground">
                            Pratinjau tampil setelah tautan YouTube yang didukung dimasukkan.
                        </p>
                    </div>
                    <div class="mt-4 rounded-lg bg-secondary/50 p-4">
                        <p class="text-sm font-semibold">Status materi</p>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            Draf tidak terlihat oleh murid. Terbitkan setelah judul, ringkasan, dan video siap.
                        </p>
                    </div>
                </RuangCard>
            </div>
        </div>
    </AppLayout>
</template>
