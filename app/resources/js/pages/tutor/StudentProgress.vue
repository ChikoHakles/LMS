<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Beranda', href: route('dashboard') },
    { title: 'Progres murid', href: route('tutor.students.progress') },
];
</script>

<template>
    <Head title="Progres murid" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">{{ date }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground">Progres murid</h1>
                <p class="mt-2 text-muted-foreground">Ringkasan hanya untuk kelas yang Anda ajar.</p>
            </header>
            <RuangCard class="overflow-hidden p-0">
                <p v-if="students.length === 0" class="p-6 text-sm text-muted-foreground">Belum ada murid di kelas Anda.</p>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 font-medium">Murid</th>
                                <th class="px-5 py-3 font-medium">Kelas</th>
                                <th class="px-5 py-3 font-medium">Sholat</th>
                                <th class="px-5 py-3 font-medium">Materi</th>
                                <th class="px-5 py-3 font-medium">Waktu aktif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="student in students" :key="student.id">
                                <td class="px-5 py-4 font-medium">{{ student.name }}</td>
                                <td class="px-5 py-4 text-muted-foreground">{{ student.classes.join(', ') }}</td>
                                <td class="px-5 py-4">{{ student.prayersCompleted }}/{{ student.prayersTotal }}</td>
                                <td class="px-5 py-4">{{ student.materialsCompleted }}/{{ student.materialsTotal }}</td>
                                <td class="px-5 py-4">{{ student.activeMinutes }} menit</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </RuangCard>
        </main>
    </AppLayout>
</template>
