<script setup lang="ts">
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { CalendarDays, Check, Plus, Save, Users } from 'lucide-vue-next';
import { computed } from 'vue';

type ClassOption = { id: number; name: string; students_count: number };
type MaterialOption = { id: number; type: 'article' | 'video' | 'quiz'; title: string };
type Slot = { id: number; type: 'article' | 'video' | 'quiz'; material_id: number; title: string; position: number };

const props = defineProps<{
    classes: ClassOption[];
    students: Array<{ id: number; name: string; email: string }>;
    selectedClass: { id: number; name: string; studentIds: number[] } | null;
    plan: { id: number; date: string; target_minutes: number; locked: boolean; materials: Slot[] } | null;
    date: string;
    materials: MaterialOption[];
    prayers: string[];
}>();
const page = usePage<SharedData & { flash: { status?: string } }>();
const classForm = useForm({ name: '' });
const memberForm = useForm({ student_ids: props.selectedClass?.studentIds ?? ([] as number[]) });
const materialFor = (type: Slot['type']) => props.plan?.materials.find((slot) => slot.type === type)?.material_id ?? null;
const planForm = useForm({
    class_id: props.selectedClass?.id ?? null,
    date: props.date,
    target_minutes: props.plan?.target_minutes ?? 45,
    materials: [
        { type: 'article' as const, material_id: materialFor('article') },
        { type: 'video' as const, material_id: materialFor('video') },
        { type: 'quiz' as const, material_id: materialFor('quiz') },
    ],
});
const groupedMaterials = computed(() => ({
    article: props.materials.filter((material) => material.type === 'article'),
    video: props.materials.filter((material) => material.type === 'video'),
    quiz: props.materials.filter((material) => material.type === 'quiz'),
}));
const typeLabels = { article: 'Artikel', video: 'Video', quiz: 'Kuis' };
const prayerLabels = { subuh: 'Subuh', zuhur: 'Zuhur', asar: 'Asar', magrib: 'Magrib', isya: 'Isya' };
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Beranda', href: route('dashboard') },
    { title: 'Tasklist harian', href: route('tutor.daily-plans.index') },
];

function createClass(): void {
    classForm.post(route('tutor.classes.store'), { preserveScroll: true, onSuccess: () => classForm.reset() });
}

function saveMembers(): void {
    if (!props.selectedClass) return;
    memberForm.put(route('tutor.classes.students.update', props.selectedClass.id), { preserveScroll: true });
}

function changeSelection(field: 'class_id' | 'date', value: string): void {
    const params = {
        class_id: field === 'class_id' ? value || undefined : props.selectedClass?.id,
        date: field === 'date' ? value : props.date,
    };
    router.get(route('tutor.daily-plans.index'), params, { preserveState: false, preserveScroll: true });
}

