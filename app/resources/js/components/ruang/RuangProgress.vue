<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';

const props = withDefaults(defineProps<{
    value: number;
    label: string;
    class?: HTMLAttributes['class'];
}>(), {
    value: 0,
});

const boundedValue = computed(() => Math.max(0, Math.min(100, Number.isFinite(props.value) ? props.value : 0)));
const valueText = computed(() => `${Math.round(boundedValue.value)}%`);
</script>

<template>
    <div
        class="h-2 w-full overflow-hidden rounded-full bg-secondary"
        role="progressbar"
        :aria-label="props.label"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-valuenow="Math.round(boundedValue)"
        :aria-valuetext="valueText"
        :class="props.class"
    >
        <div class="h-full rounded-full bg-primary transition-[width] duration-300" :style="{ width: `${boundedValue}%` }" />
    </div>
</template>
