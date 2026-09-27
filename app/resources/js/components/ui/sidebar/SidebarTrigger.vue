<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { cn } from '@/lib/utils';
import { PanelLeft } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { useSidebar } from './utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const { toggleSidebar, isMobile, open, openMobile } = useSidebar();
const isExpanded = computed(() => isMobile.value ? openMobile.value : open.value);
</script>

<template>
    <Button
        data-sidebar="trigger"
        variant="ghost"
        size="icon"
        :class="cn('h-9 w-9 shrink-0', props.class)"
        aria-label="Buka atau tutup navigasi"
        aria-controls="ruang-primary-navigation"
        :aria-expanded="isExpanded"
        @click="toggleSidebar"
    >
        <PanelLeft aria-hidden="true" />
    </Button>
</template>
