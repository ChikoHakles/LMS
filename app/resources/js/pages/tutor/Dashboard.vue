<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Props {
    date: string;
    summary: {
        classCount: number;
        studentCount: number;
        todayPlanCount: number;
        submissionCount: number;
        essayQueueCount: number;
    };
    classes: Array<{ id: number; name: string; studentCount: number; todayPlanCount: number }>;
    progressPreview: Array<{ id: number; name: string; prayersCompleted: number; materialsCompleted: number; activeMinutes: number }>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Beranda', href: route('dashboard') }];
</script>

<template>
    <Head title="Beranda tutor" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">{{ date }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground">Beranda tutor</h1>
                <p class="mt-2 text-muted-foreground">Ringkasan kelas dan aktivitas hari ini.</p>
            </header>
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan kelas">
                <RuangCard v-for="item in [
                    ['Kelas', summary.classCount],
                    ['Murid', summary.studentCount],
                    ['Tasklist hari ini', summary.todayPlanCount],
                    ['Pengumpulan', summary.submissionCount],
                    ['Esai menunggu', summary.essayQueueCount],
                ]" :key="item[0]" class="p-5">
                    <p class="text-sm text-muted-foreground">{{ item[0] }}</p>
                    <p class="mt-2 text-3xl font-bold text-card-foreground">{{ item[1] }}</p>
                </RuangCard>
            </section>
            <section class="grid gap-4 lg:grid-cols-2">
                <RuangCard class="p-5">
                    <h2 class="text-lg font-semibold">Kelas saya</h2>
                    <p v-if="classes.length === 0" class="mt-3 text-sm text-muted-foreground">Belum ada kelas yang terdaftar.</p>
                    <ul v-else class="mt-3 divide-y divide-border">
                        <li v-for="learningClass in classes" :key="learningClass.id" class="flex justify-between gap-4 py-3 text-sm">
                            <span class="font-medium">{{ learningClass.name }}</span>
                            <span class="text-muted-foreground">{{ learningClass.studentCount }} murid</span>
                        </li>
                    </ul>
                </RuangCard>
                <RuangCard class="p-5">
                    <h2 class="text-lg font-semibold">Progres murid hari ini</h2>
                    <p v-if="progressPreview.length === 0" class="mt-3 text-sm text-muted-foreground">Belum ada murid di kelas Anda.</p>
                    <ul v-else class="mt-3 divide-y divide-border">
                        <li v-for="student in progressPreview" :key="student.id" class="flex flex-wrap justify-between gap-3 py-3 text-sm">
                            <span class="font-medium">{{ student.name }}</span>
                            <span class="text-muted-foreground">Sholat {{ student.prayersCompleted }}/5 · Materi {{ student.materialsCompleted }} · {{ student.activeMinutes }} menit</span>
                        </li>
                    </ul>
                    <a :href="route('tutor.students.progress')" class="mt-4 inline-flex text-sm font-semibold text-primary hover:underline">Lihat semua progres</a>
                </RuangCard>
            </section>
        </main>
    </AppLayout>
</template>
