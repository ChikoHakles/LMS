import katex from 'katex';

export function escapeHtml(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

export function renderMarkdownAndLatex(input: string | null | undefined, options: { inline?: boolean } = {}): string {
    if (!input) return '';

    const isInline = Boolean(options.inline);
    const mathMap = new Map<string, string>();
    let counter = 0;

    // 1. Display math: $$...$$
    let text = input.replace(/\$\$([\s\S]*?)\$\$/g, (_match, formula) => {
        const id = `@@MATH_BLOCK_${counter++}@@`;
        try {
            const html = katex.renderToString(formula.trim(), {
                displayMode: true,
                throwOnError: false,
            });
            mathMap.set(id, isInline ? ` ${html} ` : `<div class="my-3 overflow-x-auto text-center">${html}</div>`);
        } catch {
            mathMap.set(id, `<code class="font-mono text-xs text-destructive">${escapeHtml(formula)}</code>`);
        }
        return isInline ? ` ${id} ` : `\n\n${id}\n\n`;
    });

    // 2. Inline math: $...$
    text = text.replace(/(^|[^\$])\$([^\$\n]+?)\$(?!\$)/g, (_match, prefix, formula) => {
        const id = `@@MATH_INLINE_${counter++}@@`;
        try {
            const html = katex.renderToString(formula.trim(), {
                displayMode: false,
                throwOnError: false,
            });
            mathMap.set(id, html);
        } catch {
            mathMap.set(id, `<code class="font-mono text-xs text-destructive">${escapeHtml(formula)}</code>`);
        }
        return `${prefix}${id}`;
    });

    // 3. Escape HTML to prevent XSS
    text = escapeHtml(text);

    // 4. Parse Markdown elements
    if (!isInline) {
        text = text.replace(/^### (.*$)/gim, '<h3 class="my-2 text-lg font-bold text-foreground">$1</h3>');
        text = text.replace(/^## (.*$)/gim, '<h2 class="my-2 text-xl font-bold text-foreground">$1</h2>');
        text = text.replace(/^# (.*$)/gim, '<h1 class="my-3 text-2xl font-extrabold text-foreground">$1</h1>');

        text = text.replace(
            /^> (.*$)/gim,
            '<blockquote class="my-2 border-l-4 border-primary/60 bg-muted/30 px-3 py-1.5 italic text-muted-foreground">$1</blockquote>',
        );
    }

    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
    text = text.replace(/`([^`]+)`/g, '<code class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs text-foreground">$1</code>');
    text = text.replace(
        /\[([^\]]+)\]\(((?:https?:\/\/|mailto:)[^\s\)]+)\)/g,
        '<a href="$2" target="_blank" rel="noopener noreferrer" class="font-medium text-primary underline underline-offset-4 hover:opacity-80">$1</a>',
    );

    if (isInline) {
        text = text.replace(/\n+/g, ' ');
    } else {
        // Convert newlines to paragraphs & breaks
        text = text.replace(/\n\n+/g, '</p><p class="my-2">');
        text = text.replace(/\n/g, '<br />');

        text = `<p class="my-1">${text}</p>`;
        text = text.replace(/<p class="my-1">\s*<\/p>/g, '');
    }

    // 5. Restore math placeholders
    for (const [id, rendered] of mathMap.entries()) {
        text = text.split(id).join(rendered);
    }

    return text;
}
