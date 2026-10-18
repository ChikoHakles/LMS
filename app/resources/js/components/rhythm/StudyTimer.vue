<script setup lang="ts">
import RuangCard from '@/components/ruang/RuangCard.vue';
import { useStudyTimer, type StudyTimerContext } from '@/composables/useStudyTimer';
import { Clock3, Pause, Play } from 'lucide-vue-next';

const props = defineProps<{ context: StudyTimerContext }>();
const { activeMinutes, dailySeconds, state, message } = useStudyTimer(props.context);
</script>

<template>
    <RuangCard class="hover-lift flex flex-wrap items-center justify-between gap-4 border border-[#06B6D4]/30 p-5 transition-all">
        <div class="flex items-center gap-4">
            <span
                class="relative grid size-12 place-items-center rounded-2xl transition-all"
                :class="
                    state === 'active'
                        ? 'bg-[#06B6D4]/20 text-[#0891B2] shadow-[0_0_16px_rgba(6,182,212,0.35)] ring-4 ring-[#06B6D4]/30'
                        : 'bg-primary/10 text-primary'
                "
            >
                <Clock3 class="size-6" :class="state === 'active' ? 'animate-spin [animation-duration:8s]' : ''" aria-hidden="true" />
            </span>
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Belajar Aktif Hari Ini</h2>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="font-mono text-3xl font-extrabold tracking-tight text-foreground">{{ activeMinutes }}</span>
                    <span class="text-xs font-bold uppercase text-muted-foreground">menit</span>
                    <span class="font-mono text-sm text-muted-foreground">({{ dailySeconds }} dtk)</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span
                class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-all"
                :class="
                    state === 'active'
                        ? 'border-[#06B6D4]/40 bg-[#E0FAFF] text-[#0E7490] shadow-sm dark:bg-[#164E63]/50 dark:text-[#7DD3FC]'
                        : 'border-border bg-muted/60 text-muted-foreground'
                "
                role="status"
                aria-live="polite"
            >
                <span v-if="state === 'active'" class="relative flex size-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#06B6D4] opacity-75" />
                    <span class="relative inline-flex size-2 rounded-full bg-[#06B6D4]" />
                </span>
                <Play v-if="state === 'active'" class="size-3.5" aria-hidden="true" />
                <Pause v-else class="size-3.5" aria-hidden="true" />
                {{ message || (state === 'active' ? 'Sedang mencatat' : state === 'starting' ? 'Memulihkan sesi…' : 'Dijeda') }}
            </span>
        </div>
    </RuangCard>
</template>
