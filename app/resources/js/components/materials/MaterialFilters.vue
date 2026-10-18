<script setup lang="ts">
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';

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

const typeOptions = computed(() => [
    { value: '', label: 'Semua jenis' },
    ...Object.entries(typeLabel).map(([value, label]) => ({ value, label })),
]);

const statusOptions = computed(() => [
    { value: '', label: 'Semua status' },
    ...Object.entries(statusLabel).map(([value, label]) => ({ value, label })),
]);
</script>

<template>
    <section
        class="grid gap-3 rounded-[20px] border border-border bg-card p-4 sm:grid-cols-[minmax(12rem,1fr)_12rem_12rem]"
        aria-label="Filter pustaka materi"
    >
        <label class="flex items-center gap-2 rounded-xl border border-input bg-background px-3 focus-within:ring-2 focus-within:ring-ring">
            <Search class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="sr-only">Cari materi</span>
            <input
                v-model="form.q"
                type="search"
                maxlength="255"
                placeholder="Cari judul atau ringkasan"
                class="w-full border-0 bg-transparent py-2 text-base outline-none ring-0 placeholder:text-muted-foreground focus:ring-0"
            />
        </label>
        <div>
            <label class="sr-only" for="material-type">Jenis materi</label>
            <SearchableSelect
                id="material-type"
                v-model="form.type"
                :options="typeOptions"
                placeholder="Semua jenis"
                aria-label="Jenis materi"
            />
        </div>
        <template v-if="showStatus">
            <div>
                <label class="sr-only" for="material-status">Status materi</label>
                <SearchableSelect
                    id="material-status"
                    v-model="form.status"
                    :options="statusOptions"
                    placeholder="Semua status"
                    aria-label="Status materi"
                />
            </div>
        </template>
    </section>
</template>
