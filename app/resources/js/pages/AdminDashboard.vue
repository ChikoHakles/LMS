<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { normalizeRuangRole, roleLabels } from '@/navigation/role-navigation';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { BookOpenCheck, Compass, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const role = computed(() => normalizeRuangRole((page.props.auth.user as typeof page.props.auth.user & { role?: string } | null)?.role));
const roleLabel = computed(() => roleLabels[role.value]);
const firstName = computed(() => page.props.auth.user?.name?.trim().split(/\s+/)[0] || 'Teman');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Beranda', href: route('dashboard') },
];
</script>

<template>
    <Head title="Beranda" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="space-y-3">
                <RuangBadge variant="primary">{{ roleLabel }}</RuangBadge>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Ruang belajar</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">Selamat datang, {{ firstName }}.</h1>
                    <p class="mt-2 max-w-2xl text-base text-muted-foreground">Satu tempat untuk kegiatan belajar, mengajar, dan pengelolaan kelas.</p>
                </div>
            </header>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(16rem,0.6fr)]">
                <RuangCard class="flex min-h-64 flex-col justify-between p-6 sm:p-8">
                    <div>
                        <div class="mb-5 flex size-12 items-center justify-center rounded-xl bg-secondary text-accent-foreground">
                            <BookOpenCheck class="size-6" aria-hidden="true" />
                        </div>
                        <h2 class="text-xl font-semibold text-card-foreground">Semua langkah ada di satu ruang.</h2>
                        <p class="mt-2 max-w-xl leading-7 text-muted-foreground">Pilih bagian dari menu untuk membuka aktivitas sesuai peran akun Anda. Navigasi dapat dibuka dan ditutup dari tombol di bagian atas.</p>
                    </div>
                </RuangCard>

                <RuangCard class="flex min-h-64 flex-col justify-between bg-secondary/50 p-6 sm:p-8">
                    <div>
                        <div class="mb-5 flex size-12 items-center justify-center rounded-xl bg-card text-primary shadow-sm">
                            <Compass class="size-6" aria-hidden="true" />
                        </div>
                        <h2 class="text-lg font-semibold text-card-foreground">Mulai dari menu utama</h2>
                        <p class="mt-2 leading-7 text-muted-foreground">Materi dan aktivitas akan tampil sesuai akses yang diberikan untuk akun Anda.</p>
                    </div>
                    <div class="mt-6 flex items-center gap-2 text-sm font-medium text-accent-foreground">
                        <Sparkles class="size-4" aria-hidden="true" />
                        <span>Ruang untuk belajar bertumbuh</span>
                    </div>
                </RuangCard>
            </div>
        </div>
    </AppLayout>
</template>
