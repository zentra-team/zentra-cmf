<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class AdminAssetController extends Controller
{
    private const MIME_TYPES = [
        'css' => 'text/css',
        'js'  => 'application/javascript',
    ];

    public function __invoke(string $path): Response
    {
        if (preg_match('/\.\./', $path) || !preg_match('#^(css|js)/[a-z0-9_-]+\.(css|js)$#', $path)) {
            abort(404);
        }

        $fullPath = resource_path($path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mimeType = self::MIME_TYPES[$extension] ?? 'application/octet-stream';
        $etag = md5_file($fullPath);
        $lastModified = filemtime($fullPath);

        if (request()->header('If-None-Match') === $etag) {
            return response()->noContent(304);
        }

        $cacheControl = 'no-cache, must-revalidate';

        return response()->file($fullPath, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => $cacheControl,
            'ETag'          => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
        ]);
    }
}
