<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Check, ClipboardCheck, Play } from 'lucide-vue-next';

type MaterialStep = { id: number; type: 'article' | 'video' | 'quiz'; title: string; summary: string | null; completed: boolean; href: string };
defineProps<{ materials: MaterialStep[] }>();
const labels = { article: 'Artikel', video: 'Video', quiz: 'Kuis' };
const icons = { article: BookOpen, video: Play, quiz: ClipboardCheck };
</script>

<template>
    <RuangCard class="p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">Materi hari ini</h2>
                <p class="mt-1 text-sm text-muted-foreground">Buka setiap materi melalui tasklist dan tandai selesai dari halaman belajarnya.</p>
            </div>
            <span class="rounded-full bg-secondary px-3 py-1 text-xs font-semibold"
                >{{ materials.filter((item) => item.completed).length }}/3 selesai</span
            >
        </div>
        <div v-if="!materials.length" class="mt-5 rounded-xl border border-dashed border-border px-4 py-8 text-center">
            <p class="font-medium">Belum ada tasklist untuk tanggal ini</p>
            <p class="mt-1 text-sm text-muted-foreground">Materi akan muncul setelah tutor menyimpan rencana harian.</p>
        </div>
        <div v-else class="mt-5 space-y-3">
            <article v-for="(item, index) in materials" :key="item.id" class="flex flex-wrap items-center gap-4 rounded-xl border border-border p-4">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-secondary text-primary">
                    <component :is="icons[item.type]" class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-muted-foreground">LANGKAH {{ index + 1 }}</span>
                        <RuangBadge :variant="item.completed ? 'success' : 'muted'">{{ item.completed ? 'Selesai' : labels[item.type] }}</RuangBadge>
                    </div>
                    <h3 class="mt-1 truncate font-semibold">{{ item.title }}</h3>
                    <p v-if="item.summary" class="mt-1 line-clamp-2 text-sm text-muted-foreground">{{ item.summary }}</p>
                </div>
                <Link :href="item.href" class="rounded-lg border border-border px-4 py-2 text-sm font-semibold hover:bg-muted">
                    {{
                        item.completed
                            ? 'Buka lagi'
                            : item.type === 'quiz'
                              ? 'Kerjakan kuis'
                              : item.type === 'video'
                                ? 'Tonton video'
                                : 'Baca artikel'
                    }}
                </Link>
                <Check v-if="item.completed" class="size-5 text-success" aria-label="Sudah selesai" />
            </article>
        </div>
    </RuangCard>
</template>
