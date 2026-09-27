<script setup lang="ts">
import ArticleRenderer, { type ArticleBlock } from '@/components/materials/ArticleRenderer.vue';
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Eye, FileText, Heading, List, MessageSquareQuote, Plus, Save, Send, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Material = {
    id: number;
    type: 'article';
    title: string;
    summary: string | null;
    status: 'draft' | 'published';
    published_at: string | null;
    blocks: ArticleBlock[] | null;
};

const props = defineProps<{ material: Material | null }>();
const page = usePage<SharedData & { flash: { status?: string } }>();
const cloneBlocks = (blocks: ArticleBlock[] | null | undefined): ArticleBlock[] =>
    blocks ? (JSON.parse(JSON.stringify(blocks)) as ArticleBlock[]) : [];
const form = useForm({ title: props.material?.title ?? '', summary: props.material?.summary ?? '', blocks: cloneBlocks(props.material?.blocks) });
const isPreview = ref(false);
const isPublishing = ref(false);
const errors = computed(() => form.errors as Record<string, string>);
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pustaka materi', href: route('tutor.materials.index') },
    { title: props.material ? 'Edit artikel' : 'Buat Materi: Artikel', href: page.url },
];

function addBlock(type: ArticleBlock['type']): void {
    const newBlock: ArticleBlock =
        type === 'list'
            ? { type: 'list', items: [''] }
            : type === 'link'
              ? { type: 'link', text: '', url: '' }
              : type === 'heading'
                ? { type: 'heading', text: '', level: 2 }
                : type === 'quote'
                  ? { type: 'quote', text: '', attribution: '' }
                  : { type: 'paragraph', text: '' };
    form.blocks.push(newBlock);
}

function removeBlock(index: number): void {
    form.blocks.splice(index, 1);
}

function updateList(index: number, value: string): void {
    const block = form.blocks[index];
    if (block?.type === 'list') block.items = value.split(/\r?\n/).slice(0, 50);
}

function saveDraft(): void {
    const options = { preserveScroll: true };
    if (props.material) {
        form.put(route('tutor.materials.update', props.material.id), options);
    } else {
        form.post(route('tutor.materials.store'), options);
    }
}

