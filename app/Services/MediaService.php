<?php

namespace App\Services;

use App\Models\MediaFile;

class MediaService
{
    public function destroyByUrl(string $url): array
    {
        if (preg_match('#^/media/([0-9a-f\-]{10,})$#i', $url, $m)) {
            $media = MediaFile::where('uuid', $m[1])->first();

            if (!$media) {
                return ['ok' => false, 'message' => 'Файл не найден', 'status' => 404];
            }

            $absolute = $media->absolutePath();

            if (is_file($absolute)) {
                @unlink($absolute);
            }

            $media->delete();

            return ['ok' => true];
        }

        $allowed = public_path('uploads/media');
        $relative = ltrim($url, '/');
        $absolute = public_path($relative);

        $real = realpath($absolute);

        if (!$real || !str_starts_with($real, realpath($allowed) . DIRECTORY_SEPARATOR)) {
            return ['ok' => false, 'message' => 'Недопустимый путь', 'status' => 403];
        }

        if (!is_file($real)) {
            return ['ok' => false, 'message' => 'Файл не найден', 'status' => 404];
        }

        unlink($real);

        return ['ok' => true];
    }
}
