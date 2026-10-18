<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, CalendarDays, Check, Clock3, ListTodo, NotebookPen, Sparkles } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

type MaterialItem = {
    id: number;
    type: 'article' | 'video' | 'quiz';
    title: string;
    summary: string | null;
    className: string;
    completed: boolean;
    href: string;
};

interface Props {
    date: string;
    summary: {
        prayersCompleted: number;
        prayersTotal: number;
        materialsCompleted: number;
        materialsTotal: number;
        activeMinutes: number;
        targetMinutes: number;
    };
    agenda: Array<{
        id: number;
        className: string;
        targetMinutes: number;
        materials: MaterialItem[];
    }>;
    newMaterials: MaterialItem[];
    deadlines: Array<{ title: string; date: string; href: string }>;
    priorities: MaterialItem[];
    prayers: string[];
}

const props = defineProps<Props>();
const page = usePage<SharedData>();
const firstName = computed(() => page.props.auth.user?.name?.trim().split(/\s+/)[0] || 'Teman');
const userId = computed(() => page.props.auth.user?.id ?? 'guest');
const note = ref('');
const noteReady = ref(false);
const noteStorageKey = computed(() => `ruang:dashboard-note:${userId.value}:${props.date}`);

const today = computed(() => new Date(`${props.date}T12:00:00`));
const monthLabel = computed(() => new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(today.value));
const dateLabel = computed(() => new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }).format(today.value));
const weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const calendarCells = computed<Array<number | null>>(() => {
    const year = today.value.getFullYear();
    const month = today.value.getMonth();
    const firstOffset = (new Date(year, month, 1).getDay() + 6) % 7;
    const count = new Date(year, month + 1, 0).getDate();
    return [...Array.from({ length: firstOffset }, () => null), ...Array.from({ length: count }, (_, index) => index + 1)];
});
const todayNumber = computed(() => today.value.getDate());
const completionPercent = computed(() => {
    const total = props.summary.prayersTotal + props.summary.materialsTotal;
    const completed = props.summary.prayersCompleted + props.summary.materialsCompleted;
    return total > 0 ? Math.round((completed / total) * 100) : 0;
});
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Beranda', href: route('dashboard') }];

onMounted(() => {
    try {
        note.value = window.localStorage.getItem(noteStorageKey.value) ?? '';
    } catch {
        note.value = '';
    }
    noteReady.value = true;
});

watch([note, noteStorageKey], ([value, key]) => {
    if (!noteReady.value) return;

    try {
        window.localStorage.setItem(key, value);
    } catch {
        // Keep the dashboard usable when this browser blocks local storage.
    }
});

function materialLabel(type: MaterialItem['type']): string {
    return { article: 'Artikel', video: 'Video', quiz: 'Kuis' }[type];
}
</script>

