<script setup lang="ts">
import LatexViewer from '@/components/common/LatexViewer.vue';
import { Bold, Eye, Heading2, Heading3, Italic, List, ListOrdered, PenLine, Quote, Radical, Sigma, Superscript } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        mode?: 'full' | 'compact';
        placeholder?: string;
        disabled?: boolean;
        minRows?: number;
        maxRows?: number;
        error?: string;
        id?: string;
    }>(),
    {
        modelValue: '',
        mode: 'full',
        placeholder: 'Tulis teks atau notasi LaTeX...',
        disabled: false,
        minRows: undefined,
        maxRows: undefined,
        error: '',
        id: undefined,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const activeTab = ref<'write' | 'preview'>('write');
const textareaRef = ref<HTMLTextAreaElement | null>(null);

const resolvedRows = computed(() => {
    if (props.minRows) return props.minRows;
    return props.mode === 'compact' ? 3 : 5;
});

const textareaStyle = computed(() => {
    if (props.maxRows) {
        return { maxHeight: `${props.maxRows * 1.5}rem` };
    }
    return undefined;
});

function insertText(snippet: string) {
    const el = textareaRef.value;
    if (!el || props.disabled) return;

    const start = el.selectionStart;
    const end = el.selectionEnd;
    const val = props.modelValue ?? '';
    const before = val.substring(0, start);
    const after = val.substring(end);

    const next = before + snippet + after;
    emit('update:modelValue', next);

    nextTick(() => {
        el.focus();
        const cursor = start + snippet.length;
        el.setSelectionRange(cursor, cursor);
    });
}

function wrapSelection(prefix: string, suffix: string, defaultText = '') {
    const el = textareaRef.value;
    if (!el || props.disabled) return;

    const start = el.selectionStart;
    const end = el.selectionEnd;
    const val = props.modelValue ?? '';
    const selected = val.substring(start, end) || defaultText;
    const before = val.substring(0, start);
    const after = val.substring(end);

    const next = before + prefix + selected + suffix + after;
    emit('update:modelValue', next);

    nextTick(() => {
        el.focus();
        el.setSelectionRange(start + prefix.length, start + prefix.length + selected.length);
    });
}

function insertLinePrefix(prefix: string, defaultText = '') {
    const el = textareaRef.value;
    if (!el || props.disabled) return;

    const start = el.selectionStart;
    const end = el.selectionEnd;
    const val = props.modelValue ?? '';
    const selected = val.substring(start, end) || defaultText;
    const before = val.substring(0, start);
    const after = val.substring(end);

    const needNewline = before.length > 0 && !before.endsWith('\n');
    const next = before + (needNewline ? '\n' : '') + prefix + selected + after;
    emit('update:modelValue', next);

    nextTick(() => {
        el.focus();
    });
}
</script>

<template>
    <div class="w-full">
        <div
            :class="[
                'overflow-hidden rounded-lg border bg-background ring-offset-background transition-colors focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
                error ? 'border-destructive' : 'border-input',
                disabled ? 'opacity-60' : '',
            ]"
        >
            <!-- Header: Tabs and Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-1 border-b border-border bg-muted/40 px-2 py-1.5">
                <!-- Mode Tabs -->
                <div class="flex items-center gap-1" role="tablist" aria-label="Mode editor">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'write'"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-1 focus:ring-ring',
                            activeTab === 'write'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        :disabled="disabled"
                        @click="activeTab = 'write'"
                    >
                        <PenLine class="size-3.5" aria-hidden="true" />
                        Tulis
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'preview'"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-1 focus:ring-ring',
                            activeTab === 'preview'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        :disabled="disabled"
                        @click="activeTab = 'preview'"
                    >
                        <Eye class="size-3.5" aria-hidden="true" />
                        Pratinjau
                    </button>
                </div>

                <!-- Formatting & Math Actions (Visible only in write tab) -->
                <div v-if="activeTab === 'write'" class="flex flex-wrap items-center gap-0.5" role="toolbar" aria-label="Alat format teks dan rumus">
                    <button
                        type="button"
                        title="Tebal (**teks**)"
                        aria-label="Tebal"
                        :disabled="disabled"
                        class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                        @click="wrapSelection('**', '**', 'teks tebal')"
                    >
                        <Bold class="size-3.5" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        title="Miring (*teks*)"
                        aria-label="Miring"
                        :disabled="disabled"
                        class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                        @click="wrapSelection('*', '*', 'teks miring')"
                    >
                        <Italic class="size-3.5" aria-hidden="true" />
                    </button>

                    <template v-if="mode === 'full'">
                        <button
                            type="button"
                            title="Judul bagian (## Judul)"
                            aria-label="Judul bagian"
                            :disabled="disabled"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertLinePrefix('## ', 'Judul bagian')"
                        >
                            <Heading2 class="size-3.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            title="Subjudul (### Subjudul)"
                            aria-label="Subjudul"
                            :disabled="disabled"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertLinePrefix('### ', 'Subjudul')"
                        >
                            <Heading3 class="size-3.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            title="Daftar poin"
                            aria-label="Daftar poin"
                            :disabled="disabled"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertLinePrefix('- ', 'Butir daftar')"
                        >
                            <List class="size-3.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            title="Daftar bernomor"
                            aria-label="Daftar bernomor"
                            :disabled="disabled"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertLinePrefix('1. ', 'Butir daftar')"
                        >
                            <ListOrdered class="size-3.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            title="Kutipan (> Kutipan)"
                            aria-label="Kutipan"
                            :disabled="disabled"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertLinePrefix('> ', 'Kutipan')"
                        >
                            <Quote class="size-3.5" aria-hidden="true" />
                        </button>
                    </template>

                    <div class="mx-1 h-3.5 w-px bg-border" aria-hidden="true" />

                    <!-- Math Helpers -->
                    <button
                        type="button"
                        title="Formula Matematika Inline ($x$)"
                        aria-label="Formula Matematika Inline"
                        :disabled="disabled"
                        class="rounded px-1.5 py-0.5 font-mono text-xs font-semibold text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                        @click="wrapSelection('$', '$', 'x')"
                    >
                        $x$
                    </button>
                    <button
                        type="button"
                        title="Formula Matematika Blok ($$...$$)"
                        aria-label="Formula Matematika Blok"
                        :disabled="disabled"
                        class="rounded px-1.5 py-0.5 font-mono text-xs font-semibold text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                        @click="wrapSelection('$$\n', '\n$$', '\\frac{a}{b}')"
                    >
                        $$...$$
                    </button>
                    <button
                        type="button"
                        title="Pecahan (\frac{a}{b})"
                        aria-label="Pecahan"
                        :disabled="disabled"
                        class="rounded px-1.5 py-0.5 font-mono text-xs text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                        @click="insertText('\\frac{a}{b}')"
                    >
                        a/b
                    </button>
                    <button
                        type="button"
                        title="Akar (\sqrt{x})"
                        aria-label="Akar"
                        :disabled="disabled"
                        class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                        @click="insertText('\\sqrt{x}')"
                    >
                        <Radical class="size-3.5" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        title="Pangkat (x^{n})"
                        aria-label="Pangkat"
                        :disabled="disabled"
                        class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                        @click="insertText('x^{n}')"
                    >
                        <Superscript class="size-3.5" aria-hidden="true" />
                    </button>

                    <template v-if="mode === 'full'">
                        <button
                            type="button"
                            title="Penjumlahan / Sigma"
                            aria-label="Sigma"
                            :disabled="disabled"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertText('\\sum_{i=1}^{n} x_i')"
                        >
                            <Sigma class="size-3.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            title="Integral"
                            aria-label="Integral"
                            :disabled="disabled"
                            class="rounded px-1.5 py-0.5 font-serif text-xs font-bold text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertText('\\int_{a}^{b} f(x) dx')"
                        >
                            ∫
                        </button>
                        <button
                            type="button"
                            title="Kurang dari sama dengan (\le)"
                            aria-label="Kurang dari sama dengan"
                            :disabled="disabled"
                            class="rounded px-1 py-0.5 text-xs text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertText('\\le ')"
                        >
                            ≤
                        </button>
                        <button
                            type="button"
                            title="Lebih dari sama dengan (\ge)"
                            aria-label="Lebih dari sama dengan"
                            :disabled="disabled"
                            class="rounded px-1 py-0.5 text-xs text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertText('\\ge ')"
                        >
                            ≥
                        </button>
                        <button
                            type="button"
                            title="Tidak sama dengan (\neq)"
                            aria-label="Tidak sama dengan"
                            :disabled="disabled"
                            class="rounded px-1 py-0.5 text-xs text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertText('\\neq ')"
                        >
                            ≠
                        </button>
                        <button
                            type="button"
                            title="Pi (\pi)"
                            aria-label="Pi"
                            :disabled="disabled"
                            class="rounded px-1 py-0.5 font-serif text-xs text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertText('\\pi ')"
                        >
                            π
                        </button>
                        <button
                            type="button"
                            title="Alpha (\alpha)"
                            aria-label="Alpha"
                            :disabled="disabled"
                            class="rounded px-1 py-0.5 font-serif text-xs text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            @click="insertText('\\alpha ')"
                        >
                            α
                        </button>
                    </template>
                </div>
            </div>

            <!-- Tab Content -->
            <div v-show="activeTab === 'write'">
                <textarea
                    :id="id"
                    ref="textareaRef"
                    :value="modelValue ?? ''"
                    :rows="resolvedRows"
                    :style="textareaStyle"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    :aria-invalid="Boolean(error)"
                    class="block w-full resize-y border-0 bg-transparent p-3 text-sm leading-6 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0 disabled:cursor-not-allowed"
                    @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
                />
            </div>
            <div v-show="activeTab === 'preview'" class="min-h-[6rem] bg-card p-3 text-sm">
                <LatexViewer v-if="modelValue?.trim()" :content="modelValue" />
                <p v-else class="text-xs italic text-muted-foreground">Tidak ada konten untuk dipratinjau.</p>
            </div>
        </div>
        <p v-if="error" role="alert" class="mt-1 text-sm text-destructive">{{ error }}</p>
    </div>
</template>
