<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import { Check, Circle } from 'lucide-vue-next';

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
                class="flex cursor-pointer items-center gap-3 rounded-xl border border-border p-3 transition hover:bg-muted/50"
            >
                <input
                    class="peer sr-only"
                    type="checkbox"
                    :checked="prayer.completed"
                    :aria-label="`Tandai ${prayer.label} selesai`"
                    @change="update(prayer, $event)"
                />
                <span
                    class="grid size-9 place-items-center rounded-full border border-border text-muted-foreground peer-checked:border-success peer-checked:bg-success-soft peer-checked:text-success peer-focus-visible:ring-2 peer-focus-visible:ring-ring"
                    aria-hidden="true"
                >
                    <Check v-if="prayer.completed" class="size-4" />
                    <Circle v-else class="size-4" />
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold">{{ prayer.label }}</span>
                    <span class="block text-xs text-muted-foreground">{{ prayer.completed ? 'Sudah ditandai' : 'Belum ditandai' }}</span>
                </span>
            </label>
        </div>
    </RuangCard>
</template>
