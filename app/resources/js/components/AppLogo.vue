<script setup lang="ts">
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
    <span class="flex min-w-0 items-center" :class="props.class">
        <img
            src="/brand/damai-karya/icon.png"
            width="128"
            height="128"
            alt="Damai Karya"
            class="hidden size-8 object-contain group-data-[collapsible=icon]:block"
        />
        <img
            :src="props.tone === 'sidebar' ? '/brand/damai-karya/logo-horizontal-tw.png' : '/brand/damai-karya/logo-horizontal.png'"
            width="640"
            height="317"
            alt="Damai Karya"
            class="h-11 w-36 object-contain group-data-[collapsible=icon]:hidden"
            :class="props.tone === 'surface' ? 'dark:hidden' : ''"
        />
        <img
            v-if="props.tone === 'surface'"
            src="/brand/damai-karya/logo-horizontal-tw.png"
            width="640"
            height="317"
            alt="Damai Karya"
            class="hidden h-11 w-36 object-contain dark:block"
        />
    </span>
</template>
