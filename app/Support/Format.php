<?php

namespace App\Support;

final class Format
{
    public static function fileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' Б';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' КБ';
        }

        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' МБ';
        }

        if ($bytes < 1099511627776) {
            return round($bytes / 1073741824, 2) . ' ГБ';
        }

        return round($bytes / 1099511627776, 2) . ' ТБ';
    }

    public static function phpIniSize(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $num = (int) $val;

        return match ($last) {
            'g'     => $num * 1024 * 1024 * 1024,
            'm'     => $num * 1024 * 1024,
            'k'     => $num * 1024,
            default => $num,
        };
    }
}
