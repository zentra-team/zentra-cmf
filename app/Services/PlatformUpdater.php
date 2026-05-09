<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class PlatformUpdater
{
    private const PRESERVE = ['.env', 'storage', 'public/uploads', 'modules'];

    public function canAutoUpdate(): bool
    {
        return is_writable(base_path());
    }

    public function update(string $zipballUrl, string $newVersion): array
    {
        if (!$this->canAutoUpdate()) {
            return $this->manualInstructions($zipballUrl, $newVersion);
        }

        try {
            Artisan::call('down', ['--retry' => 5]);

            $tempFile = $this->downloadZip($zipballUrl);

            if (!$tempFile) {
                Artisan::call('up');

                return ['ok' => false, 'message' => 'Не удалось скачать архив обновления'];
            }

            $extractDir = storage_path('app/temp/update_' . uniqid());

            if (!mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
                @unlink($tempFile);
                Artisan::call('up');

                return ['ok' => false, 'message' => 'Не удалось создать временную директорию для обновления'];
            }

            $result = $this->extractZip($tempFile, $extractDir);
            @unlink($tempFile);

            if (!$result) {
                $this->removeDirectory($extractDir);
                Artisan::call('up');

                return ['ok' => false, 'message' => 'Не удалось распаковать архив'];
            }

            $sourceDir = $this->findLaravelRoot($extractDir);

            if (!$sourceDir) {
                $this->removeDirectory($extractDir);
                Artisan::call('up');

                return ['ok' => false, 'message' => 'Структура архива не распознана'];
            }

            $this->copyUpdate($sourceDir, base_path());
            $this->removeDirectory($extractDir);

            @file_put_contents(base_path('VERSION'), $newVersion . PHP_EOL);

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            Artisan::call('up');

            Logger::adminAction(
                "Обновил платформу до v{$newVersion}",
                'edit',
                'platform',
                null,
                'Zentra CMF',
            );

            return ['ok' => true, 'message' => "Платформа успешно обновлена до v{$newVersion}"];
        } catch (\Throwable $e) {
            try {
                Artisan::call('up');
            } catch (\Throwable) {
            }

            return ['ok' => false, 'message' => 'Ошибка обновления: ' . $e->getMessage()];
        }
    }

    private function downloadZip(string $url): ?string
    {
        $headers = [
            'User-Agent' => 'Zentra-CMF',
            'Accept'     => 'application/vnd.github.v3+json',
        ];

        $token = config('services.github.token');

        if ($token) {
            $headers['Authorization'] = "token {$token}";
        }

        try {
            $response = Http::withHeaders($headers)->timeout(120)->get($url);

            if (!$response->ok()) {
                return null;
            }

            $tempDir = storage_path('app/temp');

            if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                return null;
            }

            $tempFile = $tempDir . '/platform_update_' . uniqid() . '.zip';
            file_put_contents($tempFile, $response->body());

            return $tempFile;
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractZip(string $file, string $dest): bool
    {
        $zip = new \ZipArchive();

        if ($zip->open($file) !== true) {
            return false;
        }

        $zip->extractTo($dest);
        $zip->close();

        return true;
    }

    private function findLaravelRoot(string $dir): ?string
    {
        if (file_exists($dir . '/artisan')) {
            return $dir;
        }

        foreach (glob($dir . '/*/artisan') ?: [] as $found) {
            return dirname($found);
        }

        return null;
    }

    private function copyUpdate(string $src, string $dst): void
    {
        $preserveAbsolute = array_map(fn ($p) => rtrim($dst, '/') . '/' . $p, self::PRESERVE);

        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcItem = "{$src}/{$item}";
            $dstItem = "{$dst}/{$item}";

            foreach ($preserveAbsolute as $preserved) {
                if (str_starts_with(realpath($dstItem) ?: $dstItem, $preserved) || $dstItem === $preserved) {
                    continue 2;
                }
            }

            if (is_dir($srcItem)) {
                $this->copyDirectory($srcItem, $dstItem);
            } else {
                copy($srcItem, $dstItem);
            }
        }
    }

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst) && !mkdir($dst, 0755, true) && !is_dir($dst)) {
            throw new \RuntimeException("Не удалось создать директорию: {$dst}");
        }

        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $s = "{$src}/{$item}";
            $d = "{$dst}/{$item}";
            is_dir($s) ? $this->copyDirectory($s, $d) : copy($s, $d);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = "{$path}/{$item}";
            is_dir($full) ? $this->removeDirectory($full) : unlink($full);
        }

        rmdir($path);
    }

    private function manualInstructions(string $zipballUrl, string $newVersion): array
    {
        $repo = config('services.github.repo');
        $downloadUrl = "https://github.com/{$repo}/releases/tag/v{$newVersion}";

        return [
            'ok'           => false,
            'manual'       => true,
            'version'      => $newVersion,
            'download_url' => $downloadUrl,
            'message'      => 'Нет прав на запись. Обновите вручную.',
            'steps'        => [
                "1. Скачайте архив новой версии: {$downloadUrl}",
                '2. Распакуйте архив на своём компьютере',
                '3. Скопируйте все файлы на сервер, кроме: .env, storage/, public/uploads/, modules/',
                '4. Выполните: php artisan migrate --force',
                '5. Выполните: php artisan cache:clear && php artisan config:clear',
            ],
        ];
    }
}
