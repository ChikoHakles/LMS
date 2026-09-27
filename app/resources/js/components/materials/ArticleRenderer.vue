<script setup lang="ts">
export type ArticleBlock =
    | { type: 'paragraph'; text: string }
    | { type: 'heading'; text: string; level?: 2 | 3 }
    | { type: 'list'; items: string[]; ordered?: boolean }
    | { type: 'quote'; text: string; attribution?: string }
    | { type: 'link'; text: string; url: string };

defineProps<{
    blocks: ArticleBlock[] | null;
}>();

function isSafeLink(value: string): boolean {
    try {
        const url = new URL(value);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch {
        return false;
    }
}
</script>

<template>
    <article class="mx-auto w-full max-w-3xl space-y-6 text-base leading-8 text-foreground">
        <template v-for="(block, index) in blocks ?? []" :key="index">
            <p v-if="block.type === 'paragraph'" class="whitespace-pre-wrap">{{ block.text }}</p>
            <h2 v-else-if="block.type === 'heading' && block.level !== 3" class="pt-2 text-2xl font-bold leading-tight tracking-tight">
                {{ block.text }}
            </h2>
            <h3 v-else-if="block.type === 'heading'" class="pt-2 text-xl font-semibold leading-tight">{{ block.text }}</h3>
            <ol v-else-if="block.type === 'list' && block.ordered" class="list-decimal space-y-2 pl-7 marker:text-primary">
                <li v-for="(item, itemIndex) in block.items" :key="itemIndex" class="pl-1">{{ item }}</li>
            </ol>
            <ul v-else-if="block.type === 'list'" class="list-disc space-y-2 pl-7 marker:text-primary">
                <li v-for="(item, itemIndex) in block.items" :key="itemIndex" class="pl-1">{{ item }}</li>
            </ul>
            <blockquote
                v-else-if="block.type === 'quote'"
                class="border-l-4 border-primary/40 bg-secondary/50 px-5 py-4 text-lg italic text-card-foreground"
            >
                <p class="whitespace-pre-wrap">{{ block.text }}</p>
                <footer v-if="block.attribution" class="mt-2 text-sm not-italic text-muted-foreground">{{ block.attribution }}</footer>
            </blockquote>
            <p v-else-if="block.type === 'link' && isSafeLink(block.url)" class="break-words">
                <a
                    :href="block.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-medium text-primary underline underline-offset-4 hover:text-primary/80"
                    >{{ block.text }}</a
                >
            </p>
            <p v-else-if="block.type === 'link'" class="break-words">{{ block.text }}</p>
        </template>
        <p v-if="!blocks?.length" class="rounded-xl border border-dashed border-border px-5 py-8 text-center text-sm text-muted-foreground">
            Artikel ini belum memiliki isi.
        </p>
    </article>
</template>
