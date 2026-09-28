<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import { useStudyTimer, type StudyTimerContext } from '@/composables/useStudyTimer';
import { Clock3 } from 'lucide-vue-next';

const props = defineProps<{ context: StudyTimerContext }>();
const { activeMinutes, dailySeconds, state, message } = useStudyTimer(props.context);
</script>

<template>
    <RuangCard class="flex flex-wrap items-center justify-between gap-4 p-4">
        <div class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-primary/10 text-primary">
                <Clock3 class="size-5" aria-hidden="true" />
            </span>
            <div>
                <h2 class="font-semibold">Belajar aktif hari ini</h2>
                <p class="text-sm text-muted-foreground">{{ activeMinutes }} menit · {{ dailySeconds }} detik tersimpan</p>
            </div>
        </div>
        <span class="text-xs font-medium text-muted-foreground" role="status" aria-live="polite">
            {{ message || (state === 'active' ? 'Sedang mencatat' : state === 'starting' ? 'Memulihkan sesi…' : 'Dijeda') }}
        </span>
    </RuangCard>
</template>
