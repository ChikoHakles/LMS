<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <section class="rounded-[20px] border border-border bg-card text-card-foreground shadow-sm" :class="props.class">
        <slot />
    </section>
</template>
