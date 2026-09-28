<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import RuangProgress from '@/components/ruang/RuangProgress.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ListChecks } from 'lucide-vue-next';
import { onBeforeUnmount, ref } from 'vue';

interface Props {
    date: string;
    classes: Array<{ id: number; name: string }>;
    students: Array<{
        id: number;
        name: string;
        classes: string[];
        prayersCompleted: number;
        prayersTotal: number;
        materialsCompleted: number;
        materialsTotal: number;
        activeMinutes: number;
    }>;
}

defineProps<Props>();

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Beranda', href: route('dashboard') },
    { title: 'Progres murid', href: route('tutor.students.progress') },
];
</script>

<template>
    <Head title="Progres murid" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8" :aria-busy="loading">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Progres harian · {{ date }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground">Progres murid</h1>
                    <p class="mt-2 text-muted-foreground">Checklist sholat, materi tasklist, dan durasi aktif di kelas yang Anda ajar.</p>
                </div>
                <Link
                    :href="route('tutor.daily-plans.index')"
                    class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-semibold hover:bg-muted"
                >
                    <ListChecks class="size-4" aria-hidden="true" /> Buka tasklist
                </Link>
            </header>

            <section v-if="classes.length" class="flex flex-wrap gap-2" aria-label="Kelas yang diajar">
                <Link
                    v-for="learningClass in classes"
                    :key="learningClass.id"
                    :href="route('tutor.daily-plans.index', { class_id: learningClass.id })"
                    class="rounded-full border border-border bg-card px-3 py-1.5 text-xs font-medium hover:border-primary/50 hover:text-primary"
                >
                    {{ learningClass.name }}
                </Link>
            </section>

            <p
                v-if="loading"
                role="status"
                aria-live="polite"
                class="rounded-xl border border-border bg-card px-5 py-4 text-sm text-muted-foreground"
            >
                Memuat progres murid…
            </p>
            <RuangCard v-else-if="students.length === 0" class="p-8 text-center">
                <ArrowLeft class="mx-auto size-7 text-muted-foreground" aria-hidden="true" />
                <p class="mt-3 text-base font-semibold">Belum ada murid di kelas Anda.</p>
                <p class="mt-1 text-sm text-muted-foreground">Saat murid ditambahkan ke kelas, progres hariannya akan muncul di sini.</p>
                <Link
                    :href="route('tutor.daily-plans.index')"
                    class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline"
                >
                    Kelola kelas <ArrowLeft class="size-4 rotate-180" aria-hidden="true" />
                </Link>
            </RuangCard>
            <RuangCard v-else class="overflow-hidden p-0">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
                    <div>
                        <h2 class="font-semibold">Checklist dan waktu aktif</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Data untuk hari ini · {{ students.length }} murid</p>
                    </div>
                    <span class="text-xs text-muted-foreground">5 sholat · materi yang ditugaskan · menit server</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 font-medium">Murid</th>
                                <th class="px-5 py-3 font-medium">Kelas</th>
                                <th class="min-w-36 px-5 py-3 font-medium">Sholat</th>
                                <th class="min-w-36 px-5 py-3 font-medium">Materi</th>
                                <th class="px-5 py-3 font-medium">Waktu aktif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="student in students" :key="student.id">
                                <td class="px-5 py-4 font-medium">{{ student.name }}</td>
                                <td class="px-5 py-4 text-muted-foreground">{{ student.classes.join(', ') }}</td>
                                <td class="px-5 py-4">
                                    <p class="mb-1.5 text-xs">{{ student.prayersCompleted }}/{{ student.prayersTotal }}</p>
                                    <RuangProgress
                                        :value="student.prayersTotal ? (student.prayersCompleted / student.prayersTotal) * 100 : 0"
                                        :label="`Progres sholat ${student.name}`"
                                    />
                                </td>
                                <td class="px-5 py-4">
                                    <p class="mb-1.5 text-xs">{{ student.materialsCompleted }}/{{ student.materialsTotal }}</p>
                                    <RuangProgress
                                        :value="student.materialsTotal ? (student.materialsCompleted / student.materialsTotal) * 100 : 0"
                                        :label="`Progres materi ${student.name}`"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold">{{ student.activeMinutes }} menit</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </RuangCard>
        </main>
    </AppLayout>
</template>