function savePlan(): void {
    planForm.post(route('tutor.daily-plans.store'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Tasklist harian" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Ritme murid</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Tasklist harian</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                        Atur kelas, murid, materi, dan target belajar. Lima waktu sholat selalu menjadi bagian tetap dari checklist.
                    </p>
                </div>
                <Link :href="route('tutor.materials.index')" class="rounded-lg border border-border px-4 py-2.5 text-sm font-medium hover:bg-muted">
                    Buka pustaka materi
                </Link>
            </header>

            <div
                v-if="page.props.flash?.status"
                role="status"
                aria-live="polite"
                class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success"
            >
                {{ page.props.flash.status }}
            </div>

            <RuangCard class="grid gap-5 p-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                <div>
                    <p class="text-sm font-semibold">Buat kelas</p>
                    <p class="mt-1 text-sm text-muted-foreground">Kelas milik Anda menjadi ruang penugasan bagi murid.</p>
                </div>
                <form class="flex flex-wrap gap-2" @submit.prevent="createClass">
                    <input
                        v-model="classForm.name"
                        aria-label="Nama kelas baru"
                        maxlength="120"
                        required
                        placeholder="Contoh: Matematika A"
                        class="min-w-56 flex-1 rounded-lg border-input bg-background text-sm"
                    />
                    <button
                        :disabled="classForm.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground disabled:opacity-50"
                    >
                        <Plus class="size-4" aria-hidden="true" /> Buat kelas
                    </button>
                    <p v-if="classForm.errors.name" class="basis-full text-sm text-destructive">{{ classForm.errors.name }}</p>
                </form>
            </RuangCard>

            <div v-if="!classes.length" class="rounded-xl border border-dashed border-border bg-card px-5 py-12 text-center">
                <Users class="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
                <h2 class="mt-3 font-semibold">Belum ada kelas</h2>
                <p class="mt-1 text-sm text-muted-foreground">Buat kelas untuk mengatur murid dan tasklist harian.</p>
            </div>

            <template v-else>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="space-y-2 text-sm font-medium">
                        <span>Kelas</span>
                        <select
                            :value="selectedClass?.id ?? ''"
                            class="w-full rounded-lg border-input bg-background"
                            @change="changeSelection('class_id', ($event.target as HTMLSelectElement).value)"
                        >
                            <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }} · {{ item.students_count }} murid</option>
                        </select>
                    </label>
                    <label class="space-y-2 text-sm font-medium">
                        <span>Tanggal tasklist</span>
                        <span class="relative block">
                            <CalendarDays
                                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <input
                                :value="date"
                                type="date"
                                class="w-full rounded-lg border-input bg-background pl-10"
                                @change="changeSelection('date', ($event.target as HTMLInputElement).value)"
                            />
                        </span>
                    </label>
                </div>

                <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(18rem,0.9fr)]">
                    <RuangCard class="p-5 sm:p-6">
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold">Atur ritme murid</h2>
                                <p class="mt-1 text-sm text-muted-foreground">Pilih satu materi terbit untuk setiap jenis.</p>
                            </div>
                            <RuangBadge variant="primary">{{ selectedClass?.name }}</RuangBadge>
                        </div>
                        <div v-if="plan?.locked" role="status" class="border-warning/30 bg-warning-soft mb-5 rounded-lg border px-4 py-3 text-sm">
                            Tasklist ini sudah memiliki penyelesaian. Isinya dikunci agar riwayat tanggal tersebut tetap utuh.
                        </div>
                        <form class="space-y-5" @submit.prevent="savePlan">
                            <div v-for="slot in planForm.materials" :key="slot.type">
                                <label :for="`slot-${slot.type}`" class="text-sm font-semibold">{{ typeLabels[slot.type] }} hari ini</label>
                                <select
                                    :id="`slot-${slot.type}`"
                                    v-model="slot.material_id"
                                    required
                                    :disabled="plan?.locked"
                                    class="mt-2 w-full rounded-lg border-input bg-background disabled:opacity-60"
                                >
                                    <option :value="null" disabled>Pilih {{ typeLabels[slot.type].toLowerCase() }}</option>
                                    <option v-for="item in groupedMaterials[slot.type]" :key="item.id" :value="item.id">{{ item.title }}</option>
                                </select>
                                <p v-if="!groupedMaterials[slot.type].length" class="mt-1 text-xs text-muted-foreground">
                                    Belum ada {{ typeLabels[slot.type].toLowerCase() }} terbit di pustaka.
                                </p>
                            </div>
                            <div>
                                <label for="target-minutes" class="text-sm font-semibold">Target belajar aktif</label>
                                <div class="mt-2 flex max-w-xs items-center gap-2">
                                    <input
                                        id="target-minutes"
                                        v-model.number="planForm.target_minutes"
                                        type="number"
                                        min="1"
                                        max="600"
                                        required
                                        :disabled="plan?.locked"
                                        class="w-full rounded-lg border-input bg-background disabled:opacity-60"
                                    />
                                    <span class="text-sm text-muted-foreground">menit</span>
                                </div>
                            </div>
                            <div
                                v-if="planForm.errors.plan || planForm.errors.materials"
                                role="alert"
                                class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive"
                            >
                                {{ planForm.errors.plan || planForm.errors.materials }}
                            </div>
                            <button
                                :disabled="planForm.processing || !selectedClass || plan?.locked"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground disabled:opacity-50"
                            >
                                <Save class="size-4" aria-hidden="true" /> {{ planForm.processing ? 'Menyimpan…' : 'Simpan tasklist' }}
                            </button>
                        </form>
                    </RuangCard>

                    <div class="space-y-5">
                        <RuangCard class="p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="font-semibold">Murid di kelas</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">Pilih akun aktif yang menerima tasklist.</p>
                                </div>
                                <Users class="size-5 text-muted-foreground" aria-hidden="true" />
                            </div>
                            <form class="mt-4 space-y-4" @submit.prevent="saveMembers">
                                <div v-if="!students.length" class="rounded-lg bg-muted/60 p-3 text-sm text-muted-foreground">
                                    Belum ada akun murid aktif.
                                </div>
                                <div v-else class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-border p-3">
                                    <label
                                        v-for="student in students"
                                        :key="student.id"
                                        class="flex cursor-pointer items-start gap-3 rounded-md p-2 hover:bg-muted/50"
                                    >
                                        <input
                                            v-model="memberForm.student_ids"
                                            type="checkbox"
                                            :value="student.id"
                                            class="mt-1 rounded border-input text-primary focus:ring-ring"
                                        />
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium">{{ student.name }}</span>
                                            <span class="block truncate text-xs text-muted-foreground">{{ student.email }}</span>
                                        </span>
                                    </label>
                                </div>
                                <p v-if="memberForm.errors.student_ids" role="alert" class="text-sm text-destructive">
                                    {{ memberForm.errors.student_ids }}
                                </p>
                                <button
                                    :disabled="memberForm.processing"
                                    class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-semibold hover:bg-muted disabled:opacity-50"
                                >
                                    <Check class="size-4" aria-hidden="true" /> Simpan murid
                                </button>
                            </form>
                        </RuangCard>

                        <RuangCard class="p-5">
                            <h2 class="font-semibold">Lima sholat tetap</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Slot ini selalu tersedia dan tidak dapat dihapus.</p>
                            <ul class="mt-4 space-y-2">
                                <li
                                    v-for="prayer in prayers"
                                    :key="prayer"
                                    class="flex items-center gap-3 rounded-lg bg-muted/50 px-3 py-2.5 text-sm"
                                >
                                    <span class="grid size-7 place-items-center rounded-full bg-primary/10 text-primary"
                                        ><Check class="size-4" aria-hidden="true"
                                    /></span>
                                    {{ prayerLabels[prayer as keyof typeof prayerLabels] }}
                                </li>
                            </ul>
                        </RuangCard>

                        <RuangCard class="p-5">
                            <h2 class="font-semibold">Pratinjau materi</h2>
                            <p v-if="!planForm.materials.some((slot) => slot.material_id)" class="mt-2 text-sm text-muted-foreground">
                                Pilih artikel, video, dan kuis untuk melihat urutan belajar.
                            </p>
                            <ol v-else class="mt-3 space-y-2">
                                <li v-for="slot in planForm.materials" :key="slot.type" class="flex items-center gap-3 text-sm">
                                    <span class="grid size-6 place-items-center rounded-full bg-secondary text-xs font-semibold">{{
                                        slot.type === 'article' ? '1' : slot.type === 'video' ? '2' : '3'
                                    }}</span>
                                    <span>{{
                                        groupedMaterials[slot.type].find((item) => item.id === slot.material_id)?.title || 'Belum dipilih'
                                    }}</span>
                                </li>
                            </ol>
                            <Link :href="route('student.daily-rhythm')" class="mt-4 inline-flex text-sm font-medium text-primary hover:underline"
                                >Lihat Ritme hari ini →</Link
                            >
                        </RuangCard>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
