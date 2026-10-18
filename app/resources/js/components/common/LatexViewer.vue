<script setup lang="ts">
import { renderMarkdownAndLatex } from '@/lib/latex';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        content?: string | null;
        inline?: boolean;
    }>(),
    {
        content: '',
        inline: false,
    },
);

const renderedHtml = computed(() => {
    return renderMarkdownAndLatex(props.content ?? '', { inline: props.inline });
});
</script>

<template>
    <span v-if="inline" class="latex-content inline text-foreground" v-html="renderedHtml" />
    <div v-else class="latex-content block overflow-x-auto leading-relaxed text-foreground" v-html="renderedHtml" />
</template>
