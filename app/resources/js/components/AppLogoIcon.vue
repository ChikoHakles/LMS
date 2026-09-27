<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" :class="className" v-bind="$attrs" aria-hidden="true">
        <rect x="5" y="5" width="30" height="30" rx="8" stroke="currentColor" stroke-width="4" transform="rotate(-10 20 20)" />
        <circle cx="20" cy="20" r="5.5" fill="currentColor" />
    </svg>
</template>
