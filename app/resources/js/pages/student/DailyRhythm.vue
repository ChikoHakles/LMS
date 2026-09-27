<script setup lang="ts">
import MaterialChecklist from '@/components/rhythm/MaterialChecklist.vue';
import PrayerTracker from '@/components/rhythm/PrayerTracker.vue';
import StudyTime from '@/components/rhythm/StudyTime.vue';
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, Check, Flame } from 'lucide-vue-next';
import { computed } from 'vue';

type Prayer = { id: string; label: string; completed: boolean };
type MaterialStep = { id: number; type: 'article' | 'video' | 'quiz'; title: string; summary: string | null; completed: boolean; href: string };

const props = defineProps<{
    date: string;
    classes: Array<{ id: number; name: string }>;
    selectedClassId: number | null;
    summary: { completed: number; total: number; prayersCompleted: number; materialsCompleted: number };
    prayers: Prayer[];
    plan: { id: number; className: string; targetMinutes: number; activeMinutes: number; materials: MaterialStep[] } | null;
}>();
const page = usePage<SharedData & { flash: { status?: string } }>();
const completionPercent = computed(() => (props.summary.total ? Math.round((props.summary.completed / props.summary.total) * 100) : 0));
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Beranda', href: route('dashboard') },
    { title: 'Ritme hari ini', href: route('student.daily-rhythm') },
];

function changeSelection(field: 'class_id' | 'date', value: string): void {
    router.get(
        route('student.daily-rhythm'),
        {
            class_id: field === 'class_id' ? value || undefined : (props.selectedClassId ?? undefined),
            date: field === 'date' ? value : props.date,
        },
        { preserveState: false, preserveScroll: true },
    );
}

function updatePrayer(change: { prayer: string; completed: boolean }): void {
    router.put(
        route('student.prayer-logs.update'),
        {
            date: props.date,
            class_id: props.selectedClassId,
            prayer: change.prayer,
            completed: change.completed,
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Ritme hari ini" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Ruang belajar</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Ritme hari ini</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                        Selesaikan langkah kecil satu per satu. Checklist tersimpan untuk tanggal yang kamu pilih.
                    </p>
                </div>
                <Link :href="route('student.materials.index')" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium hover:bg-muted">
                    Pustaka materi
                </Link>
            </header>

            <div
                v-if="page.props.flash?.status"
                role="status"
                aria-live="polite"
                class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success"
            >
                {{ page.props.flash.status }}
            </div>

            <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                <label v-if="classes.length" class="space-y-2 text-sm font-medium">
                    <span>Kelas</span>
                    <select
                        :value="selectedClassId ?? ''"
                        class="w-full rounded-lg border-input bg-background"
                        @change="changeSelection('class_id', ($event.target as HTMLSelectElement).value)"
                    >
                        <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </label>
                <div v-else class="rounded-lg border border-dashed border-border px-4 py-3 text-sm text-muted-foreground">
                    Kamu belum terdaftar di kelas.
                </div>
                <label class="space-y-2 text-sm font-medium">
                    <span>Tanggal ritme</span>
                    <span class="relative block">
                        <CalendarDays
                            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <input
                            :value="date"
                            type="date"
                            class="w-full rounded-lg border-input bg-background pl-10"
                            @change="changeSelection('date', ($event.target as HTMLInputElement).value)"
                        />
                    </span>
                </label>
                <RuangBadge variant="primary" class="justify-self-start sm:mb-2">
                    <Flame class="mr-1 size-3.5" aria-hidden="true" /> {{ plan?.className ?? 'Ritme pribadi' }}
                </RuangBadge>
            </div>

            <RuangCard class="grid gap-5 p-5 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-center sm:p-6">
                <div class="grid size-24 place-items-center rounded-full border-[6px] border-primary/20 bg-primary/5 text-center">
                    <div>
                        <p class="text-2xl font-bold">
                            {{ summary.completed }}<span class="text-base text-muted-foreground">/{{ summary.total }}</span>
                        </p>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">langkah</p>
                    </div>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold">Progres hari ini</h2>
                        <RuangBadge :variant="completionPercent === 100 ? 'success' : 'muted'">{{ completionPercent }}%</RuangBadge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ summary.prayersCompleted }}/5 sholat · {{ summary.materialsCompleted }}/3 materi
                    </p>
                    <div
                        class="mt-4 h-2 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="summary.completed"
                        :aria-valuemin="0"
                        :aria-valuemax="summary.total"
                        aria-label="Progres ritme harian"
                    >
                        <span class="block h-full rounded-full bg-primary transition-all" :style="{ width: `${completionPercent}%` }" />
                    </div>
                </div>
            </RuangCard>

            <PrayerTracker :prayers="prayers" @toggle="updatePrayer" />
            <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(18rem,0.75fr)]">
                <MaterialChecklist :materials="plan?.materials ?? []" />
                <div class="space-y-5">
                    <StudyTime :target-minutes="plan?.targetMinutes ?? 0" :active-minutes="plan?.activeMinutes ?? 0" />
                    <RuangCard class="space-y-3 p-5">
                        <h2 class="font-semibold">Urutan yang disarankan</h2>
                        <p class="text-sm leading-6 text-muted-foreground">
                            Mulai dari artikel, lanjutkan dengan video, lalu periksa pemahaman melalui kuis.
                        </p>
                        <p class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Check class="size-4 text-primary" aria-hidden="true" /> Progres mengikuti penugasan kelas dan tanggal ini.
                        </p>
                    </RuangCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
