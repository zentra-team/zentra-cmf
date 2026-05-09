<?php

namespace App\Support;

final class SystemPaths
{
    private const EXACT_FILES = [
        'favicon.ico', 'favicon.png', 'robots.txt', 'sitemap.xml', 'sitemap-index.xml',
        'browserconfig.xml', 'manifest.json', 'site.webmanifest',
    ];

    public static function isSystem(string $path): bool
    {
        $clean = ltrim($path, '/');
        $base = basename($clean);

        if (str_starts_with($base, 'apple-touch-icon')) {
            return true;
        }

        if (in_array($base, self::EXACT_FILES, true)) {
            return true;
        }

        if ($clean === '.well-known' || str_starts_with($clean, '.well-known/')) {
            return true;
        }

        return false;
    }
}
