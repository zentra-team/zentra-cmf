<?php

namespace App\Services;

use App\Models\Block;

class BlockRenderer
{
    private const MAX_DEPTH = 10;

    private array $cache = [];

    public function expand(string $html, int $depth = 0): string
    {
        if ($depth >= self::MAX_DEPTH) {
            return $html;
        }

        if (!str_contains($html, '[block:')) {
            return $html;
        }

        $codeBlocks = [];
        $i = 0;
        $html = preg_replace_callback(
            '/<(pre|code)([\s>])(.*?)<\/\1>/si',
            function (array $m) use (&$codeBlocks, &$i, $depth): string {
                $key = '<!--BLK_CB_' . $depth . '_' . ($i++) . '-->';
                $codeBlocks[$key] = $m[0];

                return $key;
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[block:([a-zA-Z0-9_\-]+)\]/',
            function (array $m) use ($depth): string {
                $content = $this->fetchBlockContent($m[1]);

                if ($content === null) {
                    return "<!-- block:{$m[1]} not found -->";
                }

                return $this->expand($content, $depth + 1);
            },
            $html,
        ) ?? $html;

        if ($codeBlocks) {
            $html = str_replace(array_keys($codeBlocks), array_values($codeBlocks), $html);
        }

        return $html;
    }

    private function fetchBlockContent(string $alias): ?string
    {
        if (!array_key_exists($alias, $this->cache)) {
            $this->cache[$alias] = Block::where('alias', $alias)->value('content');
        }

        return $this->cache[$alias];
    }
}
