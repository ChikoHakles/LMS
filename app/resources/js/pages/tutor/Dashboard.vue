<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import RuangProgress from '@/components/ruang/RuangProgress.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, CalendarDays, ClipboardCheck, FileText, ListChecks, Users } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

interface Props {
    date: string;
    summary: {
        classCount: number;
        studentCount: number;
        todayPlanCount: number;
        materialCount: number;
        submissionCount: number;
        essayQueueCount: number;
    };
    classes: Array<{ id: number; name: string; studentCount: number; todayPlanCount: number }>;
    progressPreview: Array<{
        id: number;
        name: string;
        prayersCompleted: number;
        prayersTotal: number;
        materialsCompleted: number;
        materialsTotal: number;
        activeMinutes: number;
    }>;
}

const props = defineProps<Props>();
const loading = ref(false);
const removeStartListener = router.on('start', () => {
    loading.value = true;
});
const removeFinishListener = router.on('finish', () => {
    loading.value = false;
});
onBeforeUnmount(() => {
    removeStartListener();
    removeFinishListener();
});

const cards = computed(() => [
    { label: 'Kelas', value: props.summary.classCount, href: route('tutor.daily-plans.index'), icon: Users, detail: 'Buka agenda kelas' },
    {
        label: 'Tasklist hari ini',
        value: props.summary.todayPlanCount,
        href: route('tutor.daily-plans.index'),
        icon: ListChecks,
        detail: 'Atur tasklist',
    },
    { label: 'Materi terbit', value: props.summary.materialCount, href: route('tutor.materials.index'), icon: BookOpen, detail: 'Buka pustaka' },
    { label: 'Pengumpulan', value: props.summary.submissionCount, href: route('tutor.essays.index'), icon: ClipboardCheck, detail: 'Lihat jawaban' },
    { label: 'Esai menunggu', value: props.summary.essayQueueCount, href: route('tutor.essays.index'), icon: FileText, detail: 'Buka antrean esai' },
]);
const dateLabel = computed(() =>
    new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${props.date}T12:00:00Z`)),
);
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Beranda', href: route('dashboard') }];
</script>

<template>
    <Head title="Beranda tutor" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8" :aria-busy="loading">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Ruang mengajar · {{ dateLabel }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">Beranda tutor</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">Pantau agenda kelas dan langkah murid hari ini.</p>
                </div>
                <Link
                    :href="route('tutor.daily-plans.index')"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
                >
                    Atur tasklist <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </header>

            <p v-if="loading" role="status" aria-live="polite" class="text-sm text-muted-foreground">Memuat data kelas…</p>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan tutor">
                <Link
                    v-for="card in cards"
                    :key="card.label"
                    :href="card.href"
                    class="group rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <RuangCard class="h-full p-4 transition-colors group-hover:border-primary/50 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm font-medium text-muted-foreground">{{ card.label }}</p>
                            <component :is="card.icon" class="size-4 text-primary" aria-hidden="true" />
                        </div>
                        <p class="mt-3 text-3xl font-bold text-card-foreground">{{ card.value }}</p>
                        <p class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary">
                            {{ card.detail }} <ArrowRight class="size-3.5" aria-hidden="true" />
                        </p>
                    </RuangCard>
                </Link>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <RuangCard class="p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-primary">Agenda kelas</p>
                            <h2 class="mt-1 text-xl font-semibold">Tasklist hari ini</h2>
                        </div>
                        <CalendarDays class="size-5 text-primary" aria-hidden="true" />
                    </div>
                    <ul v-if="classes.length" class="mt-4 divide-y divide-border">
                        <li v-for="learningClass in classes" :key="learningClass.id" class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ learningClass.name }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ learningClass.studentCount }} murid ·
                                    {{ learningClass.todayPlanCount ? 'Tasklist tersedia hari ini' : 'Belum ada tasklist hari ini' }}
                                </p>
                            </div>
                            <Link
                                :href="route('tutor.daily-plans.index', { class_id: learningClass.id })"
                                class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-primary hover:underline"
                            >
                                {{ learningClass.todayPlanCount ? 'Lihat agenda' : 'Buat tasklist' }} <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </li>
                    </ul>
                    <div v-else class="mt-4 rounded-xl border border-dashed border-border px-4 py-7 text-center">
                        <Users class="mx-auto size-6 text-muted-foreground" aria-hidden="true" />
                        <p class="mt-2 text-sm font-medium">Belum ada kelas yang terdaftar.</p>
                        <p class="mt-1 text-sm text-muted-foreground">Kelas akan tampil di sini setelah dibuat.</p>
                    </div>
                    <Link
                        :href="route('tutor.daily-plans.index')"
                        class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline"
                        >Buka tasklist harian <ArrowRight class="size-4" aria-hidden="true"
                    /></Link>
                </RuangCard>

                <RuangCard class="p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-primary">Progres murid</p>
                            <h2 class="mt-1 text-xl font-semibold">Checklist dan durasi aktif</h2>
                        </div>
                        <Link
                            :href="route('tutor.students.progress')"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"
                            >Semua murid <ArrowRight class="size-4" aria-hidden="true"
                        /></Link>
                    </div>
                    <p v-if="loading" role="status" class="mt-4 text-sm text-muted-foreground">Memuat progres murid…</p>
                    <ul v-else-if="progressPreview.length" class="mt-3 divide-y divide-border">
                        <li v-for="student in progressPreview" :key="student.id" class="py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-semibold">{{ student.name }}</p>
                                <p class="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Clock3 class="size-4 text-primary" aria-hidden="true" />{{ student.activeMinutes }} menit
                                </p>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <div class="mb-1 flex justify-between text-xs">
                                        <span>Sholat</span><span>{{ student.prayersCompleted }}/{{ student.prayersTotal }}</span>
                                    </div>
                                    <RuangProgress
                                        :value="student.prayersTotal ? (student.prayersCompleted / student.prayersTotal) * 100 : 0"
                                        :label="`Progres sholat ${student.name}`"
                                    />
                                </div>
                                <div>
                                    <div class="mb-1 flex justify-between text-xs">
                                        <span>Materi</span><span>{{ student.materialsCompleted }}/{{ student.materialsTotal }}</span>
                                    </div>
                                    <RuangProgress
                                        :value="student.materialsTotal ? (student.materialsCompleted / student.materialsTotal) * 100 : 0"
                                        :label="`Progres materi ${student.name}`"
                                    />
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="mt-4 rounded-xl border border-dashed border-border px-4 py-7 text-center">
                        <p class="text-sm font-medium">Belum ada murid di kelas Anda.</p>
                        <p class="mt-1 text-sm text-muted-foreground">Progres muncul di sini setelah murid ditambahkan ke kelas.</p>
                    </div>
                    <Link
                        :href="route('tutor.students.progress')"
                        class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline"
                        >Buka progres lengkap <ArrowRight class="size-4" aria-hidden="true"
                    /></Link>
                </RuangCard>
            </section>
        </main>
    </AppLayout>
</template>
