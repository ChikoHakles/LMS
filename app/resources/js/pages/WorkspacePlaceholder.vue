<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Construction } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
}>();
const page = usePage<SharedData>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: props.title, href: page.url },
];
</script>

<template>
    <Head :title="props.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center p-4 sm:p-8">
            <RuangCard class="mx-auto w-full max-w-2xl p-6 text-center sm:p-10">
                <div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-2xl bg-secondary text-accent-foreground">
                    <Construction class="size-7" aria-hidden="true" />
                </div>
                <RuangBadge variant="muted">Segera hadir</RuangBadge>
                <h1 class="mt-4 text-2xl font-bold tracking-tight text-card-foreground">{{ props.title }}</h1>
                <p class="mx-auto mt-3 max-w-lg leading-7 text-muted-foreground">Bagian ini belum tersedia. Kembali ke beranda untuk melanjutkan dari menu yang sudah aktif.</p>
                <Link :href="route('dashboard')" class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Kembali ke beranda
                </Link>
            </RuangCard>
        </div>
    </AppLayout>
</template>
