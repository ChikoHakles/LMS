<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import type { HTMLAttributes } from 'vue';

interface Props {
    class?: HTMLAttributes['class'];
    tone?: 'sidebar' | 'surface';
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'sidebar',
});
</script>

<template>
    <span class="flex min-w-0 items-center gap-2.5" :class="props.class">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg" :class="props.tone === 'sidebar' ? 'bg-sidebar-accent' : 'bg-secondary'">
            <AppLogoIcon class-name="size-6 text-[hsl(var(--ruang-warm))]" />
        </span>
        <span class="truncate text-lg font-bold tracking-tight" :class="props.tone === 'sidebar' ? 'text-sidebar-foreground' : 'text-foreground'">ruang<span class="text-[hsl(var(--ruang-warm))]">.</span></span>
    </span>
</template>
