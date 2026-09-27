<?php

namespace App\Support;

/** Parse only canonical YouTube watch, Shorts, and short links. */
final class YouTubeVideoUrl
{
    private const VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/D';

    public static function extractId(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])) {
            return null;
        }

        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        $path = $parts['path'] ?? '';
        $id = null;

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true) && $path === '/watch') {
            $query = [];
            parse_str($parts['query'] ?? '', $query);
            $id = is_string($query['v'] ?? null) ? $query['v'] : null;
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)
            && preg_match('~^/shorts/([A-Za-z0-9_-]{11})/?$~D', $path, $matches)) {
            $id = $matches[1];
        } elseif ($host === 'youtu.be' && preg_match('~^/([A-Za-z0-9_-]{11})/?$~D', $path, $matches)) {
            $id = $matches[1];
        }

        return is_string($id) && preg_match(self::VIDEO_ID_PATTERN, $id) === 1 ? $id : null;
    }
}
