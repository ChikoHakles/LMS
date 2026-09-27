<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { reactive, watch } from 'vue';

type MaterialType = 'article' | 'video' | 'quiz';
type MaterialStatus = 'draft' | 'published';

const props = defineProps<{
    filters: { q?: string; type?: string | null; status?: string | null };
    showStatus?: boolean;
    routeName?: string;
}>();

const form = reactive({
    q: props.filters.q ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
});

let debounce: ReturnType<typeof setTimeout> | undefined;
watch(form, () => {
    if (debounce) clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            route(props.routeName ?? 'tutor.materials.index'),
            {
                q: form.q || undefined,
                type: form.type || undefined,
                status: props.showStatus && form.status ? form.status : undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 250);
});

const typeLabel: Record<MaterialType, string> = { article: 'Artikel', video: 'Video', quiz: 'Kuis' };
const statusLabel: Record<MaterialStatus, string> = { draft: 'Draf', published: 'Terbit' };
</script>

<template>
    <section
        class="grid gap-3 rounded-xl border border-border bg-card p-4 sm:grid-cols-[minmax(12rem,1fr)_12rem_12rem]"
        aria-label="Filter pustaka materi"
    >
        <label class="flex items-center gap-2 rounded-lg border border-input bg-background px-3 focus-within:ring-2 focus-within:ring-ring">
            <Search class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="sr-only">Cari materi</span>
            <input
                v-model="form.q"
                type="search"
                maxlength="255"
                placeholder="Cari judul atau ringkasan"
                class="w-full border-0 bg-transparent py-2 text-sm outline-none ring-0 placeholder:text-muted-foreground focus:ring-0"
            />
        </label>
        <label class="sr-only" for="material-type">Jenis materi</label>
        <select id="material-type" v-model="form.type" class="rounded-lg border-input bg-background text-sm focus:ring-ring">
            <option value="">Semua jenis</option>
            <option v-for="(label, type) in typeLabel" :key="type" :value="type">{{ label }}</option>
        </select>
        <template v-if="showStatus">
            <label class="sr-only" for="material-status">Status materi</label>
            <select id="material-status" v-model="form.status" class="rounded-lg border-input bg-background text-sm focus:ring-ring">
                <option value="">Semua status</option>
                <option v-for="(label, status) in statusLabel" :key="status" :value="status">{{ label }}</option>
            </select>
        </template>
    </section>
</template>
