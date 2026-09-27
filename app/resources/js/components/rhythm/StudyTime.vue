<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import { Clock3 } from 'lucide-vue-next';

defineProps<{ targetMinutes: number; activeMinutes: number }>();
</script>

<template>
    <RuangCard class="p-5">
        <div class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-primary/10 text-primary"><Clock3 class="size-5" aria-hidden="true" /></span>
            <div>
                <h2 class="font-semibold">Belajar aktif</h2>
                <p class="text-sm text-muted-foreground">{{ activeMinutes }} dari {{ targetMinutes }} menit</p>
            </div>
        </div>
        <div
            class="mt-4 h-2 overflow-hidden rounded-full bg-muted"
            role="progressbar"
            :aria-valuenow="activeMinutes"
            :aria-valuemin="0"
            :aria-valuemax="targetMinutes"
            aria-label="Progres target belajar aktif"
        >
            <span
                class="block h-full rounded-full bg-primary transition-all"
                :style="{ width: `${Math.min(100, targetMinutes ? (activeMinutes / targetMinutes) * 100 : 0)}%` }"
            />
        </div>
        <p class="mt-3 text-xs leading-5 text-muted-foreground">Durasi akan dihitung saat fitur pencatatan waktu aktif tersedia.</p>
    </RuangCard>
</template>
