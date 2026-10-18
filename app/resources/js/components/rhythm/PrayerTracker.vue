<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import { Circle } from 'lucide-vue-next';

type Prayer = { id: string; label: string; completed: boolean };
defineProps<{ prayers: Prayer[] }>();
const emit = defineEmits<{ toggle: [change: { prayer: string; completed: boolean }] }>();

function update(prayer: Prayer, event: Event): void {
    emit('toggle', { prayer: prayer.id, completed: (event.target as HTMLInputElement).checked });
}
</script>

<template>
    <RuangCard class="p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">Lima waktu sholat</h2>
                <p class="mt-1 text-sm text-muted-foreground">Checklist tetap ini tersimpan sesuai tanggal.</p>
            </div>
            <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">5 waktu</span>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <label
                v-for="prayer in prayers"
                :key="prayer.id"
                class="hover-lift flex cursor-pointer items-center gap-3 rounded-2xl border p-3.5 transition-all"
                :class="
                    prayer.completed
                        ? 'border-[#24EA9B] bg-[#E6FBF2]/80 text-[#0D7A4D] shadow-sm dark:border-[#24EA9B]/60 dark:bg-[#064E3B]/40 dark:text-[#6EE7B7]'
                        : 'border-border bg-card hover:bg-muted/50'
                "
            >
                <input
                    class="peer sr-only"
                    type="checkbox"
                    :checked="prayer.completed"
                    :aria-label="`Tandai ${prayer.label} selesai`"
                    @change="update(prayer, $event)"
                />
                <span
                    class="grid size-9 shrink-0 place-items-center rounded-full border transition-all peer-focus-visible:ring-2 peer-focus-visible:ring-ring"
                    :class="prayer.completed ? 'border-[#24EA9B] bg-white text-[#24EA9B] shadow-sm' : 'border-border text-muted-foreground'"
                    aria-hidden="true"
                >
                    <img v-if="prayer.completed" src="/ornaments/graphic-10.png" width="36" height="36" alt="" class="size-8 select-none" />
                    <Circle v-else class="size-4" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold" :class="prayer.completed ? 'text-[#0D7A4D] dark:text-[#6EE7B7]' : 'text-foreground'">{{
                        prayer.label
                    }}</span>
                    <span
                        class="block text-xs"
                        :class="prayer.completed ? 'font-medium text-[#0D7A4D] dark:text-[#6EE7B7]' : 'text-muted-foreground'"
                        >{{ prayer.completed ? 'Alhamdulillah selesai' : 'Belum ditandai' }}</span
                    >
                </span>
            </label>
        </div>
    </RuangCard>
</template>
