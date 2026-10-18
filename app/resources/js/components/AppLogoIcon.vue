<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/brand/damai-karya/icon.png"
        width="128"
        height="128"
        alt=""
        :class="className"
        v-bind="$attrs"
        class="object-contain"
        aria-hidden="true"
    />
</template>