function publishArticle(): void {
    if (!props.material) {
        saveDraft();
        return;
    }
    isPublishing.value = true;
    router.patch(
        route('tutor.materials.publish', props.material.id),
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
    <Head :title="material ? 'Edit artikel' : 'Buat Materi: Artikel'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <Link
                        :href="route('tutor.materials.index')"
                        class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                        ><ArrowLeft class="size-4" aria-hidden="true" /> Kembali ke pustaka</Link
                    >
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Penyunting artikel</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ material ? 'Edit artikel' : 'Buat Materi: Artikel' }}</h1>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Susun isi dari blok teks yang aman. Simpan sebagai draf sebelum diterbitkan.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="isPreview = !isPreview"
                        class="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-semibold hover:bg-muted"
                    >
                        <Eye class="size-4" aria-hidden="true" /> {{ isPreview ? 'Kembali mengedit' : 'Pratinjau' }}
                    </button>
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
                        @click="publishArticle"
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
                    >Menyimpan perubahan akan mengembalikan artikel ke status draf.</span
                >
            </div>

            <div v-if="isPreview" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <RuangCard class="p-6 sm:p-10">
                    <header class="mx-auto mb-8 max-w-3xl border-b border-border pb-6">
                        <h2 class="text-3xl font-bold leading-tight tracking-tight sm:text-4xl">{{ form.title || 'Judul artikel' }}</h2>
                        <p v-if="form.summary" class="mt-4 text-lg leading-7 text-muted-foreground">{{ form.summary }}</p>
                    </header>
                    <ArticleRenderer :blocks="form.blocks" />
                </RuangCard>
                <RuangCard class="h-fit p-5">
                    <RuangBadge variant="primary"><Eye class="mr-1 inline size-3.5" aria-hidden="true" /> Pratinjau</RuangBadge>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        Tampilan ini memakai renderer yang sama dengan halaman baca siswa. Hanya teks, daftar, kutipan, dan tautan aman yang
                        ditampilkan.
                    </p>
                </RuangCard>
            </div>

            <div v-else class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="space-y-5">
                    <RuangCard class="space-y-4 p-5 sm:p-6">
                        <div>
                            <label for="article-title" class="text-sm font-semibold">Judul artikel</label>
                            <input
                                id="article-title"
                                v-model="form.title"
                                maxlength="255"
                                required
                                class="mt-2 w-full rounded-lg border-input bg-background text-base focus:ring-ring"
                                placeholder="Contoh: Menjaga kebersihan lingkungan"
                            />
                            <p v-if="errors.title" class="mt-1 text-sm text-destructive">{{ errors.title }}</p>
                        </div>
                        <div>
                            <label for="article-summary" class="text-sm font-semibold">Ringkasan</label>
                            <textarea
                                id="article-summary"
                                v-model="form.summary"
                                maxlength="1000"
                                rows="3"
                                class="mt-2 w-full resize-y rounded-lg border-input bg-background text-sm leading-6 focus:ring-ring"
                                placeholder="Ringkasan singkat untuk kartu pustaka"
                            />
                            <p v-if="errors.summary" class="mt-1 text-sm text-destructive">{{ errors.summary }}</p>
                        </div>
                    </RuangCard>

                    <div class="space-y-3">
                        <div v-if="!form.blocks.length" class="rounded-xl border border-dashed border-border bg-card px-5 py-9 text-center">
                            <FileText class="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
                            <p class="mt-3 font-medium">Mulai menulis artikel</p>
                            <p class="mt-1 text-sm text-muted-foreground">Tambahkan paragraf, judul bagian, daftar, kutipan, atau tautan.</p>
                        </div>
                        <RuangCard v-for="(block, index) in form.blocks" :key="index" class="p-4 sm:p-5">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <RuangBadge variant="muted"
                                    >Blok {{ index + 1 }} ·
                                    {{
                                        block.type === 'list'
                                            ? 'Daftar'
                                            : block.type === 'heading'
                                              ? 'Judul bagian'
                                              : block.type === 'quote'
                                                ? 'Kutipan'
                                                : block.type === 'link'
                                                  ? 'Tautan'
                                                  : 'Paragraf'
                                    }}</RuangBadge
                                >
                                <button
                                    type="button"
                                    @click="removeBlock(index)"
                                    class="rounded-md p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    :aria-label="`Hapus blok ${index + 1}`"
                                >
                                    <Trash2 class="size-4" aria-hidden="true" />
                                </button>
                            </div>
                            <textarea
                                v-if="block.type === 'paragraph'"
                                v-model="block.text"
                                rows="4"
                                maxlength="10000"
                                class="w-full resize-y rounded-lg border-input bg-background text-sm leading-7 focus:ring-ring"
                                placeholder="Tulis paragraf..."
                            />
                            <div v-else-if="block.type === 'heading'" class="space-y-3">
                                <label class="sr-only" :for="`heading-level-${index}`">Tingkat judul</label>
                                <select
                                    :id="`heading-level-${index}`"
                                    v-model="block.level"
                                    class="rounded-lg border-input bg-background text-sm focus:ring-ring"
                                >
                                    <option :value="2">Judul bagian</option>
                                    <option :value="3">Subjudul</option>
                                </select>
                                <input
                                    v-model="block.text"
                                    maxlength="10000"
                                    class="w-full rounded-lg border-input bg-background text-lg font-semibold focus:ring-ring"
                                    placeholder="Nama bagian"
                                />
                            </div>
                            <div v-else-if="block.type === 'list'">
                                <label class="flex items-center gap-2 text-sm"
                                    ><input v-model="block.ordered" type="checkbox" class="rounded border-input text-primary focus:ring-ring" />
                                    Daftar bernomor</label
                                >
                                <textarea
                                    :value="block.items.join('\n')"
                                    @input="updateList(index, ($event.target as HTMLTextAreaElement).value)"
                                    rows="4"
                                    class="mt-3 w-full resize-y rounded-lg border-input bg-background text-sm leading-7 focus:ring-ring"
                                    placeholder="Satu butir per baris"
                                />
                            </div>
                            <div v-else-if="block.type === 'quote'" class="space-y-3">
                                <textarea
                                    v-model="block.text"
                                    rows="3"
                                    maxlength="10000"
                                    class="w-full resize-y rounded-lg border-input bg-background text-sm leading-7 focus:ring-ring"
                                    placeholder="Teks kutipan"
                                />
                                <input
                                    v-model="block.attribution"
                                    maxlength="255"
                                    class="w-full rounded-lg border-input bg-background text-sm focus:ring-ring"
                                    placeholder="Sumber atau narasumber (opsional)"
                                />
                            </div>
                            <div v-else class="grid gap-3 sm:grid-cols-2">
                                <input
                                    v-model="block.text"
                                    maxlength="10000"
                                    class="w-full rounded-lg border-input bg-background text-sm focus:ring-ring"
                                    placeholder="Teks tautan"
                                />
                                <input
                                    v-model="block.url"
                                    type="url"
                                    maxlength="2048"
                                    class="w-full rounded-lg border-input bg-background text-sm focus:ring-ring"
                                    placeholder="https://contoh.com"
                                />
                            </div>
                            <p v-if="errors[`blocks.${index}.text`]" class="mt-2 text-sm text-destructive">{{ errors[`blocks.${index}.text`] }}</p>
                            <p v-if="errors[`blocks.${index}.url`]" class="mt-2 text-sm text-destructive">{{ errors[`blocks.${index}.url`] }}</p>
                            <p v-if="errors[`blocks.${index}.items`]" class="mt-2 text-sm text-destructive">{{ errors[`blocks.${index}.items`] }}</p>
                        </RuangCard>
                        <p v-if="errors.blocks" class="text-sm text-destructive">{{ errors.blocks }}</p>
                    </div>
                </div>

                <aside class="h-fit space-y-3 xl:sticky xl:top-6">
                    <RuangCard class="p-5">
                        <h2 class="font-semibold">Tambah blok</h2>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Gunakan susunan sederhana agar artikel mudah dibaca.</p>
                        <div class="mt-4 grid gap-2">
                            <button
                                type="button"
                                @click="addBlock('paragraph')"
                                class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2.5 text-left text-sm font-medium hover:bg-muted"
                            >
                                <Plus class="size-4" aria-hidden="true" /> Paragraf
                            </button>
                            <button
                                type="button"
                                @click="addBlock('heading')"
                                class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2.5 text-left text-sm font-medium hover:bg-muted"
                            >
                                <Heading class="size-4" aria-hidden="true" /> Judul bagian
                            </button>
                            <button
                                type="button"
                                @click="addBlock('list')"
                                class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2.5 text-left text-sm font-medium hover:bg-muted"
                            >
                                <List class="size-4" aria-hidden="true" /> Daftar
                            </button>
                            <button
                                type="button"
                                @click="addBlock('quote')"
                                class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2.5 text-left text-sm font-medium hover:bg-muted"
                            >
                                <MessageSquareQuote class="size-4" aria-hidden="true" /> Kutipan
                            </button>
                            <button
                                type="button"
                                @click="addBlock('link')"
                                class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2.5 text-left text-sm font-medium hover:bg-muted"
                            >
                                <Plus class="size-4" aria-hidden="true" /> Tautan
                            </button>
                        </div>
                    </RuangCard>
                    <RuangCard class="p-5">
                        <h2 class="font-semibold">Status artikel</h2>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Artikel baru disimpan sebagai draf. Terbitkan setelah isi siap agar dapat dibaca siswa yang mendapat penugasan.
                        </p>
                    </RuangCard>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