<template>
    <Head title="Beranda" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-5 p-4 sm:gap-6 sm:p-6 lg:p-8">
            <header
                class="dk-hero relative flex flex-wrap items-end justify-between gap-4 overflow-hidden rounded-[24px] border border-primary/20 bg-gradient-to-br from-[#1E1B4B] via-[#2E1065] to-[#4338CA] p-6 text-white shadow-md sm:p-8"
            >
                <img
                    src="/ornaments/graphic-11.png"
                    width="96"
                    height="96"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute -left-4 -top-4 size-24 select-none opacity-20 sm:size-32"
                />
                <img
                    src="/ornaments/graphic-14.png"
                    width="96"
                    height="96"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute bottom-0 right-36 size-20 select-none opacity-20 sm:size-28"
                />

                <div class="relative z-10 min-w-0 flex-1 sm:pr-32">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-[#FFDF18] backdrop-blur-sm"
                    >
                        <Sparkles class="size-3.5" aria-hidden="true" /> Petualangan Hari Ini · {{ dateLabel }}
                    </span>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Halo, {{ firstName }}!</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                        Semangat belajarnya, {{ firstName }}!
                        {{
                            summary.materialsTotal
                                ? `Ada ${summary.materialsTotal} materi seru menunggumu hari ini. Yuk selesaikan langkahmu!`
                                : 'Siap untuk petualangan belajar baru hari ini? Buka checklist ritmemu!'
                        }}
                    </p>
                    <div class="mt-5">
                        <Link
                            :href="route('student.daily-rhythm')"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#FFDF18] px-5 py-2.5 text-sm font-bold text-[#1E1B4B] shadow-md transition-transform hover:-translate-y-0.5 hover:bg-[#FFDF18]/90"
                        >
                            Lanjut belajar <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </div>
                </div>

                <div class="relative z-10 hidden shrink-0 items-center justify-center sm:flex">
                    <img
                        src="/brand/damai-karya/graphic-01.png"
                        width="128"
                        height="128"
                        alt="Maskot Damai Karya"
                        class="size-28 select-none drop-shadow-lg transition-transform hover:scale-105 sm:size-32"
                    />
                </div>
            </header>

            <div class="grid gap-5 lg:grid-cols-12">
                <RuangCard class="hover-lift p-5 transition-all sm:p-6 lg:col-span-7">
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-primary">Agenda</p>
                            <h2 class="mt-1 text-xl font-semibold">Kegiatan hari ini</h2>
                        </div>
                        <RuangBadge variant="muted">{{ agenda.length }} tasklist</RuangBadge>
                    </div>
                    <div v-if="agenda.length" class="space-y-5">
                        <section v-for="plan in agenda" :key="plan.id" class="border-l-2 border-primary/40 pl-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="font-semibold">{{ plan.className }}</h3>
                                <span v-if="plan.targetMinutes" class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Clock3 class="size-3.5" aria-hidden="true" /> Target {{ plan.targetMinutes }} menit
                                </span>
                            </div>
                            <ul v-if="plan.materials.length" class="mt-3 space-y-2">
                                <li
                                    v-for="material in plan.materials"
                                    :key="material.id"
                                    class="hover-lift relative flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border/80 bg-card p-3.5 shadow-sm transition-all"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span
                                            class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-xl"
                                            :class="
                                                material.completed
                                                    ? 'bg-[#E6FBF2] text-[#0D7A4D] dark:bg-[#064E3B]/40 dark:text-[#6EE7B7]'
                                                    : 'bg-secondary text-accent-foreground'
                                            "
                                        >
                                            <Check v-if="material.completed" class="size-4" aria-hidden="true" />
                                            <BookOpen v-else class="size-4" aria-hidden="true" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="break-words text-sm font-medium">{{ material.title }}</p>
                                            <p class="mt-0.5 text-xs text-muted-foreground">
                                                {{ materialLabel(material.type) }} · {{ material.completed ? 'Selesai' : 'Belum selesai' }}
                                            </p>
                                        </div>
                                    </div>
                                    <Link :href="material.href" class="shrink-0 text-xs font-semibold text-primary hover:underline">
                                        {{ material.completed ? 'Buka lagi' : 'Lanjutkan' }}
                                    </Link>
                                    <img
                                        v-if="material.completed"
                                        src="/ornaments/graphic-10.png"
                                        width="24"
                                        height="24"
                                        alt="Selesai"
                                        aria-hidden="true"
                                        class="pointer-events-none absolute -right-2 -top-2 size-6 select-none drop-shadow-sm"
                                    />
                                    <img
                                        v-else
                                        src="/ornaments/graphic-09.png"
                                        width="24"
                                        height="24"
                                        alt="Siap dikerjakan"
                                        aria-hidden="true"
                                        class="pointer-events-none absolute -right-2 -top-2 size-6 select-none drop-shadow-sm"
                                    />
                                </li>
                            </ul>
                            <p v-else class="mt-3 text-sm text-muted-foreground">Belum ada materi terbit pada tasklist ini.</p>
                        </section>
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-border px-4 py-8 text-center">
                        <ListTodo class="mx-auto size-7 text-muted-foreground" aria-hidden="true" />
                        <p class="mt-3 text-sm font-medium">Belum ada tasklist untuk hari ini.</p>
                        <p class="mt-1 text-sm text-muted-foreground">Materi yang ditugaskan tutor akan muncul di bagian ini.</p>
                    </div>
                </RuangCard>

                <RuangCard class="hover-lift p-5 transition-all sm:p-6 lg:col-span-5">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-primary">Tenggat</p>
                            <h2 class="mt-1 text-xl font-semibold">Yang perlu dikumpulkan</h2>
                        </div>
                        <CalendarDays class="size-5 text-muted-foreground" aria-hidden="true" />
                    </div>
                    <ul v-if="deadlines.length" class="divide-y divide-border">
                        <li v-for="deadline in deadlines" :key="`${deadline.title}-${deadline.date}`" class="py-4">
                            <Link :href="deadline.href" class="font-medium text-primary hover:underline">{{ deadline.title }}</Link>
                            <p class="mt-1 text-xs text-muted-foreground">{{ deadline.date }}</p>
                        </li>
                    </ul>
                    <div v-else class="mt-5 rounded-xl bg-muted/50 px-4 py-5 text-sm text-muted-foreground">
                        Belum ada tenggat pengumpulan yang tercatat.
                    </div>
                    <div class="mt-6 border-t border-border pt-5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-semibold">{{ monthLabel }}</h3>
                            <span class="text-xs text-muted-foreground">Hari ini ditandai</span>
                        </div>
                        <div class="mt-3 grid grid-cols-7 gap-1 text-center text-xs">
                            <span v-for="day in weekdayLabels" :key="day" class="py-1 font-medium text-muted-foreground">{{ day }}</span>
                            <span
                                v-for="(day, index) in calendarCells"
                                :key="`${index}-${day ?? 'empty'}`"
                                class="grid aspect-square place-items-center rounded-full"
                                :class="day === todayNumber ? 'bg-primary font-semibold text-primary-foreground' : 'text-foreground'"
                            >
                                {{ day ?? '' }}
                            </span>
                        </div>
                    </div>
                </RuangCard>

                <RuangCard class="hover-lift p-5 transition-all sm:p-6 lg:col-span-7">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-primary">Ritme hari ini</p>
                            <h2 class="mt-1 text-xl font-semibold">Langkah kecil, progres nyata</h2>
                        </div>
                        <Link :href="route('student.daily-rhythm')" class="text-sm font-semibold text-primary hover:underline">Buka checklist</Link>
                    </div>
                    <div class="mt-5 grid gap-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                        <div>
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="text-3xl font-bold">
                                    {{ summary.prayersCompleted + summary.materialsCompleted
                                    }}<span class="text-base font-medium text-muted-foreground">
                                        / {{ summary.prayersTotal + summary.materialsTotal }} langkah</span
                                    >
                                </p>
                                <RuangBadge
                                    :variant="completionPercent === 100 && summary.prayersTotal + summary.materialsTotal > 0 ? 'success' : 'muted'"
                                    >{{ completionPercent }}%</RuangBadge
                                >
                            </div>
                            <div
                                class="mt-4 h-3.5 w-full overflow-hidden rounded-full bg-muted/80 p-0.5 shadow-inner"
                                role="progressbar"
                                :aria-valuenow="completionPercent"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-label="Penyelesaian ritme hari ini"
                            >
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-[#24EA9B] via-[#06B6D4] to-[#7C3AED] transition-all duration-300"
                                    :style="{ width: `${completionPercent}%` }"
                                />
                            </div>
                            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                                <span
                                    ><strong>{{ summary.prayersCompleted }}/{{ summary.prayersTotal }}</strong> sholat</span
                                >
                                <span
                                    ><strong>{{ summary.materialsCompleted }}/{{ summary.materialsTotal }}</strong> materi ditugaskan</span
                                >
                                <span class="inline-flex items-center gap-1.5"
                                    ><Clock3 class="size-4 text-primary" aria-hidden="true" /><strong>{{ summary.activeMinutes }}</strong> menit
                                    aktif</span
                                >
                            </div>
                        </div>
                        <div class="grid size-20 place-items-center rounded-2xl bg-secondary text-accent-foreground">
                            <Sparkles class="size-8" aria-hidden="true" />
                        </div>
                    </div>
                </RuangCard>

                <RuangCard class="hover-lift p-5 transition-all sm:p-6 lg:col-span-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-primary">Waktu belajar</p>
                            <h2 class="mt-1 text-xl font-semibold">Durasi aktif hari ini</h2>
                        </div>
                        <Clock3 class="size-5 text-primary" aria-hidden="true" />
                    </div>
                    <p class="mt-5 text-4xl font-bold">
                        {{ summary.activeMinutes }} <span class="text-base font-medium text-muted-foreground">menit</span>
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{
                            summary.targetMinutes
                                ? `Target dari tasklist hari ini ${summary.targetMinutes} menit.`
                                : 'Belum ada target durasi dari tasklist hari ini.'
                        }}
                    </p>
                </RuangCard>

                <RuangCard class="hover-lift p-5 transition-all sm:p-6 lg:col-span-7">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-primary">Materi untuk dilanjutkan</p>
                            <h2 class="mt-1 text-xl font-semibold">Mulai dari sini</h2>
                        </div>
                        <BookOpen class="size-5 text-primary" aria-hidden="true" />
                    </div>
                    <ul v-if="newMaterials.length" class="mt-4 divide-y divide-border">
                        <li v-for="material in newMaterials" :key="material.id" class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="break-words font-medium">{{ material.title }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ materialLabel(material.type) }} · {{ material.className }}</p>
                            </div>
                            <Link
                                :href="material.href"
                                class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary hover:underline"
                                >Buka <ArrowRight class="size-4" aria-hidden="true"
                            /></Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 rounded-xl bg-muted/50 px-4 py-5 text-sm text-muted-foreground">
                        {{
                            summary.materialsTotal
                                ? 'Semua materi tasklist hari ini sudah selesai.'
                                : 'Belum ada materi baru yang ditugaskan hari ini.'
                        }}
                    </p>
                </RuangCard>

                <RuangCard class="hover-lift p-5 transition-all sm:p-6 lg:col-span-5">
                    <div class="flex items-center gap-2">
                        <ListTodo class="size-5 text-primary" aria-hidden="true" />
                        <div>
                            <p class="text-sm font-semibold text-primary">Prioritas</p>
                            <h2 class="mt-1 text-xl font-semibold">Langkah berikutnya</h2>
                        </div>
                    </div>
                    <ul v-if="priorities.length" class="mt-4 space-y-2">
                        <li v-for="item in priorities" :key="item.id" class="rounded-xl bg-muted/50 px-3 py-3">
                            <Link :href="item.href" class="font-medium text-primary hover:underline">{{ item.title }}</Link>
                            <p class="mt-1 text-xs text-muted-foreground">{{ materialLabel(item.type) }} · {{ item.className }}</p>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-muted-foreground">Belum ada langkah tertunda dari tasklist hari ini.</p>
                    <div class="mt-5 border-t border-border pt-5">
                        <label for="daily-note" class="inline-flex items-center gap-2 font-semibold"
                            ><NotebookPen class="size-4 text-primary" aria-hidden="true" /> Catatan pribadi</label
                        >
                        <textarea
                            id="daily-note"
                            v-model="note"
                            rows="3"
                            maxlength="500"
                            class="mt-3 w-full resize-y rounded-lg border-input bg-background text-sm"
                            placeholder="Tulis pengingat untuk dirimu sendiri..."
                        ></textarea>
                        <p class="mt-1 text-xs text-muted-foreground">Catatan ini tersimpan di browser pada perangkat ini.</p>
                    </div>
                </RuangCard>
            </div>
        </main>
    </AppLayout>
</template>
