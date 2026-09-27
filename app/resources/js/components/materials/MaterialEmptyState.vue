<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpenText } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        createHref?: string | null;
    }>(),
    {
        title: 'Belum ada materi',
        description: 'Materi yang sesuai dengan pilihan ini akan muncul di sini.',
        createHref: null,
    },
);
</script>

<template>
    <div class="rounded-2xl border border-dashed border-border bg-card px-6 py-12 text-center">
        <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-secondary text-accent-foreground">
            <BookOpenText class="size-6" aria-hidden="true" />
        </div>
        <h2 class="mt-4 text-lg font-semibold text-card-foreground">{{ title }}</h2>
        <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-muted-foreground">{{ description }}</p>
        <Link
            v-if="createHref"
            :href="createHref"
            class="mt-5 inline-flex rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
        >
            Buat artikel
        </Link>
    </div>
</template>
