<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function show(Request $request, string $uuid): Response
    {
        $media = MediaFile::where('uuid', $uuid)->first();

        if (!$media) {
            abort(404);
        }

        $path = $media->absolutePath();

        if (!is_file($path)) {
            abort(404);
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = ($finfo ? finfo_file($finfo, $path) : false) ?: ($media->mime ?: 'application/octet-stream');
        $isImage = str_starts_with($mime, 'image/');
        $force = $request->boolean('download');

        $etag = '"' . md5($media->uuid . '|' . filemtime($path)) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304);
        }

        $disposition = ($isImage && !$force) ? 'inline' : 'attachment';

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            $disposition,
            $media->original_name,
        );
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('ETag', $etag);

        if ($isImage && !$force) {
            $response->headers->set('Cache-Control', 'public, max-age=86400');
        } else {
            $response->headers->set('Cache-Control', 'private, no-cache');
        }

        return $response;
    }
}
