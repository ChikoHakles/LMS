<?php

namespace App\Services;

/** Stores article content as an allow-listed tree of plain text and safe URLs. */
class ArticleBlockSanitizer
{
    /** @param list<array<string, mixed>> $blocks
     * @return list<array<string, mixed>>
     */
    public function sanitize(array $blocks): array
    {
        return array_values(array_map(function (array $block): array {
            return match ($block['type'] ?? null) {
                'paragraph' => ['type' => 'paragraph', 'text' => trim((string) $block['text'])],
                'heading' => $this->sanitizeHeading($block),
                'list' => [
                    'type' => 'list',
                    'items' => array_values(array_map(fn ($item) => trim((string) $item), $block['items'])),
                    'ordered' => (bool) ($block['ordered'] ?? false),
                ],
                'quote' => [
                    'type' => 'quote',
                    'text' => trim((string) $block['text']),
                    'attribution' => trim((string) ($block['attribution'] ?? '')),
                ],
                'link' => [
                    'type' => 'link',
                    'text' => trim((string) $block['text']),
                    'url' => $this->safeUrl((string) $block['url']),
                ],
                default => throw new \InvalidArgumentException('Unsupported article block type.'),
            };
        }, $blocks));
    }

    /** @param array<string, mixed> $block
     * @return array{type: string, text: string, level: int}
     */
    private function sanitizeHeading(array $block): array
    {
        $level = $block['level'] ?? 2;

        return [
            'type' => 'heading',
            'text' => trim((string) $block['text']),
            'level' => in_array($level, [2, 3], true) ? $level : 2,
        ];
    }

    private function safeUrl(string $url): string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Article links must use HTTP or HTTPS.');
        }

        return trim($url);
    }
}
