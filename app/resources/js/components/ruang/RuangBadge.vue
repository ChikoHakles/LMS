<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';

type BadgeVariant = 'primary' | 'soft' | 'success' | 'danger' | 'muted';

const props = withDefaults(defineProps<{
    variant?: BadgeVariant;
    class?: HTMLAttributes['class'];
}>(), {
    variant: 'soft',
});

const variantClass = computed(() => ({
    primary: 'bg-primary text-primary-foreground',
    soft: 'bg-secondary text-secondary-foreground',
    success: 'bg-success-soft text-success',
    danger: 'bg-destructive/10 text-destructive',
    muted: 'bg-muted text-muted-foreground',
}[props.variant]));
</script>

<template>
    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold" :class="[variantClass, props.class]">
        <slot />
    </span>
</template>

