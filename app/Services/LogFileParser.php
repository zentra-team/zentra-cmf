<?php

namespace App\Services;

use Carbon\Carbon;

class LogFileParser
{
    public function getLogFiles(): array
    {
        $dir = storage_path('logs');
        $files = glob($dir . '/*.log') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map('basename', $files);
    }

    public function parse(string $filename, string $levelFilter = '', int $limit = 300): array
    {
        $path = storage_path('logs/' . basename($filename));

        if (!file_exists($path)) {
            return [];
        }

        $maxRead = 1 * 1024 * 1024;
        $size = filesize($path);

        if ($size > $maxRead) {
            $fp = fopen($path, 'r');
            fseek($fp, -$maxRead, SEEK_END);
            $content = fread($fp, $maxRead);
            fclose($fp);

            $nl = strpos($content, "\n");

            if ($nl !== false) {
                $content = substr($content, $nl + 1);
            }
        } else {
            $content = file_get_contents($path);
        }

        $rawEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $entries = [];

        foreach (array_reverse($rawEntries) as $raw) {
            $raw = trim($raw);

            if (!$raw) {
                continue;
            }

            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)/s', $raw, $m)) {
                continue;
            }

            $level = strtoupper($m[2]);

            if ($levelFilter && $level !== strtoupper($levelFilter)) {
                continue;
            }

            $body = trim($m[3]);
            $lines = explode("\n", $body);
            $msg = trim($lines[0]);
            $stack = count($lines) > 1 ? trim(implode("\n", array_slice($lines, 1))) : null;

            $entries[] = [
                'date'     => Carbon::createFromFormat('Y-m-d H:i:s', $m[1])->format('d.m.y H:i'),
                'level'    => $level,
                'message'  => $msg,
                'location' => $this->extractLocation($stack),
            ];

            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    private function extractLocation(?string $stack): ?string
    {
        if (!$stack) {
            return null;
        }

        $lines = explode("\n", $stack);
        $first = null;

        foreach ($lines as $line) {
            if (!preg_match('/#\d+\s+(.+\.php)\((\d+)\)/', $line, $m)) {
                continue;
            }

            $file = $m[1];
            $ln = $m[2];

            if (!str_contains($file, '/vendor/')) {
                $short = preg_replace('#.*/app/#', 'app/', $file);

                return $short . ':' . $ln;
            }

            if ($first === null) {
                $first = basename($file) . ':' . $ln;
            }
        }

        return $first;
    }
}
