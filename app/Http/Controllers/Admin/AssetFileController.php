<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAssetFileRequest;
use App\Http\Requests\Admin\UpdateAssetFileRequest;
use App\Http\Requests\Admin\UploadAssetFileRequest;
use App\Services\Logger;

class AssetFileController extends Controller
{
    private function basePath(string $type): string
    {
        return public_path("assets/{$type}");
    }

    private function normalizePath(string $path): string
    {
        $parts = [];

        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                array_pop($parts);
                continue;
            }

            $parts[] = $part;
        }

        return '/' . implode('/', $parts);
    }

    private function resolveFile(string $type, string $file): string
    {
        if (!in_array($type, ['css', 'js'])) {
            abort(404);
        }

        $base = $this->basePath($type);
        $normalized = $this->normalizePath($base . '/' . $file);
        $normalizedBase = $this->normalizePath($base);

        if (!str_starts_with($normalized, $normalizedBase . '/')) {
            abort(403);
        }

        if (!file_exists($normalized)) {
            abort(404);
        }

        return $normalized;
    }

    private function relPath(string $type, string $absolutePath): string
    {
        $base = $this->normalizePath($this->basePath($type));
        $norm = $this->normalizePath($absolutePath);

        return ltrim(substr($norm, strlen($base)), '/');
    }

    public function upload(UploadAssetFileRequest $request, string $type)
    {
        if (!in_array($type, ['css', 'js'])) {
            return response()->json(['ok' => false, 'message' => 'Неверный тип файла'], 422);
        }

        $uploaded = $request->file('file');
        $origName = $uploaded->getClientOriginalName();

        $ext = strtolower($uploaded->getClientOriginalExtension());

        if ($ext !== $type) {
            return response()->json(['ok' => false, 'message' => "Файл должен иметь расширение .{$type}"], 422);
        }

        $safeName = preg_replace('/[^\w\-.]/', '_', basename($origName));

        if (!str_ends_with($safeName, ".{$type}")) {
            $safeName .= ".{$type}";
        }

        $base = $this->basePath($type);

        if (!is_dir($base)) {
            mkdir($base, 0755, true);
        }

        $dest = $base . '/' . $safeName;

        if (file_exists($dest)) {
            return response()->json(['ok' => false, 'message' => "Файл «{$safeName}» уже существует. Удалите его и загрузите снова."], 422);
        }

        $uploaded->move($base, $safeName);

        Logger::adminAction("Загрузил файл {$type}/{$safeName}", 'create', 'asset', null, $safeName);

        return response()->json([
            'ok'       => true,
            'filename' => $safeName,
            'redirect' => route('admin.layouts.asset.edit', [$type, $safeName]),
        ]);
    }

    public function create(CreateAssetFileRequest $request, string $type)
    {
        if (!in_array($type, ['css', 'js'])) {
            return response()->json(['ok' => false, 'message' => 'Неверный тип файла'], 422);
        }

        $raw = $request->input('filename');

        $raw = str_replace('\\', '/', trim($raw, '/'));

        if (!str_ends_with($raw, ".{$type}")) {
            $raw .= ".{$type}";
        }

        $base = $this->basePath($type);
        $normalizedBase = $this->normalizePath($base);
        $fullPath = $this->normalizePath($base . '/' . $raw);

        if (!str_starts_with($fullPath, $normalizedBase . '/')) {
            return response()->json(['ok' => false, 'message' => 'Недопустимый путь'], 422);
        }

        if (file_exists($fullPath)) {
            return response()->json(['ok' => false, 'message' => 'Файл с таким именем уже существует'], 422);
        }

        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, '');

        $relPath = ltrim(substr($fullPath, strlen($normalizedBase)), '/');

        Logger::adminAction("Создал файл {$type}/{$relPath}", 'create', 'asset', null, $relPath);

        return response()->json([
            'ok'       => true,
            'redirect' => route('admin.layouts.asset.edit', [$type, $relPath]),
        ]);
    }

    public function edit(string $type, string $file)
    {
        $path = $this->resolveFile($type, $file);
        $content = file_get_contents($path);
        $relPath = $this->relPath($type, $path);
        $canFiles = \Illuminate\Support\Facades\Auth::guard('admin')->user()->hasPermission('layouts.files');

        return view('admin.layouts.edit-file', [
            'type'     => $type,
            'filename' => basename($relPath),
            'relPath'  => $relPath,
            'content'  => $content,
            'canFiles' => $canFiles,
        ]);
    }

    public function update(UpdateAssetFileRequest $request, string $type, string $file)
    {
        $path = $this->resolveFile($type, $file);
        $relPath = $this->relPath($type, $path);

        file_put_contents($path, $request->input('content', ''));

        Logger::adminAction("Сохранил файл {$type}/{$relPath}", 'edit', 'asset', null, $relPath);

        return back()->with('toast_success', 'Файл сохранён');
    }

    public function destroy(string $type, string $file)
    {
        $path = $this->resolveFile($type, $file);
        $relPath = $this->relPath($type, $path);

        unlink($path);

        $base = $this->normalizePath($this->basePath($type));
        $dir = dirname($path);
        while ($this->normalizePath($dir) !== $base && is_dir($dir) && count(glob($dir . '/*')) === 0) {
            rmdir($dir);
            $dir = dirname($dir);
        }

        Logger::adminAction("Удалил файл {$type}/{$relPath}", 'delete', 'asset', null, $relPath);

        return response()->json(['ok' => true]);
    }
}
