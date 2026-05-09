<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyImageRequest;
use App\Http\Requests\Admin\UploadFileRequest;
use App\Http\Requests\Admin\UploadImageRequest;
use App\Models\MediaFile;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class UploadController extends Controller
{
    private const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8',
        'pl', 'cgi', 'sh', 'bash', 'py', 'rb', 'asp', 'aspx', 'htaccess',
        'exe', 'dll', 'so', 'bat', 'cmd',
    ];

    public function __construct(
        private readonly MediaService $mediaService,
    ) {
    }

    public function image(UploadImageRequest $request): JsonResponse
    {
        return $this->store($request->file('file'), 'image');
    }

    public function file(UploadFileRequest $request): JsonResponse
    {
        return $this->store($request->file('file'), 'file');
    }

    private function store(SymfonyUploadedFile $file, string $kind): JsonResponse
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === '') {
            $ext = $kind === 'image' ? 'jpg' : 'bin';
        }

        if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            abort(422, 'Этот тип файла запрещён для загрузки.');
        }

        $uuid = (string) Str::uuid();
        $subDir = 'private/uploads/' . $kind . 's/' . date('Y/m');
        $dir = storage_path('app/' . $subDir);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            abort(500, 'Не удалось создать директорию для загрузки.');
        }

        $filename = $uuid . '.' . $ext;
        $storagePath = $subDir . '/' . $filename;
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();

        $file->move($dir, $filename);

        $fullPath = $dir . '/' . $filename;
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = ($finfo ? finfo_file($finfo, $fullPath) : false) ?: 'application/octet-stream';

        $media = MediaFile::create([
            'uuid'          => $uuid,
            'kind'          => $kind,
            'storage_path'  => $storagePath,
            'original_name' => $originalName,
            'mime'          => $mime,
            'size'          => $size,
            'uploaded_by'   => auth('admin')->id(),
        ]);

        return response()->json([
            'ok'            => true,
            'url'           => $media->publicUrl(),
            'uuid'          => $media->uuid,
            'original_name' => $originalName,
            'mime'          => $mime,
            'size'          => $size,
        ]);
    }

    public function destroyImage(DestroyImageRequest $request): JsonResponse
    {
        $url = (string) $request->input('url', '');
        $result = $this->mediaService->destroyByUrl($url);

        if (!$result['ok']) {
            return response()->json(
                ['ok' => false, 'message' => $result['message']],
                $result['status'],
            );
        }

        return response()->json(['ok' => true]);
    }
}
